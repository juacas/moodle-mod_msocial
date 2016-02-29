<?php
require_once('../../config.php');
require_once('locallib.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT); // Course Module ID, or
$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
$tcount = $DB->get_record('tcount', array('id' => $cm->instance),'*',MUST_EXIST);
require_login($cm->course, false, $cm);
$statuses = tcount_load_statuses($tcount,null);
$events = array();

foreach($statuses as $status){
    if ($status->userid==null){
        continue;
    }
    $details = json_decode($status->status);
    $event=['start'=>$details->created_at,
//        'end'=>$details->created_at,
//        'isDuration'=>false,
        'title'=>'@'.$details->user->screen_name,
        'description'=>$details->text."(RT:".$details->retweet_count." FAV:".$details->favorite_count.")"
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