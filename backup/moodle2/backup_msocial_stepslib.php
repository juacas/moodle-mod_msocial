<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
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
// along with MSocial for Moodle. If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die();
/**
 * Define the complete msocial structure for backup, with file and id annotations
 */
class backup_msocial_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $msocial = new backup_nested_element('msocial', array('id'),
                array('name', 'intro', 'introformat', 'startdate', 'enddate', 'grade_expr'));
        $pluginconfigs = new backup_nested_element('plugin_configs');
        $pluginconfig = new backup_nested_element('plugin_config', array('id'), array('plugin', 'subtype', 'name', 'value'));

        // Build the tree.
        $msocial->add_child($pluginconfigs);
        $pluginconfigs->add_child($pluginconfig);

        // Define sources.
        $msocial->set_source_table('msocial', array('id' => backup::VAR_ACTIVITYID));
        $pluginconfig->set_source_table('msocial_plugin_config', array('msocial' => backup::VAR_PARENTID));

        $this->add_subplugin_structure('msocialconnector', $msocial, true);
        $this->add_subplugin_structure('msocialview', $msocial, true);

        // Define file annotations
        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($msocial);
    }
}
