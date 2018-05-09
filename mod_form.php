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
use msocial\msocial_plugin;
use function msocial\msocial_addHelpButton;
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once('locallib.php');
require_once('classes/msocialconnectorplugin.php');
require_once($CFG->libdir . '/mathslib.php');

class mod_msocial_mod_form extends moodleform_mod {

    public function definition() {
        global $DB;
        $mform = & $this->_form;

        $key = mt_rand(0xFFF, 0x7FFFFFFF);
        $mform->addElement('hidden', 'randomkey', $key);
        $mform->setType('randomkey', PARAM_INT);
        $mform->setDefault('randomkey', $key);

        // Cabecera.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Nombre.
        $mform->addElement('text', 'name', get_string('name', 'moodle'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Descripción.
        $this->standard_intro_elements();

        // Anonymize option.
        $mform->addElement('header', 'anonymizeviewsheader', get_string('anonymizeviews', 'msocial'));
        $mform->addElement('checkbox', 'anonymizeviews', '', get_string('anonymizeviews', 'msocial'));
        $mform->addHelpButton('anonymizeviews', 'anonymizeviews', 'msocial');

        $this->add_all_plugin_settings($mform);

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);

        $name = get_string('startdate', 'msocial');
        $options = array('optional' => false);
        $mform->addElement('date_time_selector', 'startdate', $name, $options);
        $mform->addHelpButton('startdate', 'startdate', 'msocial');

        $name = get_string('enddate', 'msocial');
        $mform->addElement('date_time_selector', 'enddate', $name, array('optional' => true));
        $mform->addHelpButton('enddate', 'enddate', 'msocial');
        // Otras caracteristicas.
        $calculation = get_string('grade_expr', 'msocial');
        $varliststr = '';
        $varlisthelpstr = '';
        /** @var msocial_plugin $plugin */
        $enabledplugins = mod_msocial\plugininfo\msocialbase::get_enabled_plugins_all_types();
        foreach ($enabledplugins as $type => $plugin) {
            $kpiinfos = $plugin->get_kpi_list();
            if (count($kpiinfos) > 0) {
                $varliststr = $varliststr . '<p><b>' . $plugin->get_name() . '</b>: ' .
                implode(', ', array_map(
                        function ($kpinfo) {
                            $out = '';
                            if (isset($kpinfo->description)) {
                                return '<span title="' . $kpinfo->description . '">' . $kpinfo->name . '</span>';
                            } else {
                                return $kpinfo->name;
                            }
                        }
                        , $kpiinfos)). '</p>';
            }
        }
        $mform->addElement('static', 'list_of_variables', get_string('grade_variables', 'msocial'), $varliststr);
        echo $varlisthelpstr;
        $mform->addElement('textarea', 'grade_expr', $calculation, 'wrap="virtual" rows="4" cols="60"');
        $mform->setDefault('grade_expr', '=100*(favs+retweets+tweets)/(max_favs+max_retweets+max_tweets)');
        $mform->addHelpButton('grade_expr', 'grade_expr', 'msocial');
        $mform->setType('grade_expr', PARAM_TEXT);

        $this->standard_coursemodule_elements();
        $this->apply_admin_defaults();
        // Buttons.
        $this->add_action_buttons();
    }

    /** Perform minimal validation on the settings form
     *
     * @param array $data
     * @param array $files */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $formula = $data['grade_expr'];
        $formula = calc_formula::unlocalize($formula);
        $calculation = new calc_formula($formula);
        $enabledplugins = mod_msocial\plugininfo\msocialbase::get_enabled_plugins_all_types($data);
        $vars = [];
        foreach ($enabledplugins as $plugin) {
            /** @var \mod_msocial\kpi_info $kpi */
            foreach ($plugin->get_kpi_list() as $kpiinfo) {
                $vars[$kpiinfo->name] = 1;
            }
        }
        $calculation->set_params($vars);
        if ($calculation->evaluate() === false) {
            $errors['grade_expr'] = $calculation->get_error();
        }
        if ($data['startdate'] && $data['enddate']) {
            if ($data['startdate'] > $data['enddate']) {
                $errors['enddate'] = get_string('enddatevalidation', 'msocial');
            }
        }

        return $errors;
    }

