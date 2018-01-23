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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with MSocial for Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['pluginname'] = 'Twitter Connector';

$string['hashtag'] = 'Hashtag expression to search for in tweets';
$string['hashtag_help'] = 'It can be a list of tags with AND and OR. OR takes precedence to the left. Example: "#uva AND #msocial OR #m_social"';
$string['hashtag_missing'] = 'Hashtag to search for in tweets in missing. Configure it in the activity <a href="../../course/modedit.php?update={$a->cmid}&return=1">settings</a>.';
$string['hashtag_reminder'] = 'Twitter is searched by search string: <a href="https://twitter.com/search?q={$a->hashtagscaped}">{$a->hashtag}</a>.';

$string['widget_id'] = 'Widget id to be embedded in the main page.';
$string['widget_id_help'] = 'Tweeter API forces to create mannually a search widget in yout twitter account to be embedded in any page. Create one and copy and paste the WidgetId created. You can create the widgets at <a href="https://twitter.com/settings/widgets">Create and manage your Twitter Widgets</a>';


$string['pluginadministration'] = 'Twitter conquest';
$string['harvest_tweets'] = 'Search Twitter timeline for student activity';
// MainPage.
$string['module_connected_twitter'] = 'Module connected with Twitter as user "{$a}" ';
$string['module_not_connected_twitter'] = 'Module disconnected from twitter. It won\'t work until a facebook account is linked again.';
$string['no_twitter_name_advice'] = 'No Twitter name. </a>';
$string['no_twitter_name_advice2'] = '{$a->userfullname} is not linked to Twitter. Register using Twitter in <a href="{$a->url}"><img src="{$a->pixurl}/sign-in-with-twitter-gray.png" alt="Twitter login"/></a>';


// SETTINGS.
$string['msocial_oauth_access_token'] = 'oauth_access_token';
$string['config_oauth_access_token'] = 'oauth_access_token de acuerdo con TwitterAPI';
$string['msocial_oauth_access_token_secret'] = 'oauth_access_token_secret';
$string['config_oauth_access_token_secret'] = 'oauth_access_token_secret de acuerdo con TwitterAPI';
$string['msocial_consumer_key'] = 'consumer_key';
$string['config_consumer_key'] = 'consumer_key according to TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['msocial_consumer_secret'] = 'consumer_secret';
$string['config_consumer_secret'] = 'consumer_secret according to TwitterAPI (<a href="https://apps.twitter.com" target="_blank" >https://apps.twitter.com</a>)';
$string['problemwithtwitteraccount'] = 'Recent attempts to get the tweets resulted in an error. Try to reconnect Twitter with your user. Message: {$a}';

$string['pki_description_tweets'] = 'Published tweets';
$string['pki_description_twmentions'] = 'Received mentions';
$string['pki_description_favs'] = 'Favorite marks received)';
$string['pki_description_retweets'] = 'Retweets received';
