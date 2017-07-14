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

use mod_tcount\social\pki_info;
use mod_tcount\social\pki;
use core_calendar\local\event\proxies\std_proxy;
use mod_tcount\social\social_interaction;

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
    public $tcount;

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
     * @param \stdClass $formdata - the data submitted from the form
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(\stdClass $data) {
        if (isset($data->{$this->get_form_field_name(self::CONFIG_ENABLED)})) {
            $this->set_config('enabled', $data->{$this->get_form_field_name(self::CONFIG_ENABLED)});
        }
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

    public function get_cmid() {
        return $this->cm->id;
    }

    public function get_tcountid() {
        return $this->tcount->id;
    }

    /**
     * Statistics for grading
     *
     * @param array[]integer $users array with the userids to be calculated, null if all
     * @return array[string]object object->userstats with PKIs for each user object->maximums max
     *         values for normalization.
     */
    public abstract function calculate_stats($users);

    /**
     * Aggregate fields by pki_info metadata form interaction database.
     * Calculates max_field.
     * @param unknown $users
     */
    public function calculate_pkis($users, $pkis = []) {
        global $DB;
        $pkiinfos = $this->get_pki_list();
        $subtype = $this->get_subtype();
        // Initialize for requested users.
        foreach ($users as $user) {
            if (!isset($pkis[$user->id])) {
                $pkis[$user->id] = new pki($user->id, $this->tcount->id);
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
                $sql = "SELECT fromid as userid, count(*) as total
                    from {tcount_interactions}
                    where tcount=?
                        and source='$subtype'
                        and type='$pkiinfo->interaction_type'
                        $nativetypequery
                        and $pkiinfo->interaction_source IS NOT NULL
                    group by $pkiinfo->interaction_source";
                $aggregatedrecords = $DB->get_records_sql($sql, [$this->tcount->id]);
                // Process users' pkis.
                foreach ($aggregatedrecords as $aggr) {
                    $pki = $pkis[$aggr->userid];
                    $pki->{$pkiinfo->name} = $aggr->total;
                    $stats['max_' . $pkiinfo->name] = max(
                            [0, $aggr->total, isset($stats[$pkiinfo->name]) ? $stats[$pkiinfo->name] : 0]);
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
     * Add to the pkis the fields in the arguments or insert new records.
     * TODO: create and manage historical pkis
     * Timestamp updated
     * @param array(pki) $pkis
     */
    public function store_pkis($pkis, $newversion = false) {
        global $DB;
        $insert = false;
        $users = array();
        /** @var pki $pki */
        foreach ($pkis as $pki) {
            $users[$pki->user] = $pki;
        }
        $records = $DB->get_records_select('tcount_pkis', "historical=0  and tcount=?", [$this->tcount->id]);
        $recordindex = [];
        foreach ($records as $record) {
            $recordindex[$record->user] = $record;
        }
        // Create record templates from pki.
        $newrecords = [];
        $columnsinfo = $DB->get_columns('tcount_pkis');

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
            $record->tcount = $this->tcount->id;
            $record->historical = 0;
            $record->timestamp = time();
            $newrecords[$pki->user] = $record;
        }
        $transaction = $DB->start_delegated_transaction();
        // Remove old pkis.
        $DB->delete_records_list('tcount_pkis', 'id', array_keys($records));
        // Insert new records.
        $DB->insert_records('tcount_pkis', $newrecords);
        $DB->commit_delegated_transaction($transaction);
    }

    /**
     * TODO: impelement historical pkis.
     * Load all PKIs from the cached table
     * @param array(int) $users
     * @param int $timestamp
     * @return array of pkis indexed by userid. All users are represented. Empty pki will be created
     *         to fill the gaps.
     */
    public function get_pkis($users = null, $timestamp = null) {
        global $DB;
        // Initialize response.
        $pkiindexed = [];
        foreach ($users as $userid) {
            $pkiindexed[$userid] = new pki($userid, $this->tcount->id);
        }
        $users = null; // TODO for debug.
        if ($users == null) { // All records.
            $pkis = $DB->get_records('tcount_pkis', ['tcount' => $this->tcount->id, 'historical' => 0]);
        } else {
            $pkis = []; // TODO: query only selected users.
        }
        // Store the real Pkis.
        foreach ($pkis as $pki) {
            $pkiindexed[$pki->user] = $pki;
        }
        return $pkiindexed;
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
            $setting = new \stdClass();
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

        $config = new \stdClass();
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
