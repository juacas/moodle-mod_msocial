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
 * Define the complete tcount structure for backup, with file and id annotations
 */
class backup_tcount_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $tcount = new backup_nested_element('tcount', array('id'),
                array(
            'name', 'intro', 'introformat', 'hashtag',
            'widget_id', 'fieldid', 'counttweetsfromdate', 'counttweetstodate',
            'grade_expr'));

        $tcountstatuses = new backup_nested_element('statuses');

        $tcountstatus = new backup_nested_element('status', array(),
                array(
            'userid', 'tweetid', 'twitterusername', 'hashtag', 'status', 'retweets', 'favs'));

        $tcounttoken = new backup_nested_element('token', array(), array('token', 'token_secret', 'username'));
        // Build the tree.
        $tcount->add_child($tcounttoken);
        $tcount->add_child($tcountstatuses);
        $tcountstatuses->add_child($tcountstatus);
        // Define sources.
        $tcount->set_source_table('tcount', array('id' => backup::VAR_ACTIVITYID));
        $tcounttoken->set_source_table('tcount_tokens', array('tcount_id' => backup::VAR_PARENTID));
        if ($userinfo) {
            $tcountstatus->set_source_table('tcount_statuses', array('tcountid' => backup::VAR_ACTIVITYID));
        }
        // Define id annotations.
        $tcountstatus->annotate_ids('userid', 'userid');
        // Define file annotations
        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($tcount);
    }

}
