<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information
 *
 * @package    mod
 * @subpackage msocial
 * @copyright  2017 Juan Pablo de Castro
 * @author     Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once('locallib.php');
require_once('classes/msocialconnectorplugin.php');

global $OUTPUT, $PAGE, $DB;

$id = required_param('id', PARAM_INT);
$type = optional_param('type', 'all', PARAM_ALPHA);
$url = new moodle_url('/mod/msocial/calculatekpis.php', array('id' => $id));
if ($type !== '') {
    $url->param('type', $type);
}

$PAGE->set_url($url);
if (! $cm = get_coursemodule_from_id('msocial', $id)) {
    print_error("invalidcoursemodule");
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error("coursemisconf");
}
require_login($course->id);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);

$PAGE->set_title(get_string('recalc_kpis', 'msocial'));
$PAGE->set_heading(get_string('recalc_kpis', 'msocial')     );
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('recalc_kpis', 'msocial'));


require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
$contextcourse = context_course::instance($course->id);
require_capability('mod/msocial:manage', $context);

$plugins = \mod_msocial\plugininfo\msocialconnector::get_enabled_plugins_all_types($msocial);

$usersstruct = msocial_get_users_by_type($contextcourse);
foreach ($plugins as $plugin) {
    $kpis = $plugin->calculate_kpis($usersstruct);
    $plugin->store_kpis($kpis);
    echo "<li>Plugin " . $plugin->get_name() . " updated " . count($kpis) . " users' kpis.</li>";
}

echo $OUTPUT->footer();