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
require_once('plugininfo/msocialbase.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialuser.php');
require_once($CFG->dirroot . '/mod/msocial/classes/kpi.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialinteraction.php');
require_once($CFG->dirroot . '/mod/msocial/classes/filterinteractions.php');

use core_component;
use mod_msocial\kpi;
use mod_msocial\kpi_info;
use mod_msocial\connector\harvest_intervals;
use mod_msocial\plugininfo\msocialbase;
use mod_msocial\users_struct;

defined('MOODLE_INTERNAL') || die();

abstract class msocial_plugin {
    const CONFIG_DISABLED = 'disabled';
    const CONFIG_ENABLED = 'enabled';
    const CAT_VISUALIZATION = 'Visualization';
    const CAT_ANALYSIS = 'Analysis';
    const CAT_RESULTS = 'Results';
    const NOTIFY_WARNING = 'WARN';
    const NOTIFY_NORMAL = 'NORMAL';
    const NOTIFY_ERROR = 'ERROR';
    const LAST_HARVEST_TIME = 'lastharvest';

    /**
     * @var \stdClass $msocial the msocial record that contains the global
     *      settings for this instance */
    public $msocial;

    /**
     * @var \course_modinfo $cm info about the module */
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
     * @param \stdClass $msocial
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
     * @param \MoodleQuickForm $mform The form to add the elements to
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

    /** Add kpi fields to the database in table msocial_kpis. */
    public function create_kpi_fields() {
        global $DB;
        /* @var \database_manager $dbman*/
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('msocial_kpis');
        $kpilist = $this->get_kpi_list();
//         $transaction = $DB->start_delegated_transaction();
        foreach ($kpilist as $kpiname => $kpiinfo) {
            $kpifield = new \xmldb_field($kpiname, XMLDB_TYPE_FLOAT, null, null, null, null, null);
            if (!$dbman->field_exists($table, $kpifield)) {
                $dbman->add_field($table, $kpifield);
                mtrace("<li>Key Performance Indicators (KPIs) added to database: $kpiname.");
            }
        }
//         $DB->commit_delegated_transaction($transaction);
    }

    /**
     * @global \moodle_database $DB */
    public function drop_kpi_fields() {
        global $DB;
        /* @var \database_manager $dbman */
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('msocial_kpis');
        $kpilist = $this->get_kpi_list();
        $transaction = $DB->start_delegated_transaction();
        foreach ($kpilist as $kpiname => $kpiinfo) {
            $kpifield = new \xmldb_field($kpiname, XMLDB_TYPE_FLOAT, null, null, null, null, null);
            if ($dbman->field_exists($table, $kpifield)) {
                $dbman->drop_field($table, $kpifield);
                mtrace("Key Performance Indicators (KPIs) dropped from database: $kpiname.");
            }
        }
        $DB->commit_delegated_transaction($transaction);
    }

