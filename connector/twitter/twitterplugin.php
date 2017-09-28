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
        $messages = [];
        $notifications = [];
        if ($this->is_enabled()) {
            list($course, $cm) = get_course_and_cm_from_instance($this->msocial->id, 'msocial');
            $id = $cm->id;
            $icon = $this->get_icon();
            $icondecoration = \html_writer::img($icon->out(), $this->get_name() . ' icon.', ['height' => 16]) . ' ';
            $contextmodule = \context_module::instance($cm->id);
            if (has_capability('mod/msocial:manage', $contextmodule)) {
                $token = $DB->get_record('msocial_twitter_tokens', array('msocial' => $this->msocial->id));
                $urlconnect = new \moodle_url('/mod/msocial/connector/twitter/connectorSSO.php',
                        array('id' => $id, 'action' => 'connect'));
                if ($token) {
                    $username = $token->username;
                    $errorstatus = $token->errorstatus;
                    if ($errorstatus) {
                        $this->notify(get_string('problemwithtwitteraccount', 'msocial', $errorstatus), self::NOTIFY_WARNING);
                    }

                  $messages[] = get_string('module_connected_twitter', 'msocialconnector_twitter', $username) . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/twitter/connectorSSO.php',
                                    array('id' => $id, 'action' => 'connect')), "Change user") . '/' . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/twitter/connectorSSO.php',
                                    array('id' => $id, 'action' => 'disconnect')), "Disconnect") . ' ';
                } else {
                    $notifications[] = get_string('module_not_connected_twitter', 'msocialconnector_twitter') . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/twitter/connectorSSO.php',
                                    array('id' => $id, 'action' => 'connect')), "Connect");
                }
            }
            // Check hashtag search field.
            $hashtag = $this->get_config('hashtag');
            if (trim($hashtag) == "") {
                $notifications[] = get_string('hashtag_missing', 'msocialconnector_twitter', ['cmid' => $cm->id]);
            } else {
                $messages[] = get_string('hashtag_reminder', 'msocialconnector_twitter', ['hashtag' => $hashtag, 'hashtagscaped' => urlencode($hashtag)]);
            }
            // Check user's social credentials.
            $twitterusername = $this->get_social_userid($USER);
            if ($twitterusername === null) { // Offer to register.
                $notifications[] = $this->render_user_linking($USER, false, true);
            }
        }
        return [$messages, $notifications];
    }
    public function render_harvest_link() {
        global $OUTPUT;
        $harvestbutton = '';
        $id = $this->cm->id;
        $context = \context_module::instance($id);
        if (has_capability('mod/msocial:manage', $context) && $this->is_tracking()) {
            $harvestbutton = $OUTPUT->action_icon(
                    new \moodle_url('/mod/msocial/harvest.php', ['id' => $id, 'subtype' => $this->get_subtype()]),
                    new \pix_icon('a/refresh', get_string('harvest_tweets', 'msocialconnector_twitter')));
        } else {
            $harvestbutton = '';
        }
        return $harvestbutton;
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
            $pkis[$userid] = $this->pki_from_stat($userid, $stat, $stataggregated, $this, $pki);
        }
        return $pkis;
    }
    /**
     * @param unknown $user
     * @param unknown $stat
     * @param msocial_plugin $msocialplugin
     * @param pki $pki existent pki. For chaining calls. Assumes user and msocialid are coherent.
     * @return \mod_msocial\connector\pki_info[] */
    private function pki_from_stat($user, $stat, $stataggregated, $msocialplugin, $pki = null) {
        $pki = $pki == null ? new pki($user, $msocialplugin->msocial->id) : $pki;
        foreach ($stat as $propname => $value) {
            $pki->{$propname} = $value;
        }
        foreach ($stataggregated as $propname => $value) {
            $pki->{$propname} = $value;
        }

        return $pki;
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
        $pkiobjs['tweets'] = new pki_info('tweets', null, pki_info::PKI_INDIVIDUAL,  pki_info::PKI_CALCULATED, social_interaction::POST, 'tweet',
                social_interaction::DIRECTION_AUTHOR);
        $pkiobjs['retweets'] = new pki_info('retweets', null, pki_info::PKI_INDIVIDUAL,  pki_info::PKI_CALCULATED, pki_info::PKI_CUSTOM);
        $pkiobjs['favs'] = new pki_info('favs', null, pki_info::PKI_INDIVIDUAL,  pki_info::PKI_CALCULATED, pki_info::PKI_CUSTOM);
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
    public function harvest_hashtags() {
        global $DB;
        $token = $this->get_connection_token();
        $hashtag = $this->get_config('hashtag');
        $result = $this->get_statuses($token, $hashtag);
        if (isset($result->errors)) {
            if ($token) {
                $info = "UserToken for:$token->username ";
            } else {
                $info = "No twitter token defined!!";
            }
            $errormessage = $result->errors[0]->message;
            $errormessage = "For module msocial\connector\twitter: $this->msocial->name (id=$cm->instance) " .
                            " in course (id=$this->msocial->course) searching: $hashtag $info ERROR:" . $errormessage;
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
            $this->store_status($processedstatuses);

            $this->lastinteractions = $this->build_interactions($processedstatuses);
            $errormessage = null;
            $result = $this->post_harvest($result);
        } else {
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[0]->message = $errormessage;
            $result->messages[] = "For module msocial\\connector\\twitter: $this->msocial->name (id=$this->msocial->id) " .
                                  "in course (id=$this->msocial->course) searching: $this->msocial->hashtag  " . $errormessage;
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('msocial_twitter_tokens', $token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
            }
        }
        return $result;
    }
    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;
        $token = $this->get_connection_token();
        $hashtag = $this->get_config('hashtag');
        // Mapped users.
        $mappedusers = $DB->get_records('msocial_mapusers', ['msocial' => $this->msocial->id, 'type' => $this->get_subtype()]);
        $result = $this->get_users_statuses($token, $mappedusers, $hashtag);
        $errormessage = null;

        if (isset($result->errors)) {
            // TODO: generate best error message.
            if ($token) {
                $info = "UserToken for:$token->username ";
            } else {
                $info = "No twitter token defined!!";
            }
            $errormessage = implode('. ', $result->errors);
            $msocial = $this->msocial;
            $errormessage = "For module msocial\connector\twitter: $msocial->name (id=$cm->instance) " .
            " in course (id=$msocial->course) searching: $hashtag $info ERROR:" . $errormessage;
            $result->messages[] = $errormessage;
        }
        if (isset($result->statuses)) {
            $statuses = count($result->statuses) == 0 ? array() : $result->statuses;
            $msocial = $this->msocial;

            $processedstatuses = $this->process_statuses($statuses, $this->msocial);
            $studentstatuses = array_filter($processedstatuses,
                    function ($status) {
                        return isset($status->userauthor);
                    });
            $this->store_status($processedstatuses);

            $this->lastinteractions = $this->build_interactions($processedstatuses);
            $errormessage = null;
            $result = $this->post_harvest($result);
        } else {
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[0]->message = $errormessage;
            $result->messages[] = "For module msocial\\connector\\twitter: $this->msocial->name (id=$this->msocial->id) " .
            "in course (id=$this->msocial->course) searching: $this->msocial->hashtag  " . $errormessage;
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('msocial_twitter_tokens', $token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
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
    protected function get_statuses($tokens, $hashtag) {
            return $this->search_twitter($tokens, $hashtag); // Twitter API depends on letter cases.
    }
    /**
     *
     * @param unknown $tokens
     * @param \stdClass[] $users records from mdl_msocial_mapusers
     * @param unknown $hashtag
     * @throws \ErrorException
     * @return array|mixed
     */
    protected function get_users_statuses($tokens, $users, $hashtag) {

        if (!$tokens) {
            $result = (object) ['statuses' => [],
                            'errors' => ['message' => "No connection tokens provided!!! Impossible to connect to twitter."]];
            return array();
        }
        $hashtaglist = explode(' AND ', $hashtag);
        $hashtaglist = array_map(function ($hashtag) {
                            $h = substr(trim($hashtag), 1);
                            return $h;
        }, $hashtaglist);

        global $CFG;
        $settings = array('oauth_access_token' => $tokens->token, 'oauth_access_token_secret' => $tokens->token_secret,
                        'consumer_key' => get_config('msocialconnector_twitter', 'consumer_key'),
                        'consumer_secret' => get_config('msocialconnector_twitter', 'consumer_secret')
        );
        $totalresults = new \stdClass();
        foreach ($users as $socialuser) {
            // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
            // Perform the request and return the parsed response.
            $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
            $getfield = "screen_name=$socialuser->socialname&count=50";
            $requestmethod = "GET";
            $twitter = new TwitterAPIExchange($settings);
            $json = $twitter->set_getfield($getfield)->build_oauth($url, $requestmethod)->perform_request();
            $result = json_decode($json);
            if ($result == null) {
                $totalresults->errors[] = "Error querying last tweets from user $socialuser->socialname. Response was $json.";
            } else {

                if (isset($result->errors)) {
                    $result->errors->message .= $socialuser->socialname;
                    $totalresults->errors[] = $result->errors;
                } else {
                // Filter hashtags.
                    $statuses = [];
                    foreach ($result as $status) {
                        if (count($status->entities->hashtags) > 1) {
                            if ($this->check_hashtaglist($status, $hashtaglist)) {
                                $statuses[] = $status;
                            }
                        }
                    }
                    $totalresults->statuses = array_merge(isset($totalresults->statuses) ? $totalresults->statuses : [],
                                                        $statuses);
                }

            }
        }
        return $totalresults;
    }
    /**
     * Check filter condition. Only a list of AND tags
     * TODO: implement more conditions.
     * @param unknown $status
     * @param unknown $hashtaglist
     */
    protected function check_hashtaglist($status, $hashtaglist) {
        foreach ($hashtaglist as $hashtag) {
            if (!$this->tweet_has_hashtag($status, $hashtag)) {
                return false;
            }
        }
        return true;
    }
    protected function tweet_has_hashtag($status, $searchhashtag) {
        foreach ($status->entities->hashtags as $hashtag) {
            if ($hashtag->text == $searchhashtag) {
                return true;
            }
        }
        return false;
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
            $interaction->uid = $status->id_str;
            $interaction->rawdata = json_encode($status);
            $interaction->icon = $icon;
            $interaction->source = $this->get_subtype();
            $interaction->nativetype = 'tweet';
            $interaction->nativefromname = $status->user->screen_name;
            $interaction->timestamp = new \DateTime($status->created_at);
            $interaction->description = $status->text;
            if ($status->in_reply_to_user_id == null) {
                $interaction->type = social_interaction::POST;
            } else {
                $interaction->type = social_interaction::REPLY;
                $interaction->nativeto = $status->in_reply_to_user_id;
                $interaction->nativetoname = $status->in_reply_to_screen_name;
            }
            $interaction->nativefrom = $status->user->id_str;
            $interaction->fromid = $this->get_userid($interaction->nativefrom);
            $interaction->toid = $this->get_userid($interaction->nativeto);
            $interactions[$interaction->uid] = $interaction;
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
                $interactions[$interaction->uid] = $mentioninteraction;
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
        list($students, $nonstudent, $active, $userrecords) = array_values(msocial_get_users_by_type($context));

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
            $twittername = $status->user->screen_name;
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
            $userrecord = isset($status->userauthor) ? $status->userauthor : null;
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
