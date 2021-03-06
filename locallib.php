<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
use mod_msocial\plugininfo\msocialview;
use mod_msocial\msocial_plugin;
use mod_msocial\users_struct;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');
global $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once('classes/usersstruct.php');

/**
 * Compatibility with Moodle 2.9 notifications.
 *
 * @param string $message
 */
function msocial_notify_info($message) {
    if (class_exists('\core\notification')) {
        \core\notification::info($message);
    } else {
        global $OUTPUT;
        echo $OUTPUT->notification($message , 'notifymessage');
    }
}
/**
 * Compatibility with Moodle 2.9 notifications
 * @param string $message
 */
function msocial_notify_error($message) {
    if (class_exists('\core\notification')) {
        \core\notification::error($message);
    } else {
        global $OUTPUT;
        echo $OUTPUT->notification($message , 'notifyproblem');
    }
}
/**
 * Compatibility with Moodle 2.9 notifications
 * @param string $message
 */
function msocial_notify_warning($message) {
    if (class_exists('\core\notification')) {
        \core\notification::warning($message);
    } else {
        global $OUTPUT;
        echo $OUTPUT->notification($message , 'notifyproblem');
    }
}
/** Find the list of users and get a list with the ids of students and a list of non-students
 * @param \stdClass $contextcourse
 * @return users_struct */
function msocial_get_users_by_type($contextcourse) {
    // Get users with gradable roles.
    global $CFG;
    $gradableroles = $CFG->gradebookroles;
    $roles = explode(',', $gradableroles);
    $students = array();
    foreach ($roles as $roleid) {
        $usersinrole = get_role_users($roleid, $contextcourse);
        $ids = array_keys($usersinrole);
        $students = array_merge($students, $ids);
        $students = array_unique($students);
    }
    // ...get enrolled users.
    $userrecords = get_enrolled_users($contextcourse, '', 0, '*');
    $users = array_keys($userrecords);
    $nonstudents = array_diff($users, $students);
    // ...select active userids.
    $activeids = array();
    global $DB;
    if (count($students) > 0) {
        list($select, $params) = $DB->get_in_or_equal($students);
        $select = "userid $select";
        $select .= " AND courseid = ?";
        $params[] = (int) $contextcourse->instanceid;
        $lastaccesses = $DB->get_records_select('user_lastaccess', $select, $params);
        foreach ($lastaccesses as $record) {
            $activeids[] = $record->userid;
        }
    }
    $userstruct = new users_struct();
    $userstruct->studentids = $students;
    $userstruct->nonstudentids = $nonstudents;
    $userstruct->activeids = $activeids;
    $userstruct->userrecords = $userrecords;

    return $userstruct;
}

/**
 * @param int|DateTime $timestamp
 * @param int|null $fromdate
 * @param int|null $todate
 * @return bool */
function msocial_time_is_between($timestamp, $fromdate, $todate) {
    if ($timestamp instanceof DateTime) {
        $timestamp = $timestamp->getTimestamp();
    }
    if ($fromdate == "0") {
        $fromdate = null;
    }
    if ($todate == "0") {
        $todate = null;
    }
    return ((!isset($fromdate) || $timestamp >= $fromdate) && (!isset($todate) || $timestamp <= $todate));
}

/**
 * @deprecated REturns true if the user shows no activity in the stats
 * @param int $userid
 * @param \stdClass $stat
 * @return boolean */
function msocial_user_inactive($userid, $stat) {
    if ($stat->favs == 0 && $stat->tweets == 0 && $stat->retweets == 0) {
        return true;
    } else {
        return false;
    }
}

/** Apply a formula to calculate a raw grade.
 *
 * @param \stdClass $msocial module instance. (see function msocial_calculate_stats)
 * @return \stdClass grade struct with grade->rawgrade = -1 if no calculation is possible */
