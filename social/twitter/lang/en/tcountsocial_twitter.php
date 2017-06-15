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
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$string['pluginname'] = 'Twitter Connector';

$string['twfieldid'] = 'Field that holds the tweeter username';
$string['twfieldid_help'] = 'Field name of the user profile that holds the tweeter username';

$string['hashtag'] = 'Hashtag to search for in tweets';
$string['hashtag_help'] = 'It can be any string as specified in Twitter API. You can use this tool to compose your search string <a href="https://twitter.com/search-advanced">https://twitter.com/search-advanced</a>';
$string['widget_id'] = 'Widget id to be embedded in the main page.';
$string['widget_id_help'] = 'weeter API forces to create mannually a search widget in yout twitter account to be embedded in any page. Create one and copy and paste the WidgetId created. You can create the widgets at <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';


$string['pluginadministration'] = 'Twitter conquest';
$string['harvest_tweets'] = 'Search Twitter timeline for student activity';
// MainPage.
$string['mainpage'] = 'Twitter contest main page';
$string['mainpage_help'] = 'Twitter contest main page. You can view your achievements in Twitter contest';
$string['module_connected_twitter'] = 'Module connected with Twitter as user "{$a}" ';
$string['module_not_connected_twitter'] = 'Module not connected to Twitter.';
$string['no_twitter_name_advice'] = 'No Twitter name. Enter it in field \'{$a->field}\' of the <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">user profile</a>';
$string['no_twitter_name_advice2'] = 'No Twitter name. Enter it in field \'{$a->field}\' of the <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">user profile</a> or in <a href="{$a->url}"><img src="https://g.twimg.com/dev/sites/default/files/images_documentation/sign-in-with-twitter-gray.png" alt="Facebook login"/></a>';


// SETTINGS.
$string['tcount_oauth_access_token'] = 'oauth_access_token';
$string['config_oauth_access_token'] = 'oauth_access_token de acuerdo con TwitterAPI';
$string['tcount_oauth_access_token_secret'] = 'oauth_access_token_secret';
$string['config_oauth_access_token_secret'] = 'oauth_access_token_secret de acuerdo con TwitterAPI';
$string['tcount_consumer_key'] = 'consumer_key';
$string['config_consumer_key'] = 'consumer_key according to TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['tcount_consumer_secret'] = 'consumer_secret';
$string['config_consumer_secret'] = 'consumer_secret according to TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['problemwithtwitteraccount'] = 'Recent attempts to get the tweets resulted in an error. Try to reconnect Twitter with your user. Message: {$a}';

