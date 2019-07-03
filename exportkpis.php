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
use mod_msocial\msocial_plugin;
use mod_msocial\connector\social_interaction;
use mod_msocial\filter_interactions;

require_once("../../config.php");
require_once('locallib.php');
require_once('classes/msocialconnectorplugin.php');

global $CFG;
$id = required_param('id', PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHA);
$format = optional_param('format', '', PARAM_ALPHA);
$dumpall = optional_param('all', false, PARAM_INT);
$url = new moodle_url('/mod/msocial/exportkpis.php', array('id' => $id));
if ($type !== '') {
    $url->param('type', $type);
}
if ($format !== '') {
    $url->param('download', $format);
}

$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('msocial', $id)) {
    print_error("invalidcoursemodule");
}

$course = $DB->get_record("course", array("id" => $cm->course), '*', MUST_EXIST);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/msocial:exportkpis', $context);
$contextcourse = context_course::instance($course->id);
require_capability('mod/msocial:exportkpis', $contextcourse);


$strmsocial = get_string("modulename", "msocial");
$strmsocials = get_string("modulenameplural", "msocial");


if (!empty($format) && !empty($type) ) {
    $hiddencolumns = [];
    $anonymouscolumns = [];
    $usersstruct = msocial_get_users_by_type($contextcourse);
    if ($type == 'kpis') {
        $hiddencolumns = ['id', 'msocial'];
        $anonymouscolumns = ['userid', 'firstname', 'lastname'];
        $data = msocial_plugin::get_kpis($msocial, $students, null);
    } else if ($type == 'interactions') {
        $hiddencolumns = ['id', 'msocial', 'status'];
        $anonymouscolumns = ['fromid', 'toid'];
        require_once('classes/filterinteractions.php');
        require_once('classes/socialinteraction.php');
        $filter = new filter_interactions([], $msocial);
        if ($dumpall) {
            $filter->unknownusers = true;
            $filter->receivedbyteachers = true;
            $filter->pureexternal = true;
        } else {
            $filter->set_users($usersstruct);
        }
        // Process interactions.
        $data = social_interaction::load_interactions_filter($filter);
    }
    // Find column names.
    $columnnames = [];
    if (count($data) > 0) {
        $rowitem = reset($data);
        $columnnames = array_keys(get_object_vars($rowitem));
        $columnnames = array_filter($columnnames, function($columname) use ($hiddencolumns) {
            return array_search($columname, $hiddencolumns) === false;
        });
        $columnnames = array_values($columnnames);

        // Add usernames.
        if ($type == 'kpis') {
            $userrecords = $usersstruct->userrecords;
            foreach ($data as $item) {
                $item->firstname = $userrecords[$item->userid]->firstname;
                $item->lastname = $userrecords[$item->userid]->lastname;
                $item->idnumber = $userrecords[$item->userid]->idnumber;
            }
            array_unshift($columnnames, 'idnumber', 'firstname', 'lastname');
        }

        if ($format == "xls") {
            require_once("$CFG->libdir/excellib.class.php");
            $workbook = new MoodleExcelWorkbook("-");
        } else if ($format == "ods") {
            require_once("$CFG->libdir/odslib.class.php");
            $workbook = new MoodleODSWorkbook("-");
        } else if ($format == "csv") {
            require_once("classes/csvlib.class.php");
            $workbook = new CSVWorkbook(";");
        } else {
            print_error("coursemisconf");
        }
        // Calculate file name.
        $filename = clean_filename("{$course->shortname}_".strip_tags(format_string($msocial->name, true)). '_' . $type)
                        . '.' . $format;
        // Creating a workbook.
        // Send HTTP headers.
        $workbook->send($filename);
        // Creating the first worksheet.
        $myxls = $workbook->add_worksheet($type);
        // Print names of all the fields.
        for ($i = 0; $i < count($columnnames); $i++) {
            $myxls->write_string(0, $i, $columnnames[$i]);
        }
        // Generate the data for the body of the spreadsheet.
        $i = 0;
        $row = 1;
        foreach ($data as $datarow) {
            $columnvalues = get_object_vars($datarow);
            for ($i = 0; $i < count($columnnames); $i++) {
                $columnname = $columnnames[$i];
                $value = $columnvalues[$columnname];
                if ($value instanceof DateTime) {
                    $value = date_format($value, 'Y-m-d H:i:s');
                } else {
                    $value = (string) $value;
                }
                $myxls->write_string($row, $i, $value);
            }
            $row++;
        }
        // Close the workbook.
        $workbook->close();

        $eventparams = array(
                        'context' => $context,
                        'objectid' => $msocial->id
        );
        $event = \mod_msocial\event\kpi_exported::create($eventparams);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('msocial', $msocial);
        $event->trigger();

        exit;
    }
} else {
    $OUTPUT->early_notification('No data');
}
// Now give links for downloading spreadsheets.
echo $OUTPUT->header();
$downloadoptions = array();
$options = array();

echo $OUTPUT->heading('Interactions');
$options["id"] = "$cm->id";
$options["format"] = "ods";
$options["type"] = "interactions";
$options["all"] = $dumpall;
$button = $OUTPUT->single_button(new moodle_url("exportkpis.php", $options), get_string("downloadods"));
$downloadoptions[] = html_writer::tag('li', $button, array('class' => 'reportoption'));

$options["format"] = "xls";
$button = $OUTPUT->single_button(new moodle_url("exportkpis.php", $options), get_string("downloadexcel"));
$downloadoptions[] = html_writer::tag('li', $button, array('class' => 'reportoption'));

$options["format"] = "csv";
$button = $OUTPUT->single_button(new moodle_url("exportkpis.php", $options), get_string("downloadtext"));
$downloadoptions[] = html_writer::tag('li', $button, array('class' => 'reportoption'));

$downloadlist = html_writer::tag('ul', implode('', $downloadoptions));
echo html_writer::tag('div', $downloadlist, array('class' => 'downloadreport'));

echo $OUTPUT->heading('KPIs');
$downloadoptions = array();
$options["format"] = "ods";
$options["type"] = "kpis";
$button = $OUTPUT->single_button(new moodle_url("exportkpis.php", $options), get_string("downloadods"));
$downloadoptions[] = html_writer::tag('li', $button, array('class' => 'reportoption'));

$options["format"] = "xls";
$button = $OUTPUT->single_button(new moodle_url("exportkpis.php", $options), get_string("downloadexcel"));
$downloadoptions[] = html_writer::tag('li', $button, array('class' => 'reportoption'));

$options["format"] = "csv";
$button = $OUTPUT->single_button(new moodle_url("exportkpis.php", $options), get_string("downloadtext"));
$downloadoptions[] = html_writer::tag('li', $button, array('class' => 'reportoption'));

$downloadlist = html_writer::tag('ul', implode('', $downloadoptions));
echo html_writer::tag('div', $downloadlist, array('class' => 'downloadreport'));

echo $OUTPUT->footer();

