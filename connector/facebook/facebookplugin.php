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
use mod_msocial\kpi_info;
use msocial\msocial_plugin;
use mod_msocial\social_user;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/classes/msocialconnectorplugin.php');
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
     *
     * {@inheritDoc}
     * @see \msocial\msocial_plugin::can_harvest()
     */
    public function can_harvest() {
        return $this->is_enabled() &&
        $this->get_connection_token() != null &&
        $this->get_config(self::CONFIG_FBGROUP) != null;
    }
    /** Get the instance settings for the plugin
     *
     * @param \MoodleQuickForm $mform The form to add elements to
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
        if (!$DB->delete_records('msocial_interactions', array('msocial' => $this->msocial->id,
                                'source' => $this->get_subtype()))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_facebook_tokens', array('msocial' => $this->msocial->id))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_mapusers', array('msocial' => $this->msocial->id, 'type' => $this->get_subtype()))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_plugin_config', array('msocial' => $this->msocial->id,
                                'subtype' => $this->get_subtype()))) {
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
                        $connectlink = $OUTPUT->action_link(new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                array('id' => $id, 'action' => 'connect')), " <b>Re-link user!!</b>");
                        $notifications[] = '' . get_string('problemwithfacebookaccount', 'msocialconnector_facebook',
                                        $errorstatus) . $connectlink;
                    } else {
                        $connectlink = $OUTPUT->action_link(new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                array('id' => $id, 'action' => 'connect')), "Change user");
                        $messages[] = get_string('module_connected_facebook', 'msocialconnector_facebook',
                                            $username) . $connectlink . '/' .
                            $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                    array('id' => $id, 'action' => 'disconnect')), " Disconnect") . ' ';
                    }
                } else {
                    $notifications[] = get_string('module_not_connected_facebook', 'msocialconnector_facebook') . ":" .
                            $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
                                    array('id' => $id, 'action' => 'connect')), " <b>Connect</b>");
                }
            }
            if ($token) {
                // Check facebook group...
                $fbgroup = $this->get_config(self::CONFIG_FBGROUP);
                $action = '';
                if (has_capability('mod/msocial:manage', $context)) {
                    $action = $OUTPUT->action_link(
                            new \moodle_url('/mod/msocial/connector/facebook/groupchoice.php',
                                    array('id' => $id, 'action' => 'selectgroup')), " <b>Select group</b>");
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
        }
        return [$messages, $notifications];
    }
    public function render_harvest_link() {
        global $OUTPUT;
        $harvestbutton = $OUTPUT->action_icon(
                new \moodle_url('/mod/msocial/harvest.php', ['id' => $this->cm->id, 'subtype' => $this->get_subtype()]),
                new \pix_icon('a/refresh', get_string('harvest', 'msocialconnector_facebook')));
        return $harvestbutton;
    }
    public function render_groups_links() {
        $groupstruct = $this->get_config(self::CONFIG_FBGROUPNAME);
        $groups = json_decode($groupstruct);
        $linkinfo = [];
        if ($groups) {
            foreach ($groups as $groupid => $group) {
                $groupname = $group->name;
                $groupurl = $group->url;
                $linkinfo[] = \html_writer::link($groupurl, $groupname);
            }
        }
        return $linkinfo;
    }

    public function get_social_user_url(social_user $userid) {
        return $userid->sociallink;
    }

    public function get_interaction_url(social_interaction $interaction) {
        // Facebook uid for a comment is generated with group id and comment id.
        $parts = explode('_', $interaction->uid);
        if (count($parts) == 2) {
            if ($interaction->type == social_interaction::REACTION) {
                $likeparts = explode('-', $parts[1]);
                $url = 'https://www.facebook.com/groups/' . $parts[0] . '/permalink/' . $likeparts[0];
            } else {
                $url = 'https://www.facebook.com/groups/' . $parts[0] . '/permalink/' . $parts[1];
            }
        } else {
            $url = 'https://www.facebook.com/groups/' . $this->get_config(self::CONFIG_FBGROUP) . '/permalink/' . $parts[0];
        }

        return $url;
    }
    public function get_interaction_description(social_interaction $interaction) {
        if ($interaction->description == '' && $interaction->type == social_interaction::REACTION) {
          return $interaction->nativetype;
        } else {
            return $interaction->description;
        }
    }
    /** Statistics for grading
     *
     * @param array[]integer $users array with the userids to be calculated
     * @return array[string]object object->userstats with KPIs for each user object->maximums max
     *         values for normalization.
     * @deprecated */
    private function calculate_stats($users) {
        global $DB;
        $userstats = new \stdClass();
        $userstats->users = array();
        $kpinames = $this->get_kpi_list();
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
     * @param string $kpiname
     * @param \stdClass[] $records
     * @param \stdClass[] $users
     * @param \stdClass $userstats
     * @param number[] $accum */
    private function append_stats($kpiname, &$records, $users, &$userstats, &$accum) {
        foreach ($users as $userid) {

            if (!isset($userstats->users[$userid])) {
                $stat = new \stdClass();
            } else {
                $stat = $userstats->users[$userid];
            }
            if (isset($records[$userid])) {
                $accum[] = $records[$userid]->total;
                $stat->{$kpiname} = $records[$userid]->total;
            } else {
                $stat->{$kpiname} = null;
            }
            $userstats->users[$userid] = $stat;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \msocial\msocial_plugin::get_kpi_list() */
    public function get_kpi_list() {
        $kpiobjs['posts'] = new kpi_info('posts', get_string('kpi_description_posts', 'msocialconnector_facebook'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED,
                [social_interaction::POST, social_interaction::REPLY], '*',
                social_interaction::DIRECTION_AUTHOR);
        $kpiobjs['replies'] = new kpi_info('replies', get_string('kpi_description_replies', 'msocialconnector_facebook'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED, social_interaction::REPLY, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['likes'] = new kpi_info('likes', get_string('kpi_description_likes', 'msocialconnector_facebook'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED, social_interaction::REACTION, 'LIKE|LOVE|WOW|HAHA|THANKFUL',
                social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['dislikes'] = new kpi_info('dislikes', get_string('kpi_description_dislikes', 'msocialconnector_facebook'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED, social_interaction::REACTION, 'SAD|ANGRY',
                social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['reactions'] = new kpi_info('reactions', get_string('kpi_description_reactions', 'msocialconnector_facebook'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CALCULATED, social_interaction::REACTION, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $kpiobjs['max_posts'] = new kpi_info('max_posts', null, kpi_info::KPI_AGREGATED, kpi_info::KPI_CALCULATED);
        $kpiobjs['max_replies'] = new kpi_info('max_replies', null, kpi_info::KPI_AGREGATED, kpi_info::KPI_CALCULATED);
        $kpiobjs['max_likes'] = new kpi_info('max_likes', null, kpi_info::KPI_AGREGATED, kpi_info::KPI_CALCULATED);
        $kpiobjs['max_dislikes'] = new kpi_info('max_dislikes', null, kpi_info::KPI_AGREGATED, kpi_info::KPI_CALCULATED);
        $kpiobjs['max_reactions'] = new kpi_info('max_reactions', null, kpi_info::KPI_AGREGATED, kpi_info::KPI_CALCULATED);
        return $kpiobjs;
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
     * @return \stdClass token record */
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
        if (empty($token->errorstatus)) {
            $token->errorstatus = null;
        }
        if ($record) {
            $token->id = $record->id;
            $DB->update_record('msocial_facebook_tokens', $token);
        } else {
            $DB->insert_record('msocial_facebook_tokens', $token);
        }
    }
    /**
     * 
     * {@inheritDoc}
     * @see \msocial\msocial_plugin::reset_userdata()
     */
    public function reset_userdata(\stdClass $data) {
        // Facebook connection token is only for the teacher. Preserve it.
        
        // Remove mapusers.
        global $DB;
        $msocial = $this->msocial;
        $DB->delete_records('msocial_mapusers',['msocial' => $msocial->id, 'type' => $this->get_subtype()]);
        return array('component'=>$this->get_name(), 'item'=>get_string('resetdone', 'msocial', 
                "MSOCIAL $msocial->id: map of users"), 'error'=>false);
    }
    /**
     * 
     * {@inheritDoc}
     * @see \mod_msocial\connector\msocial_connector_plugin::unset_connection_token()
     */
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

            mtrace( '<li> Message too short: "' . $message . '".');
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
                                 'comments{message,from,created_time,likes,comments{message,from,created_time,likes}}}' .
                                 $since);
                // Mark the token as OK...
                $DB->set_field('msocial_facebook_tokens', 'errorstatus', null, array('id' => $token->id));
                /** @var Facebook\GraphNodes\GraphNode $globalnode*/
                $globalnode = $response->getGraphNode();
                // Get group members...
                /** @var Facebook\GraphNodes\GraphEdge $membersnode*/
//                 $membersnode = $globalnode->getField('members');
                /** @var Facebook\GraphNodes\Collection $members */
//                 $members = $membersnode->asArray();
                /** @var Facebook\GraphNodes\GraphEdge $feednode*/
                // Get the feed.
                $feednode = $globalnode->getField('feed');
                if (empty($feednode)) {
                    throw new \Exception("Feed can't be retrieved using token for user $token->username. Possibly user has no enough privileges.");
                }
                // Iterate the posts.
                /** @var ArrayIterator $posts*/
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
            // TODO: Move this to postharvest. Send message to teachers.
        }
        $result = $this->post_harvest($result);
        return $result;
    }

}
