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
use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\Authentication\OAuth2Client;

require_once('Facebook/autoload.php');
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/lti/OAuth.php');
require_once('locallib.php');
global $CFG;
$id = required_param('id', PARAM_INT); // Tcount module instance.
$action = optional_param('action', false, PARAM_ALPHA);
$cm = get_coursemodule_from_id('tcount', $id);
$course = get_course($cm->course);
require_login($course);
$context = context_module::instance($id);
$record = $DB->get_record('tcount_fbtokens', array("tcount_id" => $cm->instance));
 $appid = '176559559452612';
    $appsecret = 'd2d4813b97e830b35b46939a8eb86ee2';
    $fb = new \Facebook\Facebook([
        'app_id' => $appid,
        'app_secret' => $appsecret,
        'default_graph_version' => 'v2.7',
            //'default_access_token' => '{access-token}', // optional
    ]);
$fb->setDefaultAccessToken($record->token);
$response = $fb->get('/245279878876272/feed');
/* @var $group \Facebook\GraphNodes\GraphEdge */
$group= $response->getGraphEdge();
//print_object($response);
print_object($group);
$total = $group->getTotalCount();

$cursor= $group->getCursor('after');

