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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
require_once('TwitterAPIExchange.php');

/**
 * library class for social network twitter plugin extending social plugin base class
 *
 * @package tcountsocial_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
     * @global moodle_database $DB
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $DB;

        $options1 = array(
            'skype' => 'SKYPE',
            'yahoo' => 'Yahoo',
            'aim' => 'AIM',
            'msn' => 'MSN',
        );
        $options2 = array();
        $options = $DB->get_records_menu("user_info_field", null, "name", "shortname, name");
        if ($options) {
            foreach ($options as $shortname => $name) {
                $options2[$shortname] = $name;
            }
        }
        $idtypeoptions = $options1 + $options2;
        $mform->addElement('select', 'tcountsocial_twitter_twfieldid', get_string("twfieldid", "tcountsocial_twitter"), $idtypeoptions);
        $mform->setDefault('tcountsocial_twitter_twfieldid', 'aim');
        $mform->addHelpButton('tcountsocial_twitter_twfieldid', 'twfieldid', 'tcountsocial_twitter');
        $mform->addElement('text', 'tcountsocial_twitter_hashtag', get_string("hashtag", "tcountsocial_twitter"), array('size' => '20'));
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
     * @param mixed $tcount can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($tcount, MoodleQuickForm $mform, stdClass $data) {
        $elements = array();
        $tcountid = $tcount ? $tcount->id : 0;



        return true;
    }

    /**
     * The tcount has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records('tcount_statuses', array('tcountid' => $this->tcount->get_instance()->id));
        return true;
    }

    public function get_subtype() {
        return 'twitter';
    }

    /**
     * @global core_renderer $OUTPUT
     * @global moodle_database $DB
     * @param core_renderer $output
     */
    public function view_header() {
        global $OUTPUT, $DB, $USER;
        if ($this->is_tracking()) {
            list ($course, $cm) = get_course_and_cm_from_instance($this->tcount->id, 'tcount');
            $id = $cm->id;
            $token = $DB->get_record('tcount_tweeter_tokens', array('tcount_id' => $this->tcount->id));
            $url_connect = new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', array('id' => $id, 'action' => 'connect'));
            if ($token) {
                $username = $token->username;
                $errorstatus = $token->errorstatus;
                if ($errorstatus) {
                    echo $OUTPUT->notify_problem(get_string('problemwithtwitteraccount', 'tcount', $errorstatus));
                }
                echo $OUTPUT->box(get_string('module_connected_twitter', 'tcountsocial_twitter', $username)
                        . $OUTPUT->action_link(new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', array('id' => $id, 'action' => 'connect')), "Change user") . '/'
                        . $OUTPUT->action_link(new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', array('id' => $id, 'action' => 'disconnect')), "Disconnect"));
            } else {
                echo $OUTPUT->notification(get_string('module_not_connected_twitter', 'tcountsocial_twitter')
                        . $OUTPUT->action_link(new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', array('id' => $id, 'action' => 'connect')), "Connect"));
            }
            // Check user's social credentials.
            $twitterusername = $this->get_social_userid($USER);
            if (trim($twitterusername) === "") { // Offer to register.
                $url_profile = new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', array('id' => $id, 'action' => 'connect', 'type' => 'profile'));
                $twitteradvice = get_string('no_twitter_name_advice2', 'tcountsocial_twitter', ['field' => $this->get_userid_fieldname(), 'userid' => $USER->id, 'courseid' => $course->id, 'url' => $url_profile->out(false)]);
                echo $OUTPUT->notification($twitteradvice);
            }
        }
    }

    /**
     * Place social-network user information or a link to connect.
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user
     */
    public function view_user_linking($user) {
        global $USER,$COURSE;
        $course=$COURSE;
        $usermessage = '';
        $twitterusername = $this->get_social_userid($user);
        $cm = get_coursemodule_from_instance('tcount', $this->tcount->id);
        if (trim($twitterusername) === "") { // Offer to register.
            if ($USER->id == $user->id) {
                $url_profile = new moodle_url('/mod/tcount/social/twitter/twitterSSO.php', array('id' => $cm->id, 'action' => 'connect', 'type' => 'profile'));
                $usermessage = get_string('no_twitter_name_advice2', 'tcountsocial_twitter',
                                            ['field' => $this->get_userid_fieldname(),
                                             'userid' => $USER->id,
                                             'courseid' => $course->id,
                                             'url' => $url_profile->out(false)]);
            } else {
                $usermessage = get_string('no_twitter_name_advice', 'tcount',
                                                ['field' => $this->tcount->twfieldid,
                                                'userid' => $user->id,
                                                'courseid' => $course->id]);
            }
        } else {
            $usermessage = tcount_create_user_link($twitterusername, 'twitter');
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
        return trim($this->get_config('hashtag')) != "";
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
     * @param array[]integer $users array with the userids to be calculated
     * @return array[string]object object->userstats with PKIs for each user object->maximums max values for normalization.
     */
    public function calculate_stats($users) {
        global $DB;
        $cm = get_coursemodule_from_instance('tcount', $this->tcount->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql('SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs '
                . 'FROM {tcount_tweets} where tcountid = ? and userid is not null group by userid', array($this->tcount->id));
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

}
