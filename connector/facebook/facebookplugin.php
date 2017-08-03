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
    private $lastinteractions = array();

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialconnector_facebook');
    }

    /**
     * @return true if the plugin is making searches in the social network */
    public function is_tracking() {
        return ($this->is_enabled() && $this->get_connection_token() != null);
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
        $this->drop_pki_fields();
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
     * @global \moodle_database $DB */
    public function render_header() {
        global $OUTPUT, $DB, $USER;
        if ($this->is_enabled()) {
            $context = \context_module::instance($this->cm->id);
            list($course, $cm) = get_course_and_cm_from_instance($this->msocial->id, 'msocial');
            $id = $cm->id;
            if (has_capability('mod/msocial:manage', $context)) {
                $token = $DB->get_record('msocial_facebook_tokens', array('msocial' => $this->msocial->id));
                $urlconnect = new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                        array('id' => $id, 'action' => 'connect'));
                if ($token) {
                    $username = $token->username;
                    $errorstatus = $token->errorstatus;
                    if ($errorstatus) {
                        $this->notify(get_string('problemwithfacebookaccount', 'msocial', $errorstatus), self::NOTIFY_WARNING);
                    }
                    $this->notify(
                            get_string('module_connected_facebook', 'msocialconnector_facebook', $username) . $OUTPUT->action_link(
                                    new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                                            array('id' => $id, 'action' => 'connect')), "Change user") . '/' .
                                     $OUTPUT->action_link(
                                            new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                                                    array('id' => $id, 'action' => 'disconnect')), "Disconnect") . ' ' . $OUTPUT->action_icon(
                                            new \moodle_url('/mod/msocial/harvest.php',
                                                    ['id' => $id, 'subtype' => $this->get_subtype()]),
                                            new \pix_icon('a/refresh', get_string('harvest', 'msocialconnector_facebook'))));
                    // Check facebook group...
                    $fbgroup = $this->get_config(self::CONFIG_FBGROUP);
                    if (trim($fbgroup) === "") {
                        $action = '';
                        if (has_capability('mod/msocial:manage', $context)) {
                            $action = $OUTPUT->action_link(
                                    new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                                            array('id' => $id, 'action' => 'selectgroup')), "Select group");
                        }
                        $this->notify($icondecoration . get_string('fbgroup', 'msocialconnector_facebook') . " : " . $action,
                                self::NOTIFY_WARNING);
                    } else {
                        $groupinfo = '<a target="blank" href="https://www.facebook.com/groups/' .
                                 $this->get_config(self::CONFIG_FBGROUP) . '">' . $this->get_config(self::CONFIG_FBGROUPNAME) . "</a>";
                        $action = '';
                        if (has_capability('mod/msocial:manage', $context)) {
                            $action = $OUTPUT->action_link(
                                    new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                                            array('id' => $id, 'action' => 'selectgroup')), "Change group");
                        }
                        $this->notify(get_string('fbgroup', 'msocialconnector_facebook') . ' : "' . $groupinfo . '" ' . $action);
                    }
                } else {
                    $this->notify(
                            get_string('module_not_connected_facebook', 'msocialconnector_facebook') . $OUTPUT->action_link(
                                    new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                                            array('id' => $id, 'action' => 'connect')), "Connect"), self::NOTIFY_WARNING);
                }
            }
            // Check user's social credentials.
            $socialuserids = $this->get_social_userid($USER);
            if (!$socialuserids) { // Offer to register.
                $urlprofile = new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                        array('id' => $id, 'action' => 'connect', 'type' => 'profile'));
                $facebookadvice = get_string('no_facebook_name_advice2', 'msocialconnector_facebook',
                        ['userid' => $USER->id, 'courseid' => $course->id, 'url' => $urlprofile->out(false)]);
                $this->notify($facebookadvice, self::NOTIFY_WARNING);
            }
        }
    }

    /** Place social-network user information or a link to connect.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user */
    public function render_user_linking($user) {
        global $USER, $COURSE;
        $course = $COURSE;
        $usermessage = '';
        $socialids = $this->get_social_userid($user);
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id);
        if ($socialids == null) { // Offer to register.
            if ($USER->id == $user->id) {
                $urlprofile = new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                        array('id' => $cm->id, 'action' => 'connect', 'type' => 'profile'));
                $usermessage = get_string('no_facebook_name_advice2', 'msocialconnector_facebook',
                        ['userid' => $USER->id, 'courseid' => $course->id, 'url' => $urlprofile->out(false)]);
            } else {
                $usermessage = get_string('no_facebook_name_advice', 'msocialconnector_facebook',
                        ['userid' => $user->id, 'courseid' => $course->id]);
            }
        } else {
            $usermessage = $this->create_user_link($user);
        }
        return $usermessage;
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\connector\msocial_connector_plugin::get_user_url() */
    public function get_user_url($user) {
        $userid = $this->get_social_userid($user);
        if ($userid) {
            $link = $this->get_social_user_url($userid);
        } else {
            $link = null;
        }
        return $link;
    }

    public function get_social_user_url($userid) {
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

    /**
     * @param GraphEdge $groups */
    public function view_group_list(GraphEdge $groups) {
        /** @var Iterator */
        $table = new \html_table();
        $table->head = ['group'];
        $table->headspan = [2, 1];
        $data = [];
        $iter = $groups->getIterator();
        $out = '<ul>';
        while ($iter->valid()) {

            /** @var GraphGroup $group*/
            $group = $iter->current();
            $row = new \html_table_row();
            if ($group->getCover()) {
                $cover = '<img src="' . $group->getCover()->getSource() . '" width="100"/>';
            } else {
                $cover = \html_writer::img($this->get_icon(), $this->get_name());
            }
            $info = '<a target="blank" href="https://www.facebook.com/groups/' . $group->getId() . '">' . $group->getName() . "</a> " .
                     $group->getDescription();
            $url = new \moodle_url('/mod/msocial/connector/facebook/facebookSSO.php',
                    ['id' => $this->cm->id, 'gid' => $group->getId(), 'action' => 'setgroup', 'gname' => $group->getName()]);
            $action = \html_writer::link($url, get_string('selectthisgroup', 'msocialconnector_facebook'));
            $row->cells = [$cover, $info, $action];
            $table->data[] = $row;
            $iter->next();
        }
        return \html_writer::table($table);
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
        $sql = "SELECT fromid as userid, count(*) as total from {msocial_interactions} where msocial=? and source='$subtype' and type='post' and fromid IS NOT NULL group by fromid";
        $postsrecords = $DB->get_records_sql($sql, [$this->msocial->id]);
        $this->append_stats('posts', $postsrecords, $users, $userstats, $posts);
        $sql = "SELECT toid as userid, count(*) as total from {msocial_interactions} where msocial=? and source='$subtype' and type='reply' and toid IS NOT NULL group by toid";
        $replyrecords = $DB->get_records_sql($sql, [$this->msocial->id]);
        $this->append_stats('replies', $replyrecords, $users, $userstats, $replies);
        $sql = "SELECT fromid as userid, count(*) as total from {msocial_interactions} where msocial=? and source='$subtype' and type='reaction' and toid IS NOT NULL group by toid";
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
        $pkiobjs['posts'] = new pki_info('posts', null, pki_info::PKI_INDIVIDUAL, social_interaction::POST, 'POST',
                social_interaction::DIRECTION_AUTHOR);
        $pkiobjs['replies'] = new pki_info('replies', null, pki_info::PKI_INDIVIDUAL, social_interaction::REPLY, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['likes'] = new pki_info('likes', null, pki_info::PKI_INDIVIDUAL, social_interaction::REACTION, 'nativetype = "LIKE"',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['reactions'] = new pki_info('likes', null, pki_info::PKI_INDIVIDUAL, social_interaction::REACTION, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['max_posts'] = new pki_info('max_posts', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_replies'] = new pki_info('max_replies', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_likes'] = new pki_info('max_likes', null, pki_info::PKI_AGREGATED);
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

    protected function store_interactions(array $interactions) {
        $msocialid = $this->msocial->id;
        social_interaction::store_interactions($interactions, $msocialid);
    }

    /**
     * @param social_interaction $interaction */
    public function register_interaction(social_interaction $interaction) {
        $interaction->source = $this->get_subtype();
        $this->lastinteractions[] = $interaction;
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
                $reactioninteraction->nativetoname = $parentinteraction->nativetoname;
                $reactioninteraction->type = $reaction->getField('type');
                $reactioninteraction->rawdata = $reaction->asJson();
                $reactioninteraction->timestamp = null;
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

        $tooshort = $this->is_short_comment($comment->getField('message'));

        // Si el comentario es mayor de dos palabras...
        if (!$tooshort) {
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
            $commentinteraction->type = social_interaction::REPLY;
            $commentinteraction->nativetype = "comment";
            $commentinteraction->description = $comment->getField('message');
            $this->register_interaction($commentinteraction);

            // $matrix->addScore($commentname, 1 + (sizeof($comment_reactions) * 0.1));
            $commentreactions = $comment->getField('likes');
            $this->process_reactions($commentreactions, $commentinteraction);
            $commentreactions = $comment->getField('reactions');
            $this->process_reactions($commentreactions, $commentinteraction);
            return $commentinteraction;
        }
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
        $in->getField('from');
        $author = $in->getField('from');
        $name = $author->getField('name');
        $id = $author->getField('id');
        return [$name, $id];
    }

    /**
     * @todo
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;
        require_once ('vendor/Facebook/autoload.php');

        $errormessage = null;
        $result = new \stdClass();
        $result->messages = [];
        $result->errors = [];
        // Initialize GraphAPI.
        $groupid = $this->get_config(self::CONFIG_FBGROUP);
        $appid = $this->get_appid();
        $appsecret = $this->get_appsecret();
        $this->lastinteractions = [];
        // TODO: Check time configuration in some plattforms workaround:
        // date_default_timezone_set('Europe/Madrid');!
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
                             '?fields=feed{message,name,permalink_url,from,created_time,reactions,comments{message,from,created_time,likes,comments{message,from,created_time,likes}}},members' .
                             $since);
            // Mark the token as OK...
            $DB->set_field('msocial_facebook_tokens', 'errorstatus', null, array('id' => $token->id));
            /* @var Facebook\GraphNodes\GraphNode $globalnode*/
            $globalnode = $response->getGraphNode();
            // Get group members...
            /* @var Facebook\GraphNodes\GraphEdge $membersnode*/
            $membersnode = $globalnode->getField('members');
            /* @var Facebook\GraphNodes\Collection $members */
            $members = $membersnode->asArray();
            /* @var Facebook\GraphNodes\GraphEdge $feednode*/
            // Get the feed.
            $feednode = $globalnode->getField('feed');
            /* @var ArrayIterator $posts*/
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
                        if ($subcomments) {
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

            $errormessage = "For module msocial\social\facebook: $msocial->name (id=$cm->instance) in course (id=$msocial->course) " .
                     "searching group: $groupid  ERROR:" . $e->getMessage();
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
        $contextcourse = \context_course::instance($this->msocial->course);
        list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
        $pkis = $this->calculate_pkis($users);
        $this->store_pkis($pkis, true);
        $this->set_config(\mod_msocial\connector\msocial_connector_plugin::LAST_HARVEST_TIME, time());

        $logmessage = "For module msocial: \"" . $this->msocial->name . "\" (id=" . $this->msocial->id . ") in course (id=" .
                 $this->msocial->course . ")  Found " . count($this->lastinteractions) . " events. Students' events: " .
                 count($studentinteractions);
        $result->messages[] = $logmessage;

        if ($token) {
            $token->errorstatus = $errormessage;
            $this->set_connection_token($token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
            }
        }
        return $result;
    }
}