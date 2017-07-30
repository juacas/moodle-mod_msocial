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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
namespace mod_msocial\plugininfo;

require_once ('msocialbase.php');
require_once ($CFG->dirroot . '/mod/msocial/msocialconnectorplugin.php');
defined('MOODLE_INTERNAL') || die();

class msocialconnector extends msocialbase {

    /** Finds all installed plugins, the result may include missing plugins.
     *
     * @return array(msocialconnectorplugin)|null of installed plugins $pluginname=>$plugin, null means
     *         unknown */
    public static function get_installed_connector_plugins($msocial = null) {
        return parent::get_installed_plugins($msocial, 'connector');
    }

    public static function get_enabled_plugins($msocial = null, $subtype = null) {
        return self::get_enabled_connector_plugins($msocial);
    }

    /** Finds all enabled plugins, the result may include missing plugins.
     *
     * @return array(msocialconnectorplugin)|null of enabled plugins $pluginname=>$plugin, null means
     *         unknown */
    public static function get_enabled_connector_plugins($msocial = null) {
        return parent::get_enabled_plugins($msocial, 'connector');
    }

    public function is_uninstall_allowed() {
        return true;
    }

    /** Pre-uninstall hook.
     * @private */
    public function uninstall_cleanup() {
        global $DB;

        $DB->delete_records('msocial_plugin_config', array('plugin' => $this->name, 'subtype' => 'msocialconnector'));

        parent::uninstall_cleanup();
    }

    public function get_settings_section_name() {
        return $this->type . '_' . $this->name;
    }

    /** Loads plugin settings to the settings tree
     *
     * This function usually includes settings.php file in plugins folder.
     * Alternatively it can create a link to some settings page (instance of admin_externalpage)
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig whether the current user has moodle/site:config capability */
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
        $enabled = $this->is_enabled();
        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $enabled === false);

        if ($adminroot->fulltree) {
            $shortsubtype = substr($this->type, strlen('msocial'));
            include ($this->full_path('settings.php'));
        }
        $adminroot->add($parentnodename, $settings);
        $settings = null;
    }
}
