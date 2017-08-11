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
/**
 * Code fragment to define the version of msocial
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 * @package msocial
 *
 */
defined ( 'MOODLE_INTERNAL' ) || die ();

$plugin->version = 2017080100; // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2013051407.00; // Requires this Moodle version.2013111801.11.
$plugin->component = 'mod_msocial'; // Full name of the plugin (used for diagnostics).
$plugin->cron = 60 * 60; // Period for cron to check this module (secs).
$plugin->maturity = MATURITY_BETA;
$plugin->release = 'v0.1.0-beta';