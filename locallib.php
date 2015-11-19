<?php
require_once('TwitterAPIExchange.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/mathslib.php');

//require_once($CFG->dirroot . '/grade/lib.php');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Execute a Twitter API query with auth tokens and the hashtag configured in the module
 * @global type $DB
 * @param type $tcount_record
 * @return array
 */
function tcount_get_statuses($tcount_record) {
    if (eduvalab_timeIsBetween(time(), $tcount_record->counttweetsfromdate, $tcount_record->counttweetstodate)) {
        global $DB;
        $cmodule = get_coursemodule_from_instance('tcount', $tcount_record->id, null, null, MUST_EXIST);
        $tokens = $DB->get_record('tcount_tokens', array('tcount_id' => $cmodule->id));
        return tcount_find_tweeter($tokens, $tcount_record->hashtag);
    } else {
        return array();
    }
}

function tcount_process_statuses($statuses, $tcount) {

    $context = context_course::instance($tcount->course);
    list($students, $nonstudent, $active, $userrecords) = eduvalab_get_users_by_type($context);

    // Get tweeter usernames from students
    $tweeters = array();
    foreach ($students as $userid) {
        $user = $userrecords[$userid];
        $tweetername = $user->aim;
        if ($tweetername) {
            $tweeters[$tweetername] = $userid;
        }
    }

    // compile statuses of the students
    $students_statuses = array();
    foreach ($statuses as $status) {
        $tweetername = $status->user->screen_name;
        if (isset($tweeters[$tweetername])) { // tweet is of a student
            $created_date = strtotime($status->created_at);

            if (eduvalab_timeIsBetween($created_date, $tcount->counttweetsfromdate, $tcount->counttweetstodate)) {
                tcount_store_status($status, $tcount, $userrecords[$tweeters[$tweetername]]);
            }
        }
    }
}
function tcount_load_statuses($tcount,$user){
    global $DB;
    $condition=['tcountid'=>$tcount->id];
    if ($user){
        $condition['userid']=$user->id;
    }
    $statuses = $DB->get_records('tcount_statuses',$condition);
    return $statuses;
}
function tcount_store_status($status, $tcount, $userrecord) {
    global $DB;
    $tweetid = $status->id_str;
    $status_record = $DB->get_record('tcount_statuses', array('tweetid' => $tweetid));
    if (!$status_record) {
        $status_record = new stdClass();
    } else {
        $DB->delete_records('tcount_statuses', array('tweetid' => $tweetid));
    }
    $status_record->tweetid = $tweetid;
    $status_record->twitterusername = $status->user->screen_name;
    $status_record->tcountid = $tcount->id;
    $status_record->status = json_encode($status);
    $status_record->userid = $userrecord->id;
    $status_record->retweets = $status->retweet_count;
    $status_record->favs = $status->favorite_count;
    $status_record->hashtag = $tcount->hashtag;
    $DB->insert_record('tcount_statuses', $status_record);
}
/**
 * Connect to twitter API at https://api.twitter.com/1.1/search/tweets.json
 * @global type $CFG
 * @param type $tokens oauth tokens
 * @param type $hashtag hashtag to search for
 * @return stdClass result->statuses  o result->error
 */
function tcount_find_tweeter($tokens, $hashtag) {
    if (!$tokens) {
        return array();
    }
    global $CFG;
    $settings = array(
        'oauth_access_token' => $tokens->token,
        'oauth_access_token_secret' => $tokens->token_secret,
        'consumer_key' => $CFG->mod_tcount_consumer_key, // twitter developer app key,
        'consumer_secret' => $CFG->mod_tcount_consumer_secret// twitter developer app secret
    );
    // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
    // Perform the request and echo the response.
    $url = 'https://api.twitter.com/1.1/search/tweets.json';
    $getfield = "q=$hashtag";
    $requestMethod = "GET";
    $twitter = new TwitterAPIExchange($settings);
    $json = $twitter->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

    $result = json_decode($json);

    return $result;
}

/**
 * Find the list of users and get a list with the ids of students and a list of non-students
 * @param type $context_course
 * @return array(array($studentIds), array($non_studentIds), array($activeids), array($user_records))
 */
function eduvalab_get_users_by_type($context_course) {
    // Get users with gradable roles
    global $CFG;
    $gradable_roles = $CFG->gradebookroles;
    $roles = explode(',', $gradable_roles);
    $students = array();
    foreach ($roles as $roleid) {
        $users_in_role = get_role_users($roleid, $context_course);
        $ids = array_keys($users_in_role);
        $students = array_merge($students, $ids);
        $students = array_unique($students);
    }
    // get enrolled users
    $user_records = get_enrolled_users($context_course, '', 0, '*');
    $users = array_keys($user_records);
    $non_students = array_diff($users, $students);
    // select active userids
    $activeids = array();
    global $DB;
    list($select, $params) = $DB->get_in_or_equal($students);
    $select = "userid $select";
    $select.= " AND courseid = ?";
    $params[] = (int) $context_course->instanceid;
    $last_accesses = $DB->get_records_select('user_lastaccess', $select, $params);
    foreach ($last_accesses as $record) {
        $activeids[] = $record->userid;
    }
    return array($students, $non_students, $activeids, $user_records);
}

