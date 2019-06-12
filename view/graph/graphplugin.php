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

use mod_msocial\connector\social_interaction;
use mod_msocial\msocial_plugin;
use mod_msocial\kpi_info;
use mod_msocial\kpi;
use mod_msocial\connector\msocial_connector_plugin;
use mod_msocial\connector\harvest_intervals;
use mod_msocial\view\graph\graph_task;
use mod_msocial\filter_interactions;
use mod_msocial\users_struct;
use view\graph\graph_harvester_local;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/classes/msocialviewplugin.php');
require_once($CFG->dirroot . '/mod/msocial/classes/kpi.php');
require_once('graph_harvester_local.php');

/** library class for view the network activity as a sequence diagram extending view plugin base
 * class
 *
 * @package msocialview_graph
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_view_graph extends msocial_view_plugin {
    const PLUGINNAME = "graph";
    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialview_graph');
    }


    /** Get the settings for the plugin
     *
     * @param \MoodleQuickForm $mform The form to add elements to
     * @return void */
    public function get_settings(\MoodleQuickForm $mform) {
    }

    /** Save the settings for table plugin
     *
     * @param \stdClass $data
     * @return bool */
    public function save_settings(\stdClass $data) {
        if (isset($data->msocialview_graph_enabled)) {
            $this->set_config('enabled', $data->msocialview_graph_enabled);
        }
        return true;
    }


    public function get_subtype() {
        return self::PLUGINNAME;
    }

    public function get_category() {
        return msocial_plugin::CAT_VISUALIZATION;
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/graph/pix/icon.svg');
    }



    public function get_kpi_list() {
        $kpiobjs['closeness'] = new kpi_info('closeness', get_string('kpi_description_closeness', 'msocialview_graph'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CUSTOM);
        $kpiobjs['degreein'] = new kpi_info('degreein', get_string('kpi_description_degreein', 'msocialview_graph'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CUSTOM);
        $kpiobjs['degreeout'] = new kpi_info('degreeout', get_string('kpi_description_degreeout', 'msocialview_graph'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CUSTOM);
        $kpiobjs['betweenness'] = new kpi_info('betweenness', get_string('kpi_description_betweeness', 'msocialview_graph'),
                kpi_info::KPI_INDIVIDUAL, kpi_info::KPI_CUSTOM);
        $kpiobjs['max_closeness'] = new kpi_info('max_closeness', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_degreein'] = new kpi_info('max_degreein', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_degreeout'] = new kpi_info('max_degreeout', null, kpi_info::KPI_AGREGATED);
        $kpiobjs['max_betweenness'] = new kpi_info('max_betweenness', null, kpi_info::KPI_AGREGATED);
        return $kpiobjs;
    }
    /**
     *
     * {@inheritDoc}
     * @see \mod_msocial\view\msocial_view_plugin::get_sort_order()
     */
    public function get_sort_order() {
        return 3;
    }
    /**
     * {@inheritDoc}
     * @see msocial_plugin::preferred_harvest_intervals()
     */
    public function preferred_harvest_intervals() {
        return new harvest_intervals(15 * 60, 5000, 1 * 3600, 0);
    }
    /**
     *
     * {@inheritDoc}
     * @see \mod_msocial\msocial_plugin::get_harvest_plugin()
     */
    public function get_harvest_plugin() {
        return new graph_harvester_local($this);
    }

    public function render_header_requirements($reqs, $viewparam) {
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\msocial_plugin::view_header() */
    public function render_header() {
        global $OUTPUT;
        $messages = [$this->get_name()];
        return [$messages, [] ];
    }

    public function render_harvest_link() {
        global $OUTPUT;
        $harvestbutton = '';
        if ($this->is_enabled()) {
            $context = \context_module::instance($this->cm->id);
            if (has_capability('mod/msocial:manage', $context)) {
                $harvestbutton = $OUTPUT->action_icon(
                        new \moodle_url('/mod/msocial/harvest.php', ['id' => $this->get_cmid(),
                                        'subtype' => $this->get_subtype()]), new \pix_icon('a/refresh', ''));
            }
        }
        return $harvestbutton;
    }
    /**
     * {@inheritdoc}
     *
     * @global \stdClass $USER
     * @see msocial_view_plugin::render_view() */
    public function render_view($renderer, $reqs, $filter) {
        global $USER, $OUTPUT;
        $contextmodule = \context_module::instance($this->cm->id);
        $contextcourse = \context_course::instance($this->cm->course);
        global $PAGE;
        $subview = optional_param('subview', 'matrix', PARAM_ALPHA);
        $thispageurl = $PAGE->url;
        $thispageurl->param('subview', $subview);
        $PAGE->set_url($thispageurl);
        $rows = [];
        $subsubplugins = $this->viewfiles();
        foreach ($subsubplugins as $name => $path) {
            $url = new \moodle_url($thispageurl);
            $url->param('subview', $name);
            $plugintab = new \tabobject($name, $url, $name);
            $rows[] = $plugintab;
        }
        echo $OUTPUT->tabtree($rows, $subview);

        $file = $this->viewfiles($subview);
        // Render actual view. Following vars are supposed to be available.
        $redirect = base64_encode($PAGE->url->out());
        echo $filter->render_form($thispageurl);
        include($this->viewfiles()[$subview]);
    }

    protected function viewfiles() {
        global $CFG;
        $files = [
                    'matrix' => $CFG->dirroot . '/mod/msocial/view/graph/matrix.php',
                    'forcedgraph' => $CFG->dirroot . '/mod/msocial/view/graph/forcedgraph.php',
                    'chord' => $CFG->dirroot . '/mod/msocial/view/graph/chord.php',
                    'graphviz' => $CFG->dirroot . '/mod/msocial/view/graph/graphviz.php',
                    'graphvis' => $CFG->dirroot . '/mod/msocial/view/graph/graphvis.php',
        ];
        return $files;
    }
}
