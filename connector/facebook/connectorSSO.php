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
/*
 * **************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
use Facebook\Authentication\OAuth2Client;
use Facebook\Facebook;
use Facebook\GraphNodes\GraphNodeFactory;
use mod_msocial\connector\msocial_connector_facebook;

require_once('vendor/Facebook/autoload.php');
require_once("../../../../config.php");
require_once($CFG->dirroot . '/mod/lti/OAuth.php');
require_once('../../locallib.php');
require_once('../../classes/msocialconnectorplugin.php');
require_once('facebookplugin.php');
require_once('locallib.php');
global $CFG;
$id = required_param('id', PARAM_INT); // MSocial module instance.
$action = optional_param('action', false, PARAM_ALPHA);
$type = optional_param('type', 'connect', PARAM_ALPHA);
$cm = get_coursemodule_from_id('msocial', $id);
$course = get_course($cm->course);
require_login($course);
$context = context_module::instance($id);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$plugin = new msocial_connector_facebook($msocial);

$appid = get_config("msocialconnector_facebook", "appid");
$appsecret = get_config("msocialconnector_facebook", "appsecret");
/**@var \Facebook\Facebook $fb */
$fb = new \Facebook\Facebook(
        ['app_id' => $appid, 'app_secret' => $appsecret, 'default_graph_version' => 'v2.9',
                        'default_access_token' => '{access-token}' // Optional...
]);
$thispageurl = new moodle_url('/mod/msocial/connector/facebook/connectorSSO.php',
        array('id' => $id, 'action' => $action, 'type' => $type));

if ($action == 'connect') {
    // GetToken.
    $helper = $fb->getRedirectLoginHelper();
    $permissions = ['posts', 'user_likes', 'pages_show_list', 'user_posts', 'user_about_me'];
    $permissions = ['user_managed_groups'];
    $thispageurl = new moodle_url("/mod/msocial/connector/facebook/connectorSSO.php",
            array('id' => $id, 'action' => 'callback', 'type' => $type));
    $callbackurl = $thispageurl->out($escaped = false);
    $loginurl = $helper->getLoginUrl($callbackurl, $permissions);

    header("Location: $loginurl");
    die();
} else if ($action == 'callback') {
    $helper = $fb->getRedirectLoginHelper();
    try {
        $accesstoken = $helper->getAccessToken();
        $client = $fb->getOAuth2Client();
        $longaccesstoken = $client->getLongLivedAccessToken($accesstoken);
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        print_error('Graph returned an error: ' . $e->getMessage()); // TODO: pasar a lang.
        exit();
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        print_error('Facebook SDK returned an error: ' . $e->getMessage()); // TODO: pasar a lang.
        exit();
    }
    if (isset($accesstoken)) {
        // Logged in!
        // The OAuth 2.0 client handler helps us manage access tokens.
        $oauth2client = $fb->getOAuth2Client();
        if (!$accesstoken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one.
            try {
                $accesstoken = $oauth2client->getLongLivedAccessToken($accesstoken);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n"; // TODO
                                                                                                        // lang.
                exit();
            }
        }
        $fb->setDefaultAccessToken($accesstoken);
        $graphuser = $fb->get('/me')->getGraphUser();
        $username = $graphuser->getName();
        // Save tokens for future use.
        if ($type === 'connect' && has_capability('mod/msocial:manage', $context)) {

            $record = new stdClass();
            $record->token = $accesstoken->getValue();
            $record->username = $username;
            $plugin->set_connection_token($record);
            $message = get_string('module_connected_facebook', 'msocialconnector_facebook', $record->username);
            // Fill the profile with username in Facebook.
        } else if ($type === 'profile') {
            $socialname = $graphuser->getName();
            $plugin->set_social_userid($USER, $graphuser->getId(), $socialname);
            $message = "Profile updated with facebook user $socialname ";
        } else {
            print_error('unknownuseraction');
        }
    } else if ($helper->getError()) {
        // The user denied the request.
        $message = get_string('module_not_connected_facebook', 'msocialconnector_facebook');
    }
    // Show headings and menus of page.
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));

    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box($message);
    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
} else if ($action == 'disconnect') {
    if ($type == 'profile') {
        $userid = required_param('userid', PARAM_INT);
        $socialid = required_param('socialid', PARAM_RAW_TRIMMED);
        if ($userid != $USER->id) {
            require_capability('mod/msocial:manage', $context);
        }
        $user = (object) ['id' => $userid];
        // Remove the mapping.
        $plugin->unset_social_userid($user, $socialid);
        // Show headings and menus of page.
        $PAGE->set_url($thispageurl);
        $PAGE->set_title(format_string($cm->name));
        $PAGE->set_heading($course->fullname);
        // Print the page header.
        echo $OUTPUT->header();
        echo $OUTPUT->box($plugin->render_user_linking($user, false));
        echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
    } else {
        require_capability('mod/msocial:manage', $context);
        $plugin->unset_connection_token();
        // Show headings and menus of page.
        $PAGE->set_url($thispageurl);
        $PAGE->set_title(format_string($cm->name));
        $PAGE->set_heading($course->fullname);
        // Print the page header.
        echo $OUTPUT->header();
        echo $OUTPUT->box(get_string('module_not_connected_facebook', 'msocialconnector_facebook'));
        echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
    }
} else if ($action == 'selectgroup') {
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    $token = $plugin->get_connection_token();
    $fb->setDefaultAccessToken($token->token);
    $groups = $fb->get('/me/groups?fields=administrator,name,cover,icon,link,description');
    $grphfty = new GraphNodeFactory($groups);
    $groupsedge = $grphfty->makeGraphEdge('GraphGroup');
    echo $plugin->view_group_list($groupsedge);
} else if ($action == 'setgroup') {
    $gid = required_param('gid', PARAM_ALPHANUM);
    $gname = required_param('gname', PARAM_RAW_TRIMMED);
    $thispageurl = new moodle_url('/mod/msocial/connectorSSO.php', array('id' => $id, 'gid' => $gid, 'gname' => $gname));
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(get_string('selectthisgroup', 'msocialconnector_facebook') . ':' . $gname);
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('selectthisgroup', 'msocialconnector_facebook') . ':' . $gname);

    // Save the configuration.
    $plugin->set_config(msocial_connector_facebook::CONFIG_FBGROUP, $gid);
    $plugin->set_config(msocial_connector_facebook::CONFIG_FBGROUPNAME, $gname);

    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $id)));
} else {
    print_error("Bad action code");
}
echo $OUTPUT->footer();