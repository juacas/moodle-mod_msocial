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

namespace mod_tcount\plugininfo;

use core\plugininfo\base, core_plugin_manager, moodle_url;

defined('MOODLE_INTERNAL') || die();


class tcountview extends tcountbase {
    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * @return array[string]\tcount_view_plugin |null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_view_plugins($tcount=null) {
        return parent::get_enabled_plugins($tcount,'view');
    }
    public static function get_enabled_plugins($tcount = null,$subtype=null) {
        return self::get_enabled_view_plugins($tcount);
    }
    public function is_uninstall_allowed() {
        return true;
    }

   
    /**
     * Pre-uninstall hook.
     * @private
     */
    public function uninstall_cleanup() {
        global $DB;

        $DB->delete_records('tcount_plugin_config', array('plugin'=>$this->name, 'subtype'=>'tcountview'));

        parent::uninstall_cleanup();
    }

    public function get_settings_section_name() {
        return $this->type . '_' . $this->name;
    }

    /**
     * Loads plugin settings to the settings tree
     *
     * This function usually includes settings.php file in plugins folder.
     * Alternatively it can create a link to some settings page (instance of admin_externalpage)
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig whether the current user has moodle/site:config capability
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);

        if ($adminroot->fulltree) {
            $shortsubtype = substr($this->type, strlen('tcount'));
            include($this->full_path('settings.php'));
        }

        $adminroot->add($parentnodename, $settings);
    }
}