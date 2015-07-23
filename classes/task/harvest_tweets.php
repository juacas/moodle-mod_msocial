<?php

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
        // Shown in admin screens
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
            $statuses = tcount_get_statuses($tcount);
            mtrace("For module tcount: $tcount->name (id=$tcount->id) searching: $tcount->hashtag  Found " . count($statuses) . " tweets.");
            tcount_process_statuses($statuses, $tcount);
            $context_course = \context_course::instance($tcount->course);
            list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($context_course);
            tcount_update_grades($tcount, $students);
        }


        mtrace("=======================");
        mtrace("=======================");
        return true;
    }

}
