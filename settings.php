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

$ADMIN->add('modsettings', new admin_category('modtcountfolder', new lang_string('pluginname', 'mod_tcount'), $module->is_enabled() === false));

$settings = new admin_settingpage($section, get_string('settings', 'mod_tcount'), 'moodle/site:config', $module->is_enabled() === false);
if ($ADMIN->fulltree) {
    
}
$ADMIN->add('modtcountfolder', $settings);
// Tell core we already added the settings structure.
$settings = null;

$ADMIN->add('modtcountfolder', new admin_category('tcountsocialplugins',
    new lang_string('socialconnectors', 'tcount'), !$module->is_enabled()));
//$ADMIN->add('tcountsocialplugins', new assign_admin_page_manage_assign_plugins('assignsubmission'));
$ADMIN->add('modassignfolder', new admin_category('tcountviewplugins',
    new lang_string('socialviews', 'tcount'), !$module->is_enabled()));
//$ADMIN->add('assignfeedbackplugins', new assign_admin_page_manage_assign_plugins('assignfeedback'));

foreach (core_plugin_manager::instance()->get_plugins_of_type('tcountsocial') as $plugin) {
    /** @var \mod_tcount\plugininfo\tcountsocial $plugin */
    $plugin->load_settings($ADMIN, 'tcountsocialplugins', $hassiteconfig);
}

foreach (core_plugin_manager::instance()->get_plugins_of_type('tcountview') as $plugin) {
    /** @var \mod_tcount\plugininfo\tcountview $plugin */
    $plugin->load_settings($ADMIN, 'tcountviewplugins', $hassiteconfig);
}
