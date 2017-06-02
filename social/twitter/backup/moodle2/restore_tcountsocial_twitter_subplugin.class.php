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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


class restore_tcountsocial_twitter_subplugin extends restore_subplugin {

    /**
     * Returns array the paths to be handled by the subplugin at assignment level
     * @return array
     */
    protected function define_tcount_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('tcount');

        // We used get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/tcount_twitter');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes one tcountsocial_twitter element
     *
     * @param mixed $data
     */
    public function process_tcountsocial_twitter_tcount($data) {
        global $DB;

        $data = (object)$data;
        $data->tcount = $this->get_new_parentid('assign');
        $oldtcountid = $data->tcount;
        // The mapping is set in the restore for the core assign activity
        // when a tcount node is processed.
        $data->tcount = $this->get_mappingid('tcount', $data->tcount);

        $DB->insert_record('tcountsocial_twitter', $data);

        $this->add_related_files('tcountsocial_twitter', 'tcounts_twitter', 'tcount', null, $oldtcountid);
    }

}
