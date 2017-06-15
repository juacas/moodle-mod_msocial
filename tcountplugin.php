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

/**
 * library class for tcount plugins base class
 *
 * @package tcountsocial_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tcount;

use mod_tcount\social\pki;

defined('MOODLE_INTERNAL') || die();


abstract class tcount_plugin {

    const CONFIG_ENABLED = 'enabled';
    
    const CAT_VISUALIZATION = 'Visualization';
    const CAT_ANALYSIS = 'Analysis';
    const CAT_RESULTS = 'Results';

    /**
     *
     * @var tcount $tcount the tcount record that contains the global
     *      settings for this instance
     */
    protected $tcount;

    /**
     *
     * @var course_modinfo $cm info about the module
     */
    protected $cm;

    /** @var string $type tcount plugin type */
    private $type = '';

    /** @var string $error error message */
    private $error = '';

    /** @var boolean|null $enabledcache Cached lookup of the is_enabled function */
    private $enabledcache = null;

    /** @var boolean|null $enabledcache Cached lookup of the is_visible function */
    private $visiblecache = null;

    /**
     * Constructor for the abstract plugin type class
     *
     * @param tcount $tcount
     * @param string $type
     */
    public function __construct($tcount, $type) {
        $this->tcount = (object) $tcount;
        $this->type = $type;
        if (isset($tcount->id)) {
            $cm = get_coursemodule_from_instance('tcount', $tcount->id, null, null);
            $this->cm = $cm;
        }
    }

    /**
     * Is this the first plugin in the list?
     *
     * @return bool
     */
    public final function is_first() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        
        if ($order == 0) {
            return true;
        }
        return false;
    }

    /**
     * Is this the last plugin in the list?
     *
     * @return bool
     */
    public final function is_last() {
        $lastindex = count(core_component::get_plugin_list($this->get_subtype())) - 1;
        $currentindex = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        if ($lastindex == $currentindex) {
            return true;
        }
        
        return false;
    }

    /**
     * This function should be overridden to provide an array of elements that can be added to a
     * moodle
     * form for display in the settings page.
     * 
     * @param MoodleQuickForm $mform The form to add the elements to
     * @return $array
     */
    public function get_settings(\MoodleQuickForm $mform) {
        return;
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     * 
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        return;
    }

    /**
     * composes this subplugin's field name for the forms
     * 
     * @param string $setting
     * @return string
     */
    protected function get_form_field_name($setting) {
        return $this->get_type() . '_' . $this->get_subtype() . '_' . $setting;
    }

    /**
     * The tcount subtype is responsible for saving it's own settings as the database table for the
     * standard type cannot be modified.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(\stdClass $formdata) {
        return true;
    }

    /**
     * Save the error message from the last error
     *
     * @param string $msg - the error description
     */
    protected final function set_error($msg) {
        $this->error = $msg;
    }

    /**
     * What was the last error?
     *
     * @return string
     */
    public final function get_error() {
        return $this->error;
    }

    /**
     * Should return the name of this plugin type.
     *
     * @return string - the name
     */
    public abstract function get_name();

    /**
     * Should return the subtype of this plugin.
     *
     * @return string - either 'tcountsocial' or 'tcountview'
     */
    public abstract function get_subtype();

    /**
     * Subclassification for grouping in UI.
     * 
     * @return string category name
     */
    public abstract function get_category();

    /**
     * Should return the type of this plugin.
     *
     * @return string - the type
     */
    public final function get_type() {
        return $this->type;
    }
    /**
     * Reports the list of PKI offered by this plugin.
     * This method does not include any values, just metadata.
     *
     * @return array[string]pki list of PKI names indexed by name
     */
    public abstract function get_pki_list();
    /**
     * Get the installed version of this plugin
     *
     * @return string
     */
    public final function get_version() {
        $version = get_config($this->get_subtype() . '_' . $this->get_type(), 'version');
        if ($version) {
            return $version;
        } else {
            return '';
        }
    }

    /**
     * Get the required moodle version for this plugin
     *
     * @return string
     */
    public final function get_requires() {
        $requires = get_config($this->get_subtype() . '_' . $this->get_type(), 'requires');
        if ($requires) {
            return $requires;
        } else {
            return '';
        }
    }

    /**
     * Set this plugin to enabled
     *
     * @return bool
     */
    public final function enable() {
        $this->enabledcache = true;
        return $this->set_config(tcount_plugin::CONFIG_ENABLED, 1);
    }

    /**
     * Set this plugin to disabled
     *
     * @return bool
     */
    public final function disable() {
        $this->enabledcache = false;
        return $this->set_config('enabled', 0);
    }

    /**
     * Allows hiding this plugin from the submission/feedback screen if it is not enabled.
     *
     * @return bool - if false - this plugin will not accept submissions / feedback
     */
    public function is_enabled() {
        if ($this->enabledcache === null) {
            $this->enabledcache = $this->get_config('enabled');
        }
        return $this->enabledcache;
    }

    /**
     * Get the numerical sort order for this plugin
     *
     * @return int
     */
    public final function get_sort_order() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        return $order ? $order : 0;
    }

    /**
     * Is this plugin enabled?
     *
     * @return bool
     */
    public final function is_visible() {
        // if ($this->visiblecache === null) {
        // $enabled = get_config($this->get_type() . '_' . $this->get_subtype(), 'enabled');
        // $this->visiblecache = $enabled;
        // }
        // return $this->visiblecache;
        return true;
    }

    /**
     * Has this plugin got a custom settings.php file?
     *
     * @return bool
     */
    public final function has_admin_settings() {
        global $CFG;
        
        $pluginroot = $CFG->dirroot . '/mod/tcount/' . substr($this->get_subtype(), strlen('tcount')) . '/' . $this->get_type();
        $settingsfile = $pluginroot . '/settings.php';
        return file_exists($settingsfile);
    }

    /**
     * Set a configuration value for this plugin
     *
     * @param string $name The config key
     * @param string $value The config value
     * @return bool
     */
    public final function set_config($name, $value) {
        global $DB;
        
        $dbparams = array('tcount' => $this->tcount->id, 'subtype' => $this->get_subtype(), 'plugin' => $this->get_type(), 
                        'name' => $name);
        $current = $DB->get_record('tcount_plugin_config', $dbparams, '*', IGNORE_MISSING);
        
        if ($current) {
            $current->value = $value;
            return $DB->update_record('tcount_plugin_config', $current);
        } else {
            $setting = new stdClass();
            $setting->tcount = $this->tcount->id;
            $setting->subtype = $this->get_subtype();
            $setting->plugin = $this->get_type();
            $setting->name = $name;
            $setting->value = $value;
            
            return $DB->insert_record('tcount_plugin_config', $setting) > 0;
        }
    }

    /**
     * Get a configuration value for this plugin
     *
     * @param mixed $setting The config key (string) or null
     * @return mixed | null
     */
    public final function get_config($setting = null) {
        global $DB;
        
        if ($setting) {
            if (!$this->tcount) {
                return false;
            }
            $tcount = $this->tcount;
            if ($tcount) {
                $dbparams = array('tcount' => $tcount->id, 'subtype' => $this->get_subtype(), 'plugin' => $this->get_type(), 
                                'name' => $setting);
                $result = $DB->get_record('tcount_plugin_config', $dbparams, '*', IGNORE_MISSING);
                if ($result) {
                    return $result->value;
                }
            }
            return null;
        }
        $dbparams = array('tcount' => $this->tcount->id, 'subtype' => $this->get_subtype(), 'plugin' => $this->get_type());
        $results = $DB->get_records('tcount_plugin_config', $dbparams);
        
        $config = new stdClass();
        if (is_array($results)) {
            foreach ($results as $setting) {
                $name = $setting->name;
                $config->$name = $setting->value;
            }
        }
        return $config;
    }

    /**
     * The assignment has been deleted - remove the plugin specific data
     *
     * @return bool
     */
    public function delete_instance() {
        return true;
    }

    /**
     * Run cron for this plugin
     */
    public static function cron() {
    }

    /**
     * Is this assignment plugin empty? (ie no submission or feedback)
     * 
     * @param stdClass $submissionorgrade assign_submission or assign_grade
     * @return bool
     */
    public function is_empty(stdClass $submissionorgrade) {
        return true;
    }

    /**
     * This allows a plugin to render a page in the context of the tcount
     *
     * If the plugin creates a link to the tcount view.php page with
     * The following required parameters:
     * id=coursemoduleid
     * plugin=type
     * pluginsubtype=tcountview|tcountsocial
     * pluginaction=customaction
     *
     * Then this function will be called to display the page with the pluginaction passed as action
     * 
     * @param string $action The plugin specified action
     * @return string
     */
    public function view_page($action) {
        return '';
    }

    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main tcount page.
     *
     * @return string
     */
    public function view_header() {
        return '';
    }

    /**
     * If this plugin should not include a column in the grading table or a row on the summary page
     * then return false
     *
     * @return bool
     */
    public function has_user_summary() {
        return true;
    }

    /**
     * If true, the plugin will appear on the module settings page and can be
     * enabled/disabled per tcount instance.
     *
     * @return bool
     */
    public function is_configurable() {
        return true;
    }
}
