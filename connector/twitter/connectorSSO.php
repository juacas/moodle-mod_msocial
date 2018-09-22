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
use mod_msocial\connector\msocial_connector_twitter;
use mod_msocial\connector\OAuthCurl;

global $DB, $CFG, $USER;
require_once("../../../../config.php");
require_once($CFG->dirroot . '/mod/lti/OAuth.php');
require_once('../../locallib.php');
require_once('../../classes/msocialconnectorplugin.php');
require_once('twitterplugin.php');
require_once('TwitterAPIExchange.php');
$id = required_param('id', PARAM_INT); // MSocial module instance cmid.
$action = optional_param('action', 'callback', PARAM_ALPHA);
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

$thispageurl = new moodle_url("/mod/msocial/connector/twitter/connectorSSO.php",
        array('id' => $cm->id, 'action' => 'callback', 'type' => $type));
$callbackurl = $thispageurl->out(false);
$context = context_module::instance($id);
$msocial = $DB->get_record('msocial', array('id' => $cm->instance), '*', MUST_EXIST);
$plugin = new msocial_connector_twitter($msocial);
if ($action == 'callback') { // Twitter callback.
    $sigmethod = new \moodle\mod\lti\OAuthSignatureMethod_HMAC_SHA1();
    $testconsumer = new \moodle\mod\lti\OAuthConsumer($consumerkey, $consumersecret, $callbackurl);
    $acctoken = new \moodle\mod\lti\OAuthConsumer($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], 1);
    $accreq = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, $acctoken, "GET", $oauthaccesstoken);
    $accreq->sign_request($sigmethod, $testconsumer, $acctoken);
    
    $oc = new OAuthCurl();
    $reqdata = $oc->fetch_data("{$accreq}&oauth_verifier={$_GET['oauth_verifier']}");
    $accoauthdata = [];
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
    global $PAGE, $OUTPUT;
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
    if (isset($reqdata['content'])) {
        $reqoauthdata = json_decode($reqdata['content']);
    }
    if ($reqdata['errno'] == 0 && count($reqoauthdata->errors) == 0 ) {
        
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
        $errmsg = $reqdata['errmsg'];
        $errarray = array_map(function($item) { return $item->message; }, $reqoauthdata->errors);
        $errmsg = join('.', $errarray);
        $continue = new moodle_url('/mod/msocial/view.php', array('id' => $cm->id));
        echo $OUTPUT->header();
        print_error('ssoerror', 'msocial', $continue->out(), $errmsg);
    }
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
        echo $OUTPUT->box($plugin->render_user_linking($user));
        echo $OUTPUT->continue_button(new moodle_url('/mod/msocial/view.php', array('id' => $cm->id)));
        echo $OUTPUT->footer();
        
    } else {
        require_capability('mod/msocial:manage', $context);
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
    }
} else {
    print_error("Bad action code");
}
