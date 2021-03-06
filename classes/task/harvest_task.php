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
// along with MSocial for Moodle. If not, see <http://www.gnu.org/licenses/>.
/** For debugging:
 * SET XDEBUG_CONFIG=netbeans-xdebug=xdebug
 * php.exe admin\tool\task\cli\schedule_task.php --execute=\mod_msocial\task\harvest_task */
namespace mod_msocial\task;

use mod_msocial\harvest_controller;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

global $CFG;
require_once($CFG->dirroot . '/mod/msocial/lib.php');
require_once($CFG->dirroot . '/mod/msocial/locallib.php');

class harvest_task extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string('harvest_task', 'mod_msocial');
    }

    public function execute() {
        global $COURSE;
        global $USER;
        /* @var $DB \moodle_database */
        global $DB;

        mtrace("\n=======================\n");
        mtrace("MSocial count module. \n");
        mtrace("=======================\n");
        // Get instances.
        $msocials = $DB->get_records('msocial');
        $enabledplugins = \mod_msocial\plugininfo\msocialbase::get_system_enabled_plugins_all_types();
        $msocials = array_filter($msocials,
                function ($msocial) {
                    $cminfo = get_coursemodule_from_instance('msocial', $msocial->id);
                    return !isset($cminfo->deletioninprogress) || !$cminfo->deletioninprogress;
                });
        mtrace("\n<li>Processing plugins:" . implode(', ', array_keys($enabledplugins)) . ' in ' . count($msocials) . " instances.</li>");
        mtrace("\n==========================================================================");
        foreach ($msocials as $msocial) {
            try {
                mtrace("\n\n<li>Course $msocial->course  Msocial instance: '$msocial->name'<li>");
                $course = get_course($msocial->course);
                mtrace("\n<li> Course: '$course->shortname' </li>");
                $controller = new harvest_controller($msocial);
                $controller->execute_harvests();

            } catch (\Exception $ex) {
                    mtrace( "\nERRORRRR" . $ex->getTraceAsString() . "\n" );
            }
            continue;
// TODO: call Harvest proxy
            foreach (\mod_msocial\plugininfo\msocialconnector::get_enabled_plugins_all_types($msocial) as $type => $plugin) {
                try {
                    if ($plugin->is_tracking()) {
                        $result = $plugin->harvest();
                        foreach ($result->messages as $message) {
                            \mtrace($message);
                        }
                    } else {
                        mtrace("\n<li>Plugin $type is not tracking. (Missing token, hashtag or disabled.)\n");
                    }
                } catch (\Exception $e) {
                    mtrace("\n<li>Error processing msocial: $msocial->name. Skipping. " . $e->getMessage() .
                            "\n" . $e->getTraceAsString() . "\n");
                }
            }
        }
        mtrace("\n=======================\n");
        return true;
    }
}
