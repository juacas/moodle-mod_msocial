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
require_once($CFG->dirroot . '/mod/tcount/locallib.php');
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

        $this->add_intro_editor(true, get_string('description', 'moodle'));

        // Identificador.
        $options1 = array('icq' => 'ICQ',
            'skype' => 'SKYPE',
            'yahoo' => 'Yahoo',
            'aim' => 'AIM',
            'msn' => 'MSN',
        );
        $options2 = array();
        if ($options = $DB->get_records_menu("user_info_field", null, "name", "shortname, name")) {
            foreach ($options as $shortname => $name) {
                $options2["custom-" . $shortname] = $name;
            }
        }
        $idtypeoptions = $options1 + $options2;
        $mform->addElement('select', 'fieldid', get_string("fieldid", "tcount"), $idtypeoptions);
        $mform->setDefault('fieldid', 'aim');
        $mform->addHelpButton('fieldid', 'fieldid', 'tcount');

        $mform->addElement('text', 'hashtag', get_string("hashtag", "tcount"), array('size' => '20'));
        $mform->setType('hashtag', PARAM_TEXT);
        $mform->addHelpButton('hashtag', 'hashtag', 'tcount');

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

}