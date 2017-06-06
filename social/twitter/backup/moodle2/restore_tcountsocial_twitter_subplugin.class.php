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
class restore_tcountsocial_twitter_subplugin extends restore_subplugin {

    /**
     * Returns array the paths to be handled by the subplugin at tcount level
     *
     * @return array
     */
    public function define_tcount_subplugin_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $elename = $this->get_namefor('tweets');
        // We used get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/tweets');
        $paths[] = new restore_path_element($elename, $elepath);
        $elename = $this->get_namefor('token');
        $elepath = $this->get_pathfor('/token');
        $paths[] = new restore_path_element($elename, $elepath);
        if ($userinfo) {
            $elename = $this->get_namefor('status');
            $elepath = $this->get_pathfor('/tweets/status');
            $paths[] = new restore_path_element($elename, $elepath);
        }
        return $paths;
    }

    public function process_tcountsocial_twitter_token($data) {
        global $DB;

        $data = (object) $data;

        $data->tcount = $this->get_new_parentid('tcount');

        $newitemid = $DB->insert_record('tcount_twitter_tokens', $data);
    }

    public function process_tcountsocial_twitter_status($data) {
        global $DB;

        $data = (object) $data;

        $data->tcount = $this->get_new_parentid('tcount');
        $data->userid = isset($data->userid) ? $this->get_mappingid('user', $data->userid) : null;

        $newitemid = $DB->insert_record('tcount_tweets', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    public function process_tcountsocial_twitter_tweets($data) {
        global $DB;
        $data->tcount = $this->get_new_parentid('tcount');
    }
}
