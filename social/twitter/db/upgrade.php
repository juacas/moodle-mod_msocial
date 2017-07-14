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
defined('MOODLE_INTERNAL') || die();

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_tcountsocial_twitter_upgrade($oldversion) {
    global $CFG, $THEME, $DB;
    /* @var $dbman database_manager */
    $dbman = $DB->get_manager();

    if ($oldversion < 2017071001) {
        require_once ($CFG->dirroot . '/mod/tcount/social/twitter/twitterplugin.php');
        $table = new xmldb_table('tcount_pkis');
        $plugininfo = new mod_tcount\social\tcount_social_twitter(null);
        $pkilist = $plugininfo->get_pki_list();
        foreach ($pkilist as $pkiname => $pki) {
            $pkifield = new xmldb_field($pkiname, XMLDB_TYPE_FLOAT, null, null, null, null, null);
            if (!$dbman->field_exists($table, $pkifield)) {
                $dbman->add_field($table, $pkifield);
            }
        }
        // Twitter savepoint reached.
        upgrade_plugin_savepoint(true, 2017071001, 'tcountsocial', 'twitter');
    }
}
