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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/tcount/tcountviewplugin.php');

/**
 * library class for view the network activity as a table extending view plugin base class
 *
 * @package tcountview_table
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_view_table extends tcount_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountview_table');
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues['tcountview_table_enabled'] = $this->get_config('enabled');
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
        if (isset($data->tcountview_table_enabled)) {
            $this->set_config('enabled', $data->tcountview_table_enabled);
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
        return 'table';
    }
    public function get_category(){
        return tcount_plugin::CAT_ANALYSIS;
    }
    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/table/pix/icon.svg');
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
        if ($viewparam == 'table') {
            // Table view.
            $reqs->css('/mod/tcount/view/table/css/jquery.dataTables.css');
            $reqs->css('/mod/tcount/view/table/css/buttons.jqueryui.css');
            $reqs->css('/mod/tcount/view/table/css/buttons.dataTables.css');
            $reqs->css('/mod/tcount/view/table/css/colReorder.jqueryui.css');
            $reqs->js('/mod/tcount/view/table/js/configurerequire.js', false);
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
        global $USER;
 
        $contextmodule = \context_module::instance($this->cm->id);
        $contextcourse = \context_course::instance($this->cm->course);
        $showinactive = optional_param('showinactive', true, PARAM_BOOL);
        
        // Table view.
        if (has_capability('mod/tcount:viewothers', $contextmodule)) {
            list($students, $nonstudents, $activeusers, $userrecords) = eduvalab_get_users_by_type($contextcourse);
            $students = array_merge($students, $nonstudents);
        } else {
            $students = array($USER->id);
            $userrecords[$USER->id] = $USER;
        }
        $groups = groups_get_activity_allowed_groups($this->cm);
        $userstats = (object) ['users' => [], 'maximums' => (object) []];
        $enabledplugins = tcountsocial::get_enabled_social_plugins($this->tcount);
        $pkis = [];
        $pkisall = [];
       
        /** @var tcount_social_plugin $plugin */
        foreach ($enabledplugins as $type => $plugin) {
            $partialuserstats = $plugin->calculate_stats($students);
            $userstats = merge_stats($userstats, $partialuserstats);
            // Get PKIs.
            
            $pkis[$type] = $plugin->get_pki_list();
            $pkiindividual[$type] = array_filter($pkis[$type], 
                    function ($pki) {
                        return $pki->individual === true;
                    });
            $pkisall = array_merge($pkisall, $pkiindividual[$type]);

        }
        // Define column groups.
        $columnstart=2;
        $pkicolumns =  range($columnstart,$columnstart+count($pkisall)-1);
        foreach ($enabledplugins as $type => $plugin){
            $columnend=$columnstart+count($pkiindividual[$type])-1;
            $columns=range($columnstart,$columnend);
            $restcolumns= array_values(array_diff($pkicolumns,$columns));
            $showcolumns = (array)array_merge([0,1],$columns);
            $columngroups[]=(object)["extend"=>"colvisGroup",
                            "text"=>$plugin->get_name(),
                            "show"=> $showcolumns,
                            "hide"=> $restcolumns
            ];
            $columnstart=$columnend+1;
        }
        $columngroups[]=(object)["extend"=>"colvisGroup",
                        "text"=>"All",
                        "show"=> ":hidden"
        ];
        $reqs->js_call_amd('tcountview/table', 'initview', ['#pkitable', $columngroups]);
        echo $renderer->heading('Table of PKIs');
        $table = new \html_table();
        $table->id = 'pkitable';
        $table->head = array_merge(array('Student', 'Identity'), array_keys($pkisall));
        foreach ($userstats->users as $userid => $stat) {
            if ($showinactive == false && $userid != $USER->id && tcount_user_inactive($userid, $stat)) {
                continue;
            }
            
            $row = new \html_table_row();
            // Photo and link for the user profile.
            $user = $userrecords[$userid];
            if (has_capability('moodle/user:viewdetails', $contextmodule)) {
                $userpic = $renderer->user_picture($user);
                $url = new \moodle_url('/user/view.php', ['id' => $user->id, 'course' => $this->cm->course]);
                $profilelink = \html_writer::link($url, fullname($user, true));
                ;
            } else {
                $userpic = '';
                $profilelink = '';
            }
            $usersociallink = '';
            foreach ($enabledplugins as $type => $plugin) {
                if ($plugin->is_tracking()) {
                    $usersociallink .= '<p>' . $plugin->view_user_linking($user) . '</p>';
                }
            }
            
            $usercard = $userpic . $profilelink;
            $socialids = '<p>' . $usersociallink . '</p>';
            $row->cells[] = new \html_table_cell($usercard);
            $row->cells[] = new \html_table_cell($socialids);
            // Get the PKIs.
            foreach ($enabledplugins as $type => $plugin) {
                foreach ($pkiindividual[$type] as $pki) {
                    $row->cells[] = new \html_table_cell(isset($stat->{$pki->name}) ? $stat->{$pki->name} : '--');
                }
            }
            // $tweetsdata = '<a href="https://twitter.com/search?q=' .
            // urlencode($enabledplugins['twitter']->get_config('hashtag')) .
            // '%20from%3A' . $twitterusername . '&src=typd">' . $stat->tweets . "</a>";
            $table->data[] = $row;
        }
        echo \html_writer::table($table);
    }
}
