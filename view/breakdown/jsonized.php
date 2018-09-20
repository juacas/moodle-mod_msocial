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
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * *******************************************************************************/
use mod_msocial\connector\social_interaction;
use mod_msocial\social_user;
use mod_msocial\filter_interactions;
require_once('../../../../config.php');
require_once('../../locallib.php');
require_once('../../classes/msocialconnectorplugin.php');
require_once('../../classes/socialinteraction.php');
require_once('../../classes/socialuser.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$redirecturl = optional_param('redirect', null, PARAM_RAW);

$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
require_login($cm->course, false, $cm);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
$contextcourse = context_course::instance($msocial->course);
$contextmodule = context_module::instance($cm->id);
$canmapusers = has_capability('mod/msocial:mapaccounts', $contextmodule);

$events = [];
foreach ($plugins as $plugin) {
    $events[$plugin->get_subtype()] = [];
}
$lastitemdate = null;
$firstitemdate = null;

$filter = new filter_interactions($_GET, $msocial);
$usersstruct = msocial_get_viewable_users($cm, $msocial);
$userrecords = $usersstruct->userrecords;
$filter->set_users($usersstruct);
// Process interactions.
$interactions = social_interaction::load_interactions_filter($filter);
// Hierarchy: sources, type, interactions
$sources = [];
if (count($interactions) > 0) {
    foreach ($interactions as $interaction) {
        $subtype = $interaction->source;
        if (!isset($plugins[$subtype])) {
            continue;
        }
        $subtype = $interaction->source;
        if (!isset($sources[$subtype])) {
            $sources[$subtype] = (object)['name' => $subtype, 'children' => []];
        }
        $typesarray = $sources[$subtype]->children;
        $type = $interaction->type;
        if (!isset($typesarray[$type])) {
            $typesarray[$type] = (object)['name' => $type, 'size' => 0];
        }
        $typesarray[$type]->size ++;
        $sources[$subtype]->children = $typesarray;
    }
}
// Clean subtypes.
foreach ($sources as $obj) {
    $obj->children = array_values($obj->children);
}
$tree = (object) [ 'name' => "msocial",
                'children' => array_values($sources) ];
$jsonencoded = json_encode($tree, false);
echo $jsonencoded;

function get_fullname($userid, $users, $default, $msocial) {
    if ($userid != null && isset($users[$userid])) {
        $user = $users[$userid];
        return msocial_get_visible_fullname($user, $msocial);
    } else {
        return $default;
    }
}