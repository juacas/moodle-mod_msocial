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
 * This file keeps track of upgrades to the tcount module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installtion to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do. The commands in
 * here will all be database-neutral, using the functions defined in
 * lib/ddllib.php
 *
 * @package mod-clusterer
 * @copyright 2009 Your Name
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * xmldb_tcountview_graph_upgrade
 *
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_tcountview_graph_upgrade($oldversion = 0) {
    global $CFG, $DB;
    if ($oldversion < 2017071302) {
        require_once ($CFG->dirroot . '/mod/tcount/view/graph/graphplugin.php');
        $plugininfo = new mod_tcount\view\tcount_view_graph(null);
        $plugininfo->create_pki_fields();
        // graph savepoint reached.
        upgrade_plugin_savepoint(true, 2017071302, 'tcountview', 'graph');
    }
}
