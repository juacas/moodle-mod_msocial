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

class backup_msocialconnector_questournament_subplugin extends backup_subplugin {

    /** Returns the subplugin information to attach to msocial element
     *
     * @return backup_subplugin_element */
    protected function define_msocial_subplugin_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
// TODO: decidir si usar pluginsconfig general...
//         $pluginconfigs = new backup_nested_element('questournament_configs');

        $subplugin->add_child($subpluginwrapper);
//         $subpluginwrapper->add_child($pluginconfigs);
//         $plugingconfig = new backup_nested_element('questournament_config', array('name'), array('value'));
//         $pluginconfigs->add_child($plugingconfig);
        // Map tables...
//         $plugingconfig->set_source_table('msocial_plugin_config',
//                 array('msocial' => backup::VAR_ACTIVITYID, 'subtype' => ['sqlparam'=> 'questournament']));

        return $subplugin;
    }
}
