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
namespace mod_tcount\view;

use tcount\tcount_plugin;

defined('MOODLE_INTERNAL') || die();


/**
 * library class for view the network activity as a table extending view plugin base class
 *
 * @package tcountview_timeglider
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_view_timeglider extends tcount_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountview_timeglider');
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues['tcountview_timeglider_enabled'] = $this->get_config('enabled');
        return;
    }

    /**
     * Get the settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(\MoodleQuickForm $mform) {
    }

    /**
     * Save the settings for table plugin
     *
     * @param \stdClass $data
     * @return bool
     */
    public function save_settings(\stdClass $data) {
        if (isset($data->tcountview_timeglider_enabled)) {
            $this->set_config('enabled', $data->tcountview_timeglider_enabled);
        }
        return true;
    }

    /**
     * The tcount has been deleted - cleanup subplugin
     *
     * @global moodle_database $DB
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        return $result;
    }
public function get_category(){
    return tcount_plugin::CAT_VISUALIZATION;
}
    public function get_subtype() {
        return 'timeglider';
    }

    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/timeglider/pix/icon.svg');
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
        $cm = get_coursemodule_from_instance('tcount', $this->tcount->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql(
                'SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs ' .
                         'FROM {tcount_tweets} where tcount = ? and userid is not null group by userid', array($this->tcount->id));
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
        $pkis = [];
        return $pkis;
    }

    /**
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        global $DB;
        $result = (object) ['messages' => []];
        // $result = $this->get_statuses($this->tcount);
        // $token = $this->get_connection_token();
        // $hashtag = $this->get_config('hashtag');
        
        // if (isset($result->errors)) {
        // if ($token) {
        // $info = "UserToken for:$token->username ";
        // } else {
        // $info = "No table token defined!!";
        // }
        // $errormessage = $result->errors[0]->message;
        // $errormessage = "For module tcount: $this->tcount->name (id=$cm->instance) in course
        // (id=$this->tcount->course) " .
        // "searching: $hashtag $info ERROR:" . $errormessage;
        // $result->messages[] = $errormessage;
        // } else if (isset($result->statuses)) {
        // $DB->set_field('tcount_table_tokens', 'errorstatus', null, array('id' => $token->id));
        // $statuses = count($result->statuses) == 0 ? array() : $result->statuses;
        // $tcount = $this->tcount;
        
        // $processedstatuses = $this->process_statuses($statuses, $this->tcount);
        // $studentstatuses = array_filter($processedstatuses,
        // function ($status) {
        // return isset($status->userauthor);
        // });
        // $this->store_status($studentstatuses);
        // $result->messages[] = "For module tcount: $tcount->name (id=$tcount->id) in course
        // (id=$tcount->course) searching: " .
        // $hashtag . " Found " . count($statuses) . " tweets. Students' tweets: " .
        // count($studentstatuses);
        // $contextcourse = \context_course::instance($this->tcount->course);
        // list($students, $nonstudents, $active, $users) =
        // eduvalab_get_users_by_type($contextcourse);
        
        // // TODO: implements grading with plugins.
        // // tcount_update_grades($this->tcount, $students);
        // $errormessage = null;
        // } else {
        // $errormessage = "ERROR querying table results null! Maybe there is no tweeter account
        // linked in this activity.";
        // $result->errors[0]->message = $errormessage;
        // $result->messages[] = "For module tcount: $this->tcount->name (id=$this->tcount->id) in
        // course (id=$this->tcount->course) searching: $this->tcount->hashtag " .
        // $errormessage;
        // }
        // if ($token) {
        // $token->errorstatus = $errormessage;
        // $DB->update_record('tcount_table_tokens', $token);
        // if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
        // $message = "Uptatind token with id = $token->id with $errormessage";
        // $result->errors[] = (object) ['message' => $message];
        // $result->messages[] = $message;
        // }
        // }
        return $result;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see tcount_view_plugin::render_view()
     */
    public function render_view($renderer, $reqs) {
       echo "<style type='text/css'>
        /* timeline div style */
		#my-timeglider {
			width:750px;
			margin:32px auto 32px auto;
			height:800px;
		}
</style>";
        echo '<div id="my-timeglider" ></div>';
        echo $renderer->spacer(array('height' => 20));
        $reqs->js('/mod/tcount/view/timeglider/js/init_timeglider.js');
        $reqs->js_init_call("init_timeglider", [$this->cm->id, null], false);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see tcount_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            $reqs->css('/mod/tcount/view/timeglider/css/aristo/jquery-ui-1.8.5.custom.css');
            
            $reqs->css('/mod/tcount/view/timeglider/js/timeglider/Timeglider.css');
            $reqs->js('/mod/tcount/view/timeglider/js/jquery-1.4.4.min.js',true);
            $reqs->js('/mod/tcount/view/timeglider/js/jquery-ui-1.8.9.custom.min.js');
            $reqs->js('/mod/tcount/view/timeglider/js/jquery.tmpl.js');
            $reqs->js('/mod/tcount/view/timeglider/js/underscore-min.js');
            $reqs->js('/mod/tcount/view/timeglider/js/backbone-min.js');
            $reqs->js('/mod/tcount/view/timeglider/js/ba-debug.min.js');
            $reqs->js('/mod/tcount/view/timeglider/js/jquery.mousewheel.min.js');
            $reqs->js('/mod/tcount/view/timeglider/js/jquery.ui.ipad.js');
            $reqs->js('/mod/tcount/view/timeglider/js/raphael-min.js');
            $reqs->js('/mod/tcount/view/timeglider/js/jquery.global.js');
            $reqs->js('/mod/tcount/view/timeglider/js/ba-tinyPubSub.js');
            $reqs->js('/mod/tcount/view/timeglider/js/timeglider/TG_Date.js');
            $reqs->js('/mod/tcount/view/timeglider/js/timeglider/TG_Org.js');
            $reqs->js('/mod/tcount/view/timeglider/js/timeglider/TG_Timeline.js');
            $reqs->js('/mod/tcount/view/timeglider/js/timeglider/TG_TimelineView.js');
            $reqs->js('/mod/tcount/view/timeglider/js/timeglider/TG_Mediator.js');
            $reqs->js('/mod/tcount/view/timeglider/js/timeglider/timeglider.timeline.widget.js');
           
        }
    }
}
