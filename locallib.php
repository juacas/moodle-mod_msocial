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
use mod_msocial\plugininfo\msocialbase;
use msocial\msocial_plugin;
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once ($CFG->libdir . '/gradelib.php');
require_once ($CFG->libdir . '/mathslib.php');

/** Find the list of users and get a list with the ids of students and a list of non-students
 * @param type $contextcourse
 * @return array(array($studentIds), array($non_studentIds), array($activeids),
 *         array($user_records)) */
function eduvalab_get_users_by_type($contextcourse) {
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
    return array($students, $nonstudents, $activeids, $userrecords);
}

/**
 * @param int $date
 * @param int|null $fromdate
 * @param int|null $todate
 * @return bool */
function eduvalab_time_is_between($date, $fromdate, $todate) {
    if ($fromdate == "0") {
        $fromdate = null;
    }
    if ($todate == "0") {
        $todate = null;
    }
    return ((!isset($fromdate) || $date > $fromdate) && (!isset($todate) || $date < $todate));
}

/** Curl wrapper for OAuth */
class OAuthCurl {

    public function __construct() {
    }

    public static function fetch_data($url) {
        $options = [
                        CURLOPT_RETURNTRANSFER => true, // ...return web page.
                        CURLOPT_HEADER => false, // ...don't return headers.
                        CURLOPT_FOLLOWLOCATION => true, // ...follow redirects.
                        CURLOPT_SSL_VERIFYPEER => false
                    ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }
}

/**
 * @deprecated REturns true if the user shows no activity in the stats
 * @param type $userid
 * @param type $stat
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
 * @param type $msocial module instance
 * @see msocial_calculate_stats
 * @return \stdClass grade struct with grade->rawgrade = -1 if no calculation is possible */
function msocial_calculate_grades($msocial) {
    require_once ('pki.php');
    $grades = array();
    $pkis = msocial_plugin::get_pkis($msocial);
    /** @var \mod_msocial\pki $pki */
    foreach ($pkis as $userid => $pki) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->itemname = 'msocial';

        $formula = $msocial->grade_expr;
        $formula = calc_formula::unlocalize($formula);
        $calculation = new calc_formula($formula);
        // Extract fields as variables.
        $vars = $pki->as_array();
        $calculation->set_params($vars);
        $value = $calculation->evaluate();
        if ($value !== false) {
            $grade->rawgrade = $value;
            $descr = [];
            foreach ($vars as $varname => $varvalue) {
                $descr[] = $varname . '=' . number_format($varvalue);
            }
            $grade->feedback = "You have " . join(', ', $descr);
        } else {
            $grade->rawgrade = -1;
            $grade->feedback = "Error: " . $calculation->get_error() . ". Contact your teacher.";
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
    $cm = get_coursemodule_from_instance('msocial', $msocial->id, 0, false, MUST_EXIST);
    if ($userid == 0) {
        $context = context_module::instance($cm->id);
        list($sudents) = eduvalab_get_users_by_type($context);
    } else if (is_array($userid)) {
        $students = $userid;
    } else {
        $students = array($userid);
    }

    $grades = msocial_calculate_grades($msocial);
    return $grades;
}

function msocial_set_user_field_value($user, $fieldid, $value) {
    global $CFG;
    if (msocial_is_custom_field_name($fieldid)) {
        require_once ($CFG->dirroot . '/user/profile/lib.php');
        $usernew = new stdClass();
        $usernew->id = $user->id;
        $usernew->{'profile_field_' . $fieldid} = $value;
        profile_save_data($usernew);
    } else {
        $user->$fieldid = $value;
        require_once ($CFG->dirroot . "/user/lib.php");
        user_update_user($user);
    }
}

function msocial_get_user_field_value($user, $fieldid) {
    if (!$fieldid) {
        return null;
    } else if (msocial_is_custom_field_name($fieldid)) {
        global $CFG;
        require_once ($CFG->dirroot . '/user/profile/lib.php');
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
function update_plugin_instance($plugin, stdClass $formdata) {
    $enabledname = $plugin->get_type() . '_' . $plugin->get_subtype() . '_enabled';
    if (!empty($formdata->$enabledname)) {
        $plugin->enable();
    } else {
        $plugin->disable();
    }
    if (!$plugin->save_settings($formdata)) {
        print_error($plugin->get_error());
        return false;
    }
    return true;
}

/**
 * @param object $userstatsa
 * @param object $userstatsb
 * @return type */
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
function msocial_tabbed_reports($msocial, $view, $cm, $contextmodule, $categorized = false) {
    global $OUTPUT;
    $plugins = msocialview::get_enabled_view_plugins($msocial);
    $rows = [];
    /** @var msocial_view_plugin*/
    foreach ($plugins as $name => $plugin) {
        if ($plugin->is_enabled() == false) {
            continue;
        }
        $icon = $plugin->get_icon();
        $icondecoration = html_writer::img($icon->out(), $plugin->get_name() . ' icon.', ['height' => 32]);
        $url = new moodle_url('/mod/msocial/view.php', ['id' => $cm->id, 'view' => $plugin->get_subtype()]);
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
    return $OUTPUT->tabtree($rows, $view);
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
