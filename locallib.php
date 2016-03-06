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
require_once('TwitterAPIExchange.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/mathslib.php');


/**
 * Execute a Twitter API query with auth tokens and the hashtag configured in the module
 * @global type $DB
 * @param type $tcountrecord
 * @return array
 */
function tcount_get_statuses($tcountrecord) {
    if (eduvalab_time_is_between(time(), $tcountrecord->counttweetsfromdate, $tcountrecord->counttweetstodate)) {
        global $DB;
        $cmodule = get_coursemodule_from_instance('tcount', $tcountrecord->id, null, null, MUST_EXIST);
        $tokens = $DB->get_record('tcount_tokens', array('tcount_id' => $cmodule->id));
        return tcount_find_tweeter($tokens, $tcountrecord->hashtag);
    } else {
        return array();
    }
}

function tcount_process_statuses($statuses, $tcount) {

    $context = context_course::instance($tcount->course);
    list($students, $nonstudent, $active, $userrecords) = eduvalab_get_users_by_type($context);
    $all=array_keys($userrecords); //  Include all users (including teachers),
    // Get tweeter usernames from users' profile.
    $tweeters = array();
    foreach ($all as $userid) {
        $user = $userrecords[$userid];
        $tweetername = strtolower(str_replace('@', '', $user->aim));
        if ($tweetername) {
            $tweeters[$tweetername] = $userid;
        }
    }

    // Compile statuses of the users.
    $studentsstatuses = array();
    foreach ($statuses as $status) {
        $tweetername = strtolower($status->user->screen_name);
        if (isset($tweeters[$tweetername])) { // Tweet is of a users.
            $userauthor = $userrecords[$tweeters[$tweetername]];
        }else{
            $userauthor=null;
        }
        $createddate = strtotime($status->created_at);

        if (isset($status->retweeted_status)){ // Retweet count comes from original message. Ignore it.
            $status->retweet_count=0;
        }

        if (eduvalab_time_is_between($createddate, $tcount->counttweetsfromdate, $tcount->counttweetstodate)) {
            tcount_store_status($status, $tcount, $userauthor);
        }
    }
}

function tcount_load_statuses($tcount, $user) {
    global $DB;
    $condition = ['tcountid' => $tcount->id];
    if ($user) {
        $condition['userid'] = $user->id;
    }
    $statuses = $DB->get_records('tcount_statuses', $condition);
    return $statuses;
}

function tcount_store_status($status, $tcount, $userrecord) {
    global $DB;
    $tweetid = $status->id_str;
    $statusrecord = $DB->get_record('tcount_statuses', array('tweetid' => $tweetid));
    if (!$statusrecord) {
        $statusrecord = new stdClass();
    } else {
        $DB->delete_records('tcount_statuses', array('tweetid' => $tweetid));
    }
    $statusrecord->tweetid = $tweetid;
    $statusrecord->twitterusername = $status->user->screen_name;
    $statusrecord->tcountid = $tcount->id;
    $statusrecord->status = json_encode($status);
    $statusrecord->userid = $userrecord!=null?$userrecord->id:null;
    $statusrecord->retweets = $status->retweet_count;
    $statusrecord->favs = $status->favorite_count;
    $statusrecord->hashtag = $tcount->hashtag;
    $DB->insert_record('tcount_statuses', $statusrecord);
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
        'consumer_key' => $CFG->mod_tcount_consumer_key, // ...twitter developer app key.
        'consumer_secret' => $CFG->mod_tcount_consumer_secret// ...twitter developer app secret.
    );
    // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
    // Perform the request and echo the response.
    $url = 'https://api.twitter.com/1.1/search/tweets.json';
    $getfield = "q=$hashtag&count=100";
    $requestmethod = "GET";
    $twitter = new TwitterAPIExchange($settings);
    $json = $twitter->set_getfield($getfield)->build_oauth($url, $requestmethod)->perform_request();

    $result = json_decode($json);
if ($result==null){
    echo $json;
    die;
}
    return $result;
}

/**
 * Find the list of users and get a list with the ids of students and a list of non-students
 * @param type $contextcourse
 * @return array(array($studentIds), array($non_studentIds), array($activeids), array($user_records))
 */
function eduvalab_get_users_by_type($contextcourse) {
    // Get users with gradable roles.
    global $CFG;
    $gradableroles = $CFG->gradebookroles;
    $roles = explode(',', $gradableroles);
    $students = array();
    foreach ($roles as $roleid) {
        $usersinrole = get_role_users($roleid, $contextcourse);
        $ids = array_keys($usersinrole);
        $students = array_merge($students, $ids);
        $students = array_unique($students);
    }
    // ...get enrolled users.
    $userrecords = get_enrolled_users($contextcourse, '', 0, '*');
    $users = array_keys($userrecords);
    $nonstudents = array_diff($users, $students);
    // ...select active userids.
    $activeids = array();
    global $DB;
    list($select, $params) = $DB->get_in_or_equal($students);
    $select = "userid $select";
    $select .= " AND courseid = ?";
    $params[] = (int) $contextcourse->instanceid;
    $lastaccesses = $DB->get_records_select('user_lastaccess', $select, $params);
    foreach ($lastaccesses as $record) {
        $activeids[] = $record->userid;
    }
    return array($students, $nonstudents, $activeids, $userrecords);
}

/**
 *
 * @param int $date
 * @param int|null $fromdate
 * @param int|null $todate
 * @return bool
 */
function eduvalab_time_is_between($date, $fromdate, $todate) {
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

    public static function fetch_data($url) {
        $options = array(
            CURLOPT_RETURNTRANSFER => true, // ...return web page.
            CURLOPT_HEADER => false, // ...don't return headers.
            CURLOPT_FOLLOWLOCATION => true, // ...follow redirects.
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
    $stats = $DB->get_records_sql('SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs FROM {tcount_statuses} where tcountid = ? and userid is not null group by userid',
            array($tcount->id));
    $userstats = new stdClass();
    $userstats->users = array();

    $favs = array();
    $retweets = array();
    $tweets = array();
    foreach ($users as $userid) {
        $stat = new stdClass();

        if (isset($stats[$userid])) {
            $tweets[] = $stat->tweets = $stats[$userid]->tweets;
            $retweets[] = $stat->retweets = $stats[$userid]->retweets;
            $favs[] = $stat->favs = $stats[$userid]->favs;

//            $stat->tweeter = $stats[$userid]->twitterusername;
        } else {
            $stat->retweets = 0;
            $stat->tweets = 0;
            $stat->favs = 0;
            $stat->retweets = 0;
//            $stat->tweeter = 'No tweets';
        }
        $userstats->users[$userid] = $stat;
    }
    $stat = new stdClass();
    $stat->retweets = 0;
    $stat->tweets = count($tweets) == 0 ? 0 : max($tweets);
    $stat->favs = count($favs) == 0 ? 0 : max($favs);
    $stat->retweets = count($retweets) == 0 ? 0 : max($retweets);
    $userstats->maximums = $stat;

    return $userstats;
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
        $customfieldname = substr($fieldid, 7);
    } else {
        return false;
    }
}

function tcount_get_user_twittername($user, $tcount) {

    $fieldid = $tcount->fieldid;
    $customfieldname = tcount_get_custom_fieldname($tcount);

    if ($customfieldname !== false) {
        require_once('../../user/profile/lib.php');
        $profile = profile_user_record($user->id);
        return $profile->$customfieldname;
    } else {
        if (isset($user->$fieldid) && $user->$fieldid != '') {
            return $user->$fieldid;
        } else {
            return false;
        }
    }
}
