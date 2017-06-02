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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * This file contains the settings definition for the twitter social plugin
 *
 * @package    tcount_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_tcount_tweeter_header', 'Tweeter API', 'Keys for Tweeter API Access.'));

    $settings->add(new admin_setting_configtext('mod_tcount_consumer_key', get_string('tcount_consumer_key', 'tcountsocial_twitter'),
            get_string('config_consumer_key', 'tcountsocial_twitter'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('mod_tcount_consumer_secret', get_string('tcount_consumer_secret', 'tcountsocial_twitter'),
            get_string('config_consumer_secret', 'tcountsocial_twitter'), '', PARAM_RAW_TRIMMED));
}
//$ADMIN->add('modtcountfolder', $settings);
// Tell core we already added the settings structure.
//$settings = null;

