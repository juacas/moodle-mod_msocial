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

use connector\twitter\twitter_local_harvester;
use MoodleQuickForm;
use mod_msocial\kpi;
use mod_msocial\kpi_info;
use mod_msocial\msocial_harvestplugin;
use mod_msocial\msocial_plugin;
use mod_msocial\social_user;
use mod_msocial\users_struct;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once('TwitterAPIExchange.php');
require_once('twitter_local_harvester.php');
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
     * @param \MoodleQuickForm $mform The form to add elements to
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
     **/
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
     * Get the associated object implementing @see msocial_harvestplugin
     * @return msocial_harvestplugin
     */
    public function get_harvest_plugin() {
        // Mapped users.
        global $DB;
        $mappedusers = $DB->get_records('msocial_mapusers', ['msocial' => $this->msocial->id, 'type' => $this->get_subtype()]);        
        return new twitter_local_harvester($this, $mappedusers);
    }
    /**
     * @return true if the plugin is making searches in the social network */
    public function can_harvest() {
        return  $this->get_connection_token() != null &&
                trim($this->get_config('hashtag')) != "";
    }

     public function get_kpi_list() {
        $kpiobjs = [];
        $kpiobjs['tweets'] = new kpi_info('tweets',  get_string('kpi_description_tweets', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, social_interaction::POST, 'tweet',
                social_interaction::DIRECTION_AUTHOR);
        $kpiobjs['twreplies'] = new kpi_info('twreplies',  get_string('kpi_description_tweet_replies', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, social_interaction::REPLY, 'tweet',
                 social_interaction::DIRECTION_AUTHOR);
        $kpiobjs['retweets'] = new kpi_info('retweets',  get_string('kpi_description_retweets', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, social_interaction::MENTION, 'retweet',
                social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['favs'] = new kpi_info('favs',  get_string('kpi_description_favs', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL,  kpi_info::KPI_CALCULATED, social_interaction::REACTION, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['twmentions'] = new kpi_info('twmentions',  get_string('kpi_description_twmentions', 'msocialconnector_twitter'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED,
                social_interaction::MENTION, 'mention', social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['max_tweets'] = new kpi_info('max_tweets', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_retweets'] = new kpi_info('max_retweets', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_favs'] = new kpi_info('max_favs', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_twmentions'] = new kpi_info('max_twmentions', null, kpi_info::KPI_AGREGATED);
        return $kpiobjs;
    }

    /**
     * @global moodle_database $DB
     * @return \stdClass token record */
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
     * @param \stdClass token record.
     */
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
     * @see \mod_msocial\msocial_plugin::reset_userdata()
     */
    public function reset_userdata(\stdClass $data) {
        // Twitter token if for the teacher. Preserve it.
        
        // Remove mapusers.
        global $DB;
        $msocial = $this->msocial;
        $DB->delete_records('msocial_mapusers',['msocial' => $msocial->id, 'type' => $this->get_subtype()]);
        // Clear tweets log.
        $DB->delete_records('msocial_tweets', ['msocial' => $msocial->id]);
        return array('component'=>$this->get_name(), 'item'=>get_string('resetdone', 'msocial',
                "\"{$msocial->name}\": map of users, tweets"), 'error'=>false);
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
     * 
     * {@inheritDoc}
     * @see \mod_msocial\connector\msocial_connector_plugin::refresh_interaction_users()
     */
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
   
}
