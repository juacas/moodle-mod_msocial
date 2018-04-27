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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
/** This file keeps track of upgrades to the msocial module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installtion to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do. The commands in
 * here will all be database-neutral, using the functions defined in
 * lib/ddllib.php
 *
 * @package mod-msocial
 * @copyright 2009 Your Name
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
defined('MOODLE_INTERNAL') || die();

/** xmldb_msocialconnector_questournament_upgrade
 *
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool */
function xmldb_msocialconnector_questournament_upgrade($oldversion = 0) {
    global $CFG;

    if ($oldversion < 2017092201) {
        // Questournament savepoint reached.
        upgrade_plugin_savepoint(true, 2017092201, 'msocialconnector', 'questournament');
    }
    require_once($CFG->dirroot . '/mod/msocial/connector/questournament/questournamentplugin.php');
    $plugininfo = new mod_msocial\connector\msocial_connector_questournament(null);
    $plugininfo->create_kpi_fields();

    return true;
}
