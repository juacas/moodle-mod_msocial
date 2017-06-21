<?php
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
require_once ('../../../../config.php');
require_once ('../../locallib.php');
require_once ('../../tcountsocialplugin.php');
require_once ('../../social/socialinteraction.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$fromdate = optional_param('from', null, PARAM_ALPHANUMEXT);
$todate = optional_param('from', null, PARAM_ALPHANUMEXT);
$subtype = optional_param('subtype', null, PARAM_ALPHA);
$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
$tcount = $DB->get_record('tcount', array('id' => $cm->instance), '*', MUST_EXIST);
require_login($cm->course, false, $cm);
$plugins = mod_tcount\plugininfo\tcountsocial::get_enabled_social_plugins($tcount);
$contextcourse = context_course::instance($tcount->course);
if ($subtype) {
    $subtypefilter = "source ='$subtype'";
} else {
    $subtypefilter = '';
}
list($students, $nonstudents, $activeusers, $userrecords) = eduvalab_get_users_by_type($contextcourse);
// Process interactions.
$interactions = social_interaction::load_interactions((int) $tcount->id, $subtypefilter, $fromdate, $todate);
$nodes = [];
$nodemap = [];
$edges = [];
$index=0;
foreach ($interactions as $interaction) {
    if ($interaction->nativeto !== null && $interaction->nativefrom !== null) {
        $plugin = $plugins[$interaction->source];
        
        $nodenamefrom = get_fullname($interaction->fromid,$userrecords, "[$interaction->nativefromname]");
        if ($nodenamefrom == null) {
            continue;
        }
        if (!array_key_exists($nodenamefrom, $nodemap)) {
            $node = (object) ['id'=>$index, 'name' => $nodenamefrom, 'group' => $interaction->fromid == null];
            $nodes[] = $node;
            $nodemap[$node->name] = $index++;
        }
        $nodenameto =  get_fullname($interaction->toid,$userrecords, $interaction->nativetoname);
        if ($nodenameto == null) {
            continue;
        }
        if (!array_key_exists($nodenameto, $nodemap)) {
            $node = (object) ['id'=>$index,'name' => $nodenameto, 'group' => $interaction->toid == null];
            $nodes[] = $node;
            $nodemap[$node->name] = $index++;
        }
        switch ($interaction->type) {
            case social_interaction::POST:
                $typevalue = 1;
                break;
            case social_interaction::MENTION:
                $typevalue = 2;
                break;
            case social_interaction::REPLY:
                $typevalue = 3;
                break;
            case social_interaction::REACTION:
                $typevalue = 4;
                break;
            case social_interaction::MESSAGE:
                $typevalue = 5;
                break;
        }
        $subtype = $interaction->source;
        $plugin = $plugins[$subtype];
        $url = $plugin->get_interaction_url($interaction);
        
        $edge = (object) ['source' => $nodemap[$nodenamefrom], 'target' => $nodemap[$nodenameto], 'value' => $typevalue, 
                        'interactiontype' => $interaction->type, 'subtype' => $subtype, 'description' => $interaction->description, 'icon' => $plugin->get_icon()->out(), 
                        'link' => $url];
        $edges[] = $edge;
    }
}

$jsondata = (object) ['nodes' => $nodes, 'links' => $edges];
$jsonencoded = json_encode($jsondata);
echo $jsonencoded;

function get_fullname($userid, $users,$default) {
    if ($userid!=null && isset($users[$userid])) {
        $user = $users[$userid];
        return fullname($user);
    } else {
        return $default;
    }
}