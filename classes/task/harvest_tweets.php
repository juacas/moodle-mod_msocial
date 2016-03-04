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
        global $DB;
        $courseid = $COURSE->id;

        mtrace("\n=======================");
        mtrace("Twitter count module.");
        mtrace("=======================");

        $tcounts = $DB->get_records('tcount');

        foreach ($tcounts as $tcount) {
            try {
                $result = tcount_get_statuses($tcount);
                if (isset($result->errors)) {
                    $cm = get_coursemodule_from_instance('tcount', $tcount->id, null, null, MUST_EXIST);
                    $token = $DB->get_record('tcount_tokens', ['tcount_id' => $cm->id]);
                    if ($token) {
                        $info = "UserToken for:$token->username ";
                    } else {
                        $info = "No twitter token defined!!";
                    }
                    mtrace("For module tcount: $tcount->name (mdl_tcount->id=$tcount->id) searching: $tcount->hashtag $info ERROR:"
                            . $result->errors[0]->message);
                } else if (isset($result->statuses)) {
                    $statuses = count($result->statuses) == 0 ? array() : $result->statuses;

                    mtrace("For module tcount: $tcount->name (id=$tcount->id) searching: $tcount->hashtag  Found "
                            . count($statuses) . " tweets.");
                    tcount_process_statuses($statuses, $tcount);
                    $contextcourse = \context_course::instance($tcount->course);
                    list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
                    tcount_update_grades($tcount, $students);
                } else {
                    mtrace("For module tcount: $tcount->name (id=$tcount->id) searching: $tcount->hashtag  ERROR querying twitter results null! Maybe there is no tweeter account linked in this activity.");
                }
            } catch (\Exception $e) {
                mtrace("Error processing tcount: $tcount->name. Skipping. " . $e->getMessage());
            }
        }
        mtrace("=======================");
        return true;
    }

}
