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
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/lti/OAuth.php');
require_once('locallib.php');
global $CFG;
$id = required_param('id', PARAM_INT); // tcount module instance
$action = optional_param('action', false, PARAM_ALPHA);
$cm = get_coursemodule_from_id('tcount', $id);
$course = get_course($cm->course);
require_login($course);
$consumer_key = $CFG->mod_tcount_consumer_key;
$consumer_secret = $CFG->mod_tcount_consumer_secret;

$oauth_request_token = "https://twitter.com/oauth/request_token";
$oauth_authorize = "https://twitter.com/oauth/authorize";
$oauth_access_token = "https://twitter.com/oauth/access_token";

$moodleurl = new moodle_url("/mod/tcount/twitterSSO.php", array('id' => $id, 'action' => 'callback'));
$callback_url = (string) $moodleurl;
$context = context_module::instance($id);
if (has_capability('mod/tcount:manage', $context)) {
    if ($action == 'callback') { // twitter callback
        $sigmethod = new \moodle\mod\lti\OAuthSignatureMethod_HMAC_SHA1();
        $testconsumer = new \moodle\mod\lti\OAuthConsumer($consumer_key, $consumer_secret, $callback_url);
        $params = array();
        $acc_token = new \moodle\mod\lti\OAuthConsumer($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], 1);
        $acc_req = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, $acc_token, "GET", $oauth_access_token);
        $acc_req->sign_request($sigmethod, $testconsumer, $acc_token);

        $oc = new OAuthCurl();
        $reqData = $oc->fetch_data("{$acc_req}&oauth_verifier={$_GET['oauth_verifier']}");

        parse_str($reqData['content'], $accoauthdata);

        /**
         * Save tokens for future use
         */
        $record = $DB->get_record('tcount_tokens', array("tcount_id" => $cm->instance));
        if ($record) {
            $DB->delete_records('tcount_tokens',array('id'=>$record->id));
        }
        $record = new stdClass();
        $record->tcount_id = $cm->instance;
        $record->token = $accoauthdata['oauth_token'];
        $record->token_secret = $accoauthdata['oauth_token_secret'];
        $record->username = $accoauthdata['screen_name'];
        $DB->insert_record('tcount_tokens', $record);

        // show headings and menus of page
        $url = new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id));
        $PAGE->set_url($url);
        $PAGE->set_title(format_string($cm->name));
//        $PAGE->set_context($context);
        $PAGE->set_heading($course->fullname);
// Print the page header --------------------------------------------    
        echo $OUTPUT->header();
        echo $OUTPUT->box("Configured user $record->username ");
        echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
        echo $OUTPUT->footer();
    } else if ($action == 'connect') {

        $sigmethod = new \moodle\mod\lti\OAuthSignatureMethod_HMAC_SHA1;
        $testconsumer = new \moodle\mod\lti\OAuthConsumer($consumer_key, $consumer_secret, $callback_url);

        $reqreq = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, NULL, "GET", $oauth_request_token, array('oauth_callback' => $callback_url));
        $reqreq->sign_request($sigmethod, $testconsumer, NULL);

        $oc = new OAuthCurl();
        $reqData = $oc->fetch_data($reqreq->to_url());

        parse_str($reqData['content'], $reqoauthdata);

        $req_token = new \moodle\mod\lti\OAuthConsumer($reqoauthdata['oauth_token'], $reqoauthdata['oauth_token_secret'], 1);

        $acc_req = \moodle\mod\lti\OAuthRequest::from_consumer_and_token($testconsumer, $req_token, "GET", $oauth_authorize, array('oauth_callback' => $callback_url));
        $acc_req->sign_request($sigmethod, $testconsumer, $req_token);

        $_SESSION['oauth_token'] = $reqoauthdata['oauth_token'];
        $_SESSION['oauth_token_secret'] = $reqoauthdata['oauth_token_secret'];

        Header("Location: $acc_req");
    } else if ($action == 'disconnect') {
        $DB->delete_records('tcount_tokens', array('tcount_id' => $cm->instance));
        // show headings and menus of page
        $url = new moodle_url('/mod/tcount/twitterSSO.php', array('id' => $id));
        $PAGE->set_url($url);
        $PAGE->set_title(format_string($cm->name));
//        $PAGE->set_context($context);
        $PAGE->set_heading($course->fullname);
// Print the page header --------------------------------------------    
        echo $OUTPUT->header();
        echo $OUTPUT->box("Module disconnected from twitter. It won't work until an twitter account is configured. ");
        echo $OUTPUT->continue_button(new moodle_url('/mod/tcount/view.php', array('id' => $id)));
        echo $OUTPUT->footer();
    } else {
        print_error("Bad action code");
    }
} else {
    print_error('noaccess');
}
