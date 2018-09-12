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

use msocial\msocial_plugin;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/classes/msocialviewplugin.php');
require_once($CFG->dirroot . '/mod/msocial/classes/kpi.php');
/**
 * library class for view the network activity as a table extending view plugin base class
 *
 * @package msocialview_timeglider
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class msocial_view_breakdown extends msocial_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'msocialview_breakdown');
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
        if (isset($data->msocialview_breakdown_enabled)) {
            $this->set_config('enabled', $data->msocialview_breakdown_enabled);
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
    /**
     *
     * {@inheritDoc}
     * @see \msocial\msocial_plugin::get_category()
     */
    public function get_category() {
        return msocial_plugin::CAT_VISUALIZATION;
    }
    /**
     *
     * {@inheritDoc}
     * @see \msocial\msocial_plugin::get_sort_order()
     */
    public final function get_sort_order() {
        return 0;
    }
    /**
     *
     * {@inheritDoc}
     * @see \msocial\msocial_plugin::get_subtype()
     */
    public function get_subtype() {
        return 'breakdown';
    }
    /**
     *
     * {@inheritDoc}
     * @see \mod_msocial\view\msocial_view_plugin::get_icon()
     */
    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/breakdown/pix/icon.svg');
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
        $redirect = urlencode(base64_encode($PAGE->url->out()));
        echo '<div id="breakdown" width="400" height="400"></div>';
        global $CFG;
        $reqs->js_call_amd('msocialview/breakdown', 'initview',
                ['#breakdown', $this->cm->id, $filter->get_filter_params_url(), $redirect]);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see msocial_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            $reqs->js('/mod/msocial/view/breakdown/js/configurebreakdownrequire.js', false);
            $reqs->css('/mod/msocial/view/breakdown/css/breakdown.css');
            $reqs->js('/mod/msocial/view/breakdown/js/d3.v3.min.js', true);
        }
    }
}
