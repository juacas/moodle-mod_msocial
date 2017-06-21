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

use mod_tcount\plugininfo\tcountsocial;
use tcount\tcount_plugin;
use mod_tcount\social\social_interaction;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/tcount/tcountviewplugin.php');

/**
 * library class for view the network activity as a sequence diagram extending view plugin base class
 *
 * @package tcountview_adjacency
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_view_adjacency extends tcount_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountview_adjacency');
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues['tcountview_adjacency_enabled'] = $this->get_config('enabled');
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
        if (isset($data->tcountview_adjacency_enabled)) {
            $this->set_config('enabled', $data->tcountview_adjacency_enabled);
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

    public function get_subtype() {
        return 'adjacency';
    }
    public function get_category(){
        return tcount_plugin::CAT_VISUALIZATION;
    }
    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/adjacency/pix/icon.svg');
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
        return $result;
    }
    /**
     *
     * {@inheritdoc}
     *
     * @see tcount_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            // adjacency matrix view.
            $reqs->js('/mod/tcount/view/adjacency/js/viewlib.js');   
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
        foreach ($this->viewfiles() as $name=>$path){
//             $icondecoration =html_writer::img($icon->out_as_local_url(), $plugin->get_name().' icon.',['height'=>32]);
            $url = new \moodle_url('/mod/tcount/view.php',['id'=>$this->cm->id,'view'=>$this->get_subtype(),'subview'=>$name]);
            $plugintab = new \tabobject($name, $url, $name);
            $rows[]=$plugintab;
            }
        echo $OUTPUT->tabtree($rows,$subview);

        $file = $this->viewfiles($subview);
        include($this->viewfiles()[$subview]);  
        
    }
    protected function viewfiles(){
        global $CFG;
        $files = ['matrix'=>$CFG->dirroot.'/mod/tcount/view/adjacency/matrix.php',
                        'forcedgraph'=>$CFG->dirroot.'/mod/tcount/view/adjacency/forcedgraph.php',
                        'chord'=>$CFG->dirroot.'/mod/tcount/view/adjacency/chord.php',
                        
        ];
        
        return $files;
    }
}
