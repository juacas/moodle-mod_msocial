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
use mod_msocial\connector\social_interaction;

require_once("../../config.php");
require_once("locallib.php");
require_once('classes/plugininfo/msocialbase.php');
require_once("msocialplugin.php");
// require_once("msocialconnectorplugin.php");
// require_once("msocialviewplugin.php");
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
$thispageurl = new moodle_url('/mod/msocial/view.php', $_GET);
$PAGE->set_url($thispageurl);

$requ = $PAGE->requires;
$requ->css('/mod/msocial/styles.css');
$requ->js(new moodle_url('/mod/msocial/js/moment.js'), true);
$requ->jquery();
// $requ->jquery_plugin('timepicker', 'msocial');
$requ->jquery_plugin('ui');
$requ->jquery_plugin('ui-css');
$requ->jquery_plugin('daterangepicker', 'msocial');
$requ->jquery_plugin('datepicker_es', 'msocial'); // TODO support rest of languages.

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
// Description text.
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
// Module intro.
echo format_module_intro('msocial', $msocial, $cm->id);
echo $OUTPUT->box_end();
// Date span of the tracking.
$dates = new stdClass();
$dates->startdate = $msocial->startdate ? userdate($msocial->startdate) : '-∞';
$dates->enddate = $msocial->enddate ? userdate($msocial->enddate) : '∞';
$datemsg = format_string(get_string('msocial:daterange', 'msocial', $dates));
echo $OUTPUT->box($datemsg, 'block');
// Print the information about the linking of the module with social plugins..
$enabledsocialplugins = \mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
$enabledplugins = array_merge($enabledviewplugins, $enabledsocialplugins);
$totalnotification = '';
/** @var msocial_plugin $enabledplugin */
foreach ($enabledplugins as $name => $enabledplugin) {
    list($messages, $notifications) = $enabledplugin->render_header();
    $updated = $enabledplugin->get_updated_date();
    $updatemessage = '';
    if ($updated) {
        $updatemessage = get_string('harvestedtimeago', 'msocial',
                                [ 'interval' => msocial_pretty_date_difference($updated->getTimestamp())] ) .
                                ' ';
    }
    if (has_capability('mod/msocial:manage', $contextmodule)) {
        $updatemessage .= $enabledplugin->render_harvest_link();
    }
    if ($updatemessage != '') {
        $messages[] = $updatemessage;
    }
    // Group messages.
    $compactheader = true;
    if (count($messages) > 0) {
        $icon = $enabledplugin->get_icon();
        $icondecoration = \html_writer::img($icon->out(), $enabledplugin->get_name() . ' icon.', ['height' => 29]) . ' ';
        if ($compactheader) {
            $tablemsgs = join(' / ', $messages);
            $totalnotification .= ''. $icondecoration . ' ' . $tablemsgs. '<br/>';
        } else {
            $tablemsgs = join('</br>', $messages);
            $totalnotification .= '<table><tr><td valign="top">'. $icondecoration . '</td><td>' . $tablemsgs. '</td></tr></table>';
        }
    }
    // For saving vertical space all messages are rendered together  $enabledplugin->notify($messages,
    // msocial_plugin::NOTIFY_NORMAL).
    $enabledplugin->notify($notifications, msocial_plugin::NOTIFY_WARNING);
}
msocial_notify_info($totalnotification);
// Filters section.
require_once('filterinteractions.php');
$filter = new filter_interactions($_GET, $msocial);

// Reporting area...
// Tabs...
echo msocial_tabbed_reports($msocial, $view, $thispageurl, $contextmodule, false);

if (isset($enabledviewplugins[$view]) && $enabledviewplugins[$view]->is_enabled()) {
    $enabledviewplugins[$view]->render_view($OUTPUT, $requ, $filter);
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
