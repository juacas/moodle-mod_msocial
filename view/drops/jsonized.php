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
require_once('../../../../config.php');
require_once('../../locallib.php');
require_once('../../classes/msocialconnectorplugin.php');
require_once('../../classes/socialinteraction.php');
require_once('../../classes/socialuser.php');

header('Content-Type: application/json; charset=utf-8');
$id = required_param('id', PARAM_INT);
$redirecturl = optional_param('redirect', null, PARAM_RAW);
$redirecturl = urlencode($redirecturl);

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
if (count($interactions) > 0) {
    foreach ($interactions as $interaction) {
        $subtype = $interaction->source;
        if (!isset($plugins[$subtype])) {
            continue;
        }
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
        $namefrom = get_fullname($interaction->fromid, $userrecords, "[$interaction->nativefromname]", $msocial);
        if ($canmapusers || $interaction->fromid == $USER->id) {
            if (isset($userrecords[$interaction->fromid])) {
                global $OUTPUT, $PAGE;
                $userlinkfrom = (new moodle_url('/mod/msocial/socialusers.php',
                        ['action' => 'showuser',
                                        'user' => $interaction->fromid,
                                        'id' => $cm->id,
                                        'redirect' => $redirecturl,
                        ]))->out(false);
                $userpicture = new user_picture(($userrecords[$interaction->fromid]));
                $usericon = $userpicture->get_url($PAGE)->out(false);
            } else {
                $userlinkfrom = (new moodle_url('/mod/msocial/socialusers.php',
                        ['action' => 'selectmapuser',
                        'source' => $interaction->source,
                        'user' => $interaction->fromid,
                        'id' => $cm->id,
                        'nativeid' => $interaction->nativefrom,
                        'nativename' => $interaction->nativefromname,
                        'redirect' => $redirecturl,
                        ]))->out(false);
                $usericon = (new \moodle_url('/mod/msocial/pix/Anonymous2.svg'))->out();
            }
        } else {
            $userlinkfrom = '#';
            $usericon = (new \moodle_url('/mod/msocial/pix/Anonymous2.svg'))->out();
        }
        $thispageurl = $plugin->get_interaction_url($interaction);
        $event = ['id' => $interaction->uid, 'username' => $namefrom, 'usericon' => $usericon,
                        'startdate' => $date, 'title' => $userinfo->socialname,
                        'description' => $plugin->get_interaction_description($interaction), 'icon' => $plugin->get_icon()->out(),
                        'link' => $thispageurl, 'importance' => 10, 'date_limit' => 'mo',
                        'userlink' => $userlinkfrom,
        ];
        $events[$subtype][] = $event;
    }
}
$timeseries = [];
foreach ($events as $key => $eventseries) {
    $serie = new stdClass();
    $serie->name = $key;
    $serie->data = $eventseries;
    $timeseries[] = $serie;
}
$jsonencoded = json_encode($timeseries);
echo $jsonencoded;

function get_fullname($userid, $users, $default, $msocial) {
    if ($userid != null && isset($users[$userid])) {
        $user = $users[$userid];
        return msocial_get_visible_fullname($user, $msocial);
    } else {
        return $default;
    }
}