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
/*
 * **************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
use mod_msocial\connector\msocial_connector_questournament;

require_once("../../../../config.php");
require_once('../../locallib.php');
require_once('../../classes/msocialconnectorplugin.php');
require_once('questournamentplugin.php');
global $CFG;
$id = required_param('id', PARAM_INT); // MSocial module instance.
$action = optional_param('action', 'select', PARAM_ALPHA);
$type = optional_param('type', 'connect', PARAM_ALPHA);
$cm = get_coursemodule_from_id('msocial', $id);
$course = get_course($cm->course);
require_login($course);
$context = context_module::instance($id);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$plugin = new msocial_connector_questournament($msocial);
require_capability('mod/msocial:manage', $context);

if ($action == 'select') {
    $thispageurl = new moodle_url('/mod/msocial/connector/questournament/activitychoice.php', array('id' => $id, 'action' => 'select'));
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    $modinfo = course_modinfo::instance($course->id);
    $instances = $modinfo->get_instances_of('quest');
    $activities = $plugin->get_config(msocial_connector_questournament::CONFIG_ACTIVITIES);
    if ($activities) {
        $activities = explode(',', $activities);
    } else {
        $activities = [];
    }
    if (count($instances) > 0) {
        $table = new \html_table();
        $table->head = [reset($instances)->get_module_type_name(true), get_string('description')];
        $data = [];
        
        $out = '<form method="GET" action="' . $thispageurl->out_omit_querystring(true) . '" >';
        $out .= '<input type="hidden" name="id" value="' . $id . '"/>';
        $out .= '<input type="hidden" name="action" value="setactivities"/>';
        
        foreach ($instances as $forum) {
            if ($forum->deletioninprogress) {
                continue;
            }
            $row = new \html_table_row();
            // Use instance id instead of cmid... get_coursemodule_from_instance('forum',
            // $forum->id)->id;.
            $activityid = $forum->id;
            $forumurl = $forum->url;
            $info = \html_writer::img($forum->get_icon_url(), $forum->get_module_type_name()) . $forum->get_formatted_name();
            $linkinfo = \html_writer::link($forumurl, $info);
            // If no instances selected, then all are used.
            $selected = count($activities) > 0 ? (array_search($activityid, $activities) !== false) : true;
            $checkbox = \html_writer::checkbox('activity[]', $activityid, $selected, $linkinfo);
            $forumrecord = $DB->get_record('forum', ['id' => $forum->instance]);
            $row->cells = [$checkbox, format_module_intro('forum', $forumrecord, $forum->id)];
            $table->data[] = $row;
        }
        $out .= \html_writer::table($table);
        $out .= '<input type="hidden" name="totalactivities" value="' . count($instances) . '"/>';
        $out .= '<input type="submit">';
        $out .= '</form>';
        echo $out;
    }
} else if ($action == 'setactivities') {
    $activities = required_param_array('activity', PARAM_INT);
    $totalactivities = required_param('totalactivities', PARAM_INT);
    $thispageurl = new moodle_url('/mod/msocial/connector/questournament/activitychoice.php', array('id' => $id, 'action' => 'select'));
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(get_string('selectactivity', 'msocialconnector_questournament'));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    // Save the configuration.
    if (count($activities) == $totalactivities) {
        $plugin->set_linked_activities([]); // All forums.
    } else {
        $cmodinfo = get_fast_modinfo($course);
        $activitiesnames = [];
        foreach ($activities as $actid) {
            $activitiesnames[$actid] = $cmodinfo->cms[$actid]->name;
        }
        $plugin->set_linked_activities($activitiesnames);
    }
    
    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
} else {
    print_error("Bad action code");
}
echo $OUTPUT->footer();