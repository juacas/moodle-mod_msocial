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
 * Define the complete tcount structure for backup, with file and id annotations
 */
class backup_tcount_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $tcount = new backup_nested_element('tcount', array('id'
        ), array('name', 'intro', 'introformat', 'startdate', 'enddate', 'grade_expr'
        ));
        $pluginconfigs = new backup_nested_element('plugin_configs');
        $pluginconfig = new backup_nested_element('plugin_config', array('id'
        ), array('plugin', 'subtype', 'name', 'value'
        ));

        // Build the tree.
        $tcount->add_child($pluginconfigs);
        $pluginconfigs->add_child($pluginconfig);

        // Define sources.
        $tcount->set_source_table('tcount', array('id' => backup::VAR_ACTIVITYID
        ));
        $pluginconfig->set_source_table('tcount_plugin_config', array('tcount' => backup::VAR_PARENTID
        ));

        $this->add_subplugin_structure('tcountsocial', $tcount, true);
        $this->add_subplugin_structure('tcountview', $tcount, true);

        // Define file annotations
        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($tcount);
    }
}
