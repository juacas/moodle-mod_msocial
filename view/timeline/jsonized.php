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
use mod_msocial\connector\social_interaction;

require_once('../../../../config.php');
require_once('../../locallib.php');
require_once('../../msocialconnectorplugin.php');
require_once('../../socialinteraction.php');
require_once('../../filterinteractions.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$fromdate = optional_param('startdate', null, PARAM_ALPHANUMEXT);
$todate = optional_param('enddate', null, PARAM_ALPHANUMEXT);
$subtype = optional_param('subtype', null, PARAM_ALPHA);
$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$contextcourse = context_course::instance($msocial->course);

require_login($cm->course, false, $cm);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);

$usersstruct = msocial_get_users_by_type($contextcourse);
list($students, $nonstudents, $activeusers, $userrecords) = array_values($usersstruct);
$filter = new filter_interactions($_GET, $msocial);
$filter->set_users($usersstruct);
// Process interactions.
$interactions = social_interaction::load_interactions_filter($filter);

$events = [];
foreach ($interactions as $interaction) {
    $subtype = $interaction->source;
    if (!isset($plugins[$subtype])) {
        continue;
    }
    if ($interaction->timestamp == null) {
        continue;
    }
    $date = $interaction->timestamp->format('r');
    $subtype = $interaction->source;
    $plugin = $plugins[$subtype];
    $userinfo = $plugin->get_social_userid($interaction->fromid);
    if (!$userinfo) {
        $userinfo = (object) ['socialname' => $interaction->nativefromname];
    }
    $thispageurl = $plugin->get_interaction_url($interaction);
    $description = $plugin->get_interaction_description($interaction);
    $event = ['start' => $date, 'title' => $userinfo->socialname,
                    'description' => "<a target=\"_blank\" href=\"$thispageurl\">$description</a>",
                    'icon' => $plugin->get_icon()->out()];
    $events[] = $event;
}

$jsondata = array('wiki-url' => new moodle_url('/mod/msocial/view.php', ['id' => $cm->id]),
                'wiki-section' => 'Twitter count timeline', 'dateTimeFormat' => 'Gregorian', 'events' => $events);
$jsonencoded = json_encode($jsondata);
echo $jsonencoded;