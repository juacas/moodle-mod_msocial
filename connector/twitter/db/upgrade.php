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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_msocialconnector_twitter_upgrade($oldversion) {
    global $CFG, $DB;
    if ($oldversion < 2017091900) {
        // Twitter savepoint reached.
        upgrade_plugin_savepoint(true, 2017091900, 'msocialconnector', 'twitter');
    }
    require_once($CFG->dirroot . '/mod/msocial/connector/twitter/twitterplugin.php');
    $plugininfo = new mod_msocial\connector\msocial_connector_twitter(null);
    $plugininfo->create_pki_fields();
    return true;
}
