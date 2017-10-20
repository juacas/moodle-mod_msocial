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
require_once('../../../../config.php');
require_once('../../locallib.php');
require_once('../../msocialconnectorplugin.php');
require_once('../../socialinteraction.php');
require_once('../../filterinteractions.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$community = optional_param('include_community', true, PARAM_BOOL);
$redirecturl = optional_param('redirect', null, PARAM_ALPHANUM);


$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
require_login($cm->course, false, $cm);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
$contextcourse = context_course::instance($msocial->course);

$usersstruct = msocial_get_users_by_type($contextcourse);
list($students, $nonstudents, $activeusers, $userrecords) = array_values($usersstruct);
$filter = new filter_interactions($_GET, $msocial);
$filter->set_users($usersstruct);
// Process interactions.
xdebug_break();
$interactions = social_interaction::load_interactions_filter($filter);
$nodes = [];
$nodemap = [];
$edges = [];
$index = 0;
foreach ($interactions as $interaction) {
    if ($interaction->nativefrom !== null) {
        $subtype = $interaction->source;
        if (!isset($plugins[$subtype])) {
            continue;
        }
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
            $userlinkfrom = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&" .
                            "nativeid=$interaction->nativefrom&nativename=$interaction->nativefromname&redirect=$redirecturl";
        }
        if (!array_key_exists($nodenamefrom, $nodemap)) {
            global $OUTPUT, $PAGE;
            $node = (object) ['id' => $index, 'name' => $nodenamefrom, 'group' => $interaction->fromid == null ? 1 : 0,
                            'userlink' => $userlinkfrom];
            if (isset($userrecords[$interaction->fromid])) {
                $userpicture = new user_picture(($userrecords[$interaction->fromid]));
                $node->usericon = $userpicture->get_url($PAGE)->out();
            } else {
                $node->usericon = '';
            }
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
                $userlinkto = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&" .
                "nativeid=$interaction->nativeto&nativename=$interaction->nativetoname&redirect=$redirecturl";
            }
        }
        if ($nodenameto == null) {
            continue;
        }
        if (!array_key_exists($nodenameto, $nodemap)) {
            $node = (object) ['id' => $index, 'name' => $nodenameto, 'group' => $interaction->toid == null ? 1 : 0,
                            'userlink' => $userlinkto];
            if (isset($userrecords[$interaction->toid])) {
                $userpicture = new user_picture(($userrecords[$interaction->toid]));
                $node->usericon = $userpicture->get_url($PAGE)->out();
            } else {
                $node->usericon = '';
            }
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
        $thispageurl = $plugin->get_interaction_url($interaction);

        $edge = (object) ['id' => $interaction->uid, 'source' => $nodemap[$nodenamefrom], 'target' => $nodemap[$nodenameto],
                        'value' => $typevalue, 'interactiontype' => $interaction->type, 'subtype' => $subtype,
                        'description' => $interaction->description,
                        'icon' => $plugin->get_icon()->out(), 'link' => $thispageurl];
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