function msocial_calculate_grades($msocial) {
    global $CFG;
    require_once('classes/kpi.php');
    require_once('classes/msocialplugin.php');
    require_once($CFG->libdir . '/mathslib.php');
    $grades = array();
    /** @var \mod_msocial\kpi[] $kpis */
    $kpis = msocial_plugin::get_kpis($msocial);
    foreach ($kpis as $userid => $kpi) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->itemname = 'msocial';

        $formula = $msocial->grade_expr;
        $formula = calc_formula::unlocalize($formula);
        $calculation = new calc_formula($formula);
        // Extract fields as variables.
        $vars = $kpi->as_array();
        $vars = array_map(function ($val) {
            return (double)$val;
        }, $vars);
        $calculation->set_params($vars);
        $value = $calculation->evaluate();
        $descr = [];
        foreach ($vars as $varname => $varvalue) {
            $descr[] = $varname . '=' . number_format($varvalue);
        }
        if ($value !== false) {
            $grade->rawgrade = $value;
            $grade->feedback = "You have " . join(', ', $descr);
        } else {
            $grade->rawgrade = -1;
            $grade->feedback = "Error: " . $calculation->get_error() . ". Contact your teacher. Values: " .  join(', ', $descr);
        }
        $grades[$userid] = $grade;
    }
    return $grades;
}

/** Calculates grades for one or a set of users.
 * @param stdClass $msocial instance of the plugin.
 * @param int\array(int) $userid Userid or an array of user ids.
 * @return stdClass */
function msocial_calculate_user_grades($msocial, $userid = 0) {
    $cm = get_fast_modinfo($msocial->course)->instances['msocial'][$msocial->id];
    if ($userid == 0) {
        $context = context_module::instance($cm->id);
        $studentsstruct = msocial_get_users_by_type($context);
        $students = $studentsstruct->studentids;
    } else if (is_array($userid)) {
        $students = $userid;
    } else {
        $students = array($userid);
    }
    // TODO: honor $userid param. ($students subset).
    $grades = msocial_calculate_grades($msocial);
    return $grades;
}

function msocial_set_user_field_value($user, $fieldid, $value) {
    global $CFG;
    if (msocial_is_custom_field_name($fieldid)) {
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $usernew = new stdClass();
        $usernew->id = $user->id;
        $usernew->{'profile_field_' . $fieldid} = $value;
        profile_save_data($usernew);
    } else {
        $user->$fieldid = $value;
        require_once($CFG->dirroot . "/user/lib.php");
        user_update_user($user);
    }
}

function msocial_get_user_field_value($user, $fieldid) {
    if (!$fieldid) {
        return null;
    } else if (msocial_is_custom_field_name($fieldid)) {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $profile = profile_user_record($user->id);
        return $profile->$fieldid;
    } else {
        if (isset($user->$fieldid) && $user->$fieldid != '') {
            return $user->$fieldid;
        } else {
            return null;
        }
    }
}

/** Return the list of available user's profile fields
 * @return array[string]string array with id->name of user fields */
function msocial_get_user_fields() {
    global $DB;
    $options1 = array('skype' => 'SKYPE', 'yahoo' => 'Yahoo', 'aim' => 'AIM', 'msn' => 'MSN');
    $options2 = array();
    $options = $DB->get_records_menu("user_info_field", null, "name", "shortname, name");
    if ($options) {
        foreach ($options as $shortname => $name) {
            $options2[$shortname] = $name;
        }
    }
    $idtypeoptions = $options1 + $options2;
    return $idtypeoptions;
}
/**
 * Resolve user to fullusername according to permissions and settings.
 * @param string $user
 * @param stdClass $msocial
 * @param boolean $override
 * @return string
 */
