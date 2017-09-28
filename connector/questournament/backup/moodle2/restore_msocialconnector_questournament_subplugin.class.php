<?php
use mod_msocial\plugininfo\msocialbase;
use mod_msocial\connector\msocial_connector_questournament;
use mod_msocial\connector\msocial_connector_facebook;

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
/*
 * **************************
 * Specialised restore task for the msocialconnect_questournament subplugin
 * (using execute_after_tasks for recoding of target forums)
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
defined('MOODLE_INTERNAL') || die();

class restore_msocialconnector_questournament_subplugin extends restore_subplugin {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    /** Returns array the paths to be handled by the subplugin at msocial level
     *
     * @return array */
    public function define_msocial_subplugin_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');
        $elename = $this->get_namefor('dumb');
        $elepath = $this->get_pathfor('/dumb');
        $paths[] = new restore_path_element($elename, $elepath);
        return $paths;
    }

    public function process_msocialconnector_questournament_dumb() {
    }

    // TODO Investigate how to do: After restore, change instance ids of CONFIG_ACTIVITIES list.
    /** This function, executed after all the tasks in the plan
     * have been executed, will perform the recode of the
     * target quiz for the block.
     * This must be done here
     * and not in normal execution steps because the forums
     * can be restored after the subplugin. */
    public function after_execute_msocial() {
        global $DB, $CFG;
        // Get the subpluginid.
        $id = $this->get_new_parentid('msocial');
        $msocial = $DB->get_record('msocial', ['id' => $id]);
        require_once($CFG->dirroot . '/mod/msocial/connector/questournament/questournamentplugin.php');
        $plugin = new msocial_connector_questournament($msocial);
        $plugin->remap_linked_activities();
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }
}


