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
 * @package mod-clusterer
 * @copyright 2009 Your Name
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
defined('MOODLE_INTERNAL') || die();

/** xmldb_msocial_upgrade
 *
 * TODO: Clean upgrade function.
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 *
 */
function xmldb_msocial_upgrade($oldversion = 0) {
    global $CFG, $THEME, $DB;
    /* @var $dbman database_manager */
    $dbman = $DB->get_manager();

    if ($oldversion < 2016092600) {
        $table = new xmldb_table('msocial_tokens');
        $field = new xmldb_field('errorstatus', XMLDB_TYPE_CHAR, 50);
        $dbman->add_field($table, $field);
        upgrade_mod_savepoint(true, 2016092600, 'msocial');
    }
    if ($oldversion < 2016092700) {
        $table = new xmldb_table('msocial');
        $field = new xmldb_field('fbsearch', XMLDB_TYPE_CHAR, 512);
        $dbman->add_field($table, $field);
        upgrade_mod_savepoint(true, 2016092700, 'msocial');
    }
    if ($oldversion < 2017053100) {
        $table = new xmldb_table('msocial_plugin_config');
        $table->addField(new xmldb_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE));
        $table->addField(new xmldb_field('msocial', XMLDB_TYPE_INTEGER, 10, false, XMLDB_NOTNULL, false, 0));
        $table->addField(new xmldb_field('plugin', XMLDB_TYPE_CHAR, 28, false, XMLDB_NOTNULL));
        $table->addField(new xmldb_field('subtype', XMLDB_TYPE_CHAR, 28, false, XMLDB_NOTNULL));
        $table->addField(new xmldb_field('name', XMLDB_TYPE_CHAR, 28, false, XMLDB_NOTNULL));
        $table->addField(new xmldb_field('value', XMLDB_TYPE_TEXT, null, false, false));

        $table->addKey(new xmldb_key('primary', XMLDB_KEY_PRIMARY, ['id']));
        $table->addKey(new xmldb_key('msocial', XMLDB_KEY_FOREIGN, ['msocial'], 'msocial', 'id'));
        $table->addIndex(new xmldb_index('plugin', XMLDB_INDEX_NOTUNIQUE, ['plugin']));
        $table->addIndex(new xmldb_index('subtype', XMLDB_INDEX_NOTUNIQUE, ['subtype']));
        $table->addIndex(new xmldb_index('name', XMLDB_INDEX_NOTUNIQUE, ['name']));

        $dbman->create_table($table);
        upgrade_mod_savepoint(true, 2017053100, 'msocial');
    }
    if ($oldversion < 2017060500) {
        $table = new xmldb_table('msocial');
        $field = new xmldb_field('hashtag');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('fbsearch');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('twfieldid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('fbfieldid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $dbman->drop_field($table, new xmldb_field('widget_id'));
        $dbman->rename_field($table, new xmldb_field('counttweetsfromdate', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, 0),
                'startdate');
        $dbman->rename_field($table, new xmldb_field('counttweetstodate', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, 0), 'enddate');
        upgrade_mod_savepoint(true, 2017060500, 'msocial');
    }
    if ($oldversion < 2017060500) {

        // Define table msocial_interactions to be created.
        $table = new xmldb_table('msocial_interactions');

        // Adding fields to table msocial_interactions.
        $table->add_field('uid', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('msocial', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('fromid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('nativefrom', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('nativefromname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('toid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('nativeto', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('nativetoname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('parentinteraction', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('nativetype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('rawdata', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table msocial_interactions.
        $table->add_key('uid_msocial', XMLDB_KEY_UNIQUE, array('uid', 'msocial'));

        // Adding indexes to table msocial_interactions.
        $table->add_index('source', XMLDB_INDEX_NOTUNIQUE, array('source'));
        $table->add_index('timestamp', XMLDB_INDEX_NOTUNIQUE, array('timestamp'));
        $table->add_index('from', XMLDB_INDEX_NOTUNIQUE, array('fromid'));
        $table->add_index('msocial', XMLDB_INDEX_NOTUNIQUE, array('msocial'));

        // Conditionally launch create table for msocial_interactions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Define table msocial_mapusers to be created.
        $table = new xmldb_table('msocial_mapusers');
        // Conditionally launch create table for msocial_mapusers.
        if (!$dbman->table_exists($table)) {
            // Adding fields to table msocial_mapusers.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('msocial', XMLDB_TYPE_INTEGER, '9', null, null, null, '0');
            $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('socialid', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('socialname', XMLDB_TYPE_CHAR, '100', null, null, null, '0');

            // Adding keys to table msocial_mapusers.
            $table->add_key('msocial_userid', XMLDB_KEY_UNIQUE, array('msocial', 'userid', 'type'));
            $table->add_key('msocial_connectorid', XMLDB_KEY_UNIQUE, array('msocial', 'socialid', 'type'));

            $dbman->create_table($table);
        }

        // MSocial savepoint reached.
        upgrade_mod_savepoint(true, 2017060500, 'msocial');
    }
    if ($oldversion < 2017071000) {

        // Define table msocial_pkis to be created.
        $table = new xmldb_table('msocial_pkis');

        // Adding fields to table msocial_pkis.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('msocial', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('historical', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table msocial_pkis.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('msocial_user_time', XMLDB_KEY_UNIQUE, array('msocial', 'user', 'timestamp'));

        // Adding indexes to table msocial_pkis.
        $table->add_index('historical_idx', XMLDB_INDEX_NOTUNIQUE, array('historical'));
        $table->add_index('msocial_idx', XMLDB_INDEX_NOTUNIQUE, array('msocial'));

        // Conditionally launch create table for msocial_pkis.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // MSocial savepoint reached.
        upgrade_mod_savepoint(true, 2017071000, 'msocial');
    }
    if ($oldversion < 2017081801) {
        // Define field rejectedto be added to msocial_interactions.
        $table = new xmldb_table('msocial_interactions');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0 , 'nativetype');

        // Conditionally launch add field rejected.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Msocial savepoint reached.
        upgrade_mod_savepoint(true, 2017081801, 'msocial');
    }
    return true;
}
