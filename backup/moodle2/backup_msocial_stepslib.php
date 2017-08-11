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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/*
 * **************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
defined('MOODLE_INTERNAL') || die();

/** Define the complete msocial structure for backup, with file and id annotations */
class backup_msocial_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        global $DB;
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $msocial = new backup_nested_element('msocial', array('id'),
                array('name', 'intro', 'introformat', 'startdate', 'enddate', 'grade_expr'));
        $pluginconfigs = new backup_nested_element('plugin_configs');
        $pluginconfig = new backup_nested_element('plugin_config', null, array('plugin', 'subtype', 'name', 'value'));

        $pkis = new backup_nested_element('pkis');
        $pkicols = array_keys($DB->get_columns('msocial_pkis'));
        array_splice($pkicols, 0, 2); // Excludes id and msocial.
        $pki = new backup_nested_element('pki', null, $pkicols);

        $interactions = new backup_nested_element('interactions');
        $interaction = new backup_nested_element('interaction', ['uid'],
                ['fromid', 'nativeform', 'nativefromname', 'toid', 'nativeto', 'nativetoname', 'parentinteraction',
                                'source', 'timestamp', 'type', 'nativetype', 'description', 'rawdata']);

        $mapusers = new backup_nested_element('mapusers');
        $mapuser = new backup_nested_element('socialuser', ['userid','type'], ['socialid', 'socialname']);

        // Build the tree.
        $msocial->add_child($pluginconfigs);
        $msocial->add_child($pkis);
        $msocial->add_child($interactions);
        $msocial->add_child($mapusers);
        $pluginconfigs->add_child($pluginconfig);
        $pkis->add_child($pki);
        $interactions->add_child($interaction);
        $mapusers->add_child($mapuser);

        // Define sources.
        $msocial->set_source_table('msocial', array('id' => backup::VAR_ACTIVITYID));
        $pluginconfig->set_source_table('msocial_plugin_config', array('msocial' => backup::VAR_PARENTID));
        $pki->set_source_table('msocial_pkis', ['msocial' => backup::VAR_ACTIVITYID]);
        $interaction->set_source_table('msocial_interactions', ['msocial' => backup::VAR_ACTIVITYID]);
        $mapuser->set_source_table('msocial_mapusers', ['msocial' => backup::VAR_ACTIVITYID]);
        $this->add_subplugin_structure('msocialconnector', $msocial, true);
        $this->add_subplugin_structure('msocialview', $msocial, true);
        // Define id annotations.
        $mapuser->annotate_ids('user', 'userid');
        $pki->annotate_ids('user', 'user');
        $interaction->annotate_ids('user', 'toid');
        $interaction->annotate_ids('user', 'fromid');
        // Define file annotations.

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($msocial);
    }
}
