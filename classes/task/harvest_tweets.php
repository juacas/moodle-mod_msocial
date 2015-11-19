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
            try{
            $result = tcount_get_statuses($tcount);
            if (isset($result->errors)) {
                $cm = get_coursemodule_from_instance('tcount', $tcount->id, null, null, MUST_EXIST);
                $token = $DB->get_record('tcount_tokens',['tcount_id'=>$cm->id]);
                if ($token){
                    $info="UserToken for:$token->username ";
                }else{
                    $info="No twitter token defined!!";
                }
                mtrace("For module tcount: $tcount->name (mdl_tcount->id=$tcount->id) searching: $tcount->hashtag $info ERROR:".$result->errors[0]->message);
            } else {
                $statuses = count($result->statuses) == 0 ? array() : $result->statuses;

                mtrace("For module tcount: $tcount->name (id=$tcount->id) searching: $tcount->hashtag  Found " . count($statuses) . " tweets.");
                tcount_process_statuses($statuses, $tcount);
                $context_course = \context_course::instance($tcount->course);
                list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($context_course);
                tcount_update_grades($tcount, $students);
            }
            }catch(\Exception $e){
                mtrace("Error processing tcount: $tcount->name. Skipping. ".$e->getMessage());
            }
        }
        mtrace("=======================");
        return true;
    }
}
