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

use mod_msocial\kpi;
use mod_msocial\kpi_info;
use mod_msocial\social_user;
use msocial\msocial_plugin;
use mod_msocial\users_struct;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once('TwitterAPIExchange.php');
require_once($CFG->dirroot . '/mod/msocial/classes/tagparser.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialinteraction.php');
/**
 * Library class for social network twitter plugin extending social plugin base class
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
                        $notifications[] = get_string('problemwithtwitteraccount', 'msocialconnector_twitter', $errorstatus);
                    }

                    $messages[] = get_string('module_connected_twitter', 'msocialconnector_twitter', $username) .
                            $OUTPUT->action_link(
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
            $socialuserids = $this->get_social_userid($USER);
            if (!$socialuserids) { // Offer to register.
                $notifications[] = $this->render_user_linking($USER, false, true);
            }
        }
        return [$messages, $notifications];
    }
    public function render_harvest_link() {
        global $OUTPUT;
        $id = $this->cm->id;
        $harvestbutton = $OUTPUT->action_icon(
                new \moodle_url('/mod/msocial/harvest.php', ['id' => $id, 'subtype' => $this->get_subtype()]),
                new \pix_icon('a/refresh', get_string('harvest_tweets', 'msocialconnector_twitter')));
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
    public function can_harvest() {
        return  $this->get_connection_token() != null &&
                trim($this->get_config('hashtag')) != "";
    }

    /**
     * @param users_struct $user struct of arrays @see msocial_get_users_by_type().
     * @return array[kpi] */
    public function calculate_kpis($users, $kpis = []) {
        $kpis = parent::calculate_kpis($users, $kpis);
        $stats = $this->calculate_stats($users->studentids);
        $stataggregated = $stats->maximums;
        // Convert stats to KPI.
        foreach ($stats->users as $userid => $stat) {
            $kpi = isset($kpis[$userid]) ? $kpis[$userid] : null;
            $kpis[$userid] = $this->kpi_from_stat($userid, $stat, $stataggregated, $this, $kpi);
        }
        return $kpis;
    }
    /**
     * @param unknown $user
     * @param unknown $stat
     * @param msocial_plugin $msocialplugin
     * @param kpi $kpi existent kpi. For chaining calls. Assumes user and msocialid are coherent.
     * @return \mod_msocial\connector\kpi_info[] */
    private function kpi_from_stat($user, $stat, $stataggregated, $msocialplugin, $kpi = null) {
        $kpi = $kpi == null ? new kpi($user, $msocialplugin->msocial->id) : $kpi;
        foreach ($stat as $propname => $value) {
            $kpi->{$propname} = $value;
        }
        foreach ($stataggregated as $propname => $value) {
            $kpi->{$propname} = $value;
        }

        return $kpi;
    }

    /** Statistics for grading
     * @deprecated
     *
     * @param array[]integer $users array with the userids to be calculated, null not filter by
     *        users.
     * @return array[string]object object->userstats with KPIs for each user object->maximums max
     *         values for normalization. */
    private function calculate_stats($users) {
        global $DB;
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql(
                'SELECT userid as id, sum(retweets) as retweets, sum(favs) as favs ' .
                         'FROM {msocial_tweets} where msocial = ? and userid is not null group by userid', array($this->msocial->id));
        $userstats = new \stdClass();
        $userstats->users = array();

        $favs = array();
        $retweets = array();
        if ($users == null) {
            $users = array_keys($stats);
        }
        foreach ($users as $userid) {
            $stat = new \stdClass();

            if (isset($stats[$userid])) {
                $retweets[] = $stat->retweets = $stats[$userid]->retweets;
                $favs[] = $stat->favs = $stats[$userid]->favs;
            } else {
                $stat->retweets = 0;
                $stat->favs = 0;
            }
            $userstats->users[$userid] = $stat;
        }
        $stat = new \stdClass();
        $stat->max_favs = count($favs) == 0 ? 0 : max($favs);
        $stat->max_retweets = count($retweets) == 0 ? 0 : max($retweets);
        $userstats->maximums = $stat;

        return $userstats;
    }

    public function get_kpi_list() {
        $kpiobjs['tweets'] = new kpi_info('tweets',  get_string('kpi_description_tweets', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, social_interaction::POST, 'tweet',
                social_interaction::DIRECTION_AUTHOR);
        $kpiobjs['retweets'] = new kpi_info('retweets',  get_string('kpi_description_retweets', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, kpi_info::KPI_CUSTOM);
        $kpiobjs['favs'] = new kpi_info('favs',  get_string('kpi_description_favs', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, kpi_info::KPI_CUSTOM);
        $kpiobjs['twmentions'] = new kpi_info('twmentions',  get_string('kpi_description_twmentions', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED,
                social_interaction::MENTION, '*', social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['max_tweets'] = new kpi_info('max_tweets', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_retweets'] = new kpi_info('max_retweets', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_favs'] = new kpi_info('max_favs', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_twmentions'] = new kpi_info('max_twmentions', null, kpi_info::KPI_AGREGATED);
        return $kpiobjs;
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
        if (empty($token->errorstatus)) {
            $token->errorstatus = null;
        }
        $record = $DB->get_record('msocial_twitter_tokens', array("msocial" => $this->msocial->id));
        if ($record) {
            $token->id = $record->id;
            $DB->update_record('msocial_twitter_tokens', $token);
        } else {
            $DB->insert_record('msocial_twitter_tokens', $token);
        }
    }
    /**
     *
     * {@inheritDoc}
     * @see \msocial\msocial_plugin::reset_userdata()
     */
    public function reset_userdata($data) {
        // Twitter token if for the teacher. Preserve it.
        
        // Remove mapusers.
        global $DB;
        $msocial = $this->msocial;
        $DB->delete_records('msocial_mapusers',['msocial' => $msocial->id, 'type' => $this->get_subtype()]);
        // Clear tweets log.
        $DB->delete_records('msocial_tweets', ['msocial' => $msocial->id]);
        return array('component'=>$this->get_name(), 'item'=>get_string('unlinksocialaccount', 'msocial'), 'error'=>false);
    }
    /**
     * 
     * {@inheritDoc}
     * @see \mod_msocial\connector\msocial_connector_plugin::unset_connection_token()
     */
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
    protected function harvest_hashtags() {
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
            $msocial = $this->msocial;
            $cm = $this->cm;
            $result->messages[] = "Searching: $hashtag. For module msocial\connector\twitter by hashtag: $msocial->name (id=$cm->instance) " .
                            " in course (id=$msocial->course) $info ERROR:" . $errormessage;
            $result->error[] = (object) ['message' => $errormessage];
            $result->statuses = [];
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

            $this->lastinteractions = $this->merge_interactions($this->lastinteractions, $this->build_interactions($processedstatuses));
            $errormessage = null;
            $result->errors = [];
            $result->messages[] = "Searching by hashtag: $hashtag. For module msocial\\connector\\twitter by hashtags: $msocial->name (id=$msocial->id) " .
            "in course (id=$msocial->course) ";
            $result->statuses = [];
        } else {
            $msocial = $this->msocial;
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[] = (object) ['message' => $errormessage];
            $result->messages[] = "Searching: $hashtag. For module msocial\\connector\\twitter by hashtags: $msocial->name (id=$msocial->id) " .
                                  "in course (id=$msocial->course) " . $errormessage;
            $result->statuses = [];
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('msocial_twitter_tokens', $token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
                $result->statuses = [];
            }
        }
        return $result;
    }
    /**
     * Merge arrays preserving keys. (PHP may convert string to int and renumber the items).
     */
    protected function merge_interactions(array $arr1, array $arr2) {
        $merged = [];
        foreach ($arr1 as $key => $inter) {
            $merged[$key] = $inter;
        }
        foreach ($arr2 as $key => $inter) {
            $merged[$key] = $inter;
        }
        return $merged;
    }
    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        $resultusers = $this->harvest_users();
        $resulttags = $this->harvest_hashtags();
        $likeinteractions = $this->refresh_likes();
        $this->lastinteractions = $this->merge_interactions($this->lastinteractions, $likeinteractions);

        $result = new \stdClass();
        $result->statuses = array_merge($resultusers->statuses, $resulttags->statuses);
        $result->errors = array_merge($resultusers->errors, $resulttags->errors);
        $result->messages = array_merge($resultusers->messages, $resulttags->messages);
        return $this->post_harvest($result);
    }
    protected function harvest_users() {
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
            $cm = $this->cm;
            $errormessage = "ERROR:" . $errormessage;
            $result->messages[] = "Searching by users. For module msocial\connector\twitter: $msocial->name (id=$cm->instance) " .
            " in course (id=$msocial->course) searching: $hashtag $info";
            $result->errors[0] = (object) ['message' => $errormessage];
        } else {
            $result->messages = [];
            $result->errors = [];
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
            $this->lastinteractions = $this->merge_interactions($this->lastinteractions, $this->build_interactions($processedstatuses));
            $errormessage = null;
            $result->messages[] = "Searching by users. For module msocial\\connector\\twitter by users: $msocial->name (id=$msocial->id) " .
                                    "in course (id=$msocial->course) searching: $hashtag  ";
        } else {
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[0]->message = $errormessage;
            $msocial = $this->msocial;
            $result->messages[] = "Searching by users. For module msocial\\connector\\twitter by users: $msocial->name (id=$msocial->id) " .
            "in course (id=$msocial->course) searching: $hashtag  ";
            $result->statuses = [];
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
     * @return mixed
     */
    protected function get_users_statuses($tokens, $users, $hashtag) {

        $totalresults = new \stdClass();
        if (!$tokens) {
            $result = (object) ['statuses' => [],
                            'errors' => ['message' => "No connection tokens provided!!! Impossible to connect to twitter."]];
            return $result;
        }
        if (count($users) == 0) {
            $totalresults->statuses = [];
            return $totalresults;
        }
        $tagparser = new \tag_parser($hashtag);
        global $CFG;
        $settings = array('oauth_access_token' => $tokens->token, 'oauth_access_token_secret' => $tokens->token_secret,
                        'consumer_key' => get_config('msocialconnector_twitter', 'consumer_key'),
                        'consumer_secret' => get_config('msocialconnector_twitter', 'consumer_secret')
        );
        foreach ($users as $socialuser) {
            // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
            // Perform the request and return the parsed response.
            $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
            $getfield = "screen_name=$socialuser->socialname&count=50";
            $requestmethod = "GET";
            $twitter = new TwitterAPIExchange($settings);
            $json = $twitter->set_getfield($getfield)->build_oauth($url, $requestmethod)->perform_request();
            $result = json_decode($json);
            if ($result == null || isset($result->error)) {

                $msg = "Error querying last tweets from user $socialuser->socialname. Response was " .
                        ($result == null ? $json : $result->error);
                $totalresults->errors[] = $msg;
                // TODO: Notify user to renew tokens.
                $this->notify_user_token($socialuser, $msg);
            } else {
                // Filter hashtags.
                $statuses = [];
                foreach ($result as $status) {
                    if ($status instanceof \stdClass) {
                        $text = $status->text;
                        if ($tagparser->check_hashtaglist($text)) {
                            $statuses[] = $status;
                        }
                    }
                }
                $totalresults->statuses = array_merge(isset($totalresults->statuses) ? $totalresults->statuses : [],
                                                    $statuses);
            }
        }
        return $totalresults;
    }

    /**
     * @todo Get a list of interactions between the users
     * @global moodle_database $DB
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return mod_msocial\connector\social_interaction[] of interactions. @see
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
            if ($status->in_reply_to_status_id_str == "") {
                $interaction->type = social_interaction::POST;
            } else {
                $interaction->type = social_interaction::REPLY;
                $interaction->nativeto = $status->in_reply_to_user_id_str;
                $interaction->nativetoname = $status->in_reply_to_screen_name;
                $interaction->parentinteraction = $status->in_reply_to_status_id_str;
            }
            $interaction->nativefrom = $status->user->id_str;
            $interaction->fromid = $this->get_userid($interaction->nativefrom);
            $interaction->toid = $this->get_userid($interaction->nativeto);
            $interactions[$interaction->uid] = $interaction;
            // Process mentions...
            foreach ($status->entities->user_mentions as $mentionstatus) {
                $mentioninteraction = new social_interaction();
                $mentioninteraction->rawdata = json_encode($mentionstatus);
                $mentioninteraction->icon = $icon;
                $mentioninteraction->source = $this->get_subtype();
                $mentioninteraction->nativetype = 'mention';
                $mentioninteraction->toid = $this->get_userid($mentionstatus->id_str);
                $mentioninteraction->nativeto = $mentionstatus->id_str;
                $mentioninteraction->nativetoname = $mentionstatus->screen_name;
                $mentioninteraction->timestamp = new \DateTime($status->created_at);
                $mentioninteraction->type = social_interaction::MENTION;
                $mentioninteraction->nativefrom = $interaction->nativefrom;
                $mentioninteraction->fromid = $interaction->fromid;
                $mentioninteraction->nativefromname = $interaction->nativefromname;
                $mentioninteraction->description = '@' . $mentionstatus->id_str . " ($mentionstatus->name)";
                $mentioninteraction->uid = $interaction->uid . '-' . $mentionstatus->id_str;
                $mentioninteraction->parentinteraction = $interaction->uid;
                $interactions[$mentioninteraction->uid] = $mentioninteraction;
            }

        }
        return $interactions;
    }
    /**
     * Search for new Likes.
     */
    protected function refresh_likes() {
        $likeinteractions = [];
        $filter = new \filter_interactions([\filter_interactions::PARAM_SOURCES => $this->get_subtype(),
                        \filter_interactions::PARAM_INTERACTION_POST => true], $this->msocial);
        $interactions = social_interaction::load_interactions_filter($filter);
        $interactions = $this->merge_interactions($interactions, $this->lastinteractions);

        mtrace("<li>Checking ". count($interactions) . " tweets for Favs.");
        foreach ($interactions as $interaction) {
            if ($interaction->type == social_interaction::POST) {
                mtrace("<li>Getting favs for " . $this->get_interaction_url($interaction));
                $popupcode = $this->browse_twitter('https://twitter.com/i/activity/favorited_popup?id=' . $interaction->uid);
                $json = json_decode($popupcode);
                if (isset($json->htmlUsers)) {
                    $users = $json->htmlUsers;
                } else {
                    continue; // Tweet deleted or account made private.
                }
                $matches = [];
                preg_match_all('/screen-name="(?\'screenname\'[\w\s]+)"\s+data-user-id="(?\'userid\'\d+)"/', $users, $matches, PREG_PATTERN_ORDER);
                $count = count($matches[1]);
                if ($count == 0) {
                    continue;
                }
                mtrace("<li>Tweet " . $this->get_interaction_url($interaction) . " has $count favs.");
                for ($i = 0; $i < $count; $i++) {
                    // Create a new Like interaction.
                    $likeinteraction = new social_interaction();
                    $likeinteraction->source = $this->get_subtype();
                    $likeinteraction->nativefrom = $matches['userid'][$i];
                    $likeinteraction->fromid = $this->get_userid($likeinteraction->nativefrom);
                    $likeinteraction->nativeto = $interaction->fromid;
                    $likeinteraction->toid = $interaction->toid;
                    $likeinteraction->nativetoname = $interaction->nativefromname;
                    $likeinteraction->nativefromname = $matches['screenname'][$i];
                    $likeinteraction->description = $likeinteraction->nativefromname . ' liked tweet ' . $interaction->uid;
                    $likeinteraction->parentinteraction = $interaction->uid;
                    $likeinteraction->uid = $interaction->uid . '-likedby-' . $likeinteraction->nativefrom;
                    $likeinteraction->timestamp = $interaction->timestamp;
                    $likeinteraction->nativetype = 'fav';
                    $likeinteraction->type = social_interaction::REACTION;
                    $likeinteractions[$likeinteraction->uid] = $likeinteraction;
                }
            }
        }
        return $likeinteractions;
    }
    private function browse_twitter($geturl) {
        $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0';
        $options = array(
                        CURLOPT_RETURNTRANSFER => true, // to return web page
                        CURLOPT_FOLLOWLOCATION => true, // to follow redirects
                        CURLOPT_ENCODING       => "",   // to handle all encodings
                        CURLOPT_AUTOREFERER    => true, // to set referer on redirect
                        CURLOPT_CONNECTTIMEOUT => 120,  // set a timeout on connect
                        CURLOPT_TIMEOUT        => 120,  // set a timeout on response
                        CURLOPT_MAXREDIRS      => 10,   // to stop after 10 redirects
                        CURLINFO_HEADER_OUT    => true, // no header out
                        CURLOPT_SSL_VERIFYPEER => false,// to disable SSL Cert checks
                        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                        CURLOPT_USERAGENT      => $agent,
        );
        $ch = curl_init($geturl);
        curl_setopt_array( $ch, $options );
        $popupcode = curl_exec($ch);
        curl_close($ch);
        return $popupcode;
    }

    /** Process the statuses looking for students mentions
     * TODO: process entities->user_mentions[]
     *
     * @param \stdClass[] $statuses
     * @return \stdClass[] student statuses meeting criteria. */
    protected function process_statuses($statuses) {
        $context = \context_course::instance($this->msocial->course);
        $usersstruct = msocial_get_users_by_type($context);
        $userrecords = $usersstruct->userrecords;
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
            $statusrecord = $DB->get_record('msocial_tweets',
                     array('msocial' => $this->msocial->id, 'tweetid' => $tweetid));
            if (!$statusrecord) {
                $statusrecord = new \stdClass();
            } else {
                $DB->delete_records('msocial_tweets',
                        array('msocial' => $this->msocial->id, 'tweetid' => $tweetid));
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
    protected function refresh_interaction_users($socialuser) {
        parent::refresh_interaction_users($socialuser);
        global $DB;
        // Unset previous user map.
        $DB->set_field('msocial_tweets', 'userid', null ,
                ['userid' => $socialuser->userid, 'msocial' => $this->msocial->id]);
        // Set user map.
        $DB->set_field('msocial_tweets', 'userid', $socialuser->userid,
                ['twitterusername' => $socialuser->socialname, 'msocial' => $this->msocial->id]);
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
                            'errors' => [(object)['message' => "No connection tokens provided!!! Impossible to connect to twitter."]]];
            return $result;
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
