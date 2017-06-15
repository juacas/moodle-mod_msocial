<?php
// This file is part of TwitterCount activity for Moodle http://moodle.org/
//
// Questournament for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Questournament for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * For debugging:
 * SET XDEBUG_CONFIG=netbeans-xdebug=xdebug
 * php.exe admin\tool\task\cli\schedule_task.php --execute=\mod_tcount\task\harvest_tweets
 */
namespace mod_tcount\task;

global $CFG;
require_once($CFG->dirroot . '/mod/tcount/lib.php');
require_once($CFG->dirroot . '/mod/tcount/locallib.php');

class harvest_tweets extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string('harvest_tweets', 'mod_tcount');
    }

    public function execute() {
        global $COURSE;
        global $USER;
        /* @var $DB \moodle_database */
        global $DB;
        $courseid = $COURSE->id;

        mtrace("\n=======================");
        mtrace("Twitter count module.");
        mtrace("=======================");
        // Get instances.
        $tcounts = $DB->get_records('tcount');
        $enabledplugins = \mod_tcount\plugininfo\tcountsocial::get_enabled_social_plugins();
        mtrace("Processing plugins:" . implode(', ', array_keys($enabledplugins)) . ' in ' . count($tcounts) . " instances.");
        foreach ($tcounts as $tcount) {
            foreach (\mod_tcount\plugininfo\tcountsocial::get_enabled_social_plugins($tcount) as $type => $plugin) {
                try {
                    if ($plugin->is_tracking()) {
                        $result = $plugin->harvest();
                        foreach ($result->messages as $message) {
                            \mtrace($message);
                        }
                    } else {
                        mtrace("Plugin $type is not tracking. (Missing token, hashtag or disabled.)");
                    }
                } catch (\Exception $e) {
                    mtrace("Error processing tcount: $tcount->name. Skipping. " . $e->error . '\n' . $e->getTraceAsString());
                }
            }
        }
        mtrace("=======================");
        return true;
    }
}
