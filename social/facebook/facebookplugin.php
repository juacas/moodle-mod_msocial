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

use mod_tcount\social\pki as pki;
use Facebook\GraphNodes\GraphEdge;
use tcount\tcount_plugin;
use Facebook\Facebook as Facebook;
use Facebook\GraphNodes\GraphNode;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once ('Facebook/autoload.php');


/**
 * library class for social network facebook plugin extending social plugin base class
 *
 * @package tcountsocial_facebook
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_social_facebook extends tcount_social_plugin {

    const CONFIG_FBSEARCH = 'fbsearch';

    const CONFIG_FBGROUP = 'fbgroup';

    const CONFIG_FBGROUPNAME = 'fbgroupname';

    private $min_words = 0;

    private $lastinteractions = array();

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountsocial_facebook');
    }

    /**
     *
     * @return true if the plugin is making searches in the social network
     */
    public function is_tracking() {
        return $this->is_enabled() && $this->get_connection_token() != null &&
                 trim($this->get_config(tcount_social_facebook::CONFIG_FBSEARCH)) != "";
    }

    private function get_userid_fieldname() {
        $fieldname = $this->get_config(tcount_social_facebook::CONFIG_FBFIELDID);
        if (!$fieldname) {
            throw new Exception("Fatal error. Contact your administrator. Custom field need to be configured.");
        }
        return $fieldname;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \mod_tcount\social\tcount_social_plugin::set_social_userid()
     */
    public function set_social_userid($user, $socialid, $socialname) {
        // @deprecated TODO: remove use of userprofiles.
        $fieldid = $this->get_config(tcount_social_facebook::CONFIG_FBFIELDID);
        tcount_set_user_field_value($user, $fieldid, $socialid . '|' . $socialname);
        global $DB;
        $record = $DB->get_record('tcount_facebook_mapusers', ['tcount' => $this->tcount->id, 'userid' => $user->id]);
        if ($record === false) {
            $record = new \stdClass();
        }
        $record->tcount = $this->tcount->id;
        $record->userid = $user->id;
        $record->facebookid = $socialid;
        $record->facebookname = $socialname;
        if (isset($record->id)) {
            $DB->update_record('tcount_facebook_mapusers', $record);
        } else {
            $DB->insert_record('tcount_facebook_mapusers', $record);
        }
        $this->user_to_social_mapping = null;
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues[$this->get_form_field_name(tcount_plugin::CONFIG_ENABLED)] = $this->get_config(tcount_plugin::CONFIG_ENABLED);
        return;
    }

    /**
     * Get the instance settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(\MoodleQuickForm $mform) {
        
        // $mform->addElement('text', $this->get_form_field_name(self::CONFIG_FBSEARCH),
        // get_string("fbsearch", "tcountsocial_facebook"), array('size' => '20'));
        // $mform->setType($this->get_form_field_name(self::CONFIG_FBSEARCH), PARAM_TEXT);
        // $mform->addHelpButton($this->get_form_field_name(self::CONFIG_FBSEARCH), 'fbsearch',
        // 'tcountsocial_facebook');
        $mform->addElement('static', 'config_group', get_string('fbgroup', 'tcountsocial_facebook'),get_string('connectgroupinpage', 'tcountsocial_facebook'));
    }

    /**
     * Save the settings for facebook plugin
     *
     * @param \stdClass $data
     * @return bool
     */
    public function save_settings(\stdClass $data) {
        if (isset($data->{$this->get_form_field_name(self::CONFIG_ENABLED)})) {
            $this->set_config(tcount_plugin::CONFIG_ENABLED, $data->{$this->get_form_field_name(self::CONFIG_ENABLED)});
        }
        return true;
    }

    /**
     * The tcount has been deleted - cleanup subplugin
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (!$DB->delete_records('tcount_facebook_posts', array('tcount' => $this->tcount->id))) {
            $result = false;
        }
        if (!$DB->delete_records('tcount_facebook_tokens', array('tcount' => $this->tcount->id))) {
            $result = false;
        }
        return $result;
    }

    public function get_subtype() {
        return 'facebook';
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
        return new \moodle_url('/mod/tcount/social/facebook/pix/Facebook_icon.png');
    }

    /**
     *
     * @global \core_renderer $OUTPUT
     * @global \moodle_database $DB
     */
    public function view_header() {
        global $OUTPUT, $DB, $USER;
        if ($this->is_enabled()) {
            $context = \context_module::instance($this->cm->id);
            list($course, $cm) = get_course_and_cm_from_instance($this->tcount->id, 'tcount');
            $id = $cm->id;
            $token = $DB->get_record('tcount_facebook_tokens', array('tcount' => $this->tcount->id));
            $url_connect = new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', array('id' => $id, 'action' => 'connect'));
            if ($token) {
                $username = $token->username;
                $errorstatus = $token->errorstatus;
                if ($errorstatus) {
                    echo $OUTPUT->notify_problem(get_string('problemwithfacebookaccount', 'tcount', $errorstatus));
                }
                echo $OUTPUT->box(
                        get_string('module_connected_facebook', 'tcountsocial_facebook', $username) . $OUTPUT->action_link(
                                new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                                        array('id' => $id, 'action' => 'connect')), "Change user") . '/' . $OUTPUT->action_link(
                                new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                                        array('id' => $id, 'action' => 'disconnect')), "Disconnect") . ' ' . $OUTPUT->action_icon(
                                new \moodle_url('/mod/tcount/social/harvest.php', ['id' => $id, 
                                                'subtype' => $this->get_subtype()]), 
                                new \pix_icon('a/refresh', get_string('harvest', 'tcountsocial_facebook'))));
            } else {
                echo $OUTPUT->notification(
                        get_string('module_not_connected_facebook', 'tcountsocial_facebook') . $OUTPUT->action_link(
                                new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                                        array('id' => $id, 'action' => 'connect')), "Connect"));
            }
            // Check user's social credentials.
            $socialuserids = $this->get_social_userid($USER);
            if (!$socialuserids) { // Offer to register.
                $url_profile = new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                        array('id' => $id, 'action' => 'connect', 'type' => 'profile'));
                $facebookadvice = get_string('no_facebook_name_advice2', 'tcountsocial_facebook', 
                        ['field' => $this->get_userid_fieldname(), 'userid' => $USER->id, 'courseid' => $course->id, 
                                        'url' => $url_profile->out(false)]);
                echo $OUTPUT->notification($facebookadvice);
            }
            // Check facebook group...
            $fbgroup = $this->get_config(self::CONFIG_FBGROUP);
            if (trim($fbgroup) === "") {
                $action = '';
                if (has_capability('mod/tcount:manage', $context)) {
                    $action = $OUTPUT->action_link(
                            new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                                    array('id' => $id, 'action' => 'selectgroup')), "Select group");
                }
                echo $OUTPUT->notification("No hay grupo seleccionado." . $action);
            } else {
                $groupinfo = '<a target="blank" href="https://www.facebook.com/groups/' . $this->get_config(self::CONFIG_FBGROUP) .
                         '">' . $this->get_config(self::CONFIG_FBGROUPNAME) . "</a>";
                $action = '';
                if (has_capability('mod/tcount:manage', $context)) {
                    $action = $OUTPUT->action_link(
                            new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                                    array('id' => $id, 'action' => 'selectgroup')), "Change group");
                }
                echo $OUTPUT->box('Grupo seleccionado:"' . $groupinfo . '" ' . $action, 'generalbox', 'intro');
            }
        }
    }

    /**
     * Place social-network user information or a link to connect.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user
     */
    public function view_user_linking($user) {
        global $USER, $COURSE;
        $course = $COURSE;
        $usermessage = '';
        $socialids = $this->get_social_userid($user);
        $cm = get_coursemodule_from_instance('tcount', $this->tcount->id);
        if ($socialids == null) { // Offer to register.
            if ($USER->id == $user->id) {
                $url_profile = new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                        array('id' => $cm->id, 'action' => 'connect', 'type' => 'profile'));
                $usermessage = get_string('no_facebook_name_advice2', 'tcountsocial_facebook', 
                        ['field' => $this->get_userid_fieldname(), 'userid' => $USER->id, 'courseid' => $course->id, 
                                        'url' => $url_profile->out(false)]);
            } else {
                $usermessage = get_string('no_facebook_name_advice', 'tcountsocial_facebook', 
                        ['field' => $this->get_userid_fieldname(), 'userid' => $user->id, 'courseid' => $course->id]);
            }
        } else {
            $usermessage = $this->create_user_link($socialids->socialname);
        }
        return $usermessage;
    }

    function get_interaction_url(social_interaction $interaction) {
        // /groups/1670848226578336/permalink/1670848496578309/?comment_id=1670848556578303
        $parts = explode('_', $interaction->uid);
        if (count($parts) == 2) {
            $url = 'https://www.facebook.com/groups/' . $parts[0] . '/permalink/' . $parts[1];
        } else {
            $url = 'https://www.facebook.com/groups/' . $this->get_config(self::CONFIG_FBGROUP) . '/permalink/' . $parts[0];
        }
        
        return $url;
    }

    /**
     *
     * @param GraphEdge $groups
     */
    function view_group_list(GraphEdge $groups) {
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
            $url = new \moodle_url('/mod/tcount/social/facebook/facebookSSO.php', 
                    ['id' => 66, 'gid' => $group->getId(), 'action' => 'setgroup', 'gname' => $group->getName()]);
            $action = \html_writer::link($url, get_string('selectthisgroup', 'tcountsocial_facebook'));
            $row->cells = [$cover, $info, $action];
            $table->data[] = $row;
            $iter->next();
        }
        return \html_writer::table($table);
    }

    /**
     *
     * @param type $username string with the format screenname|userid
     */
    function create_user_link($username) {
        $parts = explode('|', $username);
        $screenname = $parts[0];
        $userid = isset($parts[1]) ? $parts[1] : $screenname;
        $link = "https://www.facebook.com/$userid";
        $icon = "social/facebook/pix/facebook_icon.png";
        return "<a href=\"$link\"><img src=\"$icon\"/> $screenname</a>";
    }

    /**
     * Statistics for grading
     *
     * @param array[]integer $users array with the userids to be calculated
     * @return array[string]object object->userstats with PKIs for each user object->maximums max
     *         values for normalization.
     */
    public function calculate_stats($users) {
        global $DB;
        echo ('TODO: Implement calculate_stats for facebook');
        
        // $cm = get_coursemodule_from_instance('tcount', $this->tcount->id, 0, false, MUST_EXIST);
        // $stats = $DB->get_records_sql(
        // 'SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as
        // favs ' .
        // 'FROM {tcount_tweets} where tcount = ? and userid is not null group by userid',
        // array($this->tcount->id));
        $stats = [];
        $userstats = new \stdClass();
        $userstats->users = array();
        
        $favs = array();
        $retweets = array();
        $tweets = array();
        foreach ($users as $userid) {
            $stat = new \stdClass();
            
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
        $stat = new \stdClass();
        $stat->retweets = 0;
        $stat->tweets = count($tweets) == 0 ? 0 : max($tweets);
        $stat->favs = count($favs) == 0 ? 0 : max($favs);
        $stat->retweets = count($retweets) == 0 ? 0 : max($retweets);
        $userstats->maximums = $stat;
        
        return $userstats;
    }

    public function get_pki_list() {
        $pkiobjs['posts'] = new pki('posts');
        $pkiobjs['replies'] = new pki('replies');
        $pkiobjs['likes'] = new pki('likes');
        $pkiobjs['shares'] = new pki('shares');
        $pkiobjs['max_posts'] = new pki('max_posts', null, pki::PKI_AGREGATED);
        $pkiobjs['max_replies'] = new pki('max_replies', null, pki::PKI_AGREGATED);
        $pkiobjs['max_likes'] = new pki('max_favs', null, pki::PKI_AGREGATED);
        $pkiobjs['max_shares'] = new pki('max_shares', null, pki::PKI_AGREGATED);
        return $pkiobjs;
    }

    /**
     *
     * @global $CFG
     * @return string
     */
    private function get_appid() {
        global $CFG;
        $appid = $CFG->mod_tcount_facebook_appid;
        return $appid;
    }

    /**
     *
     * @global $CFG
     * @return string
     */
    private function get_appsecret() {
        global $CFG;
        $appsecret = $CFG->mod_tcount_facebook_appsecret;
        return $appsecret;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @global moodle_database $DB
     * @return type
     */
    public function get_connection_token() {
        global $DB;
        if ($this->tcount) {
            $token = $DB->get_record('tcount_facebook_tokens', ['tcount' => $this->tcount->id]);
        } else {
            $token = null;
        }
        return $token;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @global moodle_database $DB
     * @see tcount_social_plugin::set_connection_token()
     */
    public function set_connection_token($token) {
        global $DB;
        $token->tcount = $this->tcount->id;
        $record = $DB->get_record('tcount_facebook_tokens', array("tcount" => $this->tcount->id));
        if ($record) {
            $token->id = $record->id;
            $DB->update_record('tcount_facebook_tokens', $token);
        } else {
            $DB->insert_record('tcount_facebook_tokens', $token);
        }
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
     * Obtiene el numero de reacciones recibidas en el Post, y actaliza el "score" de
     * la persona que escribio el Post
     *
     * @param GraphNode $post facebook post.
     */
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
     *
     * @param array[]GraphNode $reactions
     * @param social_interaction $parentinteraction
     */
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

    /**
     * Registra la interacción con la
     * persona a la que contesta si no son la misma persona.
     * El Comment no se registrará como interacción ni se actualizará el "score" de la persona si
     * este es demasiado corto.
     *
     * @param GraphNode $comment
     * @param social_interaction $post
     */
    function process_comment($comment, $postinteraction) {
        list($commentname, $commentid) = $this->userfacebookidfor($comment);
        
        $tooshort = $this->is_short_comment($comment->getField('message'));
        
        // Si el comentario es mayor de dos palabras...
        if (!$tooshort) {
            // Si la persona que escribe el comentario es la misma que escribio el post NO se
            // actualiza la matriz
            // if (strcmp($commentid, $postinteraction->nativefrom) != 0)
            {
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
                $comment_reactions = $comment->getField('likes');
                $this->process_reactions($comment_reactions, $commentinteraction);
                $comment_reactions = $comment->getField('reactions');
                $this->process_reactions($comment_reactions, $commentinteraction);
                return $commentinteraction;
            }
        }
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
     * Devuelve el nombre del autor de un comment o un post, o bien de un array de miembros
     * ¿¿¿¿¿¿¿¿ Si $in no es ninguno de los anteriores devuelve $in ???????
     *
     * @param array member / GraphNode $in
     * @return string $in['name'] / $name
     */
    protected function userfacebookidfor($in) {
        if ($in->getField('from')) {
            $author = $in->getField('from');
            $name = $author->getField('name');
            $id = $author->getField('id');
        } else if (isset($in['name'])) { // $in is a member array
            $name = $in['name'];
            $id = $in['id'];
        } else {
            throw new Exception('Objeto de tipo desconocido: ' . var_dump($in));
        }
        return [$name, $id];
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
        // Initialize GraphAPI.
        $groupid = $this->get_config(self::CONFIG_FBGROUP);
        $appid = $this->get_appid();
        $appsecret = $this->get_appsecret();
        $this->lastinteractions = [];
        // date_default_timezone_set('Europe/Madrid');
        try {
            /* @var Facebook\Facebook $fb api entry point */
            $fb = new Facebook(['app_id' => $appid, 'app_secret' => $appsecret, 'default_graph_version' => 'v2.7']);
            $token = $this->get_connection_token();
            $fb->setDefaultAccessToken($token->token);
            // Query Facebook...
            $since = '';
            $lastharvest = $this->get_config(LAST_HARVEST);
            if ($lastharvest) {
                $since = "&since=$lastharvest";
            }
            $response = $fb->get(
                    $groupid .
                             '?fields=feed{message,name,permalink_url,from,created_time,reactions,comments{message,from,created_time,likes,comments{message,from,created_time,likes}}},members' .
                             $since);
            // Mark the token as OK...
            $DB->set_field('tcount_facebook_tokens', 'errorstatus', null, array('id' => $token->id));
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
            $errormessage = "For module tcount\social\facebook: $this->tcount->name (id=$this->cm->instance) in course (id=$this->tcount->course) " .
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
        
        $logmessage = "For module tcount: " . $this->tcount->name . " (id=" . $this->tcount->id . ") in course (id=" .
                 $this->tcount->course . ")  Found " . count($this->lastinteractions) . " events. Students' events: " .
                 count($studentinteractions);
        $result->messages[] = $logmessage;
        // $contextcourse = \context_course::instance($this->tcount->course);
        // list($students, $nonstudents, $active, $users) =
        // eduvalab_get_users_by_type($contextcourse);
        
        // TODO: implements grading with plugins.
        // tcount_update_grades($this->tcount, $students);
        // } else {
        // $errormessage = "ERROR querying facebook results null! Maybe there is no facebook account
        // linked in this activity.";
        // $result->errors[0]->message = $errormessage;
        // $result->messages[] = "For module tcount: $this->tcount->name (id=$this->tcount->id) in
        // course (id=$this->tcount->course) " .
        // $errormessage;
        // }
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
