<?php
// This file is part of TwitterCount activity for Moodle http://moodle.org/
//
// Questournament for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Questournament for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle.  If not, see <http://www.gnu.org/licenses/>.
/* * *******************************************************************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro with the effort of many other
 * students of telecommunication engineering of Valladolid
 * Copyright 2009-2011 EdUVaLab http://www.eduvalab.uva.es
 * this module is provides as-is without any guarantee. Use it as your own risk.

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

 * @author Juan Pablo de Castro and other contributors.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package tcount
 * ******************************************************************************* */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once('locallib.php');
require_once('tcountsocialplugin.php');
require_once($CFG->libdir . '/mathslib.php');

class mod_tcount_mod_form extends moodleform_mod {

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
        // Identificador.
        $options1 = array(
            'skype' => 'SKYPE',
            'yahoo' => 'Yahoo',
            'aim' => 'AIM',
            'msn' => 'MSN',
        );
        $options2 = array();
        if ($options = $DB->get_records_menu("user_info_field", null, "name", "shortname, name")) {
            foreach ($options as $shortname => $name) {
                $options2[$shortname] = $name;
            }
        }
        $idtypeoptions = $options1 + $options2;

        $this->add_all_plugin_settings($mform);

//        $mform->addElement('select', 'fbfieldid', get_string("fbfieldid", "tcount"), $idtypeoptions);
//        $mform->setDefault('fbfieldid', 'aim');
//        $mform->addHelpButton('fbfieldid', 'fbfieldid', 'tcount');


        $mform->addElement('text', 'fbsearch', get_string("fbsearch", "tcount"), array('size' => '20'));
        $mform->setType('fbsearch', PARAM_TEXT);
        $mform->addHelpButton('fbsearch', 'fbsearch', 'tcount');

        $mform->addElement('text', 'widget_id', get_string("widget_id", "tcount"), array('size' => '20'));
        $mform->setType('widget_id', PARAM_TEXT);
        $mform->addHelpButton('widget_id', 'widget_id', 'tcount');

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);

        $name = get_string('counttweetsfromdate', 'tcount');
        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'counttweetsfromdate', $name, $options);
        $mform->addHelpButton('counttweetsfromdate', 'counttweetsfromdate', 'tcount');

        $name = get_string('counttweetstodate', 'tcount');
        $mform->addElement('date_time_selector', 'counttweetstodate', $name, array('optional' => true));
        $mform->addHelpButton('counttweetstodate', 'counttweetstodate', 'tcount');
        // Otras caracteristicas.
        $calculation = get_string('grade_expr', 'tcount');
        $vars = [];
        $varliststr='';
        foreach (mod_tcount\plugininfo\tcountsocial::get_enabled_plugins() as $type=>$plugin){
            $vars = array_merge($vars,$plugin->get_pki_list());
            $varliststr=$varliststr.' '.$type.': '.implode(',',$vars).'. ';
        }
        
        $variables = $mform->addElement('static','list_of_variables',
                            get_string('grade_variables','tcount'),
                            $varliststr);
        $mform->addElement('text', 'grade_expr', $calculation);
        $mform->setDefault('grade_expr', '=100*(favs+retweets+tweets)/(maxfavs+maxretweets+maxtweets)');
        $mform->addHelpButton('grade_expr', 'grade_expr', 'tcount');
        $mform->setType('grade_expr', PARAM_TEXT);

