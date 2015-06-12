<?php

/* * *******************************************************************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro with the effort of many other
 * students of telecommunication engineering of Valladolid
 * Copyright 2009-2011 EdUVaLab http://www.eduvalab.uva.es
 * this module is provides as-is without any guarantee. Use it as your own risk.

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

 * @author Juan Pablo de Castro and other contributors.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package tcount
 * ******************************************************************************* */

require_once("../../config.php");


// Get the params --------------------------------------------------
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT); // Course Module ID, or

$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);

if (!$tcount = $DB->get_record('tcount', array('id' => $cm->instance))) {
    print_error("Course module is incorrect");
}
$user = $USER;

// Capabilities -----------------------------------------------------
$context_module = context_module::instance($cm->id);
require_capability('mod/tcount:view', $context_module);


// Log --------------------------------------------------------------
//    $info='';
//    $url="view.php?id=$cm->id";
//    if ($CFG->version >= 2014051200) {
//        require_once 'classes/event/tcount_viewed.php';
//        \mod_tcount\event\tcount_viewed::create_from_parts($course->id, $user->id, $tcount->id,$url, $info)->trigger();
//    } else {
//        add_to_log($course->id, "tcount", "view", $url, "$tcount->id");
//    }
// show headings and menus of page
$url = new moodle_url('/mod/tcount/view.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_title(format_string($tcount->name));
// $PAGE->set_context($context_module);
$PAGE->set_heading($course->fullname);
// Print the page header --------------------------------------------    

echo $OUTPUT->header();
// Print the main part of the page ---------------------------------- 
echo $OUTPUT->spacer(array('height' => 20));
echo $OUTPUT->heading(format_string($tcount->name) . $OUTPUT->help_icon('mainpage', 'tcount'));

echo $OUTPUT->box(format_text($tcount->intro, FORMAT_MOODLE), 'generalbox', 'intro');
echo $OUTPUT->spacer(array('height' => 20));

// Print the links -------------------------------------------------- 
// Obtenemos el contexto del curso
$context_course = context_course::instance($cm->course);
if (has_capability('mod/tcount:manage', $context_module)) {

    $username = $DB->get_field('tcount_tokens', 'username', array('tcount_id' => $cm->id));
    if ($username) {
        echo $OUTPUT->box(get_string('module_connected','tcount',$username). $OUTPUT->action_link(new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id, 'action' => 'connect')), "Change user") . '/' . $OUTPUT->action_link(new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id, 'action' => 'disconnect')), "Disconnect"));
    } else {
        echo $OUTPUT->box(get_string('module_not_connected','tcount'). $OUTPUT->action_link(new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id, 'action' => 'connect')), "Connect"));
    } 
}

if (has_capability('mod/tcount:viewothers',$context_module)){
    list($students, $non_students, $active_users, $user_records) = eduvalab_get_users_by_type($context_course);
}else{
    $students = array($USER->id);
    $user_records[$USER->id]=$USER;
}
    $user_stats = tcount_calculate_stats($tcount, $students);
    $table = new html_table();
    $table->head = array('Student', 'tweeter', 'tweets','retweets','favs');
    foreach ($user_stats->users as $userid => $stat) {
        $row = new html_table_row();
        // Foto y vinculo a perfil de `user`
        $user = $user_records[$userid];
        if (has_capability('moodle/user:viewdetails', $context_module)) {
            $userpic = $OUTPUT->user_picture($user);
            $profilelink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&course=' . $course->id . '">' . fullname($user, true) . '</a>';
        }else{
            $userpic = '';
            $profilelink='';
        }
        $twittername= tcount_get_user_twittername($user,$tcount);
        if( !$twittername){
            $custom_fieldname= tcount_get_custom_fieldname($tcount);
            if ($custom_fieldname!==false){
                $field_name= $custom_fieldname;
            }else{
                $field_name = $tcount->fieldid;
            }
            $twittername=get_string('no_twitter_name_advice','tcount',$field_name);
        }
        $row->cells[] = new html_table_cell($userpic.$profilelink. ' ('.$twittername.')');
        $row->cells[] = new html_table_cell($stat->tweeter);
        $row->cells[] = new html_table_cell($stat->tweets);
        $row->cells[] = new html_table_cell($stat->retweets);
        $row->cells[] = new html_table_cell($stat->favs);
        $table->data[] = $row;
    }

    echo html_writer::table($table);
// insert timeline
    if (isset($tcount->widget_id)){
?>
<a class="twitter-timeline" data-dnt="true" href="https://twitter.com/search?q=<?php echo $tcount->hashtag; ?>" data-widget-id="<?php echo $tcount->widget_id;?>">Tweets sobre <?php echo $tcount->hashtag; ?></a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<?php
    }
// Finish the page
echo $OUTPUT->footer();
?>
