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
    global $CFG, $DB;
    if ($oldversion < 2017071001) {
        require_once ($CFG->dirroot . '/mod/tcount/social/twitter/twitterplugin.php');
        $plugininfo = new mod_tcount\social\tcount_social_twitter(null);
        $plugininfo->create_pki_fields();
        // Twitter savepoint reached.
        upgrade_plugin_savepoint(true, 2017071001, 'tcountsocial', 'twitter');
    }
}
