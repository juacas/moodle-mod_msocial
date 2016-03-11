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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Code fragment to define the version of tcount
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author
 * @version $Id: version.php,v 1.3 2006/08/28 16:41:20 mark-nielsen Exp $
 * @package tcount
 *
 */
defined('MOODLE_INTERNAL') || die;

$module->version = 2015060108;    // The current module version (Date: YYYYMMDDXX).
$module->requires = 2013051407.00;    // Requires this Moodle version.2013111801.11.
$module->component = 'mod_tcount'; // Full name of the plugin (used for diagnostics).
$module->cron = 10 * 60;          // Period for cron to check this module (secs).