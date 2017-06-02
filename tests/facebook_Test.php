<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
// Use doc: https://docs.moodle.org/dev/Writing_PHPUnit_tests 

use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\Authentication\OAuth2Client;
defined('MOODLE_INTERNAL') || die();
global $DB, $CFG;
require_once('Facebook/autoload.php');
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/lti/OAuth.php');
require_once('locallib.php');


class mod_tcount_facebook_testcase extends advanced_testcase {

    public function test_get_graph() {
       
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
        $group = $response->getGraphEdge();
//print_object($response);
        print_object($group);

        $this->assertEquals(2, 1 + 2);
    }

}
