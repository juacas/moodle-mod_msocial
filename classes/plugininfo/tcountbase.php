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
namespace mod_tcount\plugininfo;

use core\plugininfo\base, core_plugin_manager, moodle_url;

require_once ($CFG->dirroot . '/mod/tcount/tcountsocialplugin.php');
defined('MOODLE_INTERNAL') || die();


class tcountbase extends base {

    private static $plugins = [];

    /**
     * Finds all installed plugins, the result may include missing plugins.
     * 
     * @param \stdClass $tcount record of the instance for innitiallizing plugins
     * @param string $subtype name of the subplugins types. ie. 'social','view'
     * @return array(tcountsocialplugin)|null of installed plugins $pluginname=>$plugin, null means
     *         unknown
     */
    public static function get_installed_plugins($tcount = null, $subtype=null) {
        global $DB;
        $plugins = core_plugin_manager::instance()->get_installed_plugins($subtype);
        if (!$plugins) {
            return array();
        }
        $installed = array();
        foreach ($plugins as $pluginname => $version) {
            $installed[] = $subtype . '_' . $pluginname;
        }
        $result = array();
        foreach ($plugins as $pluginname => $version) {
            $result[$pluginname] = \tcountbase::instance($tcount, $subtype, $pluginname);
        }
        return $result;
    }

    /**
     *
     * @param \stdClass $tcount record of the instance for innitiallizing plugins
     * @param unknown $subtype 'social' or 'view'
     * @param unknown $pluginname
     * @return unknown
     */
    public static function instance($tcount, $subtype, $pluginname) {
        $path = \core_component::get_plugin_directory('tcount'.$subtype, $pluginname);
        $classfile = $pluginname . 'plugin.php';
        if (file_exists($path . '/' . $classfile)) {
            require_once ($path . '/' . $classfile);
            $pluginclass = '\mod_tcount\\'.$subtype.'\\tcount_'.$subtype.'_'.$pluginname;
            $plugin = new $pluginclass($tcount);
            return $plugin;
        }
    }

    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * 
     * @param \stdClass $tcount record of the instance for innitiallizing plugins
     * @param unknown $subtype 'social' or 'view'
     * @return array(tcountsocialplugin)|null of enabled plugins $pluginname=>$plugin, null means
     *         unknown
     */
    public static function get_enabled_plugins($tcount = null, $subtype = null) {
        global $DB;
        if (!isset(self::$plugins[$subtype])) {
            $plugins = core_plugin_manager::instance()->get_installed_plugins('tcount' . $subtype);
            if (!$plugins) {
                return array();
            }
            $installed = array();
            foreach ($plugins as $pluginname => $version) {
                $installed[] = 'tcount'.$subtype.'_' . $pluginname;
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
            self::$plugins[$subtype]= $plugins;
        } else {
            $plugins = self::$plugins[$subtype];
        }
        $enabled = array();
        foreach ($plugins as $pluginname => $version) {
            $enabled[$pluginname] = self::instance($tcount, $subtype,$pluginname);
        }
        return $enabled;
    }

    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Return URL used for management of plugins of this type.
     *
     * @return moodle_url
     */
    public static function get_manage_url() {
        return new moodle_url('/mod/tcount/adminmanageplugins.php', array('subtype' => 'tcountsocial'));
    }

    /**
     * Pre-uninstall hook.
     * @private
     */
    public function uninstall_cleanup() {
        global $DB;
        
        $DB->delete_records('tcount_plugin_config', array('plugin' => $this->name, 'subtype' => 'tcountsocial'));
        
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
            include ($this->full_path('settings.php'));
        }
        $adminroot->add($this->type . 'plugins', $settings);
    }
}
