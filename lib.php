<?php

/* * *******************************************************************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro with the effort of many other
 * students of telecommunication engineering of Valladolid
 * Copyright 2009-2011 EdUVaLab http://www.eduvalab.uva.es
 * this module is provides as-is without any guarantee. Use it as your own risk.

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

 * @author J�ssica Olano L�pez,Pablo Galan Sabugo, David Fernández, Natalia Haro, Juan Pablo de Castro and other contributors.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package tcount
 * 
 *
 * Library of functions and constants for module tcount
 *
 * ******************************************************************************* */


require_once($CFG->dirroot . "/config.php");
require_once ($CFG->dirroot . '/grade/lib.php');
require_once ($CFG->dirroot . '/grade/querylib.php');
require_once ('locallib.php');



/////////////////////////////////////////////////////////////////////////////////
/////                          Constants                                    /////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/////                          Low-level functions                          /////
/////////////////////////////////////////////////////////////////////////////////

/**
 * Indicates API features that the twitter module supports.
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
 * @return mixed True if yes (some features may use other values)
 */
function tcount_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS: return true;
        case FEATURE_GROUPINGS: return false;
        case FEATURE_GROUPMEMBERSONLY: return false;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_COMPLETION_HAS_RULES: return false;
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_GRADE_OUTCOMES: return false;
        case FEATURE_RATE: return false;
        case FEATURE_BACKUP_MOODLE2: return false;
        case FEATURE_SHOW_DESCRIPTION: return true;
        case FEATURE_PLAGIARISM: return false;

        default: return false;
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $instance An object from the form in mod_form.php
 * @return int The id of the newly inserted tcount record
 * */
function tcount_add_instance($tcount) {
    global $DB;
    $tcount->timecreated = time();
    $tcount->timemodified = $tcount->timecreated;

    // Try to store it in the database.
    $tcount->id = $DB->insert_record('tcount', $tcount);
    tcount_grade_item_update($tcount);
    return $tcount->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod_form.php
 * @return boolean Success/Fail
 * */
function tcount_update_instance($tcount) {
    global $DB;
    $tcount->timemodified = time();
    $tcount->id = $tcount->instance;
    tcount_grade_item_update($tcount);
    return $DB->update_record("tcount", $tcount);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.

 * @todo TO-DO Borrar la información en todas las tablas relacionadas.
 * 
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 * */
function tcount_delete_instance($id) {
    global $DB;

    if (!$tcount = $DB->get_record('tcount', array('id' => $id))) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (!$DB->delete_records('tcount', array('id' => $tcount->id))) {
        $result = false;
    }
    tcount_grade_item_update($tcount);
    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 * */
function tcount_user_outline($course, $user, $mod, $tcount) {
    $return = null;
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 * */
function tcount_user_complete($course, $user, $mod, $tcount) {
    // TODO Check if user has tweets
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in tcount activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @global $CFG
 * @return boolean
 * @todo Finish documenting this function
 * */
function tcount_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;
    // TODO report tweets
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @global $CFG
 * @return boolean
 * @todo Finish documenting this function
 * */

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of tcount. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $tcountid ID of an instance of this module
 * @return mixed boolean/array of students
 * */
function tcount_get_participants($tcountid) {
    return false;
}

/**
 * This function returns if a scale is being used by one tcount
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $tcountid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 * */
function tcount_scale_used($tcountid, $scaleid) {
    $return = false;

    return $return;
}

function tcount_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;


    $cm = $PAGE->cm;

    if (!$cm) {
        return;
    }
    if ($PAGE->pagetype == 'mod-tcount-teams-grades' || $PAGE->pagetype == 'mod-tcount-teams-introteams')
        if (has_capability('mod/tcount:introteams', $cm->context)) {
            $link = new moodle_url('/mod/tcount/teams/teams_graph.php', array('id' => $cm->id));
            $linkname = get_string('view_teams_graph', 'tcount');
            $node = $navref->add($linkname, $link, navigation_node::TYPE_CUSTOM);
        }
}

function tcount_has_config() {
    return true;
}

/**
 * Create grade item for given assignment.
 *
 * @param stdClass $tcount record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function tcount_grade_item_update($tcount, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!isset($tcount->courseid)) {
        $tcount->courseid = $tcount->course;
    }
//    $cm = get_coursemodule_from_instance('tcount', $tcount->id, $tcount->courseid);
    $tcount->cmidnumber=isset($tcount->cmidnumber)?$tcount->cmidnumber:null;
    $params = array('itemname' => $tcount->name, 'idnumber' => $tcount->cmidnumber);
  
    $params['gradetype'] = GRADE_TYPE_VALUE;
    if (isset($tcount->grade)){
            $params['grademax'] = $tcount->grade;
    }
    $params['grademin'] = 0;
  
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/tcount', $tcount->courseid, 'mod', 'tcount', $tcount->id, 0, $grades, $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $tcount record of tcount with an additional cmidnumber
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function tcount_get_user_grades($tcount, $userid = 0) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/tcount/locallib.php');

    $grades = tcount_calculate_user_grades($tcount,$userid);
    return $grades;
}

/**
 * Update activity grades.
 *
 * @param stdClass $tcount database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function tcount_update_grades($tcount, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    if ($grades = tcount_get_user_grades($tcount, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        tcount_grade_item_update($tcount, $grades);
    } else {
        tcount_grade_item_update($tcount);
    }
}

?>