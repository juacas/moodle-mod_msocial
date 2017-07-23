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
/** This file contains the settings definition for the twitter social plugin
 *
 * @package msocial_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
defined('MOODLE_INTERNAL') || die();
$settings->add(new admin_setting_heading('mod_msocial_tweeter_header', 'Tweeter API', 'Keys for Tweeter API Access.'));
$settings->addHelpButton('msocialconnector_twitter/consumer_key', 'widget_id', 'msocial');
// TODO: Add explaination text about Twitter app id, need, and implications.
$settings->add(
        new admin_setting_configtext('msocialconnector_twitter/consumer_key', get_string('msocial_consumer_key', 'msocialconnector_twitter'),
                get_string('config_consumer_key', 'msocialconnector_twitter'), '', PARAM_RAW_TRIMMED));

$settings->add(
        new admin_setting_configtext('msocialconnector_twitter/consumer_secret',
                get_string('msocial_consumer_secret', 'msocialconnector_twitter'),
                get_string('config_consumer_secret', 'msocialconnector_twitter'), '', PARAM_RAW_TRIMMED));

