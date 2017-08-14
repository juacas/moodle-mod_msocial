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

use mod_msocial\plugininfo\msocialconnector;
use msocial\msocial_plugin;
use mod_msocial\pki_info;
use mod_msocial\plugininfo\msocialbase;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once ($CFG->dirroot . '/mod/msocial/msocialviewplugin.php');

/** library class for view the network activity as a table extending view plugin base class
 *
 * @package msocialview_table
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_view_table extends msocial_view_plugin {

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialview_table');
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
        if (isset($data->msocialview_table_enabled)) {
            $this->set_config('enabled', $data->msocialview_table_enabled);
        }
        return true;
    }

    /** The msocial has been deleted - cleanup subplugin
     *
     * @global moodle_database $DB
     * @return bool */
    public function delete_instance() {
        global $DB;
        $result = true;
        return $result;
    }

    public function get_subtype() {
        return 'table';
    }

    public function get_category() {
        return msocial_plugin::CAT_ANALYSIS;
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/table/pix/icon.svg');
    }

    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;
        $result = (object) ['messages' => []];
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see msocial_view_plugin::view_set_requirements() */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == 'table') {
            // Table view.
            $reqs->css('/mod/msocial/view/table/css/jquery.dataTables.css');
            $reqs->css('/mod/msocial/view/table/css/buttons.jqueryui.css');
            $reqs->css('/mod/msocial/view/table/css/buttons.dataTables.css');
            $reqs->css('/mod/msocial/view/table/css/colReorder.jqueryui.css');
            $reqs->js('/mod/msocial/view/table/js/configurerequire.js', false);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @global \stdClass $USER
     * @see msocial_view_plugin::render_view() */
    public function render_view($renderer, $reqs) {
        global $USER;

        $contextmodule = \context_module::instance($this->cm->id);
        $contextcourse = \context_course::instance($this->cm->course);
        $showinactive = optional_param('showinactive', true, PARAM_BOOL);

        // Table view.
        if (has_capability('mod/msocial:viewothers', $contextmodule)) {
            list($students, $nonstudents, $activeusers, $userrecords) = eduvalab_get_users_by_type($contextcourse);
            $students = array_merge($students, $nonstudents);
        } else {
            $students = array($USER->id);
            $userrecords[$USER->id] = $USER;
        }
        $groups = groups_get_activity_allowed_groups($this->cm);
        $enabledsocialplugins = msocialconnector::get_enabled_connector_plugins($this->msocial);
        $enabledplugins = msocialbase::get_enabled_plugins_all_types($this->msocial);

        $pkis = msocial_plugin::get_pkis($this->msocial, $students, null);
        $pkiinfosall = [];
        foreach ($enabledplugins as $type => $plugin) {
            // Get PKIs.
            if ($plugin->is_enabled()) {
                $pkilist = $plugin->get_pki_list();
                if (count($pkilist) > 0) {
                    $pkiinfos[$type] = $pkilist;
                    $pkiindividual[$type] = array_filter($pkiinfos[$type],
                            function ($pki) {
                                return $pki->individual !== pki_info::PKI_AGREGATED;
                            });
                    $pkiinfosall = array_merge($pkiinfosall, $pkiindividual[$type]);
                }
            }
        }
        // Define column groups.
        $columnstart = 2;
        $pkicolumns = range($columnstart, $columnstart + count($pkiinfosall) - 1);
        foreach ($pkiindividual as $type => $pkiinfs) {
            $columnend = $columnstart + count($pkiinfs) - 1;
            $columns = range($columnstart, $columnend);
            $restcolumns = array_values(array_diff($pkicolumns, $columns));
            $showcolumns = (array) array_merge([0, 1], $columns);
            $columngroups[] = (object) ["extend" => "colvisGroup", "text" => $type, "show" => $showcolumns,
                            "hide" => $restcolumns];
            $columnstart = $columnend + 1;
        }
        $columngroups[] = (object) ["extend" => "colvisGroup", "text" => "All", "show" => ":hidden"];
        $reqs->js_call_amd('msocialview/table', 'initview', ['#pkitable', $columngroups]);
        echo $renderer->heading('Table of PKIs');
        $table = new \html_table();
        $table->id = 'pkitable';
        $table->head = array_merge(array('Student', 'Identity'), array_keys($pkiinfosall));
        foreach ($pkis as $userid => $pki) {
            if (!isset($userrecords[$userid]) || ($showinactive == false && $userid != $USER->id && $pki->seems_inactive())) {
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
            foreach ($enabledsocialplugins as $type => $plugin) {
                if ($plugin->is_enabled()) {
                    $usersociallink .= '<p fontsize="8" >' . $plugin->render_user_linking($user) . '</p>';
                }
            }

            $usercard = $userpic . $profilelink;
            $socialids = '<p>' . $usersociallink . '</p>';
            $row->cells[] = new \html_table_cell($usercard);
            $row->cells[] = new \html_table_cell($socialids);
            // Get the PKIs.
            foreach ($pkiindividual as $type => $pkinfs) {
                foreach ($pkinfs as $pkiinfo) {
                    $row->cells[] = new \html_table_cell(isset($pki->{$pkiinfo->name}) ? $pki->{$pkiinfo->name} : '--');
                }
            }
            // $tweetsdata = '<a href="https://twitter.com/search?q=' .
            // urlencode($enabledplugins['twitter']->get_config('hashtag')) .
            // '%20from%3A' . $twitterusername . '&src=typd">' . $stat->tweets . "</a>";
            $table->data[] = $row;
        }
        echo '<div id="tablepkis" class="container">';
        echo \html_writer::table($table);
        echo '</div>';
    }
}
