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

use mod_msocial\connector\social_interaction;
use msocial\msocial_plugin;
use mod_msocial\SocialUser;
use mod_msocial\social_user;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/msocial/msocialplugin.php');
require_once('harvestintervals.php');
abstract class msocial_connector_plugin extends msocial_plugin {
    const LAST_HARVEST_TIME = 'lastharvest';
    protected $usertosocialmapping = null;
    protected $socialtousermapping = null;
    protected $lastinteractions = array();

    /** Constructor for the abstract plugin type class
     *
     * @param msocial $msocial
     * @param string $type */
    public final function __construct($msocial) {
        parent::__construct($msocial, 'msocialconnector');
    }

    public abstract function get_connection_token();

    public abstract function set_connection_token($token);

    public abstract function unset_connection_token();

    /**
     * @return moodle_url url of the icon for this service */
    public abstract function get_icon();

    /**
     * @return boolean true if the plugin is making searches in the social network */
    public abstract function is_tracking();

    /** Connect to the social network and collect the activity.
     *
     * @return string messages generated */
    public abstract function harvest();
    /**
     * @return harvest_intervals object with intervals and rates info.
     */
    public abstract function preferred_harvest_intervals();

    /** Gets formatted text for social-network user information or a link to connect.
     *
     * @param object $user user record
     * @return string message with the linking info of the user */
    public abstract function render_user_linking($user);

    /** Gets an href fragment that links to the user's page in the social network.
     * @param \stdClass $user user record
     * @return string html with the link to social network user's profile.*/
    public function create_user_link($user) {
        $socialuserid = $this->get_social_userid($user);
        if ($socialuserid) {
            $link = $this->get_social_user_url($socialuserid);
            $icon = $this->get_icon();
            return "<a href=\"$link\"><img src=\"$icon\" height=\"29px\" /> $socialuserid->socialname </a>";
        } else {
            return '';
        }
    }
    /**
     *
     * @param \stdClass $user user record
     * @return string url of the user in the social network
     */
    public function get_user_url($user) {
        $userid = $this->get_social_userid($user);
        if ($userid) {
            $link = $this->get_social_user_url($userid);
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

    /** Get a list of interactions between the users
     *
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return array[]mod_msocial\connector\social_interaction of interactions. @see
     *         mod_msocial\connector\social_interaction */
    public function get_interactions($fromdate = null, $todate = null, $users = null) {
        $conditions = "source = '$this->get_subtype()'";
        return social_interaction::load_interactions((int) $this->msocial->id, $conditions, $fromdate, $todate, $users);
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
    /** Stores the $socialname in the profile information of the $user
     *
     * @param \stdClass|int $user user record or userid
     * @param string $socialid The identifier or the user.
     * @param string $socialname The name used by the user in the social service */
    public function set_social_userid($user, $socialid, $socialname) {
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
        $record->type = $this->get_subtype();

        $DB->insert_record('msocial_mapusers', $record);
        $this->refresh_interaction_users($record);
        // Reset cache...
        $this->usertosocialmapping = null;
        $pkis = $this->calculate_pkis([$user->id => $user]);
        $this->store_pkis($pkis);
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
        $pkis = $this->calculate_pkis([$user->id => $user]);
        $this->store_pkis($pkis);
    }

    /** Try to fill interactions with null fromid or toid.
     * This can be filled if a user has mapped his socialid after harvesting.
     * @global \moodle_database $DB
     * @param \stdClass $socialuser struct with userid, socialid, socialname, type fields. */
    protected function refresh_interaction_users($socialuser) {
        global $DB;
        $DB->set_field('msocial_interactions', 'fromid', $socialuser->userid,
                ['nativefrom' => $socialuser->socialid, 'source' => $socialuser->type]);
        $DB->set_field('msocial_interactions', 'toid', $socialuser->userid,
                ['nativeto' => $socialuser->socialid, 'source' => $socialuser->type]);
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
                    null, 'userid,socialid,socialname');
            $this->usertosocialmapping = [];
            $this->socialtousermapping = [];
            foreach ($records as $record) {
                $this->socialtousermapping[$record->socialid] = $record->userid;
                $this->usertosocialmapping[$record->userid] = new social_user($record->socialid, $record->socialname);
            }
        }
    }
}
