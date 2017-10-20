<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
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
defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one msocial activity
 */

class restore_msocial_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');
        $msocial = new restore_path_element('msocial', '/activity/msocial');
        $paths[] = $msocial;
        if ($userinfo) {
            // Pkis.
            $paths[]  = new restore_path_element('pki', '/activity/msocial/pkis/pki');
            // Interactions.
            $paths[] = new restore_path_element('interaction', '/activity/msocial/interactions/interaction');
            // Map users.
            $paths[] = new restore_path_element('socialuser', '/activity/msocial/mapusers/socialuser');
        }
        $pluginconfig = new restore_path_element('plugin_config', '/activity/msocial/plugin_configs/plugin_config');
        $paths[] = $pluginconfig;

        $this->add_subplugin_structure('msocialconnector', $msocial);

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }
    protected function process_pki($data) {
        global $DB;
        $data = (object) $data;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->msocial = $this->get_new_parentid('msocial');
        $newitemid = $DB->insert_record('msocial_pkis', $data);
    }
    protected function process_interaction($data) {
        global $DB;
        $data = (object) $data;

        $data->fromid = $this->get_mappingid('user', $data->fromid);
        $data->toid = $this->get_mappingid('user', $data->toid);
        $data->msocial = $this->get_new_parentid('msocial');

        $newitemid = $DB->insert_record('msocial_interactions', $data);
    }
    protected function process_socialuser($data) {
        global $DB;
        $data = (object) $data;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->msocial = $this->get_new_parentid('msocial');

        $newitemid = $DB->insert_record('msocial_mapusers', $data);
    }
    protected function process_plugin_config($data) {
        global $DB;

        $data = (object) $data;
        $data->msocial = $this->get_new_parentid('msocial');
        // Insert the config record.
        $newitemid = $DB->insert_record('msocial_plugin_config', $data);
    }

    protected function process_msocial($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->startdate = $this->apply_date_offset($data->startdate);
        $data->enddate = $this->apply_date_offset($data->enddate);
        // Insert the msocial record.
        $newitemid = $DB->insert_record('msocial', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_msocial', 'intro', null);
    }
    public function after_restore() {
        echo "after restore";
    }
}
