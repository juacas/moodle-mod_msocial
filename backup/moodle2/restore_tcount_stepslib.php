<?php


// This file is part of TwitterCount activity for Moodle http://moodle.org/
//
// Questournament for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Questournament for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Structure step to restore one choice activity
 */
class restore_tcount_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');
        $tcount = new restore_path_element('tcount', '/activity/tcount');
        $paths[] = $tcount;
        $plugin_config = new restore_path_element('plugin_config', '/activity/tcount/plugin_configs/plugin_config');
        $paths[] = $plugin_config;
        // $socialplugins = new restore_path_element('tcount_social',
        // '/activity/tcount/tcountsocials/tcountsocial');
        // $paths[] = $socialplugins;
        $this->add_subplugin_structure('tcountsocial', $tcount);

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_plugin_config($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->tcount = $this->get_new_parentid('tcount');
        // Insert the config record.
        $newitemid = $DB->insert_record('tcount_plugin_config', $data);
    }

    protected function process_tcount($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->startdate = $this->apply_date_offset($data->startdate);
        $data->enddate = $this->apply_date_offset($data->enddate);
        // Insert the tcount record.
        $newitemid = $DB->insert_record('tcount', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_tcount', 'intro', null);
    }
}
