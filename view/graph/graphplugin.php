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
use msocial\msocial_plugin;
use mod_msocial\pki_info;
use mod_msocial\pki;
use mod_msocial\connector\msocial_connector_plugin;
use mod_msocial\connector\harvest_intervals;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/msocialviewplugin.php');
require_once($CFG->dirroot . '/mod/msocial/pki.php');

/** library class for view the network activity as a sequence diagram extending view plugin base
 * class
 *
 * @package msocialview_graph
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_view_graph extends msocial_view_plugin {

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialview_graph');
    }


    /** Get the settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
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

    /** The msocial has been deleted - cleanup subplugin
     *
     * @global moodle_database $DB
     * @return bool */
    public function delete_instance() {
        global $DB;
        $this->drop_pki_fields();
        $result = true;
        return $result;
    }

    public function get_subtype() {
        return 'graph';
    }

    public function get_category() {
        return msocial_plugin::CAT_VISUALIZATION;
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/graph/pix/icon.svg');
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\view\msocial_view_plugin::calculate_pkis() */
    public function calculate_pkis($users, $pkis = []) {
        require_once('socialgraph.php');
        $pkiinfos = $this->get_pki_list();
        foreach ($users as $user) {
            if (!isset($pkis[$user->id])) {
                $pkis[$user->id] = new pki($user->id, $this->msocial->id);
                // Reset to 0 to avoid nulls.
                $pki = $pkis[$user->id];
                foreach ($pkiinfos as $pkiinfo) {
                    $pki->{$pkiinfo->name} = 0;
                }
            }
        }
        // Get Interactions of all users, both known and anonymous.
        $filter = new \filter_interactions([\filter_interactions::PARAM_STARTDATE => $this->msocial->startdate,
                                            \filter_interactions::PARAM_ENDDATE => $this->msocial->enddate,
                                            \filter_interactions::PARAM_UNKNOWN_USERS => true,
                                            \filter_interactions::PARAM_RECEIVED_BY_TEACHERS => true
        ],
                                            $this->msocial);
        $interactions = social_interaction::load_interactions_filter($filter);
        // Socialmatrix analyzer.
        $social = new \SocialMatrix();
        foreach ($interactions as $interaction) {
            $social->register_interaction($interaction);
        }
        $results = $social->calculate_centralities($users);
        list($degreein, $degreeout) = $social->degree_centrality(array_keys($pkis));

        foreach ($results as $userid => $result) {
            if (isset($pkis[$userid])) {
                $pkis[$userid]->closeness = isset($result->cercania) ? $result->cercania : 0;
                $pkis[$userid]->degreeout = isset($degreeout[$userid]) ? $degreeout[$userid] : 0;
                $pkis[$userid]->degreein = isset($degreein[$userid]) ? $degreein[$userid] : 0;
                $pkis[$userid]->betweenness = isset($result->intermediacion) ? $result->intermediacion : 0;
            }
        }
        $pkis = $this->calculate_aggregated_pkis($pkis);
        return $pkis;
    }

    public function get_pki_list() {
        $pkiobjs['closeness'] = new pki_info('closeness', 'Centralidad de cercanía.',
                pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['degreein'] = new pki_info('degreein', 'Centralidad de grado de entrada (interacciones recibidas).',
                pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['degreeout'] = new pki_info('degreeout', 'Centralidad de grado de salida (interacciones emitidas).',
                pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['betweenness'] = new pki_info('betweenness', 'Centralidad de intermediación.',
                pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['max_closeness'] = new pki_info('max_closeness', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_degreein'] = new pki_info('max_degreein', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_degreeout'] = new pki_info('max_degreeout', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_betweenness'] = new pki_info('max_betweenness', null, pki_info::PKI_AGREGATED);
        return $pkiobjs;
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
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        $result = (object) ['messages' => []];
        $contextcourse = \context_course::instance($this->msocial->course);
        list($students, $nonstudents, $active, $users) = array_values(msocial_get_users_by_type($contextcourse));
        $pkis = $this->calculate_pkis($users);
        $this->store_pkis($pkis, true);
        $this->set_config(msocial_connector_plugin::LAST_HARVEST_TIME, time());
        $msocial = $this->msocial;
        $result->messages[] = "For module msocial: $msocial->name (id=$msocial->id) in course (id=$msocial->course) processing network topology.";
        return $result;
    }
    /**
     * {@inheritDoc}
     * @see \mod_msocial\connector\msocial_plugin::preferred_harvest_intervals()
     */
    public function preferred_harvest_intervals() {
        return new harvest_intervals(15 * 60, 5000, 1 * 3600, 0);
    }

    public function render_header_requirements($reqs, $viewparam) {
    }

    /**
     * {@inheritdoc}
     *
     * @see \msocial\msocial_plugin::view_header() */
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
                $harvestbutton= $OUTPUT->action_icon(
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
