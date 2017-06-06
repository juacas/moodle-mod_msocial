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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die();
require_once ('TwitterAPIExchange.php');


/**
 * library class for social network twitter plugin extending social plugin base class
 *
 * @package tcountsocial_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_social_twitter extends tcount_social_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountsocial_twitter');
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $twfieldid = $this->get_config('twfieldid');
        $defaultvalues['tcountsocial_twitter_twfieldid'] = $twfieldid === "" ? null : $twfieldid;
        $defaultvalues['tcountsocial_twitter_hashtag'] = $this->get_config('hashtag');
        $defaultvalues['tcountsocial_twitter_enabled'] = $this->get_config('enabled');
        return;
    }

    /**
     * Get the settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $idtypeoptions = tcount_get_user_fields();
        $mform->addElement('select', 'tcountsocial_twitter_twfieldid', get_string("twfieldid", "tcountsocial_twitter"), 
                $idtypeoptions);
        $mform->setDefault('tcountsocial_twitter_twfieldid', 'aim');
        $mform->addHelpButton('tcountsocial_twitter_twfieldid', 'twfieldid', 'tcountsocial_twitter');
        $mform->addElement('text', 'tcountsocial_twitter_hashtag', get_string("hashtag", "tcountsocial_twitter"), 
                array('size' => '20'));
        $mform->setType('tcountsocial_twitter_hashtag', PARAM_TEXT);
        $mform->addHelpButton('tcountsocial_twitter_hashtag', 'hashtag', 'tcountsocial_twitter');
    }

    /**
     * Save the settings for twitter plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        if (empty($data->tcountsocial_twitter_twfieldid)) {
            $twfieldid = 0;
        } else {
            $twfieldid = $data->tcountsocial_twitter_twfieldid;
        }
        $this->set_config('twfieldid', $twfieldid);
        if (isset($data->tcountsocial_twitter_hashtag)) {
            $this->set_config('hashtag', $data->tcountsocial_twitter_hashtag);
        }
        if (isset($data->tcountsocial_twitter_enabled)) {
            $this->set_config('enabled', $data->tcountsocial_twitter_enabled);
        }
        return true;
    }

    /**
     * Add form elements for settings
     *
     * @param mixed $this->tcount can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements(MoodleQuickForm $mform, stdClass $data) {
        $elements = array();
        $this->tcount = $this->tcount ? $this->tcount->id : 0;
        
        return true;
    }

    /**
     * The tcount has been deleted - cleanup subplugin
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (!$DB->delete_records('tcount_tweets', array('tcount' => $this->tcount->id))) {
            $result = false;
        }
        if (!$DB->delete_records('tcount_twitter_tokens', array('tcount' => $this->tcount->id))) {
            $result = false;
        }
        return $result;
    }

    public function get_subtype() {
        return 'twitter';
    }
    public function get_icon(){
        return new moodle_url('/mod/tcount/social/twitter/pix/Twitter_icon.png');
    }
    /**
     *
     * @global core_renderer $OUTPUT
     * @global moodle_database $DB
     * @param core_renderer $output
     */
    public function view_header() {
        global $OUTPUT, $DB, $USER;
        if ($this->is_enabled()) {
            list($course, $cm) = get_course_and_cm_from_instance($this->tcount->id, 'tcount');
            $id = $cm->id;
            $token = $DB->get_record('tcount_twitter_tokens', array('tcount' => $this->tcount->id));
            $url_connect = new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', 
                    array('id' => $id, 'action' => 'connect'));
            if ($token) {
                $username = $token->username;
                $errorstatus = $token->errorstatus;
                if ($errorstatus) {
                    echo $OUTPUT->notify_problem(get_string('problemwithtwitteraccount', 'tcount', $errorstatus));
                }
                echo $OUTPUT->box(
                        get_string('module_connected_twitter', 'tcountsocial_twitter', $username) . $OUTPUT->action_link(
                                new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', 
                                        array('id' => $id, 'action' => 'connect')), "Change user") . '/' . $OUTPUT->action_link(
                                new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', 
                                        array('id' => $id, 'action' => 'disconnect')), "Disconnect") . ' ' . $OUTPUT->action_icon(
                                new moodle_url('/mod/tcount/social/twitter/harvest.php', 
                                        ['id' => $id]), 
                                new pix_icon('a/refresh', get_string('harvest_tweets', 'tcountsocial_twitter'))));
            } else {
                echo $OUTPUT->notification(
                        get_string('module_not_connected_twitter', 'tcountsocial_twitter') . $OUTPUT->action_link(
                                new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', 
                                        array('id' => $id, 'action' => 'connect')), "Connect"));
            }
            // Check user's social credentials.
            $twitterusername = $this->get_social_userid($USER);
            if (trim($twitterusername) === "") { // Offer to register.
                $url_profile = new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', 
                        array('id' => $id, 'action' => 'connect', 'type' => 'profile'));
                $twitteradvice = get_string('no_twitter_name_advice2', 'tcountsocial_twitter', 
                        ['field' => $this->get_userid_fieldname(), 'userid' => $USER->id, 'courseid' => $course->id, 
                                        'url' => $url_profile->out(false)]);
                echo $OUTPUT->notification($twitteradvice);
            }
        }
    }

    /**
     * Place social-network user information or a link to connect.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user
     */
    public function view_user_linking($user) {
        global $USER, $COURSE;
        $course = $COURSE;
        $usermessage = '';
        $twitterusername = $this->get_social_userid($user);
        $cm = get_coursemodule_from_instance('tcount', $this->tcount->id);
        if (trim($twitterusername) === "") { // Offer to register.
            if ($USER->id == $user->id) {
                $url_profile = new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', 
                        array('id' => $cm->id, 'action' => 'connect', 'type' => 'profile'));
                $usermessage = get_string('no_twitter_name_advice2', 'tcountsocial_twitter', 
                        ['field' => $this->get_userid_fieldname(), 'userid' => $USER->id, 'courseid' => $course->id, 
                                        'url' => $url_profile->out(false)]);
            } else {
                $usermessage = get_string('no_twitter_name_advice', 'tcount', 
                        ['field' => $this->tcount->twfieldid, 'userid' => $user->id, 'courseid' => $course->id]);
            }
        } else {
            $usermessage = $this->create_user_link($twitterusername, 'twitter');
        }
        return $usermessage;
    }

    /**
     *
     * @param type $username string with the format screenname|userid
     */
    function create_user_link($username) {
        $parts = explode('|', $username);
        $screenname = $parts[0];
        $userid = isset($parts[1]) ? $parts[1] : $screenname;
        $link = "https://www.twitter.com/$userid";
        $icon = "pix/Twitter_icon.png";
        return "<a href=\"$link\"><img src=\"$icon\"/> $screenname</a>";
    }

    /**
     *
     * @return true if the plugin is making searches in the social network
     */
    public function is_tracking() {
        return $this->is_enabled() && $this->get_connection_token() != null && trim($this->get_config('hashtag')) != "";
    }

    public function get_social_userid($user) {
        $fieldid = $this->get_userid_fieldname();
        return tcount_get_user_field_value($user, $fieldid);
    }

    private function get_userid_fieldname() {
        $fieldname = $this->get_config('twfieldid');
        if (!$fieldname) {
            throw new Exception("Fatal error. Contact your administrator. Custom field need to be configured.");
        }
        return $fieldname;
    }

    public function set_social_userid($user, $socialname) {
        $fieldid = $this->get_config('twfieldid');
        tcount_set_user_field_value($user, $fieldid, $socialname);
    }

    /**
     * Statistics for grading
     *
     * @param array[]integer $users array with the userids to be calculated
     * @return array[string]object object->userstats with PKIs for each user object->maximums max
     *         values for normalization.
     */
    public function calculate_stats($users) {
        global $DB;
        $cm = get_coursemodule_from_instance('tcount', $this->tcount->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql(
                'SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs ' .
                         'FROM {tcount_tweets} where tcount = ? and userid is not null group by userid', 
                        array($this->tcount->id));
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
            } else {
                $stat->retweets = 0;
                $stat->tweets = 0;
                $stat->favs = 0;
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

    public function get_pki_list() {
        $pkis = ['tweets', 'retweets', 'favs', 'max_tweets', 'max_retweets', 'max_favs'];
        return $pkis;
    }

    /**
     *
     * @global moodle_database $DB
     * @return type
     */
    public function get_connection_token() {
        global $DB;
        if ($this->tcount) {
            $token = $DB->get_record('tcount_twitter_tokens', ['tcount' => $this->tcount->id]);
        } else {
            $token = null;
        }
        return $token;
    }

    /**
     *
     * @global moodle_database $DB
     * @return type
     */
    public function set_connection_token($token) {
        global $DB;
        $token->tcount=$this->tcount->id;
        
        $record = $DB->get_record('tcount_tweeter_tokens', array("tcount" => $this->tcount->id));
        if ($record) {
            $token->id = $record->id;
            $DB->update_record('tcount_tweeter_tokens', $token);
        } else {
            $DB->insert_record('tcount_tweeter_tokens', $token);
        }
    }

    /**
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        global $DB;
        $result = $this->get_statuses($this->tcount);
        $token = $this->get_connection_token();
        if (isset($result->errors)) {
            if ($token) {
                $info = "UserToken for:$token->username ";
            } else {
                $info = "No twitter token defined!!";
            }
            $errormessage = $result->errors[0]->message;
            $errormessage = "For module tcount: $this->tcount->name (id=$cm->instance) in course (id=$this->tcount->course) " .
                     "searching: $this->tcount->hashtag $info ERROR:" . $errormessage;
            $result->messages[] = $errormessage;
        } else if (isset($result->statuses)) {
            $DB->set_field('tcount_twitter_tokens', 'errorstatus', null, array('id' => $token->id));
            $statuses = count($result->statuses) == 0 ? array() : $result->statuses;
            $tcount = $this->tcount;
            
            $processedstatuses = $this->process_statuses($statuses, $this->tcount);
            $studentstatuses = array_filter($processedstatuses, 
                    function ($status) {
                        return isset($status->userauthor);
                    });
            $this->store_status($studentstatuses);
            $result->messages[] = "For module tcount: $tcount->name (id=$tcount->id) in course (id=$tcount->course) searching: " .
                     $this->get_config('hashtag') . "  Found " . count($statuses) . " tweets. Students' tweets: " .
                     count($studentstatuses);
            $contextcourse = \context_course::instance($this->tcount->course);
            list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
            
            // TODO: implements grading with plugins.
            // tcount_update_grades($this->tcount, $students);
            $errormessage = null;
        } else {
            $errormessage = "ERROR querying twitter results null! Maybe there is no tweeter account linked in this activity.";
            $result->errors[0]->message = $errormessage;
            $result->messages[] = "For module tcount: $this->tcount->name (id=$this->tcount->id) in course (id=$this->tcount->course) searching: $this->tcount->hashtag  " .
                     $errormessage;
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('tcount_twitter_tokens', $token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Uptatind token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
            }
        }
        return $result;
    }

    /**
     * Execute a Twitter API query with auth tokens and the hashtag configured in the module
     *
     * @global type $DB
     * @param type $this->tcount
     * @return mixed object report of activity. $result->statuses $result->messages[]string
     *         $result->errors[]->message
     */
    function get_statuses() {
        if (eduvalab_time_is_between(time(), $this->tcount->startdate, $this->tcount->enddate)) {
            global $DB;
            $tokens = $DB->get_record('tcount_twitter_tokens', array('tcount' => $this->tcount->id));
            return $this->search_tweeter($tokens, strtolower($this->get_config('hashtag'))); // Twitter
                                                                                                 // API
                                                                                                 // depends
                                                                                                 // on
                                                                                                 // letter
                                                                                                 // cases.
        } else {
            return array();
        }
    }

    /**
     *
     * @todo Get a list of interactions between the users
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return array[]mod_tcount\social\social_interaction of interactions. @see
     *         mod_tcount\social\social_interaction
     */
    public function get_interactions($fromdate = null, $todate = null, $users = null) {
    }

    /**
     * Process the statuses looking for students mentions
     * TODO: process entities->user_mentions[]
     *
     * @param type $statuses
     * @return array[] student statuses meeting criteria.
     */
    function process_statuses($statuses) {
        $context = context_course::instance($this->tcount->course);
        list($students, $nonstudent, $active, $userrecords) = eduvalab_get_users_by_type($context);
        $all = array_keys($userrecords); // Include all users (including teachers).
                                         // Get tweeter usernames from users' profile.
        $tweeters = array();
        foreach ($all as $userid) {
            $user = $userrecords[$userid];
            $tweetername = strtolower(str_replace('@', '', $this->get_social_userid($user)));
            if ($tweetername) {
                $tweeters[$tweetername] = $userid;
            }
        }
        // Compile statuses of the users.
        $studentsstatuses = array();
        foreach ($statuses as $status) {
            $tweetername = strtolower($status->user->screen_name);
            // TODO : process entities->user_mentions[] here...
            if (isset($tweeters[$tweetername])) { // Tweet is from a student.
                $userauthor = $userrecords[$tweeters[$tweetername]];
            } else {
                $userauthor = null;
            }
            $createddate = strtotime($status->created_at);
            if (isset($status->retweeted_status)) { // Retweet count comes from original message.
                                                    // Ignore it.
                $status->retweet_count = 0;
            }
            if (eduvalab_time_is_between($createddate, $this->tcount->startdate, $this->tcount->enddate)) {
                $status->userauthor = $userauthor;
                $studentsstatuses[] = $status;
            }
        } // Iterate tweets (statuses)...
        return $studentsstatuses;
    }

    function load_statuses($user = null) {
        global $DB;
        $condition = ['tcount' => $this->tcount->id];
        if ($user) {
            $condition['userid'] = $user->id;
        }
        $statuses = $DB->get_records('tcount_tweets', $condition);
        return $statuses;
    }

    /**
     * TODO : save records in bunches.
     *
     * @global moodle_database $DB
     * @param array[]mixed $status
     * @param mixed $userrecord
     */
    function store_status($statuses) {
        global $DB;
        foreach ($statuses as $status) {
            $userrecord = isset($status->userrecord) ? $status->userrecord : null;
            $tweetid = $status->id_str;
            $statusrecord = $DB->get_record('tcount_tweets', array('tweetid' => $tweetid));
            if (!$statusrecord) {
                $statusrecord = new stdClass();
            } else {
                $DB->delete_records('tcount_tweets', array('tweetid' => $tweetid));
            }
            $statusrecord->tweetid = $tweetid;
            $statusrecord->twitterusername = $status->user->screen_name;
            $statusrecord->tcount = $this->tcount->id;
            $statusrecord->status = json_encode($status);
            $statusrecord->userid = $userrecord != null ? $userrecord->id : null;
            $statusrecord->retweets = $status->retweet_count;
            $statusrecord->favs = $status->favorite_count;
            $statusrecord->hashtag = $this->tcount->hashtag;
            $DB->insert_record('tcount_tweets', $statusrecord);
        }
    }

    /**
     * Connect to twitter API at https://api.twitter.com/1.1/search/tweets.json
     *
     * @global type $CFG
     * @param type $tokens oauth tokens
     * @param type $hashtag hashtag to search for
     * @return stdClass result->statuses o result->errors[]->message (From Twitter API.)
     */
    function search_tweeter($tokens, $hashtag) {
        if (!$tokens) {
            $result = (object) ['statuses' => [], 
                            'errors' => ['message' => "No connection tokens provided!!! Impossible to connect to twitter."]];
            return array();
        }
        global $CFG;
        $settings = array('oauth_access_token' => $tokens->token, 'oauth_access_token_secret' => $tokens->token_secret, 
                        'consumer_key' => $CFG->mod_tcount_twitter_consumer_key,  // ...twitter
                                                                                 // developer app
                                                                                 // key.
                        'consumer_secret' => $CFG->mod_tcount_consumer_secret // ...twitter
                                                                                  // developer app
                                                                                  // secret.
        );
        // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
        // Perform the request and return the parsed response.
        $url = 'https://api.twitter.com/1.1/search/tweets.json';
        $getfield = "q=$hashtag&count=100";
        $requestmethod = "GET";
        $twitter = new TwitterAPIExchange($settings);
        $json = $twitter->set_getfield($getfield)->build_oauth($url, $requestmethod)->perform_request();
        $result = json_decode($json);
        if ($result == null) {
            throw new ErrorException("Fatal error connecting with Twitter. Response was: $json");
        }
        return $result;
    }
}
