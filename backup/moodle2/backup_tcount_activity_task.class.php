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
require_once($CFG->dirroot . '/mod/tcount/backup/moodle2/backup_tcount_stepslib.php');
require_once($CFG->dirroot . '/mod/tcount/backup/moodle2/backup_tcount_settingslib.php');

/**
 * choice backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_tcount_activity_task extends backup_activity_task {

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
        $this->add_step(new backup_tcount_activity_structure_step('tcount_structure', 'tcount.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of choices.
        $search = "/(" . $base . "\/mod\/tcount\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@TCOUNTINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = "/(" . $base . "\/mod\/tcount\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@TCOUNTVIEWBYID*$2@$', $content);

        return $content;
    }

}
