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
use mod_msocial\connector\msocial_connector_facebook;
use DirkGroenen\facebook\facebook;

require_once ("../../../../config.php");
require_once ('../../locallib.php');
require_once ('../../msocialconnectorplugin.php');
require_once ('facebookplugin.php');
require_once ('vendor/autoload.php');
global $CFG;
$id = required_param('id', PARAM_INT); // MSocial module instance.
$action = optional_param('action', 'select', PARAM_ALPHA);
$type = optional_param('type', 'connect', PARAM_ALPHA);
$cm = get_coursemodule_from_id('msocial', $id);
$course = get_course($cm->course);
require_login($course);
$context = context_module::instance($id);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$plugin = new msocial_connector_facebook($msocial);
require_capability('mod/msocial:manage', $context);

if ($action == 'selectgroup') {
    $thispageurl = new moodle_url('/mod/msocial/connector/facebook/groupchoice.php', array('id' => $id, 'action' => 'select'));
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    $modinfo = course_modinfo::instance($course->id);
    $clientid = get_config("msocialconnector_facebook", "appid");
    $clientsecret = get_config("msocialconnector_facebook", "appsecret");
    $pr = new facebook($clientid, $clientsecret);
    $token = $plugin->get_connection_token();
    $pr->auth->setOAuthToken($token->token);
    /** @var \DirkGroenen\facebook\Models\Collection $groups */
    $groups = $pr->users->getMegroups(['fields' => 'id,name,url,image,description,created_at,counts,reason']);

    $selectedgroups = $plugin->get_config(msocial_connector_facebook::CONFIG_PRgroup);
    if ($selectedgroups) {
        $selectedgroups = explode(',', $groups);
    } else {
        $selectedgroups = [];
    }
    $allgroups = $groups->all();
    if (count($allgroups) > 0) {
        $table = new \html_table();
        $table->head = ['groups', get_string('description')];
        $data = [];

        $out = '<form method="GET" action="' . $thispageurl->out_omit_querystring(true) . '" >';
        $out .= '<input type="hidden" name="id" value="' . $id . '"/>';
        $out .= '<input type="hidden" name="action" value="setgroups"/>';
        /** @var \DirkGroenen\facebook\Models\group $group */
        foreach ($allgroups as $group) {

            $row = new \html_table_row();
            // Use instance id instead of cmid... get_coursemodule_from_instance('forum',
            // $group->id)->id;.
            $groupid = json_encode(['id' => $group->id, 'name' => $group->name, 'url' => $group->url]);
            $groupurl = $group->url;
            $groupimg = $group->image['60x60'];
            $info = \html_writer::img($groupimg['url'], $group->name) . $group->name;
            $linkinfo = \html_writer::link($groupurl, $info);
            $selected = array_search($groupid, $selectedgroups) !== false;
            $checkbox = \html_writer::checkbox('group[]', $groupid, $selected, $linkinfo);
            $row->cells = [$checkbox, $group->description];
            $table->data[] = $row;
        }
        $out .= \html_writer::table($table);
        $out .= '<input type="hidden" name="totalgroups" value="' . count($allgroups) . '"/>';
        $out .= '<input type="submit">';
        $out .= '</form>';
        echo $out;
    }
} else if ($action == 'setgroups') {
    $groups = required_param_array('group', PARAM_RAW);
    $totalgroups = required_param('totalgroups', PARAM_INT);
    $thispageurl = new moodle_url('/mod/msocial/connector/facebook/groupchoice.php', array('id' => $id, 'action' => 'select'));
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(get_string('selectthisgroup', 'msocialconnector_facebook'));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    // Save the configuration.
    $groupids = [];
    $groupnames = [];
    foreach ($groups as $groupstruct) {
        $parts = json_decode($groupstruct);
        $groupid = $parts->id;
        $groupids[] = $groupid;
        unset($parts->id);
        $groupnames[$groupid] = $parts;
    }
    $plugin->set_config(msocial_connector_facebook::CONFIG_PRgroup, implode(',', $groupids));
    $plugin->set_config(msocial_connector_facebook::CONFIG_PRgroupNAME, json_encode($groupnames));
    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
} else {
    print_error("Bad action code");
}
echo $OUTPUT->footer();