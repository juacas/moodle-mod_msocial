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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
namespace mod_msocial\view;

use mod_msocial\msocial_plugin;

defined('MOODLE_INTERNAL') || die();


/**
 * library class for view the network activity as a table extending view plugin base class
 *
 * @package msocialview_timeline
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class msocial_view_timeline extends msocial_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'msocialview_timeline');
    }


    /**
     * Get the settings for the plugin
     *
     * @param \MoodleQuickForm $mform The form to add elements to
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
        if (isset($data->msocialview_timeline_enabled)) {
            $this->set_config('enabled', $data->msocialview_timeline_enabled);
        }
        return true;
    }

    /**
     * The msocial has been deleted - cleanup subplugin
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
        return msocial_plugin::CAT_VISUALIZATION;
    }

    public function get_subtype() {
        return 'timeline';
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/timeline/pix/icon.svg');
    }

    /**
     * Statistics for grading
     *
     * @param array[]integer $users array with the userids to be calculated
     * @return array[string]object object->userstats with KPIs for each user object->maximums max
     *         values for normalization.
     */
    public function calculate_stats($users) {
        global $DB;
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql(
                'SELECT userid as id, sum(retweets) as retweets, count(tweetid) as tweets, sum(favs) as favs ' .
                         'FROM {msocial_tweets} where msocial = ? and userid is not null group by userid', array($this->msocial->id));
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
     * {@inheritdoc}
     *
     * @see msocial_view_plugin::render_view()
     */
    public function render_view($renderer, $reqs, $filter) {
        global $PAGE;
        echo $filter->render_form($PAGE->url);

        echo '<div id="my-timeline" style="overflow-y: visible; height: 650px; border: 1px solid #aaa"></div>';
        echo $renderer->spacer(array('height' => 20));
        $reqs->js_init_call("init_timeline",
                [$this->cm->id, null, $filter->get_filter_params_url()], true);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see msocial_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            $reqs->jquery();
            $reqs->js('/mod/msocial/view/timeline/js/init_timeline.js', true);
            $reqs->js('/mod/msocial/view/timeline/js/timeline/timeline-api.js?bundle=true', true);
        }
    }
}
