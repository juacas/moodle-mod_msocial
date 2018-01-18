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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once(__DIR__.'../../../msocialviewplugin.php');
require_once(__DIR__.'../../../pki.php');
/**
 * library class for view the network activity as a table extending view plugin base class
 *
 * @package msocialview_timeglider
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class msocial_view_drops extends msocial_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'msocialview_drops');
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
        if (isset($data->msocialview_drops_enabled)) {
            $this->set_config('enabled', $data->msocialview_drops_enabled);
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
        return 'drops';
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/drops/pix/icon.svg');
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
        $redirect = base64_encode($PAGE->url->out());
        echo '<div id="drops" width="80%"></div>';
        global $CFG;
        $reqs->js_call_amd('msocialview/drops', 'initview',
                ['#drops', $this->cm->id, $filter->get_filter_params_url(), $redirect]);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see msocial_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            $reqs->js('/mod/msocial/view/drops/js/configuredropsrequire.js', false);

            $reqs->js('/mod/msocial/view/drops/js/d3.v4.js', true);
            $reqs->css('/mod/msocial/view/drops/css/drops.css');
        }
    }
}