function msocial_get_visible_fullname($user, $msocial, $override = false) {
    global $USER;
    if ($USER->id == $user->id || msocial_can_view_others_names($msocial)) {
        return fullname($user, $override);
    } else {
        // Simple anonymizing method. Ofuscate user id with a simple xor.
        return mb_convert_case(substr($user->lastname, 0, 2) . substr($user->firstname, 0, 2) . ($user->id ^ 343), MB_CASE_UPPER);
    }
}
function msocial_get_fullname($userid, $users, $default, $msocial) {
    if ($userid != null && isset($users[$userid])) {
        $user = $users[$userid];
        return msocial_get_visible_fullname($user, $msocial);
    } else {
        return $default;
    }
}
function msocial_create_userlink($interaction, $dir = 'to', $userrecords, $msocial, $cm, $redirecturl, $canviewothers) {
    global $USER;
    $nativeid = $interaction->{"native{$dir}"};
    $userid = $interaction->{"{$dir}id"};
    $nativename = $interaction->{"native{$dir}name"};
    if ($nativeid == null) { // Community destination.
        $nodename = '[COMMUNITY]';
        $userlink = '';
    } else {
        $nodename = msocial_get_fullname($userid, $userrecords, "[$nativename]", $msocial);
        $userlink = '';
        if ($canviewothers || $USER->id == $userid) {
            if (isset($userrecords[$userid])) {
                $userlink = (new moodle_url('/mod/msocial/socialusers.php',
                        ['action' => 'showuser',
                            'user' => $userid,
                            'id' => $cm->id,
                            'redirect' => $redirecturl,
                        ]))->out(false);
            } else {
                $userlink = (new moodle_url('/mod/msocial/socialusers.php',
                        [
                            'action' => 'selectmapuser',
                            'source' => $interaction->source,
                            'nativeid' => $nativeid,
                            'nativename' => $nativename,
                            'id' => $cm->id,
                            'redirect' => $redirecturl,
                        ]))->out(false);
            }
        }
    }
    return [$nodename, $userlink];
}
function msocial_can_view_others($cm, $msocial) {
    $contextmodule = \context_module::instance($cm->id);
    $viewothers = has_capability('mod/msocial:viewothers', $contextmodule);
    return $viewothers;
}
function msocial_can_view_others_names($msocial) {
    $cm = get_fast_modinfo($msocial->course)->instances['msocial'][$msocial->id];
    $context = context_module::instance($cm->id);
    $canview = has_capability('mod/msocial:alwaysviewothersnames', $context) || $msocial->anonymizeviews == false;
    return $canview;
}
/**
 *
 * @param stdClass $cm
 * @param stdClass $msocial
 * @return users_struct
 */
function msocial_get_viewable_users($cm, $msocial) {
    global $USER;
    $viewothers = msocial_can_view_others($cm, $msocial);
    $contextcourse = context_course::instance($msocial->course);
    $usersstruct = msocial_get_users_by_type($contextcourse);
    if ($viewothers) {
        $usersstruct->studentids = array_merge($usersstruct->studentids, $usersstruct->nonstudentids);
    } else {
        $usersstruct->studentids = array($USER->id);
    }
    return $usersstruct;
}
function msocial_is_custom_field_name($fieldid) {
    if (in_array($fieldid, ['aim', 'msn', 'skype', 'yahoo'])) {
        return false;
    } else {
        return true;
    }
}

/** Update the settings for a single plugin.
 *
 * @param msocial_plugin $plugin The plugin to update
 * @param stdClass $formdata The form data
 * @return bool false if an error occurs */
function msocial_update_plugin_instance($plugin, stdClass $formdata) {
    $enabledname = $plugin->get_type() . '_' . $plugin->get_subtype() . '_enabled';
    if (!empty($formdata->$enabledname)) {
        $plugin->enable();
    } else {
        $plugin->disable();
    }
    if (!$plugin->save_settings($formdata)) {
        print_error("false returned! " . $plugin->get_error());
        return false;
    }
    return true;
}

/**
 * @param object $userstatsa
 * @param object $userstatsb
 * @return \stdClass */
function merge_stats($statsa, $statsb) {
    $userstatsa = $statsa->users;
    $userstatsb = $statsb->users;
    foreach ($userstatsb as $user => $stat) {
        if (key_exists($user, $userstatsa)) {
            $statmerged = (object) array_merge((array) $userstatsa[$user], (array) $stat);
            $userstatsa[$user] = $statmerged;
        } else {
            $userstatsa[$user] = $stat;
        }
    }
    // Get maximums.
    $maxa = $statsa->maximums;
    $maxb = $statsb->maximums;
    foreach ($maxb as $name => $value) {
        if (isset($maxa->$name)) {
            $maxa->$name = max([$maxa->$name, $value]);
        } else {
            $maxa->$name = $value;
        }
    }
    $statsa->maximums = $maxa;
    $statsa->users = $userstatsa;
    return $statsa;
}

/**
 * @global core_renderer $OUTPUT
 * @param stdClass $msocial record for instante msocial
 * @param course_modinfo $cm
 * @param context $contextmodule
 * @return ta */
