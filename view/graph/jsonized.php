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
use mod_msocial\connector\social_interaction;
use mod_msocial\social_user;
require_once ('../../../../config.php');
require_once ('../../locallib.php');
require_once ('../../msocialconnectorplugin.php');
require_once ('../../socialinteraction.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$fromdate = optional_param('from', null, PARAM_ALPHANUMEXT);
$community = optional_param('include_community', true, PARAM_BOOL);
$todate = optional_param('from', null, PARAM_ALPHANUMEXT);
$subtype = optional_param('subtype', null, PARAM_ALPHA);
$interactiontypes = optional_param('int_types', null, PARAM_RAW);
$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
require_login($cm->course, false, $cm);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
$contextcourse = context_course::instance($msocial->course);
if ($subtype) {
    $subtypefilter = "source ='$subtype'";
} else {
    $subtypefilter = '';
}
if ($interactiontypes) {
    $types = explode(',', $interactiontypes);
    $inttypessqlparts = [];
    foreach ($types as $type) {
        $inttypessqlparts[] = "type = '$type'";
    }
    $inttypesql = implode(' OR ', $inttypessqlparts);
    $subtypefilter .= " ($inttypesql) ";
}

list($students, $nonstudents, $activeusers, $userrecords) = msocial_get_users_by_type($contextcourse);
// Process interactions.
$interactions = social_interaction::load_interactions((int) $msocial->id, $subtypefilter, $fromdate, $todate);
$nodes = [];
$nodemap = [];
$edges = [];
$index = 0;
foreach ($interactions as $interaction) {
    if ($interaction->nativefrom !== null) {
        $subtype = $interaction->source;
        $plugin = $plugins[$subtype];
        if ($plugin->is_enabled() == false) {
            continue;
        }
        if ($interaction->nativeto == null && $community == false) {
            continue;
        }
        $nodenamefrom = get_fullname($interaction->fromid, $userrecords, "[$interaction->nativefromname]");

        if ($nodenamefrom == null) {
            continue;
        }
        if (isset($userrecords[$interaction->fromid])) {
            $userlinkfrom = (new moodle_url('/user/view.php', ['id' => $interaction->fromid]))->out();
        } else {
            $userlinkfrom = $plugin->get_social_user_url(new social_user($interaction->nativefrom, $interaction->nativefromname));
            $userlinkfrom = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&nativeid=$interaction->nativefrom&nativename=$interaction->nativefromname";
        }
        if (!array_key_exists($nodenamefrom, $nodemap)) {
            $node = (object) ['id' => $index, 'name' => $nodenamefrom, 'group' => $interaction->fromid == null,
                            'userlink' => $userlinkfrom];
            $nodes[] = $node;
            $nodemap[$node->name] = $index++;
        }
        if ($interaction->nativeto == null) { // Community destination.
            $nodenameto = '[COMMUNITY]';
            $userlinkto = '';
        } else {
            $nodenameto = get_fullname($interaction->toid, $userrecords, "[$interaction->nativetoname]");
            // TODO: link social network or local user.
            if (isset($userrecords[$interaction->toid])) {
                $userlinkto = (new moodle_url('/user/view.php', ['id' => $interaction->toid]))->out();
            } else {
                // $userlinkto = $plugin->get_social_user_url(new
                // social_user($interaction->nativeto, $interaction->nativetoname));
                $userlinkto = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&nativeid=$interaction->nativeto&nativename=$interaction->nativetoname";
            }
        }
        if ($nodenameto == null) {
            continue;
        }
        if (!array_key_exists($nodenameto, $nodemap)) {
            $node = (object) ['id' => $index, 'name' => $nodenameto, 'group' => $interaction->toid == null,
                            'userlink' => $userlinkto];
            $nodes[] = $node;
            $nodemap[$node->name] = $index++;
        }
        switch ($interaction->type) {
            case social_interaction::POST:
                $typevalue = 5;
                break;
            case social_interaction::MENTION:
                $typevalue = 2;
                break;
            case social_interaction::REPLY:
                $typevalue = 3;
                break;
            case social_interaction::REACTION:
                $typevalue = 1;
                break;
            case social_interaction::MESSAGE:
                $typevalue = 5;
                break;
        }
        $url = $plugin->get_interaction_url($interaction);

        $edge = (object) ['source' => $nodemap[$nodenamefrom], 'target' => $nodemap[$nodenameto], 'value' => $typevalue,
                        'interactiontype' => $interaction->type, 'subtype' => $subtype, 'description' => $interaction->description,
                        'icon' => $plugin->get_icon()->out(), 'link' => $url];
        $edges[] = $edge;
    }
}

$jsondata = (object) ['nodes' => $nodes, 'links' => $edges];
$jsonencoded = json_encode($jsondata);
echo $jsonencoded;

function get_fullname($userid, $users, $default) {
    if ($userid != null && isset($users[$userid])) {
        $user = $users[$userid];
        return fullname($user);
    } else {
        return $default;
    }
}