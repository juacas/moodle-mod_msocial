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
require_once('../../classes/msocialconnectorplugin.php');
require_once('../../classes/socialinteraction.php');
require_once('../../classes/filterinteractions.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$community = optional_param('include_community', true, PARAM_BOOL);
$redirecturl = optional_param('redirect', null, PARAM_RAW);
$redirecturl = urlencode($redirecturl);

$cm = get_coursemodule_from_id('msocial', $id, null, null, MUST_EXIST);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
require_login($cm->course, false, $cm);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($msocial);
$contextcourse = context_course::instance($msocial->course);
$contextmodule = context_module::instance($cm->id);
$canviewothers = has_capability('mod/msocial:viewothers', $contextmodule);
// Get list of users.
$usersstruct = msocial_get_viewable_users($cm, $msocial);
$userrecords = $usersstruct->userrecords;
$filter = new filter_interactions($_GET, $msocial);
$filter->set_users($usersstruct);
// Process interactions.
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
        list($nodenamefrom, $userlinkfrom) = msocial_create_userlink($interaction, 'from', $userrecords, $msocial, $cm, $redirecturl, $canviewothers);
        if ($nodenamefrom == null) {
            continue;
        }
        msocial_add_node_if_not_exists($nodenamefrom, $interaction->fromid, $userlinkfrom, $nodemap, $nodes, $canviewothers, $userrecords, $index);
        list($nodenameto, $userlinkto) = msocial_create_userlink($interaction, 'to', $userrecords, $msocial, $cm, $redirecturl, $canviewothers);
        if ($nodenameto == null) {
            continue;
        }
        msocial_add_node_if_not_exists($nodenameto, $interaction->toid, $userlinkto, $nodemap, $nodes, $canviewothers, $userrecords, $index);

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

        $edge = (object) ['id' => $interaction->uid,
                        'source' => $nodemap[$nodenamefrom],
                        'target' => $nodemap[$nodenameto],
                        'value' => $typevalue,
                        'interactiontype' => $interaction->type,
                        'subtype' => $subtype,
                        'description' => $plugin->get_interaction_description($interaction),
                        'icon' => $plugin->get_icon()->out(),
                        'link' => $thispageurl];
        $edges[] = $edge;
    }
}

$jsondata = (object) ['nodes' => $nodes, 'links' => $edges];
$jsonencoded = json_encode($jsondata);
echo $jsonencoded;

function msocial_add_node_if_not_exists($nodename, $userid, $userlink, &$nodemap, &$nodes, $canviewothers, $userrecords, &$index) {
    global $PAGE, $USER;
    if (!array_key_exists($nodename, $nodemap)) {
        $node = (object) ['id' => $index,
                        'name' => $nodename,
                        'group' => $userid == null ? 1 : 0,
                        'userlink' => $userlink];

        if (($canviewothers || $userid == $USER->id) && isset($userrecords[$userid])) {
            $userpicture = new user_picture(($userrecords[$userid]));
            $node->usericon = $userpicture->get_url($PAGE)->out(false);
        } else {
            $node->usericon = (new \moodle_url('/mod/msocial/pix/Anonymous2.svg'))->out();

        }
        $nodes[] = $node;
        $nodemap[$node->name] = $index++;
    }
}
function msocial_create_userlink($interaction, $dir = 'to', $userrecords, $msocial, $cm, $redirecturl, $canviewothers) {
    global $USER;
    $nativeid = $interaction->{"native{$dir}"};
    $userid = $interaction->{"{$dir}id"};
    $nativename = $interaction->{"native{$dir}name"};
    if ($nativeid == null) { // Community destination.
        $nodename = '[COMMUNITY]';
        $userlink = '';
    } else {
        $nodename = get_fullname($userid, $userrecords, "[$nativename]", $msocial);
        $userlink = '';
        if ($canviewothers || $USER->id == $userid) {
            if (isset($userrecords[$userid])) {
                $userlink = (new moodle_url('/mod/msocial/socialusers.php',
                        ['action' => 'showuser',
                                        'user' => $userid,
                                        'id' => $cm->id,
                                        'redirect' => $redirecturl,
                        ]))->out(false);
            } else {
                $userlink = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&" .
                "nativeid=$nativeid&nativename=$nativename&redirect=$redirecturl";
            }
        }
    }
    return [$nodename, $userlink];
}
function get_fullname($userid, $users, $default, $msocial) {
    if ($userid != null && isset($users[$userid])) {
        $user = $users[$userid];
        return msocial_get_visible_fullname($user, $msocial);
    } else {
        return $default;
    }
}