/**
 *
 * @param int $date
 * @param int|null $fromdate
 * @param int|null $todate
 * @return bool
 */
function eduvalab_timeIsBetween($date, $fromdate, $todate) {
    if ($fromdate == "0") {
        $fromdate = null;
    }
    if ($todate == "0") {
        $todate = null;
    }
    return ( (!isset($fromdate) || $date > $fromdate) &&
            (!isset($todate) || $date < $todate));
}

/**
 * Curl wrapper for OAuth
 */
class OAuthCurl {

    public function __construct() {

    }

    public static function fetchData($url) {
        $options = array(
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_SSL_VERIFYPEER => false,
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);

        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }

}

/**
 * Statistics for grading
 */
function tcount_calculate_stats($tcount, $users) {
    global $DB;
    $stats = $DB->get_records_sql('SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs,twitterusername FROM {tcount_statuses} where tcountid = ? group by userid, twitterusername',
            array($tcount->id));
    $user_stats = new stdClass();
    $user_stats->users = array();

    $favs = array();
    $retweets = array();
    $tweets = array();
    foreach ($users as $userid) {
        $stat = new stdClass();

        if (isset($stats[$userid])) {
            $tweets[] = $stat->tweets = $stats[$userid]->tweets;
            $retweets[] = $stat->retweets = $stats[$userid]->retweets;
            $favs[] = $stat->favs = $stats[$userid]->favs;

            $stat->tweeter = $stats[$userid]->twitterusername;
        } else {
            $stat->retweets = 0;
            $stat->tweets = 0;
            $stat->favs = 0;
            $stat->retweets = 0;
            $stat->tweeter = 'No tweets';
        }
        $user_stats->users[$userid] = $stat;
    }
    $stat = new stdClass();
    $stat->retweets = 0;
    $stat->tweets = count($tweets) == 0 ? 0 : max($tweets);
    $stat->favs = count($favs) == 0 ? 0 : max($favs);
    $stat->retweets = count($retweets) == 0 ? 0 : max($retweets);
    $user_stats->maximums = $stat;

    return $user_stats;
}
/**
 * Apply a formula to calculate a raw grade.
 *
 * @param type $tcount module instance
 * @param type $stats aggregated statistics of the tweets
 * @see tcount_calculate_stats
 * @return \stdClass grade struct with grade->rawgrade = -1 if no calculation is possible
 */
function tcount_calculate_grades($tcount, $stats) {
    $grades = array();
    foreach ($stats->users as $userid => $stat) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->itemname = 'twitterscore';

        $formula = $tcount->grade_expr;
        $formula = calc_formula::unlocalize($formula);
        $calculation = new calc_formula($formula);
        $calculation->set_params(array('favs' => $stat->favs,
            'retweets' => $stat->retweets,
            'tweets' => $stat->tweets,
            'maxfavs' => $stats->maximums->favs,
            'maxtweets' => $stats->maximums->tweets,
            'maxretweets' => $stats->maximums->retweets,
        ));
        $value = $calculation->evaluate();
        if ($value !== false) {
            $grade->rawgrade = $value;
        } else {
            $grade->rawgrade = -1;
        }
        $grade->feedback = "You have $stat->favs Favs $stat->retweets retweets and $stat->tweets tweets.";
        $grades[$userid] = $grade;
    }
    return $grades;
}

//function tcount_update_grades($tcount, $usersids) {
//    $grades = tcount_calculate_user_grades($tcount,$usersids);
//    $tcount_cm = get_coursemodule_from_instance('tcount', $tcount->id);
//    grade_update('mod/tcount', $tcount_cm->course, 'mod', 'tcount', $tcount_cm->id, 0, $grades);
//}

function tcount_calculate_user_grades($tcount, $userid = 0) {

    if ($userid == 0) {
        $cm = get_coursemodule_from_instance('tcount', $tcount->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        list($sudents) = eduvalab_get_users_by_type($context);
    } else if (is_array($userid)) {
        $students = $userid;
    } else {
        $students = array($userid);
    }

    $stats = tcount_calculate_stats($tcount, $students);
    $grades = tcount_calculate_grades($tcount, $stats);
    return $grades;
}

function tcount_get_custom_fieldname($tcount) {
    if (strpos('custom_', $tcount->fieldid) === 0) {
        $custom_fieldname = substr($fieldid, 7);
    } else {
        return false;
    }
}

function tcount_get_user_twittername($user, $tcount) {

    $fieldid = $tcount->fieldid;
    $custom_fieldname = tcount_get_custom_fieldname($tcount);

    if ($custom_fieldname !== false) {
        require_once('../../user/profile/lib.php');
        $profile = profile_user_record($user->id);
        return $profile->$custom_fieldname;
    } else {
        if (isset($user->$fieldid) && $user->$fieldid != '') {
            return $user->$fieldid;
        } else {
            return false;
        }
    }
}
