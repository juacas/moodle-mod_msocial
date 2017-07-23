<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
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
// along with MSocial for Moodle. If not, see <http://www.gnu.org/licenses/>.
use mod_msocial\connector\msocial_connector_twitter;

require_once ("../../../../config.php");
require_once ($CFG->dirroot . '/mod/lti/OAuth.php');
require_once ('../../locallib.php');
require_once ('../../msocialconnectorplugin.php');
require_once ('twitterplugin.php');
global $CFG;
$id = required_param('id', PARAM_INT); // MSocial module instance cmid.
$action = optional_param('action', false, PARAM_ALPHA);
$type = optional_param('type', 'connect', PARAM_ALPHA);
$cm = get_coursemodule_from_id('msocial', $id);
$course = get_course($cm->course);
require_login($course);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);

$consumerkey = get_config("msocialconnector_twitter", "consumer_key");
$consumersecret = get_config("msocialconnector_twitter", "consumer_secret");

$oauthrequesttoken = "https://twitter.com/oauth/request_token";
$oauthauthorize = "https://twitter.com/oauth/authorize";
$oauthaccesstoken = "https://twitter.com/oauth/access_token";

$thispageurl = new moodle_url("/mod/msocial/connector/twitter/twitterSSO.php",
        array('id' => $cm->id, 'action' => 'callback', 'type' => $type));
$callbackurl = $thispageurl->out($escaped = false);
$context = context_module::instance($id);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$plugin = new msocial_connector_twitter($msocial);
if ($action == 'callback') { // Twitter callback.
    $sigmethod = new \moodle\mod\lti\OAuthSignatureMethod_HMAC_SHA1();
    $testconsumer = new \moodle\mod\lti\OAuthConsumer($consumerkey, $consumersecret, $callbackurl);
    $params = array();
    $acctoken = new \moodle\mod\lti\OAuthConsumer($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], 1);
    $accreq = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, $acctoken, "GET", $oauthaccesstoken);
    $accreq->sign_request($sigmethod, $testconsumer, $acctoken);

    $oc = new OAuthCurl();
    $reqdata = $oc->fetch_data("{$accreq}&oauth_verifier={$_GET['oauth_verifier']}");

    parse_str($reqdata['content'], $accoauthdata);
    if (!isset($accoauthdata['oauth_token'])) {
        print_error('error');
    }
    /*
     * Save tokens for future use
     */
    $socialname = $accoauthdata['screen_name'];
    if ($type === 'connect' && has_capability('mod/msocial:manage', $context)) {

        $record = new stdClass();
        $record->token = $accoauthdata['oauth_token'];
        $record->token_secret = $accoauthdata['oauth_token_secret'];
        $record->username = $socialname;
        $plugin->set_connection_token($record);
        $message = "Configured user $record->username ";
    } else if ($type === 'profile') { // Fill the profile with user id
        $socialid = $accoauthdata['user_id'];
        $plugin->set_social_userid($USER, $socialid, $socialname);
        $message = "Profile updated with twitter user $socialname ";
    } else {
        $message = "Access forbidden.";
    }
    // Show headings and menus of page.
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));

    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box($message);
    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $cm->id)));
    echo $OUTPUT->footer();
} else if ($action == 'connect') {

    $sigmethod = new \moodle\mod\lti\OAuthSignatureMethod_HMAC_SHA1();
    $testconsumer = new \moodle\mod\lti\OAuthConsumer($consumerkey, $consumersecret, $callbackurl);

    $reqreq = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, null, "GET", $oauthrequesttoken,
            array('oauth_callback' => $callbackurl));
    $reqreq->sign_request($sigmethod, $testconsumer, null);

    $oc = new OAuthCurl();
    $reqdata = $oc->fetch_data($reqreq->to_url());
    if ($reqdata['errno'] == 0) {
        parse_str($reqdata['content'], $reqoauthdata);

        $reqtoken = new \moodle\mod\lti\OAuthConsumer($reqoauthdata['oauth_token'], $reqoauthdata['oauth_token_secret'], 1);

        $accreq = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, $reqtoken, "GET", $oauthauthorize,
                array('oauth_callback' => $callbackurl));
        $accreq->sign_request($sigmethod, $testconsumer, $reqtoken);

        $_SESSION['oauth_token'] = $reqoauthdata['oauth_token'];
        $_SESSION['oauth_token_secret'] = $reqoauthdata['oauth_token_secret'];

        header("Location: $accreq");
    } else {
        // OAUTH Error.
        $PAGE->set_url($thispageurl);
        $PAGE->set_title(format_string($cm->name));
        $PAGE->set_heading($course->fullname);
        // Print the page header.
        echo $OUTPUT->header();
        $continue = new moodle_url('/mod/msocial/view.php', array('id' => $cm->id));
        print_error('Error authenticating with Twitter.', null, $continue->out(),$reqdata['errmsg']);
    }
} else if ($action == 'disconnect') {
    $plugin->unset_connection_token();
    // Show headings and menus of page.
    $PAGE->set_url($thispageurl);
    $PAGE->set_title(format_string($cm->name));
    $PAGE->set_heading($course->fullname);
    // Print the page header.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('module_not_connected_twitter', 'msocialconnector_twitter'));
    echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $cm->id)));
    echo $OUTPUT->footer();
} else {
    print_error("Bad action code");
}
