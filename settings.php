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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle. If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die();

$ADMIN->add('modsettings', new admin_category('modtcountfolder', new lang_string('pluginname', 'mod_tcount'), $module->is_enabled() === false));

$modtcountsettings = new admin_settingpage($section, get_string('settings', 'mod_tcount'), 'moodle/site:config', $module->is_enabled() === false);
$ADMIN->add('modtcountfolder', $modtcountsettings);

$ADMIN->add('modtcountfolder', new admin_category('tcountviewplugins', new lang_string('socialviews', 'tcount'), !$module->is_enabled()));
$ADMIN->add('modtcountfolder', new admin_category('tcountsocialplugins', new lang_string('socialconnectors', 'tcount'), !$module->is_enabled()));

$modtcountsettings->add(new admin_setting_heading('tcountsocial_header', get_string('socialconnectors','tcount'),get_string('socialconnectors','tcount')));

foreach (core_plugin_manager::instance()->get_plugins_of_type('tcountsocial') as $plugin) {
    /** @var \mod_tcount\plugininfo\tcountsocial $plugin */
    $plugin->load_settings($ADMIN, 'tcountsocialplugins', $hassiteconfig); 
    $modtcountsettings->add(new admin_setting_configcheckbox($plugin->get_settings_section_name().'/disabled', get_string('disable_social_subplugin','tcount',$plugin),get_string('disable_social_subplugin','tcount',$plugin), false));
}
$modtcountsettings->add(new admin_setting_heading('tcountview_header', get_string('socialviews','tcount'),get_string('socialviews','tcount')));

foreach (core_plugin_manager::instance()->get_plugins_of_type('tcountview') as $plugin) {
     /** @var \mod_tcount\plugininfo\tcountview $plugin */
     $plugin->load_settings($ADMIN,  'tcountviewplugins', $hassiteconfig);
     $modtcountsettings->add(new admin_setting_configcheckbox($plugin->get_settings_section_name().'/disabled', get_string('disable_view_subplugin','tcount',$plugin),get_string('disable_view_subplugin','tcount',$plugin), false));
}

 $settings=null;
 // Tell core we already added the settings structure.



