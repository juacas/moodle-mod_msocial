<?php
use mod_tcount\social\social_interaction;

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
defined('MOODLE_INTERNAL') || die();
require_once ($CFG->dirroot .'/mod/tcount/tcountplugin.php');
require_once($CFG->dirroot .'/mod/tcount/social/socialinteraction.php');

abstract class tcount_social_plugin extends tcount_plugin {

    /**
     * Constructor for the abstract plugin type class
     *
     * @param tcount $tcount
     * @param string $type
     */
    public final function __construct($tcount) {
        parent::__construct($tcount, 'tcountsocial');
    }

    /**
     * Renders the section of the page to inform and manage social plugins.
     */
    // public abstract function get_status_section(core_renderer $output);
    /**
     * Maps a Moodle's $user to a user id in the social media.
     *
     * @param \stdClass $user user record
     */
    public abstract function get_social_userid($user);
    public abstract function get_connection_token();
    public abstract function set_connection_token($token);
    /**
     * @return moodle_url url of the icon for this service
     */
    public abstract function get_icon();
    /**
     * Stores the $socialname in the profile information of the $user
     *
     * @param \stdClass $user user record
     * @param string $socialname The name/id used by the user in the social service
     */
    public abstract function set_social_userid($user, $socialname);

    /** @var tcount_social_plugin $pluginsocial */

    /**
     * Reports the list of PKI calculated from this plugin
     *
     * @return array[]string list of PKI names
     */
    public abstract function get_pki_list();

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
     *
     * @global core_renderer $OUTPUT
     * @global moodle_database $DB
     * @param core_renderer $output
     */

    /**
     *
     * @param \stdClass $tcount db record.
     * @param course_modinfo $cm course module info
     * @return tcount_social_plugin
     */
    public static function instance($tcount, $subtype) {
        $path = core_component::get_plugin_directory('tcountsocial', $subtype);
        $classfile = $subtype . 'plugin.php';
        if (file_exists($path . '/' . $classfile)) {
            require_once ($path . '/' . $classfile);
            $pluginclass = 'tcount_social_' . $subtype;
            $plugin = new $pluginclass($tcount);
            return $plugin;
        }
    }
    /**
     * Get a list of interactions between the users
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return array[]mod_tcount\social\social_interaction of interactions. @see mod_tcount\social\social_interaction
     */
    public abstract function get_interactions($fromdate=null, $todate=null,$users=null);
}