    /** Aggregate fields by kpi_info metadata from interaction database.
     * Calculates max_field.
     * @param users_struct $users structure of arrays @see function msocial_get_users_by_type
     */
    public function calculate_kpis(users_struct $users, $kpis = []) {
        global $DB;
        $kpiinfos = $this->get_kpi_list();
        $subtype = $this->get_subtype();

        // Calculate totals.
        $stats = [];
        /** @var kpi_info $kpiinfo description of kpi.*/
        foreach ($kpiinfos as $kpiinfo) {
            if ($kpiinfo->individual == kpi_info::KPI_INDIVIDUAL && $kpiinfo->generated == kpi_info::KPI_CALCULATED) {
                // Calculate posts.
                $sqlparams = [];
                $nativetypequery = '';
                $nativetypeparams = [];
                if ($kpiinfo->interaction_nativetype_query !== null && $kpiinfo->interaction_nativetype_query !== '*') {
                    list($nativetypequerypart, $nativetypeparams) = $DB->get_in_or_equal(explode('|', $kpiinfo->interaction_nativetype_query));
                    $nativetypequery = "and nativetype $nativetypequerypart ";
                }
                // TODO: Check query:
                // Did you remember to make the first column something unique in your call to
                // get_records? Duplicate value '29' found in column 'userid'.
                $interactionsource = $kpiinfo->interaction_source;
                if (is_array($kpiinfo->interaction_type )) {
                    $typeparams = $kpiinfo->interaction_type;
                } else {
                    $typeparams = [$kpiinfo->interaction_type];
                }
                list($typequery, $typeparams) = $DB->get_in_or_equal($typeparams);

                $sql = "SELECT $interactionsource as userid, count(*) as total
                    from {msocial_interactions}
                    where msocial=?
                        and source=?
                        and type $typequery
                        and $interactionsource IS NOT NULL
                        and timestamp >= ?
                        and timestamp <= ?
                        $nativetypequery
                    group by $interactionsource";
                $sqlparams[] = $this->msocial->id;
                $sqlparams[] = $subtype;
                $sqlparams = array_merge($sqlparams, $typeparams);
                $sqlparams[] = $this->msocial->startdate;
                $sqlparams[] = $this->msocial->enddate == 0 ? PHP_INT_MAX : $this->msocial->enddate;
                $sqlparams = array_merge($sqlparams, $nativetypeparams);

                $aggregatedrecords = $DB->get_records_sql($sql, $sqlparams);

                // Process users' kpis.
                foreach ($aggregatedrecords as $aggr) {
                    if (!isset($kpis[$aggr->userid])) {
                        $kpis[$aggr->userid] = new kpi($aggr->userid, $this->msocial->id, $kpiinfos);
                    }
                    $kpi = $kpis[$aggr->userid];
                    $kpi->{$kpiinfo->name} = $aggr->total;
                    $stats['max_' . $kpiinfo->name] = max([ 0,
                                                            $aggr->total,
                                    isset( $stats['max_' . $kpiinfo->name]) ? $stats['max_' . $kpiinfo->name] : 0]);
                }
            }
        }
        foreach ($kpiinfos as $kpiinfo) {
            if ($kpiinfo->individual == kpi_info::KPI_AGREGATED && isset($stats[$kpiinfo->name])) {
                foreach ($kpis as $userid => $kpi) {
                    $kpi = $kpis[$userid];
                    $kpi->{$kpiinfo->name} = $stats[$kpiinfo->name];
                }
            }
        }
        return $kpis;
    }
    /**
     * Calculates aggregated kpis from existent kpis.
     * @param array $kpis
     */
    protected function calculate_aggregated_kpis(array $kpis) {
        $kpiinfos = $this->get_kpi_list();

        foreach ($kpiinfos as $kpiinfo) {
            if ($kpiinfo->individual == kpi_info::KPI_AGREGATED) {
                // Calculate aggregation.
                $parts = explode('_', $kpiinfo->name);
                $operation = $parts[0];
                $kpiname = $parts[1];
                $values = [];
                $aggregated = 0;
                foreach ($kpis as $kpi) {
                    $values[]  = $kpi->{$kpiname};
                }
                if ($operation == 'max') {
                    $aggregated = max($values);
                } else {
                    print_error('unsuported');
                }
                // Copy to all users.
                foreach ($kpis as $kpi) {
                    $kpi->{$kpiinfo->name}  = $aggregated;
                }
            }
        }
        return $kpis;
    }
    /** Reports the list of Key Performance Indicators (KPIs) offered by this plugin.
     * This method does not include any values, just metadata.
     *
     * @return kpi_info[] kpi list of KPI names indexed by name */
    public abstract function get_kpi_list();
    /**
     * Reports the date/time this plugin was evaluated: harvest or recalculted.
     * @return \DateTime
     */
    public function get_updated_date() {
        $date = null;
        $harvesttimestamp = $this->get_config(self::LAST_HARVEST_TIME);
        if ($harvesttimestamp != null ) {
            $date = new \DateTime();
            $date->setTimestamp($harvesttimestamp);
        }
        return $date;
    }
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
    /**
     * This function is used by the reset_course_userdata function in moodlelib.
     * This function will remove all internal data, tokens from the specified course
     * and clean up any related data.
     * @param \stdClass $data the data submitted from the reset course.
     * @return array status array */
    public function reset_userdata(\stdClass $data) {
        $msocial = $this->msocial;
        return array('component'=>$this->get_name(), 'item'=>get_string('resetdone', 'msocial',
                "MSOCIAL $msocial->id (Actually, no reset)"), 'error'=>false);
    }
    
    /** Add to the kpis the fields in the arguments or insert new records.
     * TODO: create and manage historical kpis
     * Timestamp updated
     * @param kpi[] $kpis */
    public function store_kpis($kpis, $newversion = false) {
        global $DB;
        $insert = false;
        $users = array();
        /** @var kpi $kpi */
        foreach ($kpis as $kpi) {
            $users[$kpi->userid] = $kpi;
        }
        $records = $DB->get_records_select('msocial_kpis', "historical=0  and msocial=?", [$this->msocial->id]);
        $recordindex = [];
        foreach ($records as $record) {
            $recordindex[$record->userid] = $record;
            unset($record->id);
        }
        // Create record templates from kpi.
        $columnsinfo = $DB->get_columns('msocial_kpis');

        foreach ($kpis as $kpi) {

            if (isset($recordindex[$kpi->userid])) {
                $record = $recordindex[$kpi->userid];
            } else {
                $record = new \stdClass();
                // Create template fields.
                foreach (array_keys($columnsinfo) as $field) {
                    if ($field !== 'id') {
                        $record->{$field} = null;
                    }
                }
            }
            // Copy new values.
            foreach ($kpi as $propname => $value) {
                if (property_exists($record, $propname)) {
                    $record->{$propname} = $value;
                } else {
                    print_error("Code error. Prop \"$propname\" in Pki is not declared by plugins. Contact administrators."
                            . "Properties in records are:" . array_keys(get_object_vars($record)));
                }
            }
            $record->userid = $kpi->userid;
            $record->msocial = $this->msocial->id;
            $record->historical = 0;
            $record->timestamp = time();
            $recordindex[$kpi->userid] = $record;
        }
        $transaction = $DB->start_delegated_transaction();
        // Remove old kpis.
        $DB->delete_records_list('msocial_kpis', 'id', array_keys($records));
        // Insert new records.
        $DB->insert_records('msocial_kpis', $recordindex);
        $DB->commit_delegated_transaction($transaction);
    }

