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

/** library class for msocial plugins base class
 *
 * @package msocialconnector_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace msocial;

defined('MOODLE_INTERNAL') || die();
require_once ('classes/plugininfo/msocialbase.php');
require_once ('socialuser.php');
require_once ($CFG->dirroot . '/mod/msocial/pki.php');
require_once ($CFG->dirroot . '/mod/msocial/socialinteraction.php');

use mod_msocial\pki;
use mod_msocial\pki_info;
use mod_msocial\plugininfo\msocialbase;

defined('MOODLE_INTERNAL') || die();

abstract class msocial_plugin {
    const CONFIG_DISABLED = 'disabled';
    const CONFIG_ENABLED = 'enabled';
    const CAT_VISUALIZATION = 'Visualization';
    const CAT_ANALYSIS = 'Analysis';
    const CAT_RESULTS = 'Results';
    const NOTIFY_WARNING = 'WARN';
    const NOTIFY_NORMAL = 'NORMAL';
    /**
     * @var msocial $msocial the msocial record that contains the global
     *      settings for this instance */
    public $msocial;

    /**
     * @var course_modinfo $cm info about the module */
    protected $cm;

    /** @var string $type msocial plugin type */
    private $type = '';

    /** @var string $error error message */
    private $error = '';

    /** @var boolean|null $enabledcache Cached lookup of the is_enabled function */
    private $enabledcache = null;

    /** @var boolean|null $enabledcache Cached lookup of the is_visible function */
    private $visiblecache = null;

    /** Constructor for the abstract plugin type class
     *
     * @param msocial $msocial
     * @param string $type */
    public function __construct($msocial, $type) {
        $this->msocial = (object) $msocial;
        $this->type = $type;
        if (isset($msocial->id)) {
            $cm = get_coursemodule_from_instance('msocial', $msocial->id, null, null);
            $this->cm = $cm;
        }
    }

    /** Is this the first plugin in the list?
     *
     * @return bool */
    public final function is_first() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');

        if ($order == 0) {
            return true;
        }
        return false;
    }

    /** Is this the last plugin in the list?
     *
     * @return bool */
    public final function is_last() {
        $lastindex = count(core_component::get_plugin_list($this->get_subtype())) - 1;
        $currentindex = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        if ($lastindex == $currentindex) {
            return true;
        }

        return false;
    }

    /** This function should be overridden to provide an array of elements that can be added to a
     * moodle
     * form for display in the settings page.
     *
     * @param MoodleQuickForm $mform The form to add the elements to
     * @return $array */
    public function get_settings(\MoodleQuickForm $mform) {
        return;
    }

    /** Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues[$this->get_form_field_name(self::CONFIG_ENABLED)] = $this->is_enabled();
        return;
    }

    /** composes this subplugin's field name for the forms
     *
     * @param string $setting
     * @return string */
    protected function get_form_field_name($setting) {
        return $this->get_type() . '_' . $this->get_subtype() . '_' . $setting;
    }

    /** The msocial subtype is responsible for saving it's own settings as the database table for
     * the
     * standard type cannot be modified.
     *
     * @param \stdClass $formdata - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false. */
    public function save_settings(\stdClass $data) {
        $formfield = $this->get_form_field_name(self::CONFIG_ENABLED);
        if (isset($data->{$formfield})) {
            if ($data->{$formfield}) {
                $this->enable();
            } else {
                $this->disable();
            }
        }
        return true;
    }

    /** Save the error message from the last error
     *
     * @param string $msg - the error description */
    protected final function set_error($msg) {
        $this->error = $msg;
    }

    /** What was the last error?
     *
     * @return string */
    public final function get_error() {
        return $this->error;
    }

    /** Should return the name of this plugin type.
     *
     * @return string - the name */
    public abstract function get_name();

    /** Should return the subtype of this plugin.
     *
     * @return string - either 'msocialconnector' or 'msocialview' */
    public abstract function get_subtype();

    /** Subclassification for grouping in UI.
     *
     * @return string category name */
    public abstract function get_category();

    /** Should return the type of this plugin.
     *
     * @return string - the type */
    public final function get_type() {
        return $this->type;
    }

    public function get_cmid() {
        return $this->cm->id;
    }

    public function get_msocialid() {
        return $this->msocial->id;
    }

    /** Add pki fields to the database in table msocial_pkis. */
    public function create_pki_fields() {
        global $DB;
        /* @var $dbman database_manager */
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('msocial_pkis');
        $pkilist = $this->get_pki_list();
        $transaction = $DB->start_delegated_transaction();
        foreach ($pkilist as $pkiname => $pkiinfo) {
            $pkifield = new \xmldb_field($pkiname, XMLDB_TYPE_FLOAT, null, null, null, null, null);
            if (!$dbman->field_exists($table, $pkifield)) {
                $dbman->add_field($table, $pkifield);
            }
        }
        $DB->commit_delegated_transaction($transaction);
    }

    /**
     * @global moodle_database $DB */
    public function drop_pki_fields() {
        global $DB;
        /* @var  database_manager $dbman */
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('msocial_pkis');
        $pkilist = $this->get_pki_list();
        $transaction = $DB->start_delegated_transaction();
        foreach ($pkilist as $pkiname => $pkiinfo) {
            $pkifield = new \xmldb_field($pkiname, XMLDB_TYPE_FLOAT, null, null, null, null, null);
            if ($dbman->field_exists($table, $pkifield)) {
                $dbman->drop_field($table, $pkifield);
            }
        }
        $DB->commit_delegated_transaction($transaction);
    }

    /** Aggregate fields by pki_info metadata form interaction database.
     * Calculates max_field.
     * @param unknown $users */
    public function calculate_pkis($users, $pkis = []) {
        global $DB;
        $pkiinfos = $this->get_pki_list();
        $subtype = $this->get_subtype();
        // Initialize for requested users.
        foreach ($users as $user) {
            if (!isset($pkis[$user->id])) {
                $pkis[$user->id] = new pki($user->id, $this->msocial->id);
                // Reset to 0 to avoid nulls.
                foreach ($pkiinfos as $pkiinfo) {
                    $pki = $pkis[$user->id];
                    $pki->{$pkiinfo->name} = 0;
                }
            }
        }
        // Calculate totals.
        $stats = [];
        /** @var pki_info $pkiinfo description of pki.*/
        foreach ($pkiinfos as $pkiinfo) {
            if ($pkiinfo->individual == pki_info::PKI_INDIVIDUAL && $pkiinfo->interaction_type !== pki_info::PKI_CUSTOM) {
                // Calculate posts.
                $nativetypequery = '';
                if ($pkiinfo->interaction_nativetype_query !== null && $pkiinfo->interaction_nativetype_query !== '*') {
                    $nativetypequery = "and nativetype = '$pkiinfo->interaction_nativetype_query' ";
                }
                // TODO: Check query:
                // Did you remember to make the first column something unique in your call to
                // get_records? Duplicate value '29' found in column 'userid'.

                $interaction_source = $pkiinfo->interaction_source;
                $sql = "SELECT $interaction_source as userid, count(*) as total
                    from {msocial_interactions}
                    where msocial=?
                        and source=?
                        and type=?
                        and $interaction_source IS NOT NULL
                        $nativetypequery
                    group by $interaction_source";
                $aggregatedrecords = $DB->get_records_sql($sql,
                        [$this->msocial->id, $subtype, $pkiinfo->interaction_type]);
                // Process users' pkis.
                foreach ($aggregatedrecords as $aggr) {
                    if (isset($pkis[$aggr->userid])) {
                        $pki = $pkis[$aggr->userid];
                        $pki->{$pkiinfo->name} = $aggr->total;
                        $stats['max_' . $pkiinfo->name] = max(
                                [0, $aggr->total, isset($stats[$pkiinfo->name]) ? $stats[$pkiinfo->name] : 0]);
                    }
                }
            }
        }
        foreach ($pkiinfos as $pkiinfo) {
            if ($pkiinfo->individual == pki_info::PKI_AGREGATED && isset($stats[$pkiinfo->name])) {
                foreach ($users as $user) {
                    $pki = $pkis[$user->id];
                    $pki->{$pkiinfo->name} = $stats[$pkiinfo->name];
                }
            }
        }
        return $pkis;
    }

    /** Reports the list of PKI offered by this plugin.
     * This method does not include any values, just metadata.
     *
     * @return array[string]pki list of PKI names indexed by name */
    public abstract function get_pki_list();

    /** Get the installed version of this plugin
     *
     * @return string */
    public final function get_version() {
        $version = get_config($this->get_subtype() . '_' . $this->get_type(), 'version');
        if ($version) {
            return $version;
        } else {
            return '';
        }
    }

    /** Add to the pkis the fields in the arguments or insert new records.
     * TODO: create and manage historical pkis
     * Timestamp updated
     * @param array(pki) $pkis */
    public function store_pkis($pkis, $newversion = false) {
        global $DB;
        $insert = false;
        $users = array();
        /** @var pki $pki */
        foreach ($pkis as $pki) {
            $users[$pki->user] = $pki;
        }
        $records = $DB->get_records_select('msocial_pkis', "historical=0  and msocial=?", [$this->msocial->id]);
        $recordindex = [];
        foreach ($records as $record) {
            $recordindex[$record->user] = $record;
        }
        // Create record templates from pki.
        $newrecords = [];
        $columnsinfo = $DB->get_columns('msocial_pkis');

        foreach ($pkis as $pki) {
            $record = new \stdClass();
            // Create template fields.
            foreach (array_keys($columnsinfo) as $field) {
                if ($field !== 'id') {
                    $record->{$field} = null;
                }
            }
            // Copy previous values.
            if (isset($recordindex[$pki->user])) {
                $prevrecord = $recordindex[$pki->user];
                foreach ($prevrecord as $propname => $value) {
                    if ($propname !== 'id') {
                        $record->{$propname} = $value;
                    }
                }
            }
            // Copy new values;
            foreach ($pki as $propname => $value) {
                $record->{$propname} = $value;
            }
            $record->user = $pki->user;
            $record->msocial = $this->msocial->id;
            $record->historical = 0;
            $record->timestamp = time();
            $newrecords[$pki->user] = $record;
        }
        $transaction = $DB->start_delegated_transaction();
        // Remove old pkis.
        $DB->delete_records_list('msocial_pkis', 'id', array_keys($records));
        // Insert new records.
        $DB->insert_records('msocial_pkis', $newrecords);
        $DB->commit_delegated_transaction($transaction);
    }

    /** Load all PKIs from the table.
     * TODO: impleement historical pkis.
     * TODO: see if a cache of pkis is needed.
     * @param \stdClass $msocial instance of a module.
     * @param array(int) $users
     * @param int $timestamp
     * @return array of pkis indexed by userid. All users are represented. Empty pki will be created
     *         to fill the gaps. */
    public static function get_pkis($msocial, $users = null, $timestamp = null) {
        global $DB;
        // Initialize response.
        $pkiindexed = [];
        if ($users) { // Fill the absent users.
            foreach ($users as $userid) {
                $pkiindexed[$userid] = new pki($userid, $msocial->id);
            }
        }
        if ($users == null) { // All records.
            $pkirecords = $DB->get_records('msocial_pkis', ['msocial' => $msocial->id, 'historical' => 0]);
        } else {
            list($insql, $params) = $DB->get_in_or_equal($users);
            $selectquery = 'msocial = ? and historical = 0 and user ' . $insql;
            $params = array_merge([$msocial->id], $params);
            $pkirecords = $DB->get_records_select('msocial_pkis', $selectquery, $params);
        }
        // Store the real Pkis.
        foreach ($pkirecords as $pkirecord) {
            $pki = pki::from_record($pkirecord);
            $pkiindexed[$pki->user] = $pki;
        }
        return $pkiindexed;
    }

    /** Get the required moodle version for this plugin
     *
     * @return string */
    public final function get_requires() {
        $requires = get_config($this->get_subtype() . '_' . $this->get_type(), 'requires');
        if ($requires) {
            return $requires;
        } else {
            return '';
        }
    }

    /** Set this plugin to enabled
     *
     * @return bool */
    public final function enable() {
        $this->enabledcache = true;
        return $this->set_config(self::CONFIG_DISABLED, 0);
    }

    /** Set this plugin to disabled
     *
     * @return bool */
    public final function disable() {
        $this->enabledcache = false;
        return $this->set_config(self::CONFIG_DISABLED, 1);
    }

    /** Allows hiding this plugin from the submission/feedback screen if it is not enabled.
     *
     * @return bool - if false - this plugin will not accept submissions / feedback */
    public function is_enabled() {
        if ($this->enabledcache === null) {
            $disabled = $this->get_config(self::CONFIG_DISABLED);
            $this->enabledcache = !$disabled;
        }
        return $this->enabledcache;
    }

    /** Get the numerical sort order for this plugin
     *
     * @return int */
    public final function get_sort_order() {
        $order = get_config($this->get_subtype() . '_' . $this->get_type(), 'sortorder');
        return $order ? $order : 0;
    }

    /** Is this plugin enabled?
     *
     * @return bool */
    public final function is_visible() {
        // if ($this->visiblecache === null) {
        // $enabled = get_config($this->get_type() . '_' . $this->get_subtype(), 'enabled');
        // $this->visiblecache = $enabled;
        // }
        // return $this->visiblecache;
        return true;
    }

    /** Has this plugin got a custom settings.php file?
     *
     * @return bool */
    public final function has_admin_settings() {
        global $CFG;

        $pluginroot = $CFG->dirroot . '/mod/msocial/' . substr($this->get_subtype(), strlen('msocial')) . '/' . $this->get_type();
        $settingsfile = $pluginroot . '/settings.php';
        return file_exists($settingsfile);
    }

    /** Set a configuration value for this plugin
     *
     * @param string $name The config key
     * @param string $value The config value
     * @return bool */
    public final function set_config($name, $value) {
        global $DB;

        $dbparams = array('msocial' => $this->msocial->id, 'subtype' => $this->get_subtype(), 'plugin' => $this->get_type(),
                        'name' => $name);
        $current = $DB->get_record('msocial_plugin_config', $dbparams, '*', IGNORE_MISSING);

        if ($current) {
            $current->value = $value;
            return $DB->update_record('msocial_plugin_config', $current);
        } else {
            $setting = new \stdClass();
            $setting->msocial = $this->msocial->id;
            $setting->subtype = $this->get_subtype();
            $setting->plugin = $this->get_type();
            $setting->name = $name;
            $setting->value = $value;

            return $DB->insert_record('msocial_plugin_config', $setting) > 0;
        }
    }

    /** Get a configuration value for this plugin
     *
     * @param mixed $setting The config key (string) or null
     * @return mixed | null */
    public final function get_config($setting = null) {
        global $DB;

        if ($setting) {
            if (!$this->msocial) {
                return false;
            }
            $msocial = $this->msocial;
            if ($msocial) {
                $dbparams = array('msocial' => $msocial->id, 'subtype' => $this->get_subtype(), 'plugin' => $this->get_type(),
                                'name' => $setting);
                $result = $DB->get_record('msocial_plugin_config', $dbparams, '*', IGNORE_MISSING);
                if ($result) {
                    return $result->value;
                }
            }
            return null;
        }
        $dbparams = array('msocial' => $this->msocial->id, 'subtype' => $this->get_subtype(), 'plugin' => $this->get_type());
        $results = $DB->get_records('msocial_plugin_config', $dbparams);

        $config = new \stdClass();
        if (is_array($results)) {
            foreach ($results as $setting) {
                $name = $setting->name;
                $config->$name = $setting->value;
            }
        }
        return $config;
    }

    /** The assignment has been deleted - remove the plugin specific data
     *
     * @return bool */
    public function delete_instance() {
        global $DB;
        $result = true;
        if (!$DB->delete_records('msocial_interactions', array('msocial' => $this->msocial->id, 'source' => $this->get_subtype()))) {
            $result = false;
        }
        if (!$DB->delete_records('msocial_plugin_config', array('msocial' => $this->msocial->id, 'subtype' => $this->get_subtype()))) {
            $result = false;
        }
        return $result;
    }

    /** Run cron for this plugin */
    public static function cron() {
    }

    /** Executes the harvest procedures of one or all plugins in this msocial instance.
     * First connector plugins, then view plugins.
     * @param \stdClass $msocial module instance
     * @param string $subtype name of the only subplugin to harvest */
    public static function execute_harvests($msocial, $subtype = null) {
        $enabledplugins = msocialbase::get_enabled_plugins_all_types($msocial);
        if ($subtype) {
            $enabledplugins = [$subtype => $enabledplugins[$subtype]];
        }

        echo "Processing plugins:" . implode(', ', array_keys($enabledplugins));

        foreach ($enabledplugins as $type => $plugin) {
            if ($plugin->is_tracking()) {
                $result = $plugin->harvest();
                foreach ($result->messages as $message) {
                    echo "<p>$message</p>";
                }
            } else {
                echo "<p>Plugin $type is not tracking. (Disabled or some critical configuration missing.)</p>";
            }
        }
    }

    /** This allows a plugin to render a page in the context of the msocial
     *
     * If the plugin creates a link to the msocial view.php page with
     * The following required parameters:
     * id=coursemoduleid
     * plugin=type
     * pluginsubtype=msocialview|msocialconnector
     * pluginaction=customaction
     *
     * Then this function will be called to display the page with the pluginaction passed as action
     *
     * @param string $action The plugin specified action
     * @return string */
    public function view_page($action) {
        return '';
    }

    /** This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main msocial page.
     *
     * @return string */
    public function render_header() {
        return '';
    }

    public function notify(array $messages, $level = self::NOTIFY_NORMAL) {
        global $OUTPUT;
        if (count($messages) > 0) {
            $icon = $this->get_icon();
            $text = join('<br/>', $messages);
            $icondecoration = \html_writer::img($icon->out(), $this->get_name() . ' icon.', ['height' => 29]) . ' ';
            if ($level === self::NOTIFY_NORMAL) {
                echo $OUTPUT->box($icondecoration . $text);
            } else if ($level === self::NOTIFY_WARNING) {
                echo $OUTPUT->notification($icondecoration . $text);
            }
        }
    }

    /** If this plugin should not include a column in the grading table or a row on the summary page
     * then return false
     * TODO: Implement user_summary
     * @return bool */
    public function has_user_summary() {
        return true;
    }

    /** If true, the plugin will appear on the module settings page and can be
     * enabled/disabled per msocial instance.
     *
     * @return bool */
    public function is_configurable() {
        return true;
    }
}
