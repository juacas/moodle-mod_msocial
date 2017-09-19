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
use msocial\msocial_plugin;

require_once("../../config.php");
require_once("locallib.php");
require_once("msocialconnectorplugin.php");
require_once("msocialviewplugin.php");
/* @var $OUTPUT \core_renderer */
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);
$view = optional_param('view', 'table', PARAM_ALPHA);
$cattab = optional_param('cattab', msocial_plugin::CAT_VISUALIZATION, PARAM_ALPHA);

$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);

$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$user = $USER;
// Capabilities.
$contextmodule = context_module::instance($cm->id);
require_capability('mod/msocial:view', $contextmodule);

// Show headings and menus of page.
$url = new moodle_url('/mod/msocial/view.php', array('id' => $id));
$PAGE->set_url($url);

$requ = $PAGE->requires;
$requ->css('/mod/msocial/styles.css');
$requ->jquery();
// Configure header for plugins.
$enabledviewplugins = msocialview::get_enabled_view_plugins($msocial);
foreach ($enabledviewplugins as $name => $plugin) {
    $plugin->render_header_requirements($requ, $view);
}
// Print the page header.
$PAGE->set_title(format_string($msocial->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// Print the main part of the page.
echo $OUTPUT->spacer(array('height' => 20));
echo $OUTPUT->heading(format_string($msocial->name) . $OUTPUT->help_icon('mainpage', 'msocial'));
// Print the information about the linking of the module with social plugins..
$enabledsocialplugins = \mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
$enabledplugins = array_merge($enabledviewplugins, $enabledsocialplugins);
$totalnotification = '';
/** @var msocial_plugin $enabledplugin */
foreach ($enabledplugins as $name => $enabledplugin) {
    list($messages, $notifications) = $enabledplugin->render_header();
    $updated = $enabledplugin->get_updated_date();
    if ($updated) {
        $messages[] = 'Updated ' . msocial_pretty_date_difference($updated->getTimestamp()) .
                        ' ago.' . $enabledplugin->render_harvest_link();
    }
    // Group messages.
    if (count($messages) > 0) {
        $icon = $enabledplugin->get_icon();
        $icondecoration = \html_writer::img($icon->out(), $enabledplugin->get_name() . ' icon.', ['height' => 29]) . ' ';
        $tablemsgs = join('</br>', $messages);
        $totalnotification .= '<table><tr><td valign="top">'. $icondecoration . '</td><td>' . $tablemsgs. '</td></tr></table>';
    }
    // For saving vertical space all messages are rendered together  $enabledplugin->notify($messages,
    // msocial_plugin::NOTIFY_NORMAL).
    $enabledplugin->notify($notifications, msocial_plugin::NOTIFY_WARNING);
}
echo $OUTPUT->box($totalnotification, 'block');

// Description text.
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
echo format_module_intro('msocial', $msocial, $cm->id);
echo $OUTPUT->box_end();
// Reporting area...

// Tabs...
echo msocial_tabbed_reports($msocial, $view, $cm, $contextmodule, false);

if (isset($enabledviewplugins[$view]) && $enabledviewplugins[$view]->is_enabled()) {
    $enabledviewplugins[$view]->render_view($OUTPUT, $requ);
}

// Insert widget view.
/*
 * if (isset($msocial->widget_id)) {
 * echo ('<a class="twitter-timeline" data-dnt="true" target="_blank"
 * href="https://twitter.com/search?q=' .
 * urlencode($msocial->hashtag) . '" data-widget-id="' . $msocial->widget_id . '">Tweets sobre ' .
 * $msocial->hashtag . '</a>');
 * ?>
 * <script>!function (d, s, id) {
 * var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
 * if (!d.getElementById(id)) {
 * js = d.createElement(s);
 * js.id = id;
 * js.src = p + "://platform.twitter.com/widgets.js";
 * fjs.parentNode.insertBefore(js, fjs);
 * }
 * }(document, "script", "twitter-wjs");</script>
 * <?php
 * }
 */
// Finish the page.
echo $OUTPUT->footer();
