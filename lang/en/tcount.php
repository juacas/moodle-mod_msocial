<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$string['modulename'] ='Twitter count contest';
$string['modulenameplural']='Twitter count contests';
$string['modulename_help']='The tcount activity enables a teacher to define a search string for the twitter timeline and tell the students to publish messages using certain hashtags or terms.

The module runs periodically in the background checking the activity of the timelines and accounting for the tweets, retweets and favs for every student. The module computes a grade by combining those stats using a formula.

The teacher need to have a Twitter account and to connect the activity with his Twitter user. Additionally the teacher can insert a Twitter widget in the main page of the activity to show the selected timeline to the students.';
$string['pluginname']='Twitter count module';

//$string['idtype'] = 'Field that holds the tweeter username';
$string['fieldid'] = 'Field that holds the tweeter username';
$string['fieldid_help'] = 'Field name of the user profile that holds the tweeter username';

$string['hashtag'] = 'Hashtag to search for in tweets';
$string['hashtag_help'] = 'It can be any string as specified in Twitter API. You can use this tool to compose your search string <a href="https://twitter.com/search-advanced">https://twitter.com/search-advanced</a>';
$string['widget_id'] = 'Widget id to be embedded in the main page.';
$string['widget_id_help'] = 'weeter API forces to create mannually a search widget in yout twitter account to be embedded in any page. Create one and copy and paste the WidgetId created. You can create the widgets at <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';

$string['counttweetsfromdate'] ='Moment of the conquest starting.';
$string['counttweetsfromdate_help'] ='blah, blah.';
$string['counttweetstodate'] ='Moment of the conquest ending.';
$string['counttweetstodate_help'] ='blah, blah.';
$string['grade_expr'] = 'Formula for converting stats into grades.';
$string['grade_expr_help'] = 'The formula for converting stats into grades can contain the following params: favs, tweets, retweets, maxfavs, maxtweets, maxretweets and a variet of functions like max, min, sum, average, etc. The decimal point is \'.\' and the separator for parameters is \',\' Example: \'=max(favs,retweets,1.15)\' ';


$string['pluginadministration'] = 'Twitter conquest';
$string['harvest_tweets']='Search Twitter timeline for student activity';
// MainPage
// 
$string['mainpage']='Twitter contest main page';
$string['mainpage_help']='Twitter contest main page. You can view your achievements in Twitter contest';
$string['module_connected']='Module connected with Twitter as user {$a} ';
$string['module_not_connected']='Module not connected to Twitter.';
$string['no_twitter_name_advice']='No Twitter name. Configure in field \'{$a}\' of the user profile';



// SETTINGS
$string['tcount_oauth_access_token']='oauth_access_token';
$string['config_oauth_access_token']='oauth_access_token de acuerdo con TwitterAPI';
$string['tcount_oauth_access_token_secret']='oauth_access_token_secret';
$string['config_oauth_access_token_secret']='oauth_access_token_secret de acuerdo con TwitterAPI';
$string['tcount_consumer_key']='consumer_key';
$string['config_consumer_key']='consumer_key de acuerdo con TwitterAPI';
$string['tcount_consumer_secret']='consumer_secret';
$string['config_consumer_secret']='consumer_secret de acuerdo con TwitterAPI';
