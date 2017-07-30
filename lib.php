<?php
// This file is part of SocialMoodle activity for Moodle http://moodle.org/
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
// along with SocialMoodle for Moodle. If not, see <http://www.gnu.org/licenses/>.
/*
 * ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at the telecommunication engineering of Valladolid
 * Copyright 2009-2011 EdUVaLab http://www.eduvalab.uva.es
 * this module is provides as-is without any guarantee. Use it as your own risk.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 * @author Juan Pablo de Castro and other contributors.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * Library of functions and constants for module msocial
 * *******************************************************************************
 */
use mod_msocial\plugininfo\msocialconnector;
defined('MOODLE_INTERNAL') || die();
require_once ($CFG->dirroot . "/config.php");
require_once ($CFG->dirroot . '/grade/lib.php');
require_once ($CFG->dirroot . '/grade/querylib.php');

/** Indicates API features that the twitter module supports.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature
 * @return mixed True if yes (some features may use other values) */
function msocial_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_PLAGIARISM:
            return false;

        default:
            return false;
    }
}

/** Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod_form.php
 * @return int The id of the newly inserted msocial record */
function msocial_add_instance($msocial) {
    global $DB;
    $msocial->timecreated = time();
    $msocial->timemodified = $msocial->timecreated;

    // Try to store it in the database.
    $msocial->id = $DB->insert_record('msocial', $msocial);
    msocial_grade_item_update($msocial);
    // Call save_settings hook for submission plugins.
    /** @var msocial_plugin $plugin */
    foreach (mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial) as $type => $plugin) {
        $plugin->enable(); // Default value is enabled...
        if (!update_plugin_instance($plugin, $msocial)) {
            print_error($plugin->get_error());
            return false;
        }
    }
    return $msocial->id;
}

/** Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod_form.php
 * @return boolean Success/Fail */
function msocial_update_instance($msocial) {
    global $DB;
    $msocial->timemodified = time();
    $msocial->id = $msocial->instance;
    msocial_grade_item_update($msocial);

    // Call save_settings hook for subplugins.
    foreach (mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial) as $type => $plugin) {
        if (!update_plugin_instance($plugin, $msocial)) {
            print_error($plugin->get_error());
            return false;
        }
    }
    foreach (mod_msocial\plugininfo\msocialview::get_enabled_view_plugins($msocial) as $type => $plugin) {
        if (!update_plugin_instance($plugin, $msocial)) {
            print_error($plugin->get_error());
            return false;
        }
    }
    return $DB->update_record("msocial", $msocial);
}

/** Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @todo TO-DO Borrar la información en todas las tablas relacionadas.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure */
function msocial_delete_instance($id) {
    global $DB;

    if (!$msocial = $DB->get_record('msocial', array('id' => $id))) {
        return false;
    }

    $result = true;
    // Delete any dependent records here.
    if (!$DB->delete_records('msocial', array('id' => $msocial->id))) {
        $result = false;
    }
    $plugins = msocialconnector::get_installed_plugins($msocial);
    foreach ($plugins as $subtype => $plugin) {
        $result = $result && $plugin->delete_instance();
    }
    msocial_grade_item_update($msocial);
    return $result;
}

/** Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function */
function msocial_user_outline($course, $user, $mod, $msocial) {
    $return = null;
    return $return;
}

/** Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function */
function msocial_user_complete($course, $user, $mod, $msocial) {
    // TODO Check if user has tweets.
    return true;
}

/** Given a course and a time, this module should find recent activity
 * that has occurred in msocial activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @global $CFG
 * @return boolean
 * @todo Finish documenting this function */
function msocial_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;
    // TODO report tweets.
    return false; // True if anything was printed, otherwise false.
}

/** Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @global $CFG
 * @return boolean
 * @todo Finish documenting this function */

/** Must return an array of user records (all data) who are participants
 * for a given instance of msocial.
 * Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $msocialid ID of an instance of this module
 * @return mixed boolean/array of students */
function msocial_get_participants($msocialid) {
    return false;
}

/** This function returns if a scale is being used by one msocial
 * it it has support for grading and scales.
 * Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $msocialid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function */
function msocial_scale_used($msocialid, $scaleid) {
    $return = false;

    return $return;
}

function msocial_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }
    if ($PAGE->pagetype == 'mod-msocial-teams-grades' || $PAGE->pagetype == 'mod-msocial-teams-introteams') {
        if (has_capability('mod/msocial:introteams', $cm->context)) {
            $link = new moodle_url('/mod/msocial/teams/teams_graph.php', array('id' => $cm->id));
            $linkname = get_string('view_teams_graph', 'msocial');
            $node = $navref->add($linkname, $link, navigation_node::TYPE_CUSTOM);
        }
    }
}

function msocial_has_config() {
    return true;
}

/** Create grade item for given assignment.
 *
 * @param stdClass $msocial record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise */
function msocial_grade_item_update($msocial, $grades = null) {
    global $CFG;
    require_once ($CFG->libdir . '/gradelib.php');

    if (!isset($msocial->courseid)) {
        $msocial->courseid = $msocial->course;
    }
    $msocial->cmidnumber = isset($msocial->cmidnumber) ? $msocial->cmidnumber : null;
    $params = array('itemname' => $msocial->name, 'idnumber' => $msocial->cmidnumber);

    $params['gradetype'] = GRADE_TYPE_VALUE;
    if (isset($msocial->grade)) {
        $params['grademax'] = $msocial->grade;
    }
    $params['grademin'] = 0;

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/msocial', $msocial->courseid, 'mod', 'msocial', $msocial->id, 0, $grades, $params);
}

/** Return grade for given user or all users.
 *
 * @param stdClass $msocial record of msocial with an additional cmidnumber
 * @param array $userid optional user id, 0 means all users
 * @return array array of grades, false if none */
function msocial_get_user_grades($msocial, $userid = 0) {
    global $CFG;

    require_once ($CFG->dirroot . '/mod/msocial/locallib.php');

    $grades = msocial_calculate_user_grades($msocial, $userid);
    return $grades;
}

/** Update activity grades.
 *
 * @param stdClass $msocial database record
 * @param array $userid specific user only, 0 means all
 * @param bool $nullifnone - not used */
function msocial_update_grades($msocial, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once ($CFG->libdir . '/gradelib.php');
    if ($grades = msocial_get_user_grades($msocial, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        msocial_grade_item_update($msocial, $grades);
    } else {
        msocial_grade_item_update($msocial);
    }
}