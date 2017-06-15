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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with TwitterCount for Moodle. If not, see <http://www.gnu.org/licenses/>.
use Facebook\Authentication\OAuth2Client;
use Facebook\Facebook;
use Facebook\GraphNodes\GraphNodeFactory;
use mod_tcount\social\tcount_social_facebook;

require_once ('Facebook/autoload.php');
require_once ("../../../../config.php");
require_once ($CFG->dirroot . '/mod/lti/OAuth.php');
require_once ('../../locallib.php');
require_once ('../../tcountsocialplugin.php');
require_once ('facebookplugin.php');
require_once ('locallib.php');
global $CFG;
$id = required_param('id', PARAM_INT); // Tcount module instance.
$action = optional_param('action', false, PARAM_ALPHA);
$type = optional_param('type', 'connect', PARAM_ALPHA);
$cm = get_coursemodule_from_id('tcount', $id);
$course = get_course($cm->course);
require_login($course);
$context = context_module::instance($id);
$tcount = $DB->get_record('tcount', array('id' => $cm->instance), '*', MUST_EXIST);
$plugin = new tcount_social_facebook($tcount);

// $appid = '176559559452612';
// $appsecret = 'd2d4813b97e830b35b46939a8eb86ee2';
$appid = $CFG->mod_tcount_facebook_appid;
$appsecret = $CFG->mod_tcount_facebook_appsecret;
/**@var \Facebook\Facebook $fb */
$fb = new \Facebook\Facebook(
        ['app_id' => $appid, 'app_secret' => $appsecret, 'default_graph_version' => 'v2.7', 
                        'default_access_token' => '{access-token}' // optional
]);
$record = $DB->get_record('tcount_facebook_tokens', array("tcount" => $cm->instance));
if ($action == 'connect') {
    // GetToken
    $helper = $fb->getRedirectLoginHelper();
    $permissions = ['posts', 'user_likes', 'pages_show_list', 'user_posts', 'user_about_me'];
    $permissions = ['user_managed_groups'];
    $moodleurl = new moodle_url("/mod/tcount/social/facebook/facebookSSO.php", 
            array('id' => $id, 'action' => 'callback', 'type' => $type));
    $callbackurl = $moodleurl->out($escaped = false);
    $loginUrl = $helper->getLoginUrl($callbackurl, $permissions);
    
    header("Location: $loginUrl");
    die;
} else if ($action == 'callback') {
    $helper = $fb->getRedirectLoginHelper();
    try {
        $accessToken = $helper->getAccessToken();
        $client = $fb->getOAuth2Client();
        $longaccesstoken = $client->getLongLivedAccessToken($accessToken);
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        print_error('Graph returned an error: ' . $e->getMessage()); // TODO: pasar a lang
        exit();
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        print_error('Facebook SDK returned an error: ' . $e->getMessage()); // TODO: pasar a lang
        exit();
    }
    if (isset($accessToken)) {
        // Logged in!
        // The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();
        if (!$accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";                                                                                            // lang
                exit();
            }
        }
        $fb->setDefaultAccessToken($accessToken);
        $graphuser = $fb->get('/me')->getGraphUser();
        $username = $graphuser->getName();
        // Save tokens for future use.
        if ($type === 'connect' && has_capability('mod/tcount:manage', $context)) {
            if ($record) {
                $DB->delete_records('tcount_facebook_tokens', array('id' => $record->id));
            }
            $record = new stdClass();
            $record->token = $accessToken->getValue();
            $record->username = $username;
            $plugin->set_connection_token($record);
            $message = get_string('module_connected_facebook', 'tcountsocial_facebook', $record->username);
            // Fill the profile with username in Facebook.
        } else if ($type === 'profile') {
            $socialname = $graphuser->getName();
            $plugin->set_social_userid($USER, $graphuser->getId(),$socialname);
            $message = "Profile updated with facebook user $socialname ";
        } else {
            print_error('unknownuseraction');
        }
        // Now you can redirect to another page and use the
    } elseif ($helper->getError()) {
        // The user denied the request
        $message = get_string('module_not_connected_facebook', 'tcountsocial_facebook');
    }
    // Show headings and menus of page.
    $url = new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id));
    $PAGE->set_url($url);
    $PAGE->set_title(format_string($cm->name));
    
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box($message);
    echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
} else if ($action == 'disconnect') {
    $DB->delete_records('tcount_facebook_tokens', array('tcount' => $cm->instance));
    // Show headings and menus of page.
    $url = new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id));
    $PAGE->set_url($url);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box("Module disconnected from facebook. It won't work until a facebook account is linked again. ");
    echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
} else if ($action == 'selectgroup') {
    $url = new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id, 'action' => 'selectgroup'));
    $PAGE->set_url($url);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    $token = $plugin->get_connection_token();
    $fb->setDefaultAccessToken($token->token);
    $groups = $fb->get('/me/groups?fields=administrator,name,cover,icon,link,description');
    $grphFty = new GraphNodeFactory($groups);
    $groupsEdge = $grphFty->makeGraphEdge('GraphGroup');
    echo $plugin->view_group_list($groupsEdge);
} else if ($action == 'setgroup') {
    $gid = required_param('gid', PARAM_INT);
    $gname = required_param('gname', PARAM_RAW_TRIMMED);
    $url = new moodle_url('/mod/tcount/facebookSSO.php', array('id' => $id,'gid'=>$gid,'gname'=>$gname));
    $PAGE->set_url($url);
    $PAGE->set_title(get_string('selectthisgroup','tcountsocial_facebook').':'.$gname);
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('selectthisgroup','tcountsocial_facebook').':'.$gname);
    
    // Save the configuration
    $plugin->set_config(tcount_social_facebook::CONFIG_FBGROUP, $gid);
    $plugin->set_config(tcount_social_facebook::CONFIG_FBGROUPNAME, $gname);
    
    echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
    
} else {
    print_error("Bad action code");
}
echo $OUTPUT->footer();