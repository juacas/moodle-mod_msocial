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

/** library class for msocial social plugins base class
 *
 * @package msocialconnector_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial\connector;

use mod_msocial\filter_interactions;
use mod_msocial\social_user;
use msocial\msocial_plugin;
use mod_msocial\users_struct;

defined('MOODLE_INTERNAL') || die();
require_once('msocialplugin.php');
require_once('usersstruct.php');
require_once('harvestintervals.php');
require_once('filterinteractions.php');
abstract class msocial_connector_plugin extends msocial_plugin {
    protected $usertosocialmapping = null;
    protected $socialtousermapping = null;
    protected $lastinteractions = array();

    /** Constructor for the abstract plugin type class
     *
     * @param \stdClass $msocial
     * @param string $type */
    public final function __construct($msocial) {
        parent::__construct($msocial, 'msocialconnector');
    }
    /**
     * User's token broke. Maybe expired. Ask the user to relogin.
     * @param \stdClass $socialuser record from msocial_map_user table.
     * @param string $msg
     */
    protected function notify_user_token( $socialuser, $msg) {
        // TODO: Notify users with messagging.
        $this->notify([$msg], self::NOTIFY_WARNING);
    }
    /**
     * Sometimes the social networks API access is not authorized (due to review processes)
     * and SSO login is only allowed for certain users (i.e. teachers registered manually in the API provider.)
     * @return boolean
     */
    public function isautologinapproved() {
        return true;
    }
    public abstract function get_connection_token();

    public abstract function set_connection_token($token);

    public abstract function unset_connection_token();

    /**
     * @return \moodle_url url of the icon for this service */
    public abstract function get_icon();

    /** Place social-network user information or a link to connect.
     *  Uses i18n strings get_string("no_{$subtype}_name_advice2", "msocialconnector_{$subtype}")
     *  and get_string("no_{$subtype}_advice2_when_api_unapproved", "msocialconnector_{$subtype}")
     *  depending on $this->isautologinapproved()
     *  if $connectaction == true renders a link to connect the account.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user */
    public function render_user_linking($user, $brief = false, $connectaction = false, $disconnectaction = false) {
        global $COURSE;
        $course = $COURSE;
        $usermessage = '';
        $socialids = $this->get_social_userid($user);
        $subtype = $this->get_subtype();
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id);
        if ($socialids == null) { // Offer to register.
            $pixurl = new \moodle_url("/mod/msocial/connector/{$subtype}/pix"); // For i18n strings.
            $userfullname = msocial_get_visible_fullname($user, $this->msocial);
            if ($connectaction) {
                $urlprofile = new \moodle_url("/mod/msocial/connector/$subtype/connectorSSO.php",
                        array('id' => $cm->id, 'action' => 'connect', 'type' => 'profile'));
                if ($this->isautologinapproved()) {
                    $msgstring = "no_{$subtype}_name_advice2";
                } else {
                    $msgstring = "no_{$subtype}_advice2_when_api_unapproved";
                }
                $usermessage = get_string($msgstring, "msocialconnector_{$subtype}",
                        ['userfullname' => $userfullname, 'userid' => $user->id, 'courseid' => $course->id,
                                        'url' => $urlprofile->out(false), 'pixurl' => $pixurl->out(false)]);                    
            } else {
                if ($brief) {
                    $usermessage = $this->render_user_link($user, $brief);
                } else {
                    $usermessage = get_string("no_{$subtype}_name_advice", "msocialconnector_{$subtype}",
                            ['userfullname' => $userfullname, 'userid' => $user->id, 'courseid' => $course->id,
                                            'pixurl' => $pixurl->out()]);
                }
            }
        } else {
            global $OUTPUT;
            $usermessage = $this->render_user_link($user, $brief);
            if ($disconnectaction) {
                $iconurl = new \moodle_url('/mod/msocial/pix/icon_unlink.png');
                $unlinktext = get_string('unlinksocialaccount', 'msocial');
                $iconhtml = \html_writer::img($iconurl->out(), $unlinktext, [ 'title' => $unlinktext, 'width' => 15]);
                $urlprofile = new \moodle_url("/mod/msocial/connector/{$subtype}/connectorSSO.php",
                        array('id' => $this->cm->id, 'action' => 'disconnect', 'type' => 'profile', 'userid' => $user->id,
                                        'socialid' => $socialids->socialid));
                $link = \html_writer::link($urlprofile, $iconhtml);
                $usermessage = '<div style="position:relative; display:inline-block"><div style="">' . $usermessage .
                                '</div><div style="position:absolute; top:-5px; left:-5px">' . $link . '</div></div>';
            }
        }
        return $usermessage;
    }

    /** Gets an href fragment that links to the user's page in the social network.
     * @param \stdClass $user user record
     * @return string html with the link to social network user's profile.*/
    public function render_user_link($user, $brief = false) {
        $socialuserid = $this->get_social_userid($user);
        if ($socialuserid) {
            $userlink = $this->get_social_user_url($socialuserid);
            $linkpart1 = "";
            $linkpart2 = "";
            if ($userlink) {
                $linkpart1 = "<a target=\"_blank\" href=\"$userlink\">";
                $linkpart2 = "</a>";
            }
            $icon = $this->get_icon();
            $link = $linkpart1 . "<img src=\"$icon\" height=\"29px\" title=\"$socialuserid->socialname\" " .
                    "alt=\"$socialuserid->socialname\"/>";
            if (!$brief) {
                $link .= $socialuserid->socialname;
            }
            $link .= $linkpart2;
            return $link;
        } else {
                $icon = $this->get_icon();
                return "<img src=\"$icon\" height=\"29px\" style=\"-webkit-filter: blur(3px); filter: blur(3px);\"/>";
        }
    }
    /**
     *
     * @param \stdClass $user user record
     * @return string url of the user in the social network
     */
    public function get_user_url($user) {
        $socialuser = $this->get_social_userid($user);
        if ($socialuser) {
            $link = $this->get_social_user_url($socialuser);
        } else {
            $link = null;
        }
        return $link;
    }
    /** Construct a native link
     * @param social_user $socialid
     * @return string url of the user in the social network
     */
    abstract public function get_social_user_url(social_user $socialid);

    /** URL to a page with the social interaction.
     *
     * @param social_interaction $interaction */
    public abstract function get_interaction_url(social_interaction $interaction);
    public function get_interaction_description(social_interaction $interaction) {
        return $interaction->description;
    }
    /** Get a list of interactions between the users
     *
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param users_struct $users filter of users $users struct obtained from msocial_get_users_by_type
     * @return \mod_msocial\connector\social_interaction[] of interactions. @see
     *         mod_msocial\connector\social_interaction */
    public function get_interactions($fromdate = null, $todate = null, $users = null) {
        $filter = new filter_interactions([filter_interactions::PARAM_SOURCES => $this->get_subtype(),
                                            filter_interactions::PARAM_INTERACTION_MENTION => true,
                                            ], $this->msocial);
        $filter->set_users($users);
        return social_interaction::load_interactions_filter($filter);
    }
    protected function store_interactions(array $interactions) {
        $msocialid = $this->msocial->id;
        social_interaction::store_interactions($interactions, $msocialid);
    }
    /**
     * @param social_interaction $interaction */
    public function register_interaction(social_interaction $interaction) {
        $interaction->source = $this->get_subtype();
        // Array is indexed by uid to ensure that there is unicity in the uid.
        $this->lastinteractions[$interaction->uid] = $interaction;
    }
    /**
     * Common tasks after harvesting.
     * Generate Key Performance Indicators (KPIs), store KPIs, mark harvest time, report harvest messages.
     * @param string[] $result
     * @return string[] $result
     */
    protected function post_harvest($result) {
        // TODO: define if processsing is needed or not.
        $processedinteractions = $this->lastinteractions;

        // TODO: define if all interactions are
        // worth to be registered or only student's.
        $this->store_interactions($processedinteractions);
        $contextcourse = \context_course::instance($this->msocial->course);
        $usersstruct = msocial_get_users_by_type($contextcourse);
        $kpis = $this->calculate_kpis($usersstruct);
        $this->store_kpis($kpis, true);
        $this->set_config(self::LAST_HARVEST_TIME, time());

        $studentinteractions = array_filter($processedinteractions,
                function (social_interaction $interaction) {
                    return isset($interaction->fromid) &&
                    msocial_time_is_between($interaction->timestamp,
                                            (int) $this->msocial->startdate,
                                            (int) $this->msocial->enddate);
                });
        $intimeinteractions = array_filter($processedinteractions,
                function (social_interaction $interaction) {
                    return msocial_time_is_between($interaction->timestamp,
                            $this->msocial->startdate, $this->msocial->enddate);
                });
        $subtype = $this->get_subtype();
        $logmessage = "For module msocial\\connector\\$subtype: \"" . $this->msocial->name .
        "\" (id=" . $this->msocial->id . ") in course (id=" .
        $this->msocial->course . ")  Found " . count($this->lastinteractions) .
        " events. In time period: " . count($intimeinteractions) . ". Students' events: " . count($studentinteractions);
        $result->messages[] = $logmessage;

        return $result;
    }
    /** Stores the $socialname in the profile information of the $user
     *
     * @param \stdClass $user user record
     * @param string $socialid The identifier or the user.
     * @param string $socialname The name used by the user in the social service
     * @param string $link URL to the user page in the social service.
     */
    public function set_social_userid($user, $socialid, $socialname, $link = null) {
        global $DB;
        // Clean previous maps.
        $DB->delete_records('msocial_mapusers',
                ['msocial' => $this->msocial->id, 'type' => $this->get_subtype(), 'userid' => $user->id]);
        $DB->delete_records('msocial_mapusers',
                ['msocial' => $this->msocial->id, 'type' => $this->get_subtype(), 'socialid' => $socialid]);

        $record = new \stdClass();
        $record->msocial = $this->msocial->id;
        $record->userid = $user->id;
        $record->socialid = $socialid;
        $record->socialname = $socialname;
        $record->link = $link;
        $record->type = $this->get_subtype();

        $DB->insert_record('msocial_mapusers', $record);
        $this->refresh_interaction_users($record);
        // Reset cache...
        $this->usertosocialmapping = null;
        $userstruct = new users_struct();
        $userstruct->studentids[] = $user->id;
        $userstruct->userrecords[] = $user;
        $kpis = $this->calculate_kpis($userstruct);
        $this->store_kpis($kpis);
    }

    public function unset_social_userid($user, $socialid) {
        global $DB;
        $DB->delete_records('msocial_mapusers',
                ['msocial' => $this->msocial->id, 'type' => $this->get_subtype(), 'userid' => $user->id, 'socialid' => $socialid]);
        $record = new \stdClass();
        $record->msocial = $this->msocial->id;
        $record->userid = null; // Remove interaction resolutions.
        $record->socialid = $socialid;
        $record->type = $this->get_subtype();
        $this->refresh_interaction_users($record);
        // Reset cache...
        $this->usertosocialmapping = null;
        $kpis = $this->calculate_kpis([$user->id => $user]);
        $this->store_kpis($kpis);
    }

    /** Try to fill interactions with null fromid or toid.
     * This can be filled if a user has mapped his socialid after harvesting.
     * @global \moodle_database $DB
     * @param \stdClass $socialuser struct with userid, socialid, socialname, type fields. */
    protected function refresh_interaction_users($socialuser) {
        global $DB;
        // Unset previous map.
        $DB->set_field('msocial_interactions', 'fromid', null,
                ['fromid' => $socialuser->userid, 'source' => $socialuser->type, 'msocial' => $this->msocial->id]);
        $DB->set_field('msocial_interactions', 'toid', null,
                ['toid' => $socialuser->userid, 'source' => $socialuser->type, 'msocial' => $this->msocial->id]);
        // Set new user map.
        $DB->set_field('msocial_interactions', 'fromid', $socialuser->userid,
                ['nativefrom' => $socialuser->socialid, 'source' => $socialuser->type, 'msocial' => $this->msocial->id]);
        $DB->set_field('msocial_interactions', 'toid', $socialuser->userid,
                ['nativeto' => $socialuser->socialid, 'source' => $socialuser->type, 'msocial' => $this->msocial->id]);
    }

    /** Maps social ids to moodle's user ids
     *
     * @param int $socialid */
    public function get_userid($socialid) {
        $this->check_mapping_cache();
        return isset($this->socialtousermapping[$socialid]) ? $this->socialtousermapping[$socialid] : null;
    }
    /** Reports if the users are from an external social network or from a Moodle activity.
     * @return boolean */
    public function users_are_local() {
        return false;
    }

    /** Maps a Moodle's $user to a user id in the social media.
     *
     * @param \stdClass|int $user user record or userid
     * @return social_user  */
    public function get_social_userid($user) {
        if ($user instanceof \stdClass) {
            $userid = $user->id;
        } else {
            $userid = (int) $user;
        }
        $this->check_mapping_cache();
        return isset($this->usertosocialmapping[$userid]) ? $this->usertosocialmapping[$userid] : null;
    }

    private function check_mapping_cache() {
        global $DB;
        if ($this->usertosocialmapping == null || $this->socialtousermapping == null) {
            $records = $DB->get_records('msocial_mapusers', ['msocial' => $this->msocial->id, 'type' => $this->get_subtype()],
                    null, 'userid,socialid,socialname,link');
            $this->usertosocialmapping = [];
            $this->socialtousermapping = [];
            foreach ($records as $record) {
                $this->socialtousermapping[$record->socialid] = $record->userid;
                $this->usertosocialmapping[$record->userid] = new social_user($record->socialid, $record->socialname, isset($record->link) ? $record->link : '');
            }
        }
    }
}
