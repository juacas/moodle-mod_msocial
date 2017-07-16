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
namespace mod_tcount\social;

use mod_tcount\social\pki_info;
use mod_tcount\social\pki;
use moodleforum\GraphNodes\GraphEdge;
use tcount\tcount_plugin;
use moodleforum\moodleforum as moodleforum;
use moodleforum\GraphNodes\GraphNode;
use mod_tcount\plugininfo\tcountsocial;

defined('MOODLE_INTERNAL') || die();
global $CFG;


/**
 * library class for social network moodleforum plugin extending social plugin base class
 *
 * @package tcountsocial_moodleforum
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_social_moodleforum extends tcount_social_plugin {

    private $lastinteractions = array();

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountsocial_moodleforum');
    }

    /**
     *
     * @return true if the plugin is making searches in the social network
     */
    public function is_tracking() {
        return $this->is_enabled();
    }

    /**
     * Get the instance settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(\MoodleQuickForm $mform) {
    }

    /**
     * The tcount has been deleted - cleanup subplugin
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (!$DB->delete_records('tcount_interactions', array('tcount' => $this->tcount->id, 'source' => $this->get_subtype()))) {
            $result = false;
        }
        if (!$DB->delete_records('tcount_plugin_config', array('tcount' => $this->tcount->id, 'subtype' => $this->get_subtype()))) {
            $result = false;
        }
        $this->drop_pki_fields();
        return $result;
    }

    public function get_subtype() {
        return 'moodleforum';
    }

    public function get_category() {
        return tcount_plugin::CAT_ANALYSIS;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \mod_tcount\social\tcount_social_plugin::get_icon()
     */
    public function get_icon() {
        return new \moodle_url('/mod/tcount/social/moodleforum/pix/moodleforum_icon.png');
    }

    /**
     *
     * @global \core_renderer $OUTPUT
     * @global \moodle_database $DB
     */
    public function view_header() {
        global $OUTPUT, $USER;
        if ($this->is_enabled()) {
            $context = \context_module::instance($this->cm->id);
            if (has_capability('mod/tcount:manage', $context)) {
                echo $OUTPUT->box(
                        get_string('harvest', 'tcountsocial_moodleforum') . $OUTPUT->action_icon(
                                new \moodle_url('/mod/tcount/social/harvest.php',
                                        ['id' => $this->cm->id, 'subtype' => $this->get_subtype()]),
                                new \pix_icon('a/refresh', get_string('harvest', 'tcountsocial_moodleforum'))));
            }
        }
    }

    /**
     * Place social-network user information or a link to connect.
     * Moodle internal users don't need to be detailed.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user
     */
    public function view_user_linking($user) {
        return '';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \mod_tcount\social\tcount_social_plugin::get_user_url()
     */
    function get_user_url($user) {
        $userid = $user->id;
        if ($userid) {
            $link = new \moodle_url("/user/view.php", ['id' => $userid]);
        } else {
            $link = null;
        }
        return $link;
    }

    function get_interaction_url(social_interaction $interaction) {
        // /groups/1670848226578336/permalink/1670848496578309/?comment_id=1670848556578303
        $parts = explode('_', $interaction->uid);
        if (count($parts) == 2) { // TODO...
            $url = new \moodle_url("/mod/forum/view.php", ['id' => $parts[0], 'post' => $parts[1]]);
        } else {
            $url = new \moodle_url("/mod/forum/view.php", ['id' => $parts[0]]);
        }

        return $url;
    }

    public function get_pki_list() {
        $pkiobjs['mfposts'] = new pki_info('mfposts', null, pki_info::PKI_INDIVIDUAL, social_interaction::POST, 'POST',
                social_interaction::DIRECTION_AUTHOR);
        $pkiobjs['mfreplies'] = new pki_info('mfreplies', null, pki_info::PKI_INDIVIDUAL, social_interaction::REPLY, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['mfgrades'] = new pki_info('mfgrades', null, pki_info::PKI_INDIVIDUAL, social_interaction::REACTION, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['max_mfposts'] = new pki_info('max_mfposts', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_mfreplies'] = new pki_info('max_mfreplies', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_mfgrades'] = new pki_info('max_mfgrades', null, pki_info::PKI_AGREGATED);
        return $pkiobjs;
    }

    protected function store_interactions(array $interactions) {
        $tcountid = $this->tcount->id;
        social_interaction::store_interactions($interactions, $tcountid);
    }

    /**
     *
     * @param social_interaction $interaction
     */
    public function register_interaction(social_interaction $interaction) {
        $interaction->source = $this->get_subtype();
        $this->lastinteractions[] = $interaction;
    }

    /**
     * TODO
     * Obtiene el numero de respuestas y notas recibidas en el Post, y actaliza el "score" de
     * la persona que escribio el Post
     *
     * @param \stdClass $post moodleforum post.
     * @param array(\stdClass) $posts other posts for lookup.
     */
    protected function process_post($post, $posts, $users) {
        $postinteraction = new social_interaction();
        $postinteraction->uid = $post->id;
        $postinteraction->nativefrom = $post->userid;
        $postinteraction->nativefromname = fullname($users[$post->userid]);
        $postinteraction->fromid = $post->userid;
        $postinteraction->rawdata = json_encode($post);
        $time = new \DateTime();
        $time->setTimestamp($post->created);
        $postinteraction->timestamp = $time;
        if ($post->parent == 0) {
            $postinteraction->type = social_interaction::POST;
            $postinteraction->nativetype = 'POST';
        } else {
            $postinteraction->type = social_interaction::REPLY;
            $postinteraction->nativetype = 'REPLY';
            $postinteraction->nativeto = $posts[$post->parent]->userid;
            $postinteraction->nativefromname = fullname($users[$postinteraction->nativeto]);
            $postinteraction->toid = $posts[$post->parent]->userid;
        }
        $message = $post->subject;
        $postinteraction->description = $message == '' ? 'No text.' : $message;
        $this->register_interaction($postinteraction);
        return $postinteraction;
    }

    /**
     * Classify the text as too short to be relevant
     *
     * @param GraphEdge $comment
     * @return boolean $ok
     */
    function is_short_comment($message) {
        $ok = false;
        // Cuento el numero de palabras del mensaje
        $num_words = str_word_count($message, 0);
        // Si el mensaje tiene dos o menos palabras ignoramos dicho mensaje
        if ($num_words <= $this->min_words) {
            $ok = true;
        }
        return $ok;
    }

    /**
     *
     * @todo
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        global $DB;

        $errormessage = null;
        $result = new \stdClass();
        $result->messages = [];
        $result->errors = [];

        $this->lastinteractions = [];
        $contextcourse = \context_course::instance($this->tcount->course);
        list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);

        try {
            // Query moodleforum...
            $since = '';
            $lastharvest = $this->get_config(tcount_social_moodleforum::LAST_HARVEST_TIME);
            if ($lastharvest) {
                $since = "&since=$lastharvest";
            }
            // Read forum_posts.
            $sql = 'select p.* from {forum_posts} p left join {forum_discussions} d on d.id = p.discussion where d.course = ?';
            $posts = $DB->get_records_sql($sql, [$this->tcount->course]);

            // Iterate the posts.
            foreach ($posts as $post) {

                $postinteraction = $this->process_post($post, $posts, $users);
            }
        } catch (\Exception $e) {
            $tcount = $this->tcount;
            $errormessage = "For module tcount\social\moodleforum: $tcount->name  in course (id=$tcount->course) " . "ERROR:" .
                     $e->getMessage();
            $result->messages[] = $errormessage;
            $result->errors[] = (object) ['message' => $errormessage];
        }
        // TODO: define if processsing is needed or not.
        $processedinteractions = $this->lastinteractions; // $this->process_interactions($this->lastinteractions);

        $studentinteractions = array_filter($processedinteractions,
                function ($interaction) {
                    return isset($interaction->fromid);
                });
        // TODO: define if all interactions are
        // worth to be registered or only student's.
        $this->store_interactions($processedinteractions);

        $pkis = $this->calculate_pkis($users);
        $this->store_pkis($pkis, true);
        $this->set_config(\mod_tcount\social\tcount_social_plugin::LAST_HARVEST_TIME, time());

        $logmessage = "For module tcount: \"" . $this->tcount->name . "\" (id=" . $this->tcount->id . ") in course (id=" .
                 $this->tcount->course . ")  Found " . count($this->lastinteractions) . " events. Students' events: " .
                 count($studentinteractions);
        $result->messages[] = $logmessage;
        return $result;
    }

    public function get_connection_token() {
        return '';
    }

    public function set_connection_token($token) {
    }

    public function unset_connection_token() {
    }

    public function calculate_stats($users) {
    }
}