    /** Load all Key Performance Indicators (KPIs) from the table.
     * TODO: impleement historical kpis.
     * TODO: see if a cache of kpis is needed.
     * @param \stdClass $msocial instance of a module.
     * @param array(int) $users
     * @param int $timestamp
     * @return array of kpis indexed by userid. All users are represented. Empty kpi will be created
     *         to fill the gaps. */
    public static function get_kpis($msocial, $users = null, $timestamp = null) {
        global $DB;
        // Initialize response.
        $kpiindexed = [];
        if ($users) { // Fill the absent users.
            foreach ($users as $userid) {
                $kpiindexed[$userid] = new kpi($userid, $msocial->id);
            }
        }
        if ($users == null) { // All records.
            $kpirecords = $DB->get_records('msocial_kpis', ['msocial' => $msocial->id, 'historical' => 0]);
        } else {
            list($insql, $params) = $DB->get_in_or_equal($users);
            $selectquery = 'msocial = ? and historical = 0 and userid ' . $insql;
            $params = array_merge([$msocial->id], $params);
            $kpirecords = $DB->get_records_select('msocial_kpis', $selectquery, $params);
        }
        // Store the real Pkis.
        foreach ($kpirecords as $kpirecord) {
            $kpi = kpi::from_record($kpirecord);
            $kpiindexed[$kpi->userid] = $kpi;
        }
        return $kpiindexed;
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

    /** Allows hiding this plugin from the MSocial screen if it is not enabled.
     *
     * @return bool - if false - this plugin will not accept submissions / feedback */
    public function is_enabled() {
        if ($this->enabledcache === null) {
            $disabled = $this->get_config(self::CONFIG_DISABLED);
            $this->enabledcache = !$disabled;
        }
        return $this->enabledcache;
    }
    /**
     * Disable tracking a day after the end of the activity time window.
     * @return boolean true if the plugin is making searches in the social network or computing kpis.
     * (@see msocial_plugin->harvest())
     */
    public function is_tracking() {
        return $this->can_harvest() &&
        msocial_time_is_between(time(), $this->msocial->startdate,
                $this->msocial->enddate ? $this->msocial->enddate + 24 * 2600 : null);
    }
    /**
     * Check if the plugin is properly configured to harvest data. Ignores time window.
     */
    public function can_harvest() {
        return $this->is_enabled();
    }
    /**
     * Get the numerical sort order for this plugin
     * @return int */
    public function get_sort_order() {
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
    /**
     * Collect information and calculate fresh Key Performance Indicators (KPIs) if supported.
     * @return string[] messages generated
     */
    public abstract function harvest();
    /**
     * @return harvest_intervals object with intervals and rates info.
     */
    public abstract function preferred_harvest_intervals();

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
            if (!isset($this->msocial->id)) {
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
            if ($plugin->can_harvest()) {
                $result = $plugin->harvest();
                if (isset($result->errors)) {
                        $plugin->notify(array_map(function ($item) {
                            if (isset($item->message)) {
                                return $item->message;
                            } else {
                                return '';
                            }
                        }, $result->errors), self::NOTIFY_ERROR);
                }
                $plugin->notify($result->messages, self::NOTIFY_NORMAL);

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
     * @return array[string[], string[]] messages, notifications
     */
    public function render_header() {
        return [ [], [] ];
    }
    public function render_harvest_link() {
        return '';
    }
    /**
     * @global
     * @param string[] $messages
     * @param int $level
     */
    public function notify(array $messages, $level = self::NOTIFY_NORMAL) {
        global $OUTPUT;
        if (count($messages) > 0) {
            $icon = $this->get_icon();

            $icondecoration = \html_writer::img($icon->out(), $this->get_name() . ' icon.', ['height' => 29]) . ' ';
            if ($level === self::NOTIFY_NORMAL) {
                $tablemsgs = join("\n<br/>", $messages);
                $table = '<table><tr><td valign="top">'. $icondecoration . '</td><td>' . $tablemsgs. '</td></tr></table>';
                msocial_notify_info($table);
            } else if ($level === self::NOTIFY_WARNING) {
                $text = join("\n<br/>", $messages);
                msocial_notify_warning($icondecoration . $text);
            } else if ($level === self::NOTIFY_ERROR) {
                $text = join("\n<br/>", $messages);
                msocial_notify_error($icondecoration . $text);
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
