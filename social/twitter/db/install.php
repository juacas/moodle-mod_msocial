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

/**
 * Post-install code for the tcountsocial_twitter module.
 *
 * @package tcountsocial_twitter
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Code run after the tcountsocial_twitter module database tables have been created.
 * Migrate from old tables
 * @global moodle_database $DB
 * @return bool
 */
function xmldb_tcountsocial_twitter_install() {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    $table = new xmldb_table('tcount_tokens');
    if ($dbman->table_exists($table)) {
        $sql = "insert into {tcount_twitter_tokens} select * from {tcount_tokens} where true";
        $DB->execute($sql);
        // Double check the copy of info.
        if ($DB->count_records('tcount_twitter_tokens') == $DB->count_records('tcount_tokens')) {
            $dbman->drop_table($table);
        }
    }
    $table = new xmldb_table('tcount_statuses');
    if ($dbman->table_exists($table)) {
        $sql = "insert into {tcount_tweets} select * from {tcount_statuses} where true";
        $DB->execute($sql);
        // Double check the copy of info.
        if ($DB->count_records('tcount_tweets') == $DB->count_records('tcount_statuses')) {
            $dbman->drop_table($table);
        }
    }

    require_once ($CFG->dirroot . '/mod/tcount/social/twitter/twitterplugin.php');
    $plugininfo = new mod_tcount\social\tcount_social_twitter(null);
    $plugininfo->create_pki_fields();
    return true;
}
