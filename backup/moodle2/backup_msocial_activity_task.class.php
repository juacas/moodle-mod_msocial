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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with MSocial for Moodle.  If not, see <http://www.gnu.org/licenses/>.
require_once($CFG->dirroot . '/mod/msocial/backup/moodle2/backup_msocial_stepslib.php');
require_once($CFG->dirroot . '/mod/msocial/backup/moodle2/backup_msocial_settingslib.php');

/**
 * choice backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_msocial_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new backup_msocial_activity_structure_step('msocial_structure', 'msocial.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of choices.
        $search = "/(" . $base . "\/mod\/msocial\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@TCOUNTINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = "/(" . $base . "\/mod\/msocial\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@TCOUNTVIEWBYID*$2@$', $content);

        return $content;
    }

}
