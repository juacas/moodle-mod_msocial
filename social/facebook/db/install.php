<?php
use tcount\tcount_plugin;

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
 * Post-install code for the tcountsocial_facebook module.
 *
 * @package tcountsocial_facebook
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Code run after the tcountsocial_facebook module database tables have been created.
 * Migrate from old tables
 * @global moodle_database $DB
 * @return bool
 */
function xmldb_tcountsocial_facebook_install() {
    global $CFG, $DB;
    require_once ($CFG->dirroot . '/mod/tcount/social/facebook/facebookplugin.php');
    $plugin = new mod_tcount\social\tcount_social_facebook(null);
    $plugin->create_pki_fields();
    return true;
}
