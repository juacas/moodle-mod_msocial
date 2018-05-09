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
use mod_msocial\plugininfo\msocialbase;
use mod_msocial\plugininfo\msocialconnector;
use mod_msocial\SocialUser;
use mod_msocial\social_user;

require_once("../../config.php");
require_once("locallib.php");
require_once("classes/msocialconnectorplugin.php");
require_once("classes/msocialviewplugin.php");
require_once('classes/usersstruct.php');
/* @var $OUTPUT \core_renderer */
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);
// Maybe the request include a mapping request.
$action = optional_param('action', null, PARAM_ALPHA);
$redirecturl = optional_param('redirect', null, PARAM_RAW);

// Show.
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$user = $USER;
// Capabilities.
$contextmodule = context_module::instance($cm->id);
require_capability('mod/msocial:view', $contextmodule);
if ($action == 'setmap') {
    $nativeid = required_param('nativeid', PARAM_ALPHANUMEXT);
    $nativename = required_param('nativename', PARAM_RAW_TRIMMED);
    $source = required_param('source', PARAM_ALPHA);
    $userid = required_param('user', PARAM_INT);
    if (trim($nativeid) == '') {
        throw new Exception('Social user id empty.');
    }
    require_sesskey();
    require_capability('mod/msocial:manage', $contextmodule);
    $user = $DB->get_record('user', ['id' => $userid]);
    $plugin = msocialconnector::instance($msocial, 'connector', $source);
    $plugin->set_social_userid($user, $nativeid, $nativename);

} else if ($action == 'selectmapuser') {
    require_capability('mod/msocial:mapaccounts', $contextmodule);
    $nativeid = required_param('nativeid', PARAM_ALPHANUMEXT);
    $nativename = required_param('nativename', PARAM_RAW_TRIMMED);
    $source = required_param('source', PARAM_ALPHA);
} else if ($action == 'showuser') {
    require_capability('mod/msocial:viewothers', $contextmodule);
    $userid = required_param('user', PARAM_INT);
    $user = $DB->get_record('user', ['id' => $userid]);
}
$mappingrequested = $action == 'selectmapuser' && trim($nativeid) != '';
// Show headings and menus of page.
$thispageurl = new moodle_url('/mod/msocial/socialusers.php', array('id' => $id));
$PAGE->set_url($thispageurl);

$requ = $PAGE->requires;
$requ->css('/mod/msocial/styles.css');

// Print the page header.
$PAGE->set_title(get_string('view_social_users', 'msocial'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// Print the main part of the page.
echo $OUTPUT->spacer(array('height' => 20));
echo $OUTPUT->heading(get_string('view_social_users', 'msocial'));
// Print the information about the linking of the module with social plugins..
$enabledsocialplugins = \mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
// Select user explaination...
if ($mappingrequested) {
    $plugin = $enabledsocialplugins[$source];
    $socialuserid = new social_user($nativeid, $nativename);
    $link = $plugin->get_social_user_url($socialuserid);
    $icon = $plugin->get_icon();
    $linksocial = "<a href=\"$link\"><img src=\"$icon\" height=\"29px\" /> $nativename </a>";
    $a = (object) ['link' => $linksocial, 'socialname' => $nativename, 'source' => $plugin->get_name()];
    echo $OUTPUT->box(get_string('mapunknownsocialusers', 'msocial', $a));
}
$table = new html_table();
if (msocial_can_view_others($cm, $msocial)) {
    $usersstruct = msocial_get_users_by_type($contextmodule);
    $studentids = $usersstruct->studentids;
    $users = $usersstruct->userrecords;
} else {
    $studentids = [$USER->id];
    $users = [$USER];
}
$table->head = [get_string('user')];
foreach ($enabledsocialplugins as $plugin) {
    if ($plugin->users_are_local() === false) {
        $table->head[] = $plugin->get_name();
    }
}
// Show info about modified user and redirect.
if ($action == 'setmap' || $action == 'showuser') {
    $users = [$user];
}
$cm = get_fast_modinfo($msocial->course)->instances['msocial'][$msocial->id];
$context = context_module::instance($cm->id);

foreach ($users as $user) {
    $row = new html_table_row();
    $table->data[] = $row;
    $checkbox = '';
    if ($mappingrequested) {
        $checkbox = '<input type="radio" name="user" value="' . $user->id . '">';
    }
    $anonym = ! msocial_can_view_others_names($msocial);
    if ($anonym) {
        $pic = '';
        $link = msocial_get_visible_fullname($user, $msocial);
    } else {
        // User name and pic.
        $pic = $OUTPUT->user_picture($user);
        $link = html_writer::link(new moodle_url('/user/view.php', ['id' => $user->id]),
                                msocial_get_visible_fullname($user, $msocial));
    }
    $row->cells[] = new html_table_cell($checkbox . $pic . ' ' . $link);
    foreach ($enabledsocialplugins as $plugin) {
        if ($plugin->users_are_local() === false) {
            $row->cells[] = $plugin->render_user_link($user, false);
        }
    }
}
if ($mappingrequested) {
    echo '<form method="GET" action="' . $thispageurl->out_omit_querystring(true) . '" >';
    echo '<input type="hidden" name="id" value="' . $id . '"/>';
    echo '<input type="hidden" name="action" value="setmap"/>';
    echo '<input type="hidden" name="nativeid" value="' . $nativeid . '"/>';
    echo '<input type="hidden" name="nativename" value="' . $nativename . '"/>';
    echo '<input type="hidden" name="source" value="' . $source . '"/>';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '"/>';
    echo '<input type="hidden" name="redirect" value="' . $redirecturl . '"/>';
}
echo html_writer::table($table);
// Redirect.
if ($action == 'setmap') {
    echo $OUTPUT->continue_button(base64_decode(urldecode($redirecturl)));
    echo $OUTPUT->footer();
    die;
}
if ($mappingrequested) {
    echo '<input type="submit">';
    echo '</form>';
}

echo $OUTPUT->footer();
