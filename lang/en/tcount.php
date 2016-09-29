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

$string['modulename'] = 'Social activity count contest';
$string['modulenameplural'] = 'Social activity count contests';
$string['modulename_help'] = 'The tcount activity enables a teacher to define a search string for the twitter timeline and facebook posts and tell the students to publish messages using certain hashtags or terms.

The module runs periodically in the background checking the activity of the timelines and accounting for the tweets, retweets and favs for every student. The module computes a grade by combining those stats using a formula.

The teacher need to have a Twitter and/or Facebook account and to connect the activity with his Twitter user to give permissions to the module to query Twitter and/or Facebook. Additionally the teacher can insert a Twitter widget in the main page of the activity to show the selected timeline to the students.';
$string['pluginname'] = 'Social Activity count module';

$string['twfieldid'] = 'Field that holds the tweeter username';
$string['twfieldid_help'] = 'Field name of the user profile that holds the tweeter username';
$string['fbfieldid'] = 'Field that holds the facebook username';
$string['fbfieldid_help'] = 'Field name of the user profile that holds the facebook username';

$string['hashtag'] = 'Hashtag to search for in tweets';
$string['hashtag_help'] = 'It can be any string as specified in Twitter API. You can use this tool to compose your search string <a href="https://twitter.com/search-advanced">https://twitter.com/search-advanced</a>';
$string['fbsearch'] = 'Search string to search for in Facebook';
$string['fbsearch_help'] = 'It can be any string as specified in Facebook search API. You can use this reference to find out how to compose your search string <a href="https://developers.facebook.com/docs/graph-api/using-graph-api/v1.0#searchtypes">https://developers.facebook.com/docs/graph-api/using-graph-api/v1.0#searchtypes</a>';
$string['widget_id'] = 'Widget id to be embedded in the main page.';
$string['widget_id_help'] = 'weeter API forces to create mannually a search widget in yout twitter account to be embedded in any page. Create one and copy and paste the WidgetId created. You can create the widgets at <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';

$string['counttweetsfromdate'] = 'Moment of the conquest starting.';
$string['counttweetsfromdate_help'] = 'Tweets before this moment are not included in the statistics.';
$string['counttweetstodate'] = 'Moment of the conquest ending.';
$string['counttweetstodate_help'] = 'Tweets after this moment are not included in the statistics.';
$string['grade_expr'] = 'Formula for converting stats into grades.';
$string['grade_expr_help'] = 'The formula for converting stats into grades can contain the following params: favs, tweets, retweets, maxfavs, maxtweets, maxretweets and a variet of functions like max, min, sum, average, etc. The decimal point is \'.\' and the separator for parameters is \',\' Example: \'=max(favs,retweets,1.15)\' Parameters maxtweets, maxretweets and maxfavs account for the maximun numbers achieved among the participants.';

$string['pluginadministration'] = 'Twitter conquest';
$string['harvest_tweets'] = 'Search Twitter timeline for student activity';
// MainPage.
$string['mainpage'] = 'Twitter contest main page';
$string['mainpage_help'] = 'Twitter contest main page. You can view your achievements in Twitter contest';
$string['module_connected_twitter'] = 'Module connected with Twitter as user "{$a}" ';
$string['module_not_connected_twitter'] = 'Module not connected to Twitter.';
$string['no_twitter_name_advice'] = 'No Twitter name. Enter it in field \'{$a->field}\' of the <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">user profile</a>';
$string['no_twitter_name_advice2'] = 'No Twitter name. Enter it in field \'{$a->field}\' of the <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">user profile</a> or in <a href="{$a->url}"><img src="https://g.twimg.com/dev/sites/default/files/images_documentation/sign-in-with-twitter-gray.png" alt="Facebook login"/></a>';
$string['no_facebook_name_advice'] = 'No Facebook name. Enter it in field \'{$a->field}\' of the <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">user profile</a>';
$string['no_facebook_name_advice2'] = 'No Facebook name. Enter it in field \'{$a->field}\' of the <a href="../../user/edit.php?id={$a->userid}&course={$a->courseid}">user profile</a> or in <a href="{$a->url}"><img src="pix/loginwithfacebook.png" alt="Facebook login"/></a>';
$string['module_connected_facebook'] = 'Module connected with Facebook as user "{$a}" ';
$string['module_not_connected_facebook'] = 'Module not connected to Facebook.';

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
$string['problemwithfacebookaccount'] = 'Recent attempts to get the posts resulted in an error. Try to reconnect Facebook with your user. Message: {$a}';
// Permissions.
$string['tcount:view']= 'View basic information of Tcount module.';
$string['tcount:viewothers'] = 'View activity of other users.';
$string['tcount:addinstance'] = 'Add new Tcount activities to the course.';
$string['tcount:manage'] = 'Change settings of a Tcount activity';
$string['tcount:view'] = 'View information of Tcount about me';
$string['tcount:viewothers'] = 'View all information collected by Tcount';
