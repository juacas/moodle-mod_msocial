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
        $courseid = $COURSE->id;

        mtrace("\n=======================");
        mtrace("MSocial count module.");
        mtrace("=======================");
        // Get instances.
        $msocials = $DB->get_records('msocial');
        $enabledplugins = \mod_msocial\plugininfo\msocialbase::get_system_enabled_plugins_all_types();
        $msocials = array_filter($msocials,
                function ($msocial) {
                    $cminfo = get_coursemodule_from_instance('msocial', $msocial->id);
                    return !$cminfo->deletioninprogress;
                });
        mtrace("<li>Processing plugins:" . implode(', ', array_keys($enabledplugins)) . ' in ' . count($msocials) . " instances.");
        mtrace("==========================================================================");
        foreach ($msocials as $msocial) {

            foreach (\mod_msocial\plugininfo\msocialconnector::get_enabled_plugins_all_types($msocial) as $type => $plugin) {
                try {
                    if ($plugin->is_tracking()) {
                        $result = $plugin->harvest();
                        foreach ($result->messages as $message) {
                            \mtrace($message);
                        }
                    } else {
                        mtrace("<li>Plugin $type is not tracking. (Missing token, hashtag or disabled.)");
                    }
                } catch (\Exception $e) {
                    mtrace("<li>Error processing msocial: $msocial->name. Skipping. " . $e->getMessage() .
                            '\n' . $e->getTraceAsString());
                }
            }
        }
        mtrace("=======================");
        return true;
    }
}
