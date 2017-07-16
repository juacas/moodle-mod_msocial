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
 * @package tcountview_timeline
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_view_timeline extends tcount_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountview_timeline');
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
        if (isset($data->tcountview_timeline_enabled)) {
            $this->set_config('enabled', $data->tcountview_timeline_enabled);
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

    public function get_category() {
        return tcount_plugin::CAT_VISUALIZATION;
    }

    public function get_subtype() {
        return 'timeline';
    }

    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/timeline/pix/icon.svg');
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

    /**
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        global $DB;
        $result = (object) ['messages' => []];
        return $result;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see tcount_view_plugin::render_view()
     */
    public function render_view($renderer, $reqs) {
        echo '<div id="my-timeline" style="overflow-y: visible; height: 250px; border: 1px solid #aaa"></div>';
        echo $renderer->spacer(array('height' => 20));
        $reqs->js_init_call("init_timeline", [$this->cm->id, null], false);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see tcount_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            $reqs->js('/mod/tcount/view/timeline/js/init_timeline.js', true);
            $reqs->js('/mod/tcount/view/timeline/js/timeline/timeline-api.js?bundle=true', true);
        }
    }
}
