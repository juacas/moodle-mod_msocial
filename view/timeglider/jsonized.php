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

if ($subtype) {
    $subtypefilter = "source ='$subtype'";
} else {
    $subtypefilter = '';
}
$events = [];
// Process interactions.
$interactions = social_interaction::load_interactions((int) $tcount->id, $subtypefilter, $fromdate, $todate);
foreach ($interactions as $interaction) {
    if ($interaction->timestamp == null) {
        continue;
    }
    $date = $interaction->timestamp->format('Y-m-d H:i:s');
    $subtype = $interaction->source;
    $plugin = $plugins[$subtype];
    $userinfo = $plugin->get_social_userid($interaction->fromid);
    if (!$userinfo) {
        $userinfo = (object) ['socialname' => $interaction->nativefromname];
    }
    $url = $plugin->get_interaction_url($interaction);
    $event = ['id' => $interaction->uid, 
                    'startdate' => $date,
                    'title' => $userinfo->socialname, 
                    'description' => $interaction->description, 
                    'icon' => $plugin->get_icon()->out(),
                    'link' => $url, 
                    'importance'=>10,
                    'date_limit'=>'mo'];
    $events[] = $event;
}
$legend=[];
foreach ($plugins as $plugin){
    $legend[]=(object)['title'=>$plugin->get_name(),'icon'=>$plugin->get_icon()->out()];
}
$jsondata = [(object)['id' => 'e', 
                'description' => 'Twitter count timeglide',
                'title' => 'Timeglider',
                'focus_date' => "2017-05-12 12:00:00",
                'initial_zoom'=>"38",
                'events' => $events,
                'legend'=>$legend,
                'size_importance'=>false
]
];
$jsonencoded = json_encode($jsondata);
echo $jsonencoded;