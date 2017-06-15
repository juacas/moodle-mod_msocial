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

/**
 * library class for tcount social plugins base class
 *
 * @package tcountsocial_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_tcount\social;

use mod_tcount\social\social_interaction;
use tcount\tcount_plugin;
use mod_tcount\social\pki;

defined('MOODLE_INTERNAL') || die();
require_once ($CFG->dirroot . '/mod/tcount/tcountplugin.php');
require_once ($CFG->dirroot . '/mod/tcount/pki.php');
require_once ($CFG->dirroot . '/mod/tcount/social/socialinteraction.php');


abstract class tcount_social_plugin extends tcount_plugin {

    protected $user_to_social_mapping = null;

    protected $social_to_user_mapping = null;

    /**
     * Constructor for the abstract plugin type class
     *
     * @param tcount $tcount
     * @param string $type
     */
    public final function __construct($tcount) {
        parent::__construct($tcount, 'tcountsocial');
    }

    public abstract function get_connection_token();

    public abstract function set_connection_token($token);

    /**
     *
     * @return moodle_url url of the icon for this service
     */
    public abstract function get_icon();

    /**
     *
     * @return boolean true if the plugin is making searches in the social network
     */
    public abstract function is_tracking();

    /**
     * Connect to the social network and collect the activity.
     *
     * @return string messages generated
     */
    public abstract function harvest();

    /**
     * Gets formatted text for social-network user information or a link to connect.
     *
     * @param object $user user record
     * @return string message with the linking info of the user
     */
    public abstract function view_user_linking($user);

    /**
     * URL to a page with the social interaction.
     *
     * @param social_interaction $interaction
     */
    public abstract function get_interaction_url(social_interaction $interaction);

    /**
     * Get a list of interactions between the users
     *
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return array[]mod_tcount\social\social_interaction of interactions. @see
     *         mod_tcount\social\social_interaction
     */
    public function get_interactions($fromdate = null, $todate = null, $users = null) {
        $conditions = "source = '$this->get_subtype()'";
        return social_interaction::load_interactions((int) $this->tcount->id, $conditions, $fromdate, $todate, $users);
    }

    /**
     * Stores the $socialname in the profile information of the $user
     *
     * @param \stdClass|int $user user record or userid
     * @param string $socialid The identifier or the user.
     * @param string $socialname The name used by the user in the social service
     */
    public function set_social_userid($user, $socialid, $socialname) {
        global $DB;
        $record = $DB->get_record('tcount_social_mapusers', 
                ['tcount' => $this->tcount->id, 'type' => $this->get_subtype(), 'userid' => $user->id]);
        if ($record === false) {
            $record = new \stdClass();
        }
        $record->tcount = $this->tcount->id;
        $record->userid = $user->id;
        $record->socialid = $socialid;
        $record->socialname = $socialname;
        if (isset($record->id)) {
            $DB->update_record('tcount_social_mapusers', $record);
        } else {
            $DB->insert_record('tcount_social_mapusers', $record);
        }
        // Reset cache...
        $this->user_to_social_mapping = null;
    }

    /**
     * Maps social ids to moodle's user ids
     *
     * @param int $socialid
     */
    public function get_userid($socialid) {
        $this->check_mapping_cache();
        return isset($this->social_to_user_mapping[$socialid]) ? $this->social_to_user_mapping[$socialid] : null;
    }

    /**
     * Maps a Moodle's $user to a user id in the social media.
     *
     * @param \stdClass|int $user user record or userid
     * @return \stdClass socialid, socialname
     */
    public function get_social_userid($user) {
        if ($user instanceof \stdClass) {
            $userid = $user->id;
        } else {
            $userid = (int) $user;
        }
        $this->check_mapping_cache();
        return isset($this->user_to_social_mapping[$userid]) ? $this->user_to_social_mapping[$userid] : null;
    }

    private function check_mapping_cache() {
        global $DB;
        if ($this->user_to_social_mapping == null || $this->social_to_user_mapping == null) {
            $records = $DB->get_records('tcount_social_mapusers', ['tcount' => $this->tcount->id, 
                            'type' => $this->get_subtype()], null, 'userid,socialid,socialname');
            $this->user_to_social_mapping = [];
            $this->social_to_user_mapping = [];
            foreach ($records as $record) {
                $this->social_to_user_mapping[$record->socialid] = $record->userid;
                $this->user_to_social_mapping[$record->userid] = (object) ['socialid' => $record->socialid, 
                                'socialname' => $record->socialname];
            }
        }
    }
}
