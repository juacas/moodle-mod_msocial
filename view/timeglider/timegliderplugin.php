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

    public function get_category() {
        return tcount_plugin::CAT_VISUALIZATION;
    }

    public function get_subtype() {
        return 'timeglider';
    }

    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/timeglider/pix/icon.svg');
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
        $reqs->js_init_call("init_view", [$this->cm->id, null], false);
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
            $reqs->js('/mod/tcount/view/timeglider/js/jquery-1.4.4.min.js', true);
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
