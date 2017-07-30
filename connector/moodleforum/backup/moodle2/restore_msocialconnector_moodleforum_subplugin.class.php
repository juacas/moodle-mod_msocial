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
defined('MOODLE_INTERNAL') || die();

class restore_msocialconnector_moodleforum_subplugin extends restore_subplugin {

    /**
     * Returns array the paths to be handled by the subplugin at msocial level
     *
     * @return array
     */
    public function define_msocial_subplugin_structure() {
        $paths = array();
        return $paths;
    }
    // TODO Investigate how to do: After restore, change instance ids of CONFIG_ACTIVITIES list.

}
