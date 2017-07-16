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

use mod_tcount\social\social_interaction;
use tcount\tcount_plugin;
use mod_tcount\social\pki_info;
use mod_tcount\social\pki;
use mod_tcount\social\tcount_social_plugin;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once ($CFG->dirroot . '/mod/tcount/tcountviewplugin.php');
require_once ($CFG->dirroot . '/mod/tcount/pki.php');


/**
 * library class for view the network activity as a sequence diagram extending view plugin base
 * class
 *
 * @package tcountview_graph
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_view_graph extends tcount_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountview_graph');
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues['tcountview_graph_enabled'] = $this->get_config('enabled');
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
        if (isset($data->tcountview_graph_enabled)) {
            $this->set_config('enabled', $data->tcountview_graph_enabled);
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
        $this->drop_pki_fields();
        $result = true;
        return $result;
    }

    public function get_subtype() {
        return 'graph';
    }

    public function get_category() {
        return tcount_plugin::CAT_VISUALIZATION;
    }

    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/graph/pix/icon.svg');
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \mod_tcount\view\tcount_view_plugin::calculate_pkis()
     */
    public function calculate_pkis($users, $pkis = []) {
        require_once ('socialgraph.php');
        $pkiinfos = $this->get_pki_list();
        foreach ($users as $user) {
            if (!isset($pkis[$user->id])) {
                $pkis[$user->id] = new pki($user->id, $this->tcount->id);
                // Reset to 0 to avoid nulls.
                foreach ($pkiinfos as $pkiinfo) {
                    $pki = $pkis[$user->id];
                    $pki->{$pkiinfo->name} = 0;
                }
            }
        }
        // get Interactions of all users, both known and anonymous.
        $interactions = social_interaction::load_interactions($this->tcount->id, null, null, null, null);
        // Socialmatrix analyzer.
        $social = new \SocialMatrix();
        foreach ($interactions as $interaction) {
            $social->register_interaction($interaction->fromid ? $interaction->fromid : $interaction->nativefrom,
                    $interaction->toid ? $interaction->toid : $interaction->nativeto,
                    $interaction->type);
        }
        $results = $social->calculateCentralities();
        list($degreein, $degreeout) = $social->centralidad_grado(array_keys($pkis));

        foreach ($results as $userid => $result) {
            if (isset($pkis[$userid])) {
                $pkis[$userid]->closeness = isset($result->cercania)?$result->cercania:0;
                $pkis[$userid]->degree = isset($degreeout[$userid])?$degreeout[$userid]:0;
                $pkis[$userid]->betweenness = isset($result->intermediacion)?$result->intermediacion:0;
            }
        }
        return $pkis;
    }

    public function get_pki_list() {
        $pkiobjs['closeness'] = new pki_info('closeness', 'Centralidad de cercanía.', pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['degree'] = new pki_info('degree', 'Centralidad de grado.', pki_info::PKI_INDIVIDUAL, pki_info::PKI_CUSTOM);
        $pkiobjs['betweenness'] = new pki_info('betweenness', 'Centralidad de intermediación.', pki_info::PKI_INDIVIDUAL,
                pki_info::PKI_CUSTOM);
        $pkiobjs['max_closeness'] = new pki_info('max_closeness', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_degree'] = new pki_info('max_degree', null, pki_info::PKI_AGREGATED);
        $pkiobjs['max_betweenness'] = new pki_info('max_betweenness', null, pki_info::PKI_AGREGATED);
        return $pkiobjs;
    }

    /**
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        $result = (object) ['messages' => []];
        $contextcourse = \context_course::instance($this->tcount->course);
        list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
        $pkis= $this->calculate_pkis($users);
        $this->store_pkis($pkis,true);
        $this->set_config(tcount_social_plugin::LAST_HARVEST_TIME, time());
        $tcount=$this->tcount;
        $result->messages[] = "For module tcount: $tcount->name (id=$tcount->id) in course (id=$tcount->course) processing network topology.";
        return $result;
    }

    public function render_header_requirements($reqs, $viewparam) {
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \tcount\tcount_plugin::view_header()
     */
    public function view_header() {
        global $OUTPUT;
        if ($this->is_enabled()) {
            $headline = $this->get_name() . ' refresca el calculo' . $OUTPUT->action_icon(
                    new \moodle_url('/mod/tcount/social/harvest.php',
                            ['id' => $this->get_cmid(), 'subtype' => $this->get_subtype()]), new \pix_icon('a/refresh', ''));
            echo $OUTPUT->box($headline);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @global \stdClass $USER
     * @see tcount_view_plugin::render_view()
     */
    public function render_view($renderer, $reqs) {
        global $USER, $OUTPUT;

        $contextmodule = \context_module::instance($this->cm->id);
        $contextcourse = \context_course::instance($this->cm->course);
        $subview = optional_param('subview', 'matrix', PARAM_ALPHA);
        $rows = [];
        foreach ($this->viewfiles() as $name => $path) {
            // $icondecoration =html_writer::img($icon->out_as_local_url(), $plugin->get_name().'
            // icon.',['height'=>32]);
            $url = new \moodle_url('/mod/tcount/view.php',
                    ['id' => $this->cm->id, 'view' => $this->get_subtype(), 'subview' => $name]);
            $plugintab = new \tabobject($name, $url, $name);
            $rows[] = $plugintab;
        }
        echo $OUTPUT->tabtree($rows, $subview);

        $file = $this->viewfiles($subview);
        include ($this->viewfiles()[$subview]);
    }

    protected function viewfiles() {
        global $CFG;
        $files = ['matrix' => $CFG->dirroot . '/mod/tcount/view/graph/matrix.php',
                        'forcedgraph' => $CFG->dirroot . '/mod/tcount/view/graph/forcedgraph.php',
                        'chord' => $CFG->dirroot . '/mod/tcount/view/graph/chord.php',
                        'graphviz' => $CFG->dirroot . '/mod/tcount/view/graph/graphviz.php'];

        return $files;
    }
}
