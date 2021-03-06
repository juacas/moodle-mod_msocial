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
/*
 * **************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
namespace mod_msocial\plugininfo;

defined('MOODLE_INTERNAL') || die();

use core\plugininfo\base, core_plugin_manager, moodle_url;
use mod_msocial\msocial_plugin;

class msocialbase extends base {
    private static $plugins = [];

    /** Finds all installed plugins, the result may include missing plugins.
     *
     * @param \stdClass $msocial record of the instance for innitiallizing plugins
     * @param string $subtype name of the subplugins types. ie. 'connector','view'
     * @return msocial_plugin[] of installed plugins $pluginname=>$plugin */
    public static function get_installed_plugins($msocial = null, $subtype = null) {
        global $DB;
        $installed = array();
        $subtypes = array();
        $result = array();

        if ($subtype == null) {
            $subtypes = ['connector', 'view'];
        } else {
            $subtypes = [$subtype];
        }

        foreach ($subtypes as $subtype) {
            $plugins = core_plugin_manager::instance()->get_installed_plugins('msocial'.$subtype);
            foreach ($plugins as $pluginname => $version) {
                $installed[] = $subtype . '_' . $pluginname;
                $result[$pluginname] = self::instance($msocial, $subtype, $pluginname);
            }
        }
        return $result;
    }

    /**
     * @param \stdClass $msocial record of the instance for initializing plugins
     * @param string $subtype 'connector' or 'view'
     * @param string $pluginname
     * @return msocial_plugin */
    public static function instance($msocial, $subtype, $pluginname) {
        $path = \core_component::get_plugin_directory('msocial' . $subtype, $pluginname);
        $classfile = $pluginname . 'plugin.php';
        if (file_exists($path . '/' . $classfile)) {
            require_once($path . '/' . $classfile);
            $pluginclass = '\mod_msocial\\' . $subtype . '\\msocial_' . $subtype . '_' . $pluginname;
            $plugin = new $pluginclass($msocial);
            return $plugin;
        }
    }

    /** Finds all system-wide enabled plugins, the result may include missing plugins.
     * First connectors, then views.
     * @param \stdClass $msocial record of the instance for innitiallizing plugins
     * @return msocial_plugin[] of enabled plugins $pluginname=>$plugin,
     */
    public static function get_system_enabled_plugins_all_types($msocial = null) {
        $connectors = self::get_system_enabled_plugins($msocial, 'connector');
        $views = self::get_system_enabled_plugins($msocial, 'view');
        return array_merge($connectors, $views);
    }
    /** Finds all activity-wide enabled plugins, the result may include missing plugins.
     * First connectors, then views.
     * @param \stdClass $msocial record of the instance for innitiallizing plugins
     * @return array(msocial_plugin)|null of enabled plugins $pluginname=>$plugin, null means
     *         unknown */
    public static function get_enabled_plugins_all_types($msocial = null) {
        $connectors = self::get_enabled_plugins($msocial, 'connector');
        $views = self::get_enabled_plugins($msocial, 'view');
        return array_merge($connectors, $views);
    }

    /** Finds all system-wide enabled plugins, the result may include missing plugins.
     *
     * @param \stdClass $msocial record of the instance for initiallizing plugins
     * @param string $subtype 'connector' or 'view'
     * @return msocialconnectorplugin[] of enabled plugins $pluginname=>$plugin */
    public static function get_system_enabled_plugins($msocial = null, $subtype = null) {
        global $DB;
        if (!isset(self::$plugins[$subtype])) {
            if ($subtype == null) {
                return self::get_system_enabled_plugins_all_types($msocial);
            } else {
                $plugins = core_plugin_manager::instance()->get_installed_plugins('msocial' . $subtype);
            }
            if (!$plugins) {
                return array();
            }
            $installed = array();
            foreach ($plugins as $pluginname => $version) {
                $installed[] = 'msocial' . $subtype . '_' . $pluginname;
            }

            list($installed, $params) = $DB->get_in_or_equal($installed, SQL_PARAMS_NAMED);
            $disabled = $DB->get_records_select('config_plugins', "plugin $installed AND name = 'disabled'", $params, 'plugin ASC');
            foreach ($disabled as $conf) {
                if (empty($conf->value)) {
                    continue;
                }
                list($type, $name) = explode('_', $conf->plugin, 2);
                unset($plugins[$name]);
            }
            self::$plugins[$subtype] = $plugins;
        } else {
            $plugins = self::$plugins[$subtype];
        }
        $enabled = array();
        foreach ($plugins as $pluginname => $version) {
            $enabled[$pluginname] = self::instance($msocial, $subtype, $pluginname);
        }
        return $enabled;
    }
    /**
     * Gets all instance-wide enabled plugins.
     * @param \stdClass $msocial
     * @param string $subtype 'connector' or 'view'
     * @return array
     */
    public static function get_enabled_plugins($msocial = null, $subtype = null) {
        $systemenabled = self::get_system_enabled_plugins($msocial, $subtype);
        $enabled = array_filter($systemenabled, function(msocial_plugin $plugin) {
            return $plugin->is_enabled();
        });
        return $enabled;
    }
    public function is_uninstall_allowed() {
        return true;
    }

    /** Return URL used for management of plugins of this type.
     *
     * @return moodle_url */
    public static function get_manage_url() {
        return new moodle_url('/mod/msocial/adminmanageplugins.php', array('subtype' => 'msocialconnector'));
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

        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);

        if ($adminroot->fulltree) {
            $shortsubtype = substr($this->type, strlen('msocial'));
            include ($this->full_path('settings.php'));
        }
        $adminroot->add($this->type . 'plugins', $settings);
    }
}
