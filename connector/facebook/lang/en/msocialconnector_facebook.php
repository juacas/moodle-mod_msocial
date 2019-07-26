<?php
// This file is part of FacebookCount activity for Moodle http://moodle.org/
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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$string['pluginname'] = 'Facebook Connector';
$string['fbgroup'] = 'Facebook Group to analyze';
$string['connectgroupinpage'] = 'Select your group in the main page of the activity';
$string['configure_group'] = 'Facebook requires that the MSocial app is installed <b>INSIDE your group</b>. To enable monitoring of your group you must add MSocial to your group and give it permissions following <a href="https://www.facebook.com/help/www/1967138829984927"> these instructions</a>.';
$string['fbfieldid'] = 'Field that holds the facebook username';
$string['fbfieldid_help'] = 'Field name of the user profile that holds the facebook username';
$string['fbsearch'] = 'Search string to search for in Facebook';
$string['fbsearch_help'] = 'It can be any string as specified in Facebook search API. You can use this reference to find out how to compose your search string <a href="https://developers.facebook.com/docs/graph-api/using-graph-api/v1.0#searchtypes">https://developers.facebook.com/docs/graph-api/using-graph-api/v1.0#searchtypes</a>';
$string['harvest'] = 'Search Facebook groups for student activity';

$string['no_facebook_name_advice'] = 'Unlinked to Facebook.';
$string['no_facebook_name_advice2'] = '{$a->userfullname} is not linked to Facebook. Register using Facebook clicking in <a href="{$a->url}"><img src="{$a->pixurl}/loginwithfacebook.png" alt="Facebook login"/></a>';

$string['module_connected_facebook'] = 'Module connected with Facebook as user "{$a}" ';
$string['module_not_connected_facebook'] = 'Module disconnected from facebook. It won\'t work until a facebook account is linked again.';
$string['selectthisgroup'] = 'Use this group';

// SETTINGS.
$string['facebook_app_id'] = 'app_id';
$string['config_app_id'] = 'app_id according to FacebookAPI (<a href="https://developers.facebook.com/docs/apps/register" target="_blank" >https://developers.facebook.com/docs/apps/register</a>)';
$string['facebook_app_secret'] = 'app_secret';
$string['config_app_secret'] = 'app_secret according to FacebookAPI (<a href="https://developers.facebook.com/docs/apps/register" target="_blank" >https://developers.facebook.com/docs/apps/register</a>)';
$string['problemwithfacebookaccount'] = 'Recent attempts to get the posts resulted in an error. Try to reconnect Facebook with your user. Message: {$a}';

$string['kpi_description_posts'] = 'Published messages (posts and replies)';
$string['kpi_description_replies'] = 'Received comments';
$string['kpi_description_reactions'] = 'Received reactions (LIKE, LOVE, WOW, HAHA, SAD, ANGRY, THANKFUL)';
$string['kpi_description_likes'] = 'Received LIKEs';
$string['kpi_description_dislikes'] = 'Received dis-LIKEs (SAD or ANGRY reactions)';