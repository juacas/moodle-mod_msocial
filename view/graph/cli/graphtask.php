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
namespace mod_msocial\view\graph;
use mod_msocial\plugininfo\msocialbase;

define('CLI_SCRIPT', true);
$moodleconfig = __DIR__.'/../../../../../config.php';
$moodleconfig = 'D:\desarrollo\apacheMoodle\moodle3\moodle\config.php';
require($moodleconfig);
require_once($CFG->libdir.'/clilib.php');
// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false, 'msocial' => true,
                'noaspectratio' => false, 'path' => $CFG->dirroot),
                array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
if ($options['help']) {
    $help = "Launch the graph analysis task

Options:
-h, --help            Print out this help
--msocial=id          Internal Id of the msocial instance.
Example:
\$sudo -u www-data /usr/bin/php mod/msocial/view/graph/cli/graphtask.php --msocial=23
";

    echo $help;
    exit(0);
}
$msocialpath = __DIR__.'/../../..';
require_once($msocialpath . '/classes/plugininfo/msocialbase.php');
require($msocialpath.'/view/graph/graphtask.php');
require($msocialpath.'/locallib.php');
require($msocialpath.'/msocialconnectorplugin.php');
require($msocialpath.'/view/graph/graphplugin.php');

$msocialid = $options['msocial'];
global $DB;
$msocial = $DB->get_record('msocial', ['id' => $msocialid]);
// $plugin = msocialbase::instance($msocial, 'view', msocial_view_graph::PLUGINNAME);
$task = new graph_task();
$contextcourse = \context_course::instance($msocial->course);
list($students, $nonstudents, $active, $users) = array_values(msocial_get_users_by_type($contextcourse));
$task->set_custom_data((object)['msocial' => $msocial, 'users' => $users ]);
$task->execute();
