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
require_once('../../config.php');
require_once('locallib.php');
header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT); // Course Module ID, or
$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
$tcount = $DB->get_record('tcount', array('id' => $cm->instance),'*',MUST_EXIST);
require_login($cm->course, false, $cm);
$statuses = tcount_load_statuses($tcount,$cm,null);
$events = array();

foreach($statuses as $status){
    if ($status->userid==null){
        continue;
    }
    $details = json_decode($status->status);
    $username=$details->user->screen_name;
    $url = "https://twitter.com/$username/status/$details->id_str";
    $stats = "(RT:".$details->retweet_count." FAV:".$details->favorite_count.")";
    $event=['start'=>$details->created_at,
//        'end'=>$details->created_at,
//        'isDuration'=>false,
        'title'=>'@'.$details->user->screen_name.$stats,
        'description'=>"<a target=\"_blank\" href=\"$url\">$details->text</a> $stats"
        ];
    $events[]=$event;
}
$json_data = array (
        'wiki-url'=>new moodle_url('/mod/tcount/view.php',['id'=>$cm->id]),
        'wiki-section'=>'Twitter count timeline',
        'dateTimeFormat'=>'Gregorian',
        'events'=> $events
);
$json_encoded=json_encode($json_data);
echo $json_encoded;