function msocial_tabbed_reports($msocial, $view, moodle_url $thispageurl, $contextmodule, $categorized = false) {
    global $OUTPUT;
    $plugins = msocialview::get_enabled_view_plugins($msocial);
    usort($plugins, function($a, $b){
                            return ($a->get_sort_order() > $b->get_sort_order());
    }
        );
    
    $rows = [];
    /** @var msocial_view_plugin*/
    foreach ($plugins as $plugin) {
        if ($plugin->is_enabled() == false) {
            continue;
        }
        if ($view==null) {
            $view = $plugin->get_subtype();
        }
        $icon = $plugin->get_icon();
        $icondecoration = html_writer::img($icon->out(), $plugin->get_name() . ' icon.', ['height' => 32]);
        $url = new moodle_url($thispageurl);
        $url->param('view', $plugin->get_subtype());
        $plugintab = new tabobject($plugin->get_subtype(), $url, $icondecoration . $plugin->get_name());
        if ($categorized) {
            $category = $plugin->get_category();
            if (isset($rows[$category])) {
                $parenttab = $rows[$category];
            } else {
                $parenttab = new tabobject($category, null, $category);

                $rows[$category] = $parenttab;
            }
            $parenttab->subtree[] = $plugintab;
        } else {
            $rows[] = $plugintab;
        }
    }
    return [$OUTPUT->tabtree($rows, $view), $view];
}

/** This function creates a minimal JS script that requires and calls a single function from an AMD
 * module with arguments.
 * If it is called multiple times, it will be executed multiple times.
 *
 * @param string $fullmodule The format for module names is <component name>/<module name>.
 * @param string $func The function from the module to call
 * @param array $params The params to pass to the function. They will be json encoded, so no nasty
 *        classes/types please. */
function msocial_js_call_subplugin_amd($fullmodule, $func, $params = array(), $req) {
    global $CFG;

    list($component, $subtype, $plugin, $module) = explode('/', $fullmodule, 4);

    $component = clean_param($component, PARAM_COMPONENT);
    $module = clean_param($module, PARAM_ALPHANUMEXT);
    $subtype = clean_param($subtype, PARAM_ALPHANUMEXT);
    $plugin = clean_param($plugin, PARAM_ALPHANUMEXT);
    $func = clean_param($func, PARAM_ALPHANUMEXT);

    $jsonparams = array();
    foreach ($params as $param) {
        $jsonparams[] = json_encode($param);
    }
    $strparams = implode(', ', $jsonparams);
    if ($CFG->debugdeveloper) {
        $toomanyparamslimit = 1024;
        if (strlen($strparams) > $toomanyparamslimit) {
            debugging('Too many params passed to js_call_amd("' . $fullmodule . '", "' . $func . '")', DEBUG_DEVELOPER);
        }
    }

    $js = 'require(["' . $component . '/' . $subtype . '/' . $plugin . '/' . $module . '"], function(amd) { amd.' . $func . '(' .
             $strparams . '); });';

    $req->js_amd_inline($js);
}
/**
 *  Make a pretty date difference from the current time
 * Author: Luca Zorzi (@LucaTNT)
 * License: BSD
 * @author Juan Pablo de Castro
 * @param int $timestamp
 * @return string
 */
function msocial_pretty_date_difference($timestamp) {
    // Current timestamp.
    $now = time();
    // Difference from given timestamp.
    $difference = $now - $timestamp;
    // If less than one hour (59 minutes and 30 seconds, to be precise), we count minutes.
    if ($difference < (3600)) {
        $output = round($difference / 60) . ' ' . get_string('minutes');
    } else if ($difference < (24 * 3600)) {
        // If less than 23 hours 59 minutes and 30 seconds, we count hours.
        $output = round($difference / 3600) . ' ' . get_string('hours');
    } else if ($difference < (31 * 24 * 3600 )) {
        // If less than 6 days 23 hours 59 minutes and 30 seconds, we count days.
        $output = round($difference / 86400) . ' ' . get_string('days');
    } else if ($difference < (365 * 24 * 3600)) {
        // If less than 364 days 23 hours 59 minutes and 30 seconds, we count months.
        $output = round($difference / (30 * 24 * 3600)) . ' ' . get_string('months');
    } else {
        // Else we count years.
        $output = round($difference / 31536000) . ' ' . get_string('years');
    }
    return $output;
}