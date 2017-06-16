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

$string['socialconnectors'] = 'Social connectors.';
$string['socialviews'] = 'Social visualizations.';
$string['settings'] = 'Config TCount';
$string['disable_social_subplugin'] = 'Disable {$a->name} connector.';
$string['disable_view_subplugin'] = 'Disable {$a->name} view plugin.';
$string['modulename'] = 'Social activity count contest';
$string['modulenameplural'] = 'Social activity count contests';
$string['modulename_help'] = 'The tcount activity enables a teacher to define a search string for the twitter timeline and facebook posts and tell the students to publish messages using certain hashtags or terms.

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
$string['pluginadministration'] = 'Social networks conquest';
// MainPage.
$string['mainpage'] = 'Social networks contest main page';
$string['mainpage_help'] = 'Social networks contest main page. You can view your achievements in Social networks contest';

// SETTINGS.
// Permissions.
$string['tcount:view']= 'View basic information of Tcount module.';
$string['tcount:viewothers'] = 'View activity of other users.';
$string['tcount:addinstance'] = 'Add new Tcount activities to the course.';
$string['tcount:manage'] = 'Change settings of a Tcount activity';
$string['tcount:view'] = 'View information of Tcount about me';
$string['tcount:viewothers'] = 'View all information collected by Tcount';
