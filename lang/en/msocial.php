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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
$string['socialconnectors'] = 'Social connectors.';
$string['socialviews'] = 'Social visualizations.';
$string['settings'] = 'MSocial settings';
$string['disable_social_subplugin'] = 'Disable {$a->name} connector.';
$string['disable_view_subplugin'] = 'Disable {$a->name} view plugin.';
$string['modulename'] = 'Social activity count contest';
$string['modulenameplural'] = 'Social activity count contests';
$string['modulename_help'] = 'The msocial activity enables a teacher to define a search string for the twitter timeline and facebook posts and tell the students to publish messages using certain hashtags or terms.

The module runs periodically in the background checking the activity of the timelines and accounting for the tweets, retweets and favs for every student. The module computes a grade by combining those stats using a formula.

The teacher need to have a Twitter and/or Facebook account and to connect the activity with his Twitter user to give permissions to the module to query Twitter and/or Facebook. Additionally the teacher can insert a Twitter widget in the main page of the activity to show the selected timeline to the students.';
$string['pluginname'] = 'Social Activity count module';

$string['widget_id'] = 'Widget id to be embedded in the main page.';
$string['widget_id_help'] = 'weeter API forces to create mannually a search widget in yout twitter account to be embedded in any page. Create one and copy and paste the WidgetId created. You can create the widgets at <a href="https://twitter.com/settings/widgets">Create and manage yout Twitter Widgets</a>';

$string['startdate'] = 'Moment of the conquest starting.';
$string['startdate_help'] = 'Tweets before this moment are not included in the statistics.';
$string['enddate'] = 'Moment of the conquest ending.';
$string['enddate_help'] = 'Tweets after this moment are not included in the statistics.';
$string['grade_variables'] = 'Social variables';
$string['grade_variables_help'] = 'Variables generated from the social network activities. They can be used in the grading formula';
$string['grade_expr'] = 'Formula for converting stats into grades.';
$string['grade_expr_help'] = 'The formula for converting stats into grades can contain the following params: favs, tweets, retweets, maxfavs, maxtweets, maxretweets and a variet of functions like max, min, sum, average, etc. The decimal point is \'.\' and the separator for parameters is \',\' Example: \'=max(favs,retweets,1.15)\' Parameters maxtweets, maxretweets and maxfavs account for the maximun numbers achieved among the participants.';
$string['harvest_task'] = 'Scheduled harvesting of social networks and calculation of pkis.';
$string['pluginadministration'] = 'Social networks conquest';
$string['view_social_users'] = 'Social users table';
// MainPage.
$string['mainpage'] = 'Social networks contest main page';
$string['mainpage_help'] = 'Social networks contest main page. You can view your achievements in Social networks contest';

// SETTINGS.
// Permissions.
$string['msocial:view'] = 'View basic information of MSocial module.';
$string['msocial:viewothers'] = 'View activity of other users.';
$string['msocial:addinstance'] = 'Add new MSocial activities to the course.';
$string['msocial:manage'] = 'Change settings of a MSocial activity';
$string['msocial:view'] = 'View information of MSocial about me';
$string['msocial:viewothers'] = 'View all information collected by MSocial';