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
use mod_msocial\plugininfo\msocialview;
use msocial\msocial_plugin;

require_once ("../../config.php");
require_once ("locallib.php");
require_once ("msocialconnectorplugin.php");
require_once ("msocialviewplugin.php");
/* @var $OUTPUT \core_renderer */
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);

$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$user = $USER;
// Capabilities.
$contextmodule = context_module::instance($cm->id);
require_capability('mod/msocial:view', $contextmodule);

// Show headings and menus of page.
$url = new moodle_url('/mod/msocial/socialusers.php', array('id' => $id));
$PAGE->set_url($url);

$requ = $PAGE->requires;
$requ->css('/mod/msocial/styles.css');

// Print the page header.
$PAGE->set_title(get_string('view_social_users','msocial'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// Print the main part of the page.
echo $OUTPUT->spacer(array('height' => 20));
echo $OUTPUT->heading(get_string('view_social_users','msocial'));
// Print the information about the linking of the module with social plugins..
$enabledsocialplugins = \mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);

$table = new html_table();

list($studentids, $nonstudentids, $inactiveids, $users) = eduvalab_get_users_by_type($contextmodule);
$table->head = [get_string('user')];
foreach ($enabledsocialplugins as $plugin) {
    if ($plugin->users_are_local() === false) {
        $table->head[] = $plugin->get_name();
    }
}
foreach ($users as $user) {
    $row = new html_table_row();
    $table->data[] = $row;
    // User name and pic.
    $pic = $OUTPUT->user_picture($user);
    $link = html_writer::link(new moodle_url('/user/view.php', ['id' => $user->id]), fullname($user));
    $row->cells[] = new html_table_cell($pic . ' ' . $link);
    foreach ($enabledsocialplugins as $plugin) {
        if ($plugin->users_are_local() === false) {
            $row->cells[] = $plugin->create_user_link($user);
        }
    }
}

echo html_writer::table($table);

echo $OUTPUT->footer();
