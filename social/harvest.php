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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle. If not, see <http://www.gnu.org/licenses/>.
/*
 * ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro with the effort of many other
 * students of telecommunication engineering of Valladolid
 * Copyright 2009-2011 EdUVaLab http://www.eduvalab.uva.es
 * this module is provides as-is without any guarantee. Use it as your own risk.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Juan Pablo de Castro and other contributors.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package tcount
 * *******************************************************************************
 */
require_once ("../../../config.php");
require_once ($CFG->dirroot . '/mod/lti/OAuth.php');
require_once ('../locallib.php');
require_once ('../tcountsocialplugin.php');
require_once ('twitter/twitterplugin.php');
global $CFG;
/* @var $OUTPUT \core_renderer */
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);
$showinactive = optional_param('showinactive', false, PARAM_BOOL);
$subtype = optional_param('subtype',false,PARAM_ALPHA);
$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);
$tcount = $DB->get_record('tcount', array('id' => $cm->instance), '*', MUST_EXIST);
// Capabilities.
$contextmodule = context_module::instance($cm->id);
require_capability('mod/tcount:manage', $contextmodule);
$url = new moodle_url('/mod/tcount/social/twitter/harvest.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_title('Harvest activity');
$PAGE->set_heading($course->fullname);
// Print the page header.
echo $OUTPUT->header();

$enabledplugins = mod_tcount\plugininfo\tcountsocial::get_enabled_social_plugins($tcount);
if ($subtype){
    $enabledplugins = [$subtype => $enabledplugins[$subtype]];
}

echo $OUTPUT->box("Processing plugins:" . implode(', ', array_keys($enabledplugins)));

foreach ($enabledplugins as $type => $plugin) {
    if ($plugin->is_tracking()) {
        $result = $plugin->harvest();
        foreach ($result->messages as $message) {
            echo "<p>$message</p>";
        }
    } else {
        echo "<p>Plugin $type is not tracking. (Missing token, hashtag or disabled.)</p>";
    }
}

echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
echo $OUTPUT->footer();