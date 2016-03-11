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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Structure step to restore one choice activity
 */
class restore_tcount_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('tcount', '/activity/tcount');
        $paths[] = new restore_path_element('token', '/activity/tcount/token');
        if ($userinfo) {
            $paths[] = new restore_path_element('status', '/activity/tcount/statuses/status');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_tcount($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->counttweetsfromdate = $this->apply_date_offset($data->counttweetsfromdate);
        $data->counttweetstodate = $this->apply_date_offset($data->counttweetstodate);
        // Insert the choice record.
        $newitemid = $DB->insert_record('tcount', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_token($data) {
        global $DB;

        $data = (object) $data;

        $data->tcount_id = $this->get_new_parentid('tcount');

        $newitemid = $DB->insert_record('tcount_tokens', $data);
    }

    protected function process_status($data) {
        global $DB;

        $data = (object) $data;

        $data->tcountid = $this->get_new_parentid('tcount');
        $data->userid = isset($data->userid)?$this->get_mappingid('user', $data->userid):null;

        $newitemid = $DB->insert_record('tcount_statuses', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_tcount', 'intro', null);
    }

}
