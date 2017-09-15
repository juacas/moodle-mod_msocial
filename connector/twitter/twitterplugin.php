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
namespace mod_msocial\connector;

use msocial\msocial_plugin;
use mod_msocial\pki_info;
use mod_msocial\pki;
use mod_msocial\social_user;

defined('MOODLE_INTERNAL') || die();
require_once('TwitterAPIExchange.php');

/** library class for social network twitter plugin extending social plugin base class
 *
 * @package msocialconnector_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_connector_twitter extends msocial_connector_plugin {
    const CONFIG_HASHTAG = 'hashtag';

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialconnector_twitter');
    }

    /** Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues[$this->get_form_field_name(self::CONFIG_HASHTAG)] = $this->get_config(self::CONFIG_HASHTAG);
        parent::data_preprocessing($defaultvalues);
    }

    /** Get the settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void */
    public function get_settings(\MoodleQuickForm $mform) {
        $mform->addElement('text', $this->get_form_field_name(self::CONFIG_HASHTAG),
                get_string("hashtag", "msocialconnector_twitter"), array('size' => '20'));
        $mform->setType($this->get_form_field_name(self::CONFIG_HASHTAG), PARAM_TEXT);
        $mform->addHelpButton($this->get_form_field_name(self::CONFIG_HASHTAG), 'hashtag', 'msocialconnector_twitter');
    }

    /** Save the settings for twitter plugin
     *
     * @param \stdClass $data
     * @return bool */
    public function save_settings(\stdClass $data) {
        if (isset($data->{$this->get_form_field_name(self::CONFIG_HASHTAG)})) {
            $this->set_config(self::CONFIG_HASHTAG, $data->{$this->get_form_field_name(self::CONFIG_HASHTAG)});
        }

        return true;
    }

    /** Add form elements for settings
     *
     * @param mixed $this->msocial can be null
     * @param MoodleQuickForm $mform
     * @param \stdClass $data
     * @return true if elements were added to the form */
    public function get_form_elements(MoodleQuickForm $mform, \stdClass $data) {
        $elements = array();
        $this->msocial = $this->msocial ? $this->msocial->id : 0;

        return true;
    }

    /** The msocial has been deleted - cleanup subplugin
     *
     * @return bool */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (!$DB->delete_records('msocial_tweets', array('msocial' => $this->msocial->id))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_twitter_tokens', array('msocial' => $this->msocial->id))) {
            $result = false;
        }
        return $result;
    }

    public function get_subtype() {
        return 'twitter';
    }

    public function get_category() {
        return msocial_plugin::CAT_ANALYSIS;
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/connector/twitter/pix/Twitter_icon.png');
    }

    /**
     * @global core_renderer $OUTPUT
     * @global moodle_database $DB
     * @param core_renderer $output */
    public function render_header() {
        global $OUTPUT, $DB, $USER;
        if ($this->is_enabled()) {
            list($course, $cm) = get_course_and_cm_from_instance($this->msocial->id, 'msocial');
            $id = $cm->id;
            $icon = $this->get_icon();
            $messages = [];
            $notifications = [];
            $icondecoration = \html_writer::img($icon->out(), $this->get_name() . ' icon.', ['height' => 16]) . ' ';
            $contextmodule = \context_module::instance($cm->id);
            if (has_capability('mod/msocial:manage', $contextmodule)) {
                $token = $DB->get_record('msocial_twitter_tokens', array('msocial' => $this->msocial->id));
                $urlconnect = new \moodle_url('/mod/msocial/connector/twitter/twitterSSO.php',
                        array('id' => $id, 'action' => 'connect'));
                if ($token) {
                    $username = $token->username;
                    $errorstatus = $token->errorstatus;
                    if ($errorstatus) {
                        $this->notify(get_string('problemwithtwitteraccount', 'msocial', $errorstatus), self::NOTIFY_WARNING);
                    }
                    if ($this->is_tracking()) {
                        $harvestbutton = $OUTPUT->action_icon(
                                new \moodle_url('/mod/msocial/harvest.php', ['id' => $id, 'subtype' => $this->get_subtype()]),
                                new \pix_icon('a/refresh', get_string('harvest_tweets', 'msocialconnector_twitter')));
                    } else {
                        $harvestbutton = '';
                    }
                    $messages[] = get_string('module_connected_twitter', 'msocialconnector_twitter', $username) . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/twitter/twitterSSO.php',
                                    array('id' => $id, 'action' => 'connect')), "Change user") . '/' . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/twitter/twitterSSO.php',
                                    array('id' => $id, 'action' => 'disconnect')), "Disconnect") . ' ' . $harvestbutton;
                } else {
                    $notifications[] = get_string('module_not_connected_twitter', 'msocialconnector_twitter') . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/twitter/twitterSSO.php',
                                    array('id' => $id, 'action' => 'connect')), "Connect");
                }
            }
            // Check hashtag search field.
            $hashtag = $this->get_config('hashtag');
            if (trim($hashtag) == "") {
                $notifications[] = get_string('hashtag_missing', 'msocialconnector_twitter', ['cmid' => $cm->id]);
            } else {
                $messages[] = get_string('hashtag_reminder', 'msocialconnector_twitter', $hashtag);
            }
            // Check user's social credentials.
            $twitterusername = $this->get_social_userid($USER);
            if ($twitterusername === null) { // Offer to register.

                $notifications[] = $this->render_user_linking($USER);
            }
            $this->notify($notifications, self::NOTIFY_WARNING);
            $this->notify($messages, self::NOTIFY_NORMAL);
        }
    }

    /** Place social-network user information or a link to connect.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user */
    public function render_user_linking($user) {
        global $USER, $COURSE;
        $course = $COURSE;
        $usermessage = '';
        $twitterusername = $this->get_social_userid($user);
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id);
        if ($twitterusername === null) { // Offer to register.
            $userfullname = fullname($user);
            if ($USER->id == $user->id) {
                $urlprofile = new \moodle_url('/mod/msocial/connector/twitter/twitterSSO.php',
                        array('id' => $cm->id, 'action' => 'connect', 'type' => 'profile'));
                $pixurl = new \moodle_url('/mod/msocial/connector/twitter/pix');

                $usermessage = get_string('no_twitter_name_advice2', 'msocialconnector_twitter',
                        ['userfullname' => $userfullname, 'userid' => $USER->id, 'courseid' => $course->id,
                                        'url' => $urlprofile->out(false), 'pixurl' => $pixurl->out()]);
            } else {
                $usermessage = get_string('no_twitter_name_advice', 'msocialconnector_twitter',
                        ['userfullname' => $userfullname, 'userid' => $user->id, 'courseid' => $course->id]);
            }
        } else {

            $usermessage = $this->create_user_link($user);
            $contextmodule = \context_module::instance($this->cm->id);
            if ($USER->id == $user->id || has_capability('mod/msocial:manage', $contextmodule)) {
                $icon = new \pix_icon('t/delete', 'delete');
                $urlprofile = new \moodle_url('/mod/msocial/connector/twitter/twitterSSO.php',
                        array('id' => $this->cm->id, 'action' => 'disconnect', 'type' => 'profile', 'userid' => $user->id,
                                        'socialid' => $twitterusername->socialid));
                global $OUTPUT;
                $link = \html_writer::link($urlprofile, $OUTPUT->render($icon));

                $usermessage .= $link;
            }
        }
        return $usermessage;
    }

    public function get_social_user_url(social_user $userid) {
        return "https://twitter.com/$userid->socialname";
    }

    public function get_interaction_url(social_interaction $interaction) {
        $userid = $interaction->nativefrom;
        $uid = $interaction->uid;
        $baseuid = explode('-', $uid)[0]; // Mentions have a format id-userid...
        $url = "https://twitter.com/$userid/status/$baseuid";
        return $url;
    }

    /**
     * @return true if the plugin is making searches in the social network */
    public function is_tracking() {
        return $this->is_enabled() && $this->get_connection_token() != null && trim($this->get_config('hashtag')) != "";
    }

    /**
     * @param array(\stdClass) $user records indexed by userid.
     * @return array[pki] */
    public function calculate_pkis($users, $pkis = []) {
        $stats = $this->calculate_stats(array_keys($users));
        $stataggregated = $stats->maximums;
        // Convert stats to PKI.
        foreach ($stats->users as $userid => $stat) {
            $pki = isset($pkis[$userid]) ? $pkis[$userid] : null;
            $pkis[$userid] = pki::from_stat($userid, $stat, $stataggregated, $this, $pki);
        }
        return $pkis;
    }

    /** Statistics for grading
     * @deprecated
     *
     * @param array[]integer $users array with the userids to be calculated, null not filter by
     *        users.
     * @return array[string]object object->userstats with PKIs for each user object->maximums max
     *         values for normalization. */
    private function calculate_stats($users) {
        global $DB;
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql(
                'SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs ' .
                         'FROM {msocial_tweets} where msocial = ? and userid is not null group by userid', array($this->msocial->id));
        $userstats = new \stdClass();
        $userstats->users = array();

        $favs = array();
        $retweets = array();
        $tweets = array();
        if ($users == null) {
            $users = array_keys($stats);
        }
        foreach ($users as $userid) {
            $stat = new \stdClass();

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
        $stat = new \stdClass();
        $stat->max_retweets = 0;
        $stat->max_tweets = count($tweets) == 0 ? 0 : max($tweets);
        $stat->max_favs = count($favs) == 0 ? 0 : max($favs);
        $stat->max_retweets = count($retweets) == 0 ? 0 : max($retweets);
        $userstats->maximums = $stat;

        return $userstats;
    }

    public function get_pki_list() {
        $pkiobjs['tweets'] = new pki_info('tweets', null, pki_info::PKI_INDIVIDUAL, social_interaction::POST, 'tweet',
                social_interaction::DIRECTION_AUTHOR);
        $pkiobjs['retweets'] = new pki_info('retweets', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['favs'] = new pki_info('favs', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['max_tweets'] = new pki_info('max_tweets', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_retweets'] = new pki_info('max_retweets', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_favs'] = new pki_info('max_favs', null, pki_info::PKI_AGREGATED);
        return $pkiobjs;
    }

    /**
     * @global moodle_database $DB
     * @return type */
    public function get_connection_token() {
        global $DB;
        if ($this->msocial) {
            $token = $DB->get_record('msocial_twitter_tokens', ['msocial' => $this->msocial->id]);
        } else {
            $token = null;
        }
        return $token;
    }

    /**
     * @global moodle_database $DB
     * @return type */
    public function set_connection_token($token) {
        global $DB;
        $token->msocial = $this->msocial->id;

        $record = $DB->get_record('msocial_twitter_tokens', array("msocial" => $this->msocial->id));
        if ($record) {
            $token->id = $record->id;
            $DB->update_record('msocial_twitter_tokens', $token);
        } else {
            $DB->insert_record('msocial_twitter_tokens', $token);
        }
    }

    public function unset_connection_token() {
        global $DB;
        $DB->delete_records('msocial_twitter_tokens', array('msocial' => $this->msocial->id));
    }
    /**
     * {@inheritDoc}
     * @see \mod_msocial\connector\msocial_connector_plugin::preferred_harvest_intervals()
     */
    public function preferred_harvest_intervals() {
        return new harvest_intervals (24 * 3600, 5000, 7 * 24 * 3600, 0);
    }
    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;
        $result = $this->get_statuses($this->msocial);
        $token = $this->get_connection_token();
        $hashtag = $this->get_config('hashtag');

        if (isset($result->errors)) {
            if ($token) {
                $info = "UserToken for:$token->username ";
            } else {
                $info = "No twitter token defined!!";
            }
            $errormessage = $result->errors[0]->message;
            $errormessage = "For module msocial\connector\twitter: $this->msocial->name (id=$cm->instance) in course (id=$this->msocial->course) " .
                     "searching: $hashtag $info ERROR:" . $errormessage;
            $result->messages[] = $errormessage;
        } else if (isset($result->statuses)) {
            $DB->set_field('msocial_twitter_tokens', 'errorstatus', null, array('id' => $token->id));
            $statuses = count($result->statuses) == 0 ? array() : $result->statuses;
            $msocial = $this->msocial;

            $processedstatuses = $this->process_statuses($statuses, $this->msocial);
            $studentstatuses = array_filter($processedstatuses,
                    function ($status) {
                        return isset($status->userauthor);
                    });
            $this->store_status($studentstatuses);

            $interactions = $this->build_interactions($processedstatuses);
            social_interaction::store_interactions($interactions, $this->msocial->id);

            $result->messages[] = "For module msocial\\connector\\twitter: $msocial->name (id=$msocial->id) in course (id=$msocial->course) searching: " .
                     $hashtag . "  Found " . count($statuses) . " tweets. Students' tweets: " . count($studentstatuses);
            $contextcourse = \context_course::instance($this->msocial->course);
            list($students, $nonstudents, $active, $users) = msocial_get_users_by_type($contextcourse);

            // TODO: implements grading with plugins.
            // msocial_update_grades($this->msocial, $students);
            $errormessage = null;

            $pkis = $this->calculate_pkis($users);
            $this->store_pkis($pkis, true);
            $this->set_config(msocial_connector_plugin::LAST_HARVEST_TIME, time());
        } else {
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[0]->message = $errormessage;
            $result->messages[] = "For module msocial\\connector\\twitter: $this->msocial->name (id=$this->msocial->id) in course (id=$this->msocial->course) searching: $this->msocial->hashtag  " .
                     $errormessage;
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('msocial_twitter_tokens', $token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Uptatind token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
            }
        }
        return $result;
    }

    /** Execute a Twitter API query with auth tokens and the hashtag configured in the module
     *
     * @global type $DB
     * @param type $this->msocial
     * @return mixed object report of activity. $result->statuses $result->messages[]string
     *         $result->errors[]->message */
    protected function get_statuses() {
        if (msocial_time_is_between(time(), $this->msocial->startdate, $this->msocial->enddate)) {
            global $DB;
            $tokens = $DB->get_record('msocial_twitter_tokens', array('msocial' => $this->msocial->id));
            return $this->search_twitter($tokens, strtolower($this->get_config('hashtag'))); // Twitter
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
     * @todo Get a list of interactions between the users
     * @global moodle_database $DB
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return array[]mod_msocial\connector\social_interaction of interactions. @see
     *         mod_msocial\connector\social_interaction */
    public function get_interactions($fromdate = null, $todate = null, $users = null) {
        global $DB;
        $tweets = $DB->get_records('msocial_tweets', ["msocial" => $this->msocial->id], 'tweetid');
        $interactions = $this->build_interactions($tweets);
        return $interactions;
    }

    protected function build_interactions($statuses) {
        $interactions = [];
        $icon = $this->get_icon();
        foreach ($statuses as $status) {
            $interaction = new social_interaction();
            $interaction->uid = $status->id;
            $interaction->rawdata = json_encode($status);
            $interaction->icon = $icon;
            $interaction->source = $this->get_subtype();
            $interaction->fromid = $this->get_userid($interaction->nativefrom);
            $interaction->nativetype = 'tweet';
            $interaction->nativefrom = $status->user->id;
            $interaction->nativefromname = $status->user->screen_name;
            $interaction->timestamp = new \DateTime($status->created_at);
            $interaction->toid = $this->get_userid($interaction->nativeto);

            $interaction->description = $status->text;
            if ($status->in_reply_to_user_id == null) {
                $interaction->type = social_interaction::POST;
            } else {
                $interaction->type = social_interaction::REPLY;
                $interaction->nativeto = $status->in_reply_to_user_id;
                $interaction->nativetoname = $status->in_reply_to_screen_name;
            }
            $interactions[] = $interaction;
            // Process mentions...
            foreach ($status->entities->user_mentions as $mention) {
                $mentioninteraction = new social_interaction();
                $mentioninteraction->rawdata = json_encode($mention);
                $mentioninteraction->icon = $icon;
                $mentioninteraction->source = $this->get_subtype();
                $mentioninteraction->nativetype = 'mention';
                $mentioninteraction->toid = $this->get_userid($mention->id);
                $mentioninteraction->nativeto = $mention->id;
                $mentioninteraction->nativetoname = $mention->screen_name;
                $mentioninteraction->timestamp = new \DateTime($status->created_at);
                $mentioninteraction->type = social_interaction::MENTION;
                $mentioninteraction->nativefrom = $interaction->nativefrom;
                $mentioninteraction->fromid = $interaction->fromid;
                $mentioninteraction->nativefromname = $interaction->nativefromname;
                $mentioninteraction->description = '@' . $mention->id . " ($mention->name)";
                $mentioninteraction->uid = $interaction->uid . '-' . $mention->id;
                $mentioninteraction->parentinteraction = $interaction->uid;
                $interactions[] = $mentioninteraction;
            }
        }
        return $interactions;
    }

    /** Process the statuses looking for students mentions
     * TODO: process entities->user_mentions[]
     *
     * @param type $statuses
     * @return array[] student statuses meeting criteria. */
    protected function process_statuses($statuses) {
        $context = \context_course::instance($this->msocial->course);
        list($students, $nonstudent, $active, $userrecords) = msocial_get_users_by_type($context);

        $twitters = array();
        foreach ($userrecords as $userid => $user) { // Include all users (including teachers).
            $socialuserid = $this->get_social_userid($user); // Get twitter usernames from users'
                                                             // profile.
            if ($socialuserid !== null) {
                $twittername = $socialuserid->socialname;
                $twitters[$twittername] = $userid;
            }
        }
        // Compile statuses of the users.
        $studentsstatuses = array();
        foreach ($statuses as $status) {
            $twittername = strtolower($status->user->screen_name);
            // TODO : process entities->user_mentions[] here...
            if (isset($twitters[$twittername])) { // Tweet is from a student.
                $userauthor = $userrecords[$twitters[$twittername]];
            } else {
                $userauthor = null;
            }
            $createddate = strtotime($status->created_at);
            if (isset($status->retweeted_status)) { // Retweet count comes from original message.
                                                    // Ignore it.
                $status->retweet_count = 0;
            }
            if (msocial_time_is_between($createddate, $this->msocial->startdate, $this->msocial->enddate)) {
                $status->userauthor = $userauthor;
                $studentsstatuses[] = $status;
            }
        } // Iterate tweets (statuses)...
        return $studentsstatuses;
    }

    protected function load_statuses($user = null) {
        global $DB;
        $condition = ['msocial' => $this->msocial->id];
        if ($user) {
            $condition['userid'] = $user->id;
        }
        $statuses = $DB->get_records('msocial_tweets', $condition);
        return $statuses;
    }

    /** TODO : save records in bunches.
     *
     * @deprecated
     *
     * @global moodle_database $DB
     * @param array[]mixed $status
     * @param mixed $userrecord */
    protected function store_status($statuses) {
        global $DB;
        foreach ($statuses as $status) {
            $userrecord = isset($status->userrecord) ? $status->userrecord : null;
            $tweetid = $status->id_str;
            $statusrecord = $DB->get_record('msocial_tweets', array('tweetid' => $tweetid));
            if (!$statusrecord) {
                $statusrecord = new \stdClass();
            } else {
                $DB->delete_records('msocial_tweets', array('tweetid' => $tweetid));
            }
            $statusrecord->tweetid = $tweetid;
            $statusrecord->twitterusername = $status->user->screen_name;
            $statusrecord->msocial = $this->msocial->id;
            $statusrecord->status = json_encode($status);
            $statusrecord->userid = $userrecord != null ? $userrecord->id : null;
            $statusrecord->retweets = $status->retweet_count;
            $statusrecord->favs = $status->favorite_count;
            $statusrecord->hashtag = $this->get_config(self::CONFIG_HASHTAG);
            $DB->insert_record('msocial_tweets', $statusrecord);
        }
    }

    /** Connect to twitter API at https://api.twitter.com/1.1/search/tweets.json
     *
     * @global type $CFG
     * @param type $tokens oauth tokens
     * @param type $hashtag hashtag to search for
     * @return \stdClass result->statuses o result->errors[]->message (From Twitter API.) */
    protected function search_twitter($tokens, $hashtag) {
        if (!$tokens) {
            $result = (object) ['statuses' => [],
                            'errors' => ['message' => "No connection tokens provided!!! Impossible to connect to twitter."]];
            return array();
        }
        global $CFG;
        $settings = array('oauth_access_token' => $tokens->token, 'oauth_access_token_secret' => $tokens->token_secret,
                        'consumer_key' => get_config('msocialconnector_twitter', 'consumer_key'),  // ...twitter
                                                                                                  // developer
                                                                                                  // app
                                                                                                  // key.
                        'consumer_secret' => get_config('msocialconnector_twitter', 'consumer_secret') // ...twitter
                                                                                                           // developer
                                                                                                           // app
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
            throw new \ErrorException("Fatal error connecting with Twitter. Response was: $json");
        }
        return $result;
    }
}
