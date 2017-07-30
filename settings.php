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
defined('MOODLE_INTERNAL') || die();

// TODO: check duplication of admin menu.

$ADMIN->add('modsettings',
        new admin_category('modmsocialfolder', new lang_string('pluginname', 'mod_msocial'), $module->is_enabled() === false));

$modmsocialsettings = new admin_settingpage($section, get_string('settings', 'mod_msocial'), 'moodle/site:config',
        $module->is_enabled() === false);
$ADMIN->add('modmsocialfolder', $modmsocialsettings);

$ADMIN->add('modmsocialfolder',
        new admin_category('msocialviewplugins', new lang_string('socialviews', 'msocial'), !$module->is_enabled()));
$ADMIN->add('modmsocialfolder',
        new admin_category('msocialconnectorplugins', new lang_string('socialconnectors', 'msocial'), !$module->is_enabled()));

$modmsocialsettings->add(
        new admin_setting_heading('msocialconnector_header', get_string('socialconnectors', 'msocial'),
                get_string('socialconnectors', 'msocial')));

foreach (core_plugin_manager::instance()->get_plugins_of_type('msocialconnector') as $plugin) {
    /** @var \mod_msocial\plugininfo\msocialconnector $plugin */
    $plugin->load_settings($ADMIN, 'msocialconnectorplugins', $hassiteconfig);
    $modmsocialsettings->add(
            new admin_setting_configcheckbox($plugin->get_settings_section_name() . '/disabled',
                    get_string('disable_social_subplugin', 'msocial', $plugin),
                    get_string('disable_social_subplugin', 'msocial', $plugin), false));
}
$modmsocialsettings->add(
        new admin_setting_heading('msocialview_header', get_string('socialviews', 'msocial'), get_string('socialviews', 'msocial')));

foreach (core_plugin_manager::instance()->get_plugins_of_type('msocialview') as $plugin) {
    /** @var \mod_msocial\plugininfo\msocialview $plugin */
    $plugin->load_settings($ADMIN, 'msocialviewplugins', $hassiteconfig);
    $modmsocialsettings->add(
            new admin_setting_configcheckbox($plugin->get_settings_section_name() . '/disabled',
                    get_string('disable_view_subplugin', 'msocial', $plugin), get_string('disable_view_subplugin', 'msocial', $plugin),
                    false));
}

$settings = null;
 // Tell core we already added the settings structure.



