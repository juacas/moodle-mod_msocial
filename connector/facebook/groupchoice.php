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
use Facebook\GraphNodes\GraphNodeFactory;

require_once("../../../../config.php");
require_once('../../locallib.php');
require_once('../../classes/msocialconnectorplugin.php');
require_once('facebookplugin.php');
require_once('vendor/Facebook/autoload.php');
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
// TODO: Allow multiple groups.
if ($action == 'selectgroup') {
    $thispageurl = new moodle_url('/mod/msocial/connector/facebook/groupchoice.php', array('id' => $id, 'action' => 'select'));
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    $modinfo = course_modinfo::instance($course->id);
    $appid = get_config("msocialconnector_facebook", "appid");
    $appsecret = get_config("msocialconnector_facebook", "appsecret");
    /**@var \Facebook\Facebook $fb */
    $fb = new \Facebook\Facebook(
            ['app_id' => $appid, 'app_secret' => $appsecret, 'default_graph_version' => 'v2.9',
                            'default_access_token' => '{access-token}' // Optional...
            ]);
    $token = $plugin->get_connection_token();
    $fb->setDefaultAccessToken($token->token);
    $groups = $fb->get('/me/groups?fields=administrator,name,cover,icon,link,description');
    $grphfty = new GraphNodeFactory($groups);
    $groupsedge = $grphfty->makeGraphEdge('GraphGroup');
    
    $selectedgroups = $plugin->get_config(msocial_connector_facebook::CONFIG_FBGROUP);
    if ($selectedgroups) {
        $selectedgroups = explode(',', $selectedgroups);
    } else {
        $selectedgroups = [];
    }
    
    $out = '<form method="GET" action="' . $thispageurl->out_omit_querystring(true) . '" >';
    $out .= '<input type="hidden" name="id" value="' . $id . '"/>';
    $out .= '<input type="hidden" name="action" value="setgroups"/>';
    // Render list of groups.
    $table = new \html_table();
    $table->head = ['Groups', get_string('description')];
    $data = [];
    $iter = $groupsedge->getIterator();
    $numgroups = 0;
    while ($iter->valid()) {
        /** @var GraphGroup $group*/
        $group = $iter->current();
        $row = new \html_table_row();
        if ($group->getCover()) {
            $info = \html_writer::img($group->getCover()->getSource(), $group->getName(), ['height' => 50 ]);
        } else {
            $info = \html_writer::img($plugin->get_icon(), $plugin->get_name(), ['height' => 50 ]);
        }
        $groupurl = 'https://www.facebook.com/groups/' . $group->getId();
        $groupstructform = json_encode(['id' => $group->getId(), 'name' => $group->getName(), 'url' => $groupurl]);
        $linkinfo = \html_writer::link($groupurl, $info);
        $selected = array_search($group->getId(), $selectedgroups) !== false;
        $checkbox = \html_writer::checkbox('group[]', $groupstructform, $selected, $linkinfo);
        $row->cells = [$checkbox, $group->getDescription()];
        
        $table->data[] = $row;
        $iter->next();
        $numgroups++;
    }
    $out .= \html_writer::table($table);
    $out .= '<input type="hidden" name="totalgroups" value="' . $numgroups . '"/>';
    $out .= '<input type="submit">';
    $out .= '</form>';
    echo $out;
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
    $groupstructrecord = [];
    foreach ($groups as $groupstruct) {
        $parts = json_decode($groupstruct);
        $groupstructform = $parts->id;
        $groupnames[] = $parts->name;
        $groupids[] = $groupstructform;
        unset($parts->id);
        $groupstructrecord[$groupstructform] = $parts;
    }
    $plugin->set_config(msocial_connector_facebook::CONFIG_FBGROUP, implode(',', $groupids));
    $plugin->set_config(msocial_connector_facebook::CONFIG_FBGROUPNAME, json_encode($groupstructrecord));
    $grouplinks = $plugin->render_groups_links();
    echo get_string('fbgroup', 'msocialconnector_facebook') . ' : "' . implode(', ', $grouplinks);
    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
} else {
    print_error("Bad action code");
}
echo $OUTPUT->footer();