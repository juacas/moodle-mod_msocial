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
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_tcount_tweeter_header', 'Tweeter API', 'Keys for Tweeter API Access.'));

    $settings->add(new admin_setting_configtext('mod_tcount_consumer_key', get_string('tcount_consumer_key', 'tcount'),
            get_string('config_consumer_key', 'tcount'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('mod_tcount_consumer_secret', get_string('tcount_consumer_secret', 'tcount'),
            get_string('config_consumer_secret', 'tcount'), '', PARAM_RAW_TRIMMED));
}