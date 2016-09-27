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
/* * ***************************
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
require_once("locallib.php");
/* @var $OUTPUT \core_renderer */
global $DB, $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('tcount', $id, null, null, MUST_EXIST);
require_login($cm->course, false, $cm);
$course = get_course($cm->course);

if (!$tcount = $DB->get_record('tcount', array('id' => $cm->instance))) {
    print_error("Course module is incorrect");
}
$user = $USER;
// Capabilities.
$contextmodule = context_module::instance($cm->id);
require_capability('mod/tcount:view', $contextmodule);

// Show headings and menus of page.
$url = new moodle_url('/mod/tcount/view.php', array('id' => $id));
$PAGE->set_url($url);

$requ = $PAGE->requires;
$requ->js('/mod/tcount/js/init_timeline.js', true);
$requ->js('/mod/tcount/js/timeline/timeline-api.js?bundle=true', true);
$requ->css('/mod/tcount/styles.css');

$requ->js_init_call("init_timeline", [$cm->id, null], true);
$PAGE->set_title(format_string($tcount->name));
$PAGE->set_heading($course->fullname);
// Print the page header.

echo $OUTPUT->header();
// Print the main part of the page.
echo $OUTPUT->spacer(array('height' => 20));
echo $OUTPUT->heading(format_string($tcount->name) . $OUTPUT->help_icon('mainpage', 'tcount'));
// Print the links.
$contextcourse = context_course::instance($cm->course);
if (has_capability('mod/tcount:manage', $contextmodule)) {
    $token = $DB->get_record('tcount_tokens', array('tcount_id' => $tcount->id));

    if ($token) {
        $username = $token->username;
        $errorstatus = $token->errorstatus;
        if ($errorstatus) {
            echo $OUTPUT->notify_problem(get_string('problemwithtwitteraccount', 'tcount', $errorstatus));
        }
        echo $OUTPUT->box(get_string('module_connected_twitter', 'tcount', $username)
                . $OUTPUT->action_link(new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id, 'action' => 'connect')),
                        "Change user") . '/'
                . $OUTPUT->action_link(new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id, 'action' => 'disconnect')),
                        "Disconnect"));
    } else {
        echo $OUTPUT->notification(get_string('module_not_connected_twitter', 'tcount')
                . $OUTPUT->action_link(new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id, 'action' => 'connect')),
                        "Connect"));
    }
    // Facebook connection
    $fbtoken = $DB->get_record('tcount_fbtokens', array('tcount_id' => $tcount->id));
    if ($fbtoken) {
        if ($fbtoken->errorstatus) {
            echo $OUTPUT->notify_problem(get_string('problemwithfacebookaccount', 'tcount', $errorstatus));
        }
        echo $OUTPUT->box(get_string('module_connected_facebook', 'tcount', $fbtoken->username)
                . $OUTPUT->action_link(new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id, 'action' => 'connect')),
                        "Change user") . '/'
                . $OUTPUT->action_link(new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id, 'action' => 'disconnect')),
                        "Disconnect"));
    } else {
        echo $OUTPUT->notification(get_string('module_not_connected_facebook', 'tcount')
                . $OUTPUT->action_link(new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id, 'action' => 'connect')),
                        "Connect"));
    }
}
echo $OUTPUT->box(format_text($tcount->intro, FORMAT_MOODLE), 'generalbox', 'intro');
echo '<div id="my-timeline" style="overflow-y: visible; height: 250px; border: 1px solid #aaa"></div>';
echo $OUTPUT->spacer(array('height' => 20));


if (has_capability('mod/tcount:viewothers', $contextmodule)) {
    list($students, $nonstudents, $activeusers, $userrecords) = eduvalab_get_users_by_type($contextcourse);
    $students = array_merge($students, $nonstudents);
} else {
    $students = array($USER->id);
    $userrecords[$USER->id] = $USER;
}
$groups = groups_get_activity_allowed_groups($cm);
$userstats = tcount_calculate_stats($tcount, $students);
$table = new html_table();
$table->head = array('Student', 'tweets', 'retweets', 'favs');
foreach ($userstats->users as $userid => $stat) {
    $row = new html_table_row();
    // Photo and link to user profile.
    $user = $userrecords[$userid];
    if (has_capability('moodle/user:viewdetails', $contextmodule)) {
        $userpic = $OUTPUT->user_picture($user);
        $profilelink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&course='
                . $course->id . '">' . fullname($user, true) . '</a>';
    } else {
        $userpic = '';
        $profilelink = '';
    }
    $twitterusername = tcount_get_social_username($user, $tcount, 'twitter');
    if (!$twitterusername) {
        $customfieldname = tcount_get_custom_fieldname($tcount);
        if ($customfieldname !== false) {
            $fieldname = $customfieldname;
        } else {
            $fieldname = $tcount->fieldid;
        }
        $a = new stdClass();
        $twittername = get_string('no_twitter_name_advice', 'tcount',
                ['field' => $fieldname, 'userid' => $user->id, 'courseid' => $course->id]);
    } else {
        $twittername = $twitterusername;
    }
    $row->cells[] = new html_table_cell($userpic . $profilelink . ' (' . $twittername . ')');
    $row->cells[] = new html_table_cell($twitterusername ? '<a href="https://twitter.com/search?q=' . urlencode($tcount->hashtag)
                    . '%20from%3A' . $twitterusername . '&src=typd">' . $stat->tweets . '</a>' : '--');
    $row->cells[] = new html_table_cell($twitterusername ? $stat->retweets : '--');
    $row->cells[] = new html_table_cell($twitterusername ? $stat->favs : '--');
    $table->data[] = $row;
}

echo html_writer::table($table);
// Insert timeline.
if (isset($tcount->widget_id)) {
    echo('<a class="twitter-timeline" data-dnt="true" target="_blank" href="https://twitter.com/search?q='
    . urlencode($tcount->hashtag) . '" data-widget-id="' . $tcount->widget_id . '">Tweets sobre ' . $tcount->hashtag . '</a>');
    ?>
    <script>!function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
            if (!d.getElementById(id)) {
                js = d.createElement(s);
                js.id = id;
                js.src = p + "://platform.twitter.com/widgets.js";
                fjs.parentNode.insertBefore(js, fjs);
                }
            }(document, "script", "twitter-wjs");</script>
    <?php
}
// Finish the page.
echo $OUTPUT->footer();