    /** Add settings to edit plugin form.
     *
     * @param MoodleQuickForm $mform The form to add the configuration settings to.
     *        This form is modified directly (not returned).
     * @return void */
    public function add_all_plugin_settings(MoodleQuickForm $mform) {
        $mform->addElement('header', 'socialtypes', get_string('socialconnectors', 'msocial'));

        foreach (mod_msocial\plugininfo\msocialconnector::get_system_enabled_connector_plugins(null) as $pluginname => $plugin) {
            $this->add_plugin_settings($plugin, $mform);
        }
        $mform->addElement('header', 'viewtypes', get_string('socialviews', 'msocial'));

        foreach (mod_msocial\plugininfo\msocialview::get_system_enabled_view_plugins(null) as $pluginname => $plugin) {
            $this->add_plugin_settings($plugin, $mform);
        }
        $mform->setExpanded('socialtypes');
        $mform->setExpanded('viewtypes');
    }

    /** Add one plugins settings to edit plugin form.
     *
     * @param msocial_plugin $plugin The plugin to add the settings from
     * @param MoodleQuickForm $mform The form to add the configuration settings to.
     *        This form is modified directly (not returned).
     * @return void */
    public function add_plugin_settings($plugin, MoodleQuickForm $mform) {
        global $PAGE;
        $enabledfieldname = $plugin->get_type() . '_' . $plugin->get_subtype() . '_enabled';
        if ($plugin->is_visible() && !$plugin->is_configurable() && $plugin->is_enabled()) {
            $mform->addElement('hidden', $enabledfieldname, 1);
            $mform->setType($enabledfieldname, PARAM_BOOL);
            $plugin->get_settings($mform);
        } else if ($plugin->is_visible() && $plugin->is_configurable()) {
            $label = $plugin->get_name();
            // $label .= ' ' . $renderer->help_icon('enabled', $plugin->get_subtype() . '_' .
            // $plugin->get_type());
            $mform->addElement('checkbox', $enabledfieldname, '', $label);
            // $mform->addHelpButton($name, $name, $plugin->get_type() . '_' .
            // $plugin->get_subtype());
            // $default = get_config($plugin->get_subtype() . '_' . $plugin->get_type(), 'default');
            // TODO: Configure default activation in admin settings.
            $plugin->get_settings($mform);
        }
        $mform->setDefault($enabledfieldname, true);
    }

    /** Allow each plugin an opportunity to update the defaultvalues
     * passed in to the settings form (needed to set up draft areas for
     * editor and filemanager elements)
     * TODO: Check usage.
     *
     * @param array $defaultvalues */
    public function plugin_data_preprocessing(&$defaultvalues) {
        foreach (mod_msocial\plugininfo\msocialbase::get_system_enabled_plugins_all_types($defaultvalues) as $pluginname => $plugin) {
            if ($plugin->is_visible()) {
                $plugin->data_preprocessing($defaultvalues);
            }
        }
    }

    /** Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     *
     * @param array $defaultvalues */
    public function data_preprocessing(&$defaultvalues) {
        $this->plugin_data_preprocessing($defaultvalues);
        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $defaultvalues['completionpasssenabled'] = !empty($defaultvalues['completionpass']) ? 1 : 0;
        if (empty($defaultvalues['completionusegrade']) || empty($defaultvalues['completionpass'])) {
            $defaultvalues['completionpass'] = 0;
        }
    }

    /** Load the plugins from the sub folders under subtype.
     * TODO check use
     *
     * @param string $subtype - either submission or feedback
     * @return array - The sorted list of plugins */
    public function load_plugins($subtype) {
        global $CFG;
        $result = array();

        $names = core_component::get_plugin_list($subtype);

        foreach ($names as $name => $path) {
            $shortsubtype = substr($subtype, strlen('msocial'));
            if (file_exists($path . '/' . $shortsubtype . 'plugin.php')) {
                require_once($path . '/' . $shortsubtype . 'plugin.php');
                $pluginclass = 'msocial_' . $shortsubtype . '_' . $name;
                $plugin = new $pluginclass($this, $name);
                if ($plugin instanceof msocial_plugin) {
                    $idx = $plugin->get_sort_order();
                    while (array_key_exists($idx, $result)) {
                        $idx += 1;
                    }
                    $result[$idx] = $plugin;
                }
            }
        }
        ksort($result);
        return $result;
    }
    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completionpass', '', get_string('completionpass', 'quiz'));
        $mform->disabledIf('completionpass', 'completionusegrade', 'notchecked');
        $mform->addHelpButton('completionpass', 'completionpass', 'quiz');
        // Enable this completion rule by default.
        $mform->setDefault('completionpass', 0);
        return array('completionpass');
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionpass']);
    }

}