//        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->apply_admin_defaults();
        // Buttons.
        $this->add_action_buttons();
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $formula = $data['grade_expr'];
        $formula = calc_formula::unlocalize($formula);
        $calculation = new calc_formula($formula);
        $calculation->set_params(array('favs' => 1, 'retweets' => 1, 'tweets' => 1, 'maxfavs' => 1,
            'maxretweets' => 1, 'maxtweets' => 1));
        if ($calculation->evaluate() === false) {
            $errors['grade_expr'] = $calculation->get_error();
        }
        if ($data['counttweetsfromdate'] && $data['counttweetstodate']) {
            if ($data['counttweetsfromdate'] > $data['counttweetstodate']) {
                $errors['counttweetstodate'] = get_string('counttweetstodatevalidation', 'tcount');
            }
        }

        return $errors;
    }

    /**
     * Add one plugins settings to edit plugin form.
     *
     * @param tcount_plugin $plugin The plugin to add the settings from
     * @param MoodleQuickForm $mform The form to add the configuration settings to.
     *                               This form is modified directly (not returned).
     * @param array $pluginsenabled A list of form elements to be added to a group.
     *                              The new element is added to this array by this function.
     * @return void
     */
    function add_plugin_settings($plugin, MoodleQuickForm $mform, & $pluginsenabled) {
        global $PAGE;
        $name = $plugin->get_type() . '_' . $plugin->get_subtype() . '_enabled';
        if ($plugin->is_visible() && !$plugin->is_configurable() && $plugin->is_enabled()) {
            $pluginsenabled[] = $mform->createElement('hidden', $name, 1);
            $mform->setType($name, PARAM_BOOL);
            $plugin->get_settings($mform);
        } else if ($plugin->is_visible() && $plugin->is_configurable()) {
            $label = $plugin->get_name();
            //$label .= ' ' . $renderer->help_icon('enabled', $plugin->get_subtype() . '_' . $plugin->get_type());
            $pluginsenabled[] = $mform->createElement('checkbox', $name, '', $label);
//            $mform->addHelpButton($name, $name, $plugin->get_type() . '_' . $plugin->get_subtype());
            //$default = get_config($plugin->get_subtype() . '_' . $plugin->get_type(), 'default');
            // TODO: Configure default activation in admin settings.
            $plugin->get_settings($mform);
        }
        $mform->setDefault($name, true);
    }

    /**
     * Add settings to edit plugin form.
     *
     * @param MoodleQuickForm $mform The form to add the configuration settings to.
     *                               This form is modified directly (not returned).
     * @return void
     */
    function add_all_plugin_settings(MoodleQuickForm $mform) {
        $mform->addElement('header', 'socialtypes', get_string('socialconnectors', 'tcount'));

        $tcountpluginsenabled = array();
        $group = $mform->addGroup(array(), 'tcountsocialplugins', get_string('socialconnectors', 'tcount'), array(' '), false);
        foreach (mod_tcount\plugininfo\tcountsocial::get_enabled_plugins(null) as $pluginname=>$plugin) {
            $this->add_plugin_settings($plugin, $mform, $tcountpluginsenabled);
        }
        $group->setElements($tcountpluginsenabled);

//        $mform->addElement('header', 'viewtypes', get_string('socialviews', 'tcount'));
//        $viewpluginsenabled = array();
//        $group = $mform->addGroup(array(), 'viewplugins', get_string('socialviews', 'tcount'), array(' '), false);
//        foreach (mod_tcount\plugininfo\tcountview::get_enabled_plugins() as $pluginname) {
//            $plugin = tcount_view_plugin::instance(null, $pluginname);
//            $this->add_plugin_settings($plugin, $mform, $viewpluginsenabled);
//        }
//        $group->setElements($viewpluginsenabled);
        $mform->setExpanded('socialtypes');
    }

    /**
     * Allow each plugin an opportunity to update the defaultvalues
     * passed in to the settings form (needed to set up draft areas for
     * editor and filemanager elements)
     * TODO: Check usage.
     * @param array $defaultvalues
     */
    function plugin_data_preprocessing(&$defaultvalues) {
        foreach (mod_tcount\plugininfo\tcountsocial::get_enabled_plugins($defaultvalues) as $pluginname=>$plugin) {
            if ($plugin->is_visible()) {
                $plugin->data_preprocessing($defaultvalues);
            }
        }
//        foreach ($this->feedbackplugins as $plugin) {
//            if ($plugin->is_visible()) {
//                $plugin->data_preprocessing($defaultvalues);
//            }
//        }
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {

        $this->plugin_data_preprocessing($defaultvalues);
    }

    /**
     * Load the plugins from the sub folders under subtype.
     * TODO check use
     * @param string $subtype - either submission or feedback
     * @return array - The sorted list of plugins
     */
    function load_plugins($subtype) {
        global $CFG;
        $result = array();

        $names = core_component::get_plugin_list($subtype);

        foreach ($names as $name => $path) {
            $shortsubtype = substr($subtype, strlen('tcount'));
            if (file_exists($path . '/' . $shortsubtype . 'plugin.php')) {
                require_once($path . '/' . $shortsubtype . 'plugin.php');
                $pluginclass = 'tcount_' . $shortsubtype . '_' . $name;
                $plugin = new $pluginclass($this, $name);
                if ($plugin instanceof tcount_plugin) {
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

}
