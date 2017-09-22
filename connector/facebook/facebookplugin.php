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

use Facebook\Facebook as Facebook;
use Facebook\GraphNodes\GraphEdge;
use Facebook\GraphNodes\GraphNode;
use mod_msocial\pki_info;
use msocial\msocial_plugin;
use mod_msocial\social_user;

defined('MOODLE_INTERNAL') || die();
global $CFG;

/** library class for social network facebook plugin extending social plugin base class
 *
 * @package msocialconnector_facebook
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_connector_facebook extends msocial_connector_plugin {
    const CONFIG_FBSEARCH = 'fbsearch';
    const CONFIG_FBGROUP = 'fbgroup';
    const CONFIG_FBGROUPNAME = 'fbgroupname';
    const CONFIG_MIN_WORDS = 'fbminwords';

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialconnector_facebook');
    }

    /**
     * @return true if the plugin is making searches in the social network */
    public function is_tracking() {
        return ($this->is_enabled() && $this->get_connection_token() != null && $this->get_config(self::CONFIG_FBGROUP) != null);
    }

    /** Get the instance settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void */
    public function get_settings(\MoodleQuickForm $mform) {
        $mform->addElement('static', 'config_group', get_string('fbgroup', 'msocialconnector_facebook'),
                get_string('connectgroupinpage', 'msocialconnector_facebook'));
    }

    /** Save the settings for facebook plugin
     *
     * @param \stdClass $data
     * @return bool */
    public function save_settings(\stdClass $data) {
        if (isset($data->{$this->get_form_field_name(self::CONFIG_DISABLED)})) {
            $this->set_config(msocial_plugin::CONFIG_ENABLED, $data->{$this->get_form_field_name(self::CONFIG_DISABLED)});
        }
        return true;
    }

    /** The msocial has been deleted - cleanup subplugin
     *
     * @return bool */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (!$DB->delete_records('msocial_interactions', array('msocial' => $this->msocial->id, 'source' => $this->get_subtype()))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_facebook_tokens', array('msocial' => $this->msocial->id))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_mapusers', array('msocial' => $this->msocial->id, 'type' => $this->get_subtype()))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_plugin_config', array('msocial' => $this->msocial->id, 'subtype' => $this->get_subtype()))) {
            $result = false;
        }
        return $result;
    }

    public function get_subtype() {
        return 'facebook';
    }

    public function get_category() {
        return msocial_plugin::CAT_ANALYSIS;
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\connector\msocial_connector_plugin::get_icon() */
    public function get_icon() {
        return new \moodle_url('/mod/msocial/connector/facebook/pix/facebook_icon.png');
    }

    /**
     * @global \core_renderer $OUTPUT
     * @global \moodle_database $DB
     */
    public function render_header() {
        global $OUTPUT, $DB, $USER;
        $notifications = [];
        $messages = [];
        if ($this->is_enabled()) {
            $context = \context_module::instance($this->cm->id);
            list($course, $cm) = get_course_and_cm_from_instance($this->msocial->id, 'msocial');
            $id = $cm->id;
            if (has_capability('mod/msocial:manage', $context)) {
                $token = $DB->get_record('msocial_facebook_tokens', array('msocial' => $this->msocial->id));
                $urlconnect = new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                        array('id' => $id, 'action' => 'connect'));
                if ($token) {
                    $username = $token->username;
                    $errorstatus = $token->errorstatus;
                    if ($errorstatus) {
                        $notifications[] = '<p>' . get_string('problemwithfacebookaccount', 'msocialconnector_facebook', $errorstatus);
                    }

                    $messages[] = get_string('module_connected_facebook', 'msocialconnector_facebook', $username) . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                    array('id' => $id, 'action' => 'connect')), "Change user") . '/' . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                    array('id' => $id, 'action' => 'disconnect')), "Disconnect") . ' ';
                } else {
                    $notifications[] = get_string('module_not_connected_facebook', 'msocialconnector_facebook') . $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                    array('id' => $id, 'action' => 'connect')), "Connect");
                }
            }
            // Check facebook group...
            $fbgroup = $this->get_config(self::CONFIG_FBGROUP);
            $action = '';
            if (has_capability('mod/msocial:manage', $context)) {
                $action = $OUTPUT->action_link(
                        new \moodle_url('/mod/msocial/connector/facebook/groupchoice.php',
                                array('id' => $id, 'action' => 'selectgroup')), "Select group");
            }
            if (trim($fbgroup) === "") {
                $notifications[] = get_string('fbgroup', 'msocialconnector_facebook') . " : " . $action;
            } else {
                $groupinfo = implode(', ', $this->render_groups_links());
                $messages[] = get_string('fbgroup', 'msocialconnector_facebook') . ' : "' . $groupinfo . '" ' . $action;
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
        $harvestbutton = '';
        $context = \context_module::instance($this->cm->id);
        if (has_capability('mod/msocial:manage', $context) && $this->is_tracking()) {
            $harvestbutton = $OUTPUT->action_icon(
                    new \moodle_url('/mod/msocial/harvest.php', ['id' => $this->cm->id, 'subtype' => $this->get_subtype()]),
                    new \pix_icon('a/refresh', get_string('harvest', 'msocialconnector_facebook')));
        }
        return $harvestbutton;
    }
    public function render_groups_links() {
        $groupstruct = $this->get_config(self::CONFIG_FBGROUPNAME);
        $groups = json_decode($groupstruct);
        $linkinfo = [];
        if ($groups) {
            foreach ($groups as $groupid => $group) {
                $groupname = $group->name;
                $groupurl = 'https://www.facebook.com/groups/' . $groupid;
                $linkinfo[] = \html_writer::link($groupurl, $groupname);
            }
        }
        return $linkinfo;
    }

    public function get_social_user_url(social_user $userid) {
        return "https://www.facebook.com/app_scoped_user_id/$userid->socialid";
    }

    public function get_interaction_url(social_interaction $interaction) {
        // Facebook uid for a comment is generated with group id and comment id.
        $parts = explode('_', $interaction->uid);
        if (count($parts) == 2) {
            $url = 'https://www.facebook.com/groups/' . $parts[0] . '/permalink/' . $parts[1];
        } else {
            $url = 'https://www.facebook.com/groups/' . $this->get_config(self::CONFIG_FBGROUP) . '/permalink/' . $parts[0];
        }

        return $url;
    }

    /** Statistics for grading
     *
     * @param array[]integer $users array with the userids to be calculated
     * @return array[string]object object->userstats with PKIs for each user object->maximums max
     *         values for normalization.
     * @deprecated */
    private function calculate_stats($users) {
        global $DB;
        $userstats = new \stdClass();
        $userstats->users = array();
        $pkinames = $this->get_pki_list();
        $posts = [];
        $replies = [];
        $reactions = [];
        $subtype = $this->get_type();
        // Calculate posts.
        $sql = "SELECT fromid as userid, count(*) as total from {msocial_interactions} " .
                "where msocial=? and source='$subtype' and type='post' and fromid IS NOT NULL group by fromid";
        $postsrecords = $DB->get_records_sql($sql, [$this->msocial->id]);
        $this->append_stats('posts', $postsrecords, $users, $userstats, $posts);
        $sql = "SELECT toid as userid, count(*) as total from {msocial_interactions} " .
                "where msocial=? and source='$subtype' and type='reply' and toid IS NOT NULL group by toid";
        $replyrecords = $DB->get_records_sql($sql, [$this->msocial->id]);
        $this->append_stats('replies', $replyrecords, $users, $userstats, $replies);
        $sql = "SELECT fromid as userid, count(*) as total from {msocial_interactions} " .
                "where msocial=? and source='$subtype' and type='reaction' and toid IS NOT NULL group by toid";
        $reactionrecords = $DB->get_records_sql($sql, [$this->msocial->id]);
        $this->append_stats('likes', $reactionrecords, $users, $userstats, $reactions);
        $stat = new \stdClass();
        $stat->max_replies = count($replies) == 0 ? 0 : max($replies);
        $stat->max_likes = count($reactions) == 0 ? 0 : max($reactions);
        $stat->max_posts = count($posts) == 0 ? 0 : max($posts);

        $userstats->maximums = $stat;

        return $userstats;
    }

    /**
     * @deprecated
     *
     * @param unknown $pkiname
     * @param unknown $records
     * @param unknown $users
     * @param unknown $userstats
     * @param unknown $accum */
    private function append_stats($pkiname, &$records, $users, &$userstats, &$accum) {
        foreach ($users as $userid) {

            if (!isset($userstats->users[$userid])) {
                $stat = new \stdClass();
            } else {
                $stat = $userstats->users[$userid];
            }
            if (isset($records[$userid])) {
                $accum[] = $records[$userid]->total;
                $stat->{$pkiname} = $records[$userid]->total;
            } else {
                $stat->{$pkiname} = null;
            }
            $userstats->users[$userid] = $stat;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \msocial\msocial_plugin::get_pki_list() */
    public function get_pki_list() {
        $pkiobjs['posts'] = new pki_info('posts', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CALCULATED, social_interaction::POST, 'POST',
                social_interaction::DIRECTION_AUTHOR);
        $pkiobjs['replies'] = new pki_info('replies', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CALCULATED, social_interaction::REPLY, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['likes'] = new pki_info('likes', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CALCULATED, social_interaction::REACTION, 'LIKE',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['reactions'] = new pki_info('reactions', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CALCULATED, social_interaction::REACTION, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['max_posts'] = new pki_info('max_posts', null, pki_info::PKI_AGREGATED, pki_info::PKI_CALCULATED);
        $pkiobjs['max_replies'] = new pki_info('max_replies', null, pki_info::PKI_AGREGATED, pki_info::PKI_CALCULATED);
        $pkiobjs['max_likes'] = new pki_info('max_likes', null, pki_info::PKI_AGREGATED, pki_info::PKI_CALCULATED);
        $pkiobjs['max_reactions'] = new pki_info('max_reactions', null, pki_info::PKI_AGREGATED, pki_info::PKI_CALCULATED);
        return $pkiobjs;
    }

    /**
     * @global $CFG
     * @return string */
    private function get_appid() {
        global $CFG;
        $appid = get_config('msocialconnector_facebook', 'appid');
        return $appid;
    }

    /**
     * @global $CFG
     * @return string */
    private function get_appsecret() {
        global $CFG;
        $appsecret = get_config('msocialconnector_facebook', 'appsecret');
        return $appsecret;
    }

    /**
     * {@inheritdoc}
     *
     * @global moodle_database $DB
     * @return type */
    public function get_connection_token() {
        global $DB;
        if ($this->msocial) {
            $token = $DB->get_record('msocial_facebook_tokens', ['msocial' => $this->msocial->id]);
        } else {
            $token = null;
        }
        return $token;
    }

    /**
     * {@inheritdoc}
     *
     * @global moodle_database $DB
     * @see msocial_connector_plugin::set_connection_token() */
    public function set_connection_token($token) {
        global $DB;
        $token->msocial = $this->msocial->id;
        $record = $DB->get_record('msocial_facebook_tokens', array("msocial" => $this->msocial->id));
        if ($record) {
            $token->id = $record->id;
            $DB->update_record('msocial_facebook_tokens', $token);
        } else {
            $DB->insert_record('msocial_facebook_tokens', $token);
        }
    }

    public function unset_connection_token() {
        global $DB;
        $DB->delete_records('msocial_facebook_tokens', array('msocial' => $this->msocial->id));
        // Remove group selection.
        $this->set_config(self::CONFIG_FBGROUP, '');
    }


    /** Obtiene el numero de reacciones recibidas en el Post, y actaliza el "score" de
     * la persona que escribio el Post
     *
     * @param GraphNode $post facebook post. */
    protected function process_post($post) {
        list($postname, $postid) = $this->userfacebookidfor($post);
        $postinteraction = new social_interaction();
        $postinteraction->uid = $post->getField('id');
        $postinteraction->nativefrom = $postid;
        $postinteraction->nativefromname = $postname;
        $postinteraction->fromid = $this->get_userid($postid);
        $postinteraction->rawdata = $post->asJson();
        $postinteraction->timestamp = $post->getField('created_time', null);
        $postinteraction->type = social_interaction::POST;
        $postinteraction->nativetype = 'POST';
        $message = $post->getField('message'); // TODO: detect post with only photos.
        $postinteraction->description = $message == '' ? 'No text.' : $message;
        $this->register_interaction($postinteraction);
        // Register each reaction as an interaction...
        $reactions = $post->getField('reactions');
        $this->process_reactions($reactions, $postinteraction);
        // $this->addScore($postname, (0.1 * sizeof($reactions)) + 1);
        return $postinteraction;
    }

    /**
     * @param array[]GraphNode $reactions
     * @param social_interaction $parentinteraction */
    protected function process_reactions($reactions, $parentinteraction) {
        if ($reactions) {
            /* @var GraphNode $reaction */
            foreach ($reactions as $reaction) {
                $nativetype = $reaction->getField('type');
                if (!isset($nativetype)) {
                    $nativetype = 'LIKE';
                }
                $reactioninteraction = new social_interaction();
                $reactuserid = $reaction->getField('id');
                $reactioninteraction->fromid = $this->get_userid($reactuserid);
                $reactioninteraction->nativefrom = $reactuserid;
                $reactioninteraction->nativefromname = $reaction->getField('name');
                $reactioninteraction->uid = $parentinteraction->uid . '-' . $reactioninteraction->nativefrom;
                $reactioninteraction->parentinteraction = $parentinteraction->uid;
                $reactioninteraction->nativeto = $parentinteraction->nativefrom;
                $reactioninteraction->toid = $parentinteraction->fromid;
                $reactioninteraction->nativetoname = $parentinteraction->nativefromname;
                $reactioninteraction->type = $reaction->getField('type');
                $reactioninteraction->rawdata = $reaction->asJson();
                $reactioninteraction->timestamp = $parentinteraction->timestamp; // Reactions has no time. Aproximate it.
                $reactioninteraction->type = social_interaction::REACTION;
                $reactioninteraction->nativetype = $nativetype;
                $this->register_interaction($reactioninteraction);
            }
        }
    }

    /** Registra la interacción con la
     * persona a la que contesta si no son la misma persona.
     * El Comment no se registrará como interacción ni se actualizará el "score" de la persona si
     * este es demasiado corto.
     *
     * @param GraphNode $comment
     * @param social_interaction $post */
    protected function process_comment($comment, $postinteraction) {
        list($commentname, $commentid) = $this->userfacebookidfor($comment);
        $message = $comment->getField('message');
        $tooshort = $this->is_short_comment($message);


        // TODO: manage auto-messaging activity.
        $commentinteraction = new social_interaction();
        $commentinteraction->uid = $comment->getField('id');
        $commentinteraction->fromid = $this->get_userid($commentid);
        $commentinteraction->nativefromname = $commentname;
        $commentinteraction->nativefrom = $commentid;
        $commentinteraction->toid = $postinteraction->fromid;
        $commentinteraction->nativeto = $postinteraction->nativefrom;
        $commentinteraction->nativetoname = $postinteraction->nativefromname;
        $commentinteraction->parentinteraction = $postinteraction->uid;
        $commentinteraction->rawdata = $comment->asJson();
        $commentinteraction->timestamp = $comment->getField('created_time', null);
        $commentinteraction->description = $comment->getField('message');
        $this->register_interaction($commentinteraction);
        // Si el comentario es mayor de dos palabras...
        if (!$tooshort) {
            $commentinteraction->type = social_interaction::REPLY;
            $commentinteraction->nativetype = "comment";

            $commentreactions = $comment->getField('likes');
            $this->process_reactions($commentreactions, $commentinteraction);
            $commentreactions = $comment->getField('reactions');
            $this->process_reactions($commentreactions, $commentinteraction);
        } else {
            $commentinteraction->type = social_interaction::REACTION;
            $commentinteraction->nativetype = "short-comment";

            mtrace( ' * Message too short: "' . $message . '".');
        }
        return $commentinteraction;

    }

    /** Classify the text as too short to be relevant
     * TODO: implement relevance logic.
     * @param GraphEdge $comment
     * @return boolean $ok */
    protected function is_short_comment($message) {
        $numwords = str_word_count($message, 0);
        $minwords = $this->get_config(self::CONFIG_MIN_WORDS);
        return ($numwords <= ($minwords == null ? 2 : $minwords));
    }

    /** Gets username and userid of the author of the post.
     * @param GraphNode $in
     * @return array(string,string) $name, $id */
    protected function userfacebookidfor($in) {
        $author = $in->getField('from');
        if ($author !== null) { // User unknown (lack of permissions probably).
            $name = $author->getField('name');
            $id = $author->getField('id');
        } else {
            $name = '';
            $id = null;
        }
        return [$name, $id];
    }
    public function preferred_harvest_intervals () {
        return new harvest_intervals(24 * 3600, 0, 0, 0);
    }
    /**
     * @todo
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;
        require_once('vendor/Facebook/autoload.php');

        $errormessage = null;
        $result = new \stdClass();
        $result->messages = [];
        $result->errors = [];
        // Initialize GraphAPI.
        $groups = explode(',', $this->get_config(self::CONFIG_FBGROUP));
        $appid = $this->get_appid();
        $appsecret = $this->get_appsecret();
        $this->lastinteractions = [];
        foreach ($groups as $groupid) {
            // TODO: Check time configuration in some plattforms workaround: date_default_timezone_set('Europe/Madrid');!
            try {
                /* @var Facebook\Facebook $fb api entry point */
                $fb = new Facebook(['app_id' => $appid, 'app_secret' => $appsecret, 'default_graph_version' => 'v2.7']);
                $token = $this->get_connection_token();
                $fb->setDefaultAccessToken($token->token);
                // Query Facebook...
                $since = '';
                $lastharvest = $this->get_config(self::LAST_HARVEST_TIME);
                if ($lastharvest) {
                    $since = "&since=$lastharvest";
                }
                $response = $fb->get(
                        $groupid .
                                 '?fields=feed{message,name,permalink_url,from,created_time,reactions,' .
                                 'comments{message,from,created_time,likes,comments{message,from,created_time,likes}}},members' .
                                 $since);
                // Mark the token as OK...
                $DB->set_field('msocial_facebook_tokens', 'errorstatus', null, array('id' => $token->id));
                /** @var Facebook\GraphNodes\GraphNode $globalnode*/
                $globalnode = $response->getGraphNode();
                // Get group members...
                /** @var Facebook\GraphNodes\GraphEdge $membersnode*/
                $membersnode = $globalnode->getField('members');
                /** @var Facebook\GraphNodes\Collection $members */
                $members = $membersnode->asArray();
                /** @var Facebook\GraphNodes\GraphEdge $feednode*/
                // Get the feed.
                $feednode = $globalnode->getField('feed');
                /** @var ArrayIterator $posts*/
                // Iterate the posts.
                $posts = $feednode->getIterator();
                while ($posts->valid()) {

                    /* @var Facebook\GraphNodes\GraphNode $post Post in the group. */
                    $post = $posts->current();
                    $postinteraction = $this->process_post($post);

                    /* @var Facebook\GraphNodes\GraphEdge $comments Comments to this post. */
                    $comments = $post->getField('comments');
                    // Process comments...
                    if ($comments) {
                        foreach ($comments as $comment) {
                            $commentinteraction = $this->process_comment($comment, $postinteraction);
                            /* @var $subcomment Facebook\GraphNodes\GraphEdge */
                            $subcomments = $comment->getField('comments');
                            if ($commentinteraction != null && $subcomments) {
                                foreach ($subcomments as $subcomment) {
                                    $this->process_comment($subcomment, $commentinteraction);
                                }
                            }
                        }
                    }
                    // Get next post.
                    $posts->next();
                }
            } catch (\Exception $e) {
                $cm = $this->cm;
                $msocial = $this->msocial;

                $errormessage = "For module msocial\\connection\\facebook: $msocial->name (id=$cm->instance) in course (id=$msocial->course) " .
                         "searching group: $groupid  ERROR:" . $e->getMessage();
                $result->messages[] = $errormessage;
                $result->errors[] = (object) ['message' => $errormessage];
            }
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $this->set_connection_token($token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
            }
        }
        $result = $this->post_harvest($result);
        return $result;
    }

}
