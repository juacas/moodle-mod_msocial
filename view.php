<?php
use mod_tcount\plugininfo\tcountview;
use tcount\tcount_plugin;
use mod_tcount\social\social_interaction;

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
require_once ("../../config.php");
require_once ("locallib.php");
require_once ("tcountsocialplugin.php");
require_once ("tcountviewplugin.php");
/* @var $OUTPUT \core_renderer */
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);
$view = optional_param('view', 'table', PARAM_ALPHA);
$cattab = optional_param('cattab', tcount_plugin::CAT_VISUALIZATION, PARAM_ALPHA);

$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);

$tcount = $DB->get_record('tcount', array('id' => $cm->instance), '*', MUST_EXIST);
$user = $USER;
// Capabilities.
$contextmodule = context_module::instance($cm->id);
require_capability('mod/tcount:view', $contextmodule);

// Show headings and menus of page.
$url = new moodle_url('/mod/tcount/view.php', array('id' => $id));
$PAGE->set_url($url);

$requ = $PAGE->requires;
$requ->css('/mod/tcount/styles.css');
$requ->jquery();
// Configure header for plugins.
$enabledviewplugins = tcountview::get_enabled_view_plugins($tcount);
foreach ($enabledviewplugins as $name => $plugin) {
    $plugin->render_header_requirements($requ, $view);
}
// Print the page header.
$PAGE->set_title(format_string($tcount->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
// Print the main part of the page.
echo $OUTPUT->spacer(array('height' => 20));
echo $OUTPUT->heading(format_string($tcount->name) . $OUTPUT->help_icon('mainpage', 'tcount'));
// Print the information about the linking of the module with social plugins..
    $enabledsocialplugins = \mod_tcount\plugininfo\tcountsocial::get_enabled_social_plugins($tcount);
    $enabledplugins = array_merge($enabledviewplugins,$enabledsocialplugins);
    /** @var tcount_plugin $plugin Enabled social plugins status section. */
    foreach ($enabledplugins as $name => $enabledplugin) {
        /** @var tcount_plugin $enabledplugin */
        echo $enabledplugin->view_header();
    }

// Description text.
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
echo format_module_intro('tcount', $tcount, $cm->id);
echo $OUTPUT->box_end();
// Reporting area...

// Tabs...

echo tcount_tabbed_reports($tcount, $view, $cm, $contextmodule, false);

if (isset($enabledviewplugins[$view]) && $enabledviewplugins[$view]->is_enabled()) {
    $enabledviewplugins[$view]->render_view($OUTPUT, $requ);
}

// Insert widget view.
/*
if (isset($tcount->widget_id)) {
    echo ('<a class="twitter-timeline" data-dnt="true" target="_blank" href="https://twitter.com/search?q=' .
             urlencode($tcount->hashtag) . '" data-widget-id="' . $tcount->widget_id . '">Tweets sobre ' . $tcount->hashtag . '</a>');
    ?>
<script>!function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
            if (!d.getElementById(id)) {
                js = d.createElement(s);
                js.id = id;
                js.src = p + "://platform.twitter.com/widgets.js";
                fjs.parentNode.insertBefore(js, fjs);
            }
        }(document, "script", "twitter-wjs");</script>
<?php
}
*/
// Finish the page.
echo $OUTPUT->footer();
