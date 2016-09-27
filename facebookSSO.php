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
if (has_capability('mod/tcount:manage', $context)) {
    $appid = '176559559452612';
    $appsecret = 'd2d4813b97e830b35b46939a8eb86ee2';
    $fb = new \Facebook\Facebook([
        'app_id' => $appid,
        'app_secret' => $appsecret,
        'default_graph_version' => 'v2.7',
            //'default_access_token' => '{access-token}', // optional
    ]);
    if ($action == 'connect') {
//GetToken
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['posts', 'user_likes'];
        $moodleurl = new moodle_url("/mod/tcount/facebookSSO.php", array('id' => $id, 'action' => 'callback'));
        $callbackurl = $moodleurl->out($escaped = false);
        $loginUrl = $helper->getLoginUrl($callbackurl);
        // echo $loginUrl; die;
        header("Location: $loginUrl");
    } else
    if ($action = 'callback') {
        $helper = $fb->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
             echo "<p>User token: $accessToken</p>\n\n";
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (isset($accessToken)) {
            // Logged in!
            $_SESSION['facebook_access_token'] = (string) $accessToken;
           
            // The OAuth 2.0 client handler helps us manage access tokens
            $oAuth2Client = $fb->getOAuth2Client();
            if (!$accessToken->isLongLived()) {
                // Exchanges a short-lived access token for a long-lived one
                try {
                    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                    echo "<p>loglived token $accessToken</p>\n\n";
                } catch (Facebook\Exceptions\FacebookSDKException $e) {
                    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
                    exit;
                }
            }else{
                 echo "<p>User token is long-lived</p>\n\n";
            }
            $fb->setDefaultAccessToken($accessToken);
            $graphuser = $fb->get('/me')->getGraphUser();
            print_object($graphuser);
            $username = $graphuser->getName();
            echo "<p>username: $username</p>\n\n";
            /*
             * Save tokens for future use
             */
            $record = $DB->get_record('tcount_fbtokens', array("tcount_id" => $cm->instance));
            if ($record) {
                $DB->delete_records('tcount_fbtokens', array('id' => $record->id));
            }
            $record = new stdClass();
            $record->tcount_id = $cm->instance;
            $record->token = $accessToken->getValue();
            $record->username = $username;
            $DB->insert_record('tcount_fbtokens', $record);
            // Now you can redirect to another page and use the
            // access token from $_SESSION['facebook_access_token']
        } elseif ($helper->getError()) {
            // The user denied the request
            exit;
        }
 // Show headings and menus of page.
        $url = new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id));
        $PAGE->set_url($url);
        $PAGE->set_title(format_string($cm->name));

        $PAGE->set_heading($course->fullname);
        // Print the page header.
        echo $OUTPUT->header();
        echo $OUTPUT->box("Configured facebook user $record->username ");
        echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
        echo $OUTPUT->footer();
    } else if ($action == 'disconnect') {
        $DB->delete_records('tcount_fbtokens', array('tcount_id' => $cm->instance));
        // Show headings and menus of page.
        $url = new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id));
        $PAGE->set_url($url);
        $PAGE->set_title(format_string($cm->name));
        $PAGE->set_heading($course->fullname);
        // Print the page header.
        echo $OUTPUT->header();
        echo $OUTPUT->box("Module disconnected from facebook. It won't work until a facebook account is linked again. ");
        echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
        echo $OUTPUT->footer();
    }  else {
        print_error("Bad action code");
    }
} else {
    print_error('noaccess');
}    