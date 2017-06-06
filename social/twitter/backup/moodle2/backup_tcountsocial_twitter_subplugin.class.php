<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
defined ( 'MOODLE_INTERNAL' ) || die ();
class backup_tcountsocial_twitter_subplugin extends backup_subplugin {

	/**
	 * Returns the subplugin information to attach to tcount element
	 *
	 * @return backup_subplugin_element
	 */
	protected function define_tcount_subplugin_structure() {
		// To know if we are including userinfo.
		$userinfo = $this->get_setting_value ( 'userinfo' );
		// Create XML elements.
		$subplugin = $this->get_subplugin_element ();
		$subpluginwrapper = new backup_nested_element ( $this->get_recommended_name () );

		$tcountstatuses = new backup_nested_element ( 'tweets' );
		$tcountstatus = new backup_nested_element ( 'status', array (), array (
				'userid',
				'tweetid',
				'twitterusername',
				'hashtag',
				'status',
				'retweets',
				'favs'
		) );
		$twittertoken = new backup_nested_element ( 'token', array (), array (
				'token',
				'token_secret',
				'username'
		) );
		$subplugin->add_child ( $subpluginwrapper );
		$subpluginwrapper->add_child ( $twittertoken );
		$subpluginwrapper->add_child ( $tcountstatuses );
		$tcountstatuses->add_child ( $tcountstatus );
		// Map tables...
		$twittertoken->set_source_table ( 'tcount_twitter_tokens', array (
				'tcount' => backup::VAR_ACTIVITYID
		) );
		if ($userinfo) {
			$tcountstatus->set_source_table ( 'tcount_tweets', array (
					'tcount' => backup::VAR_ACTIVITYID
			) );
		}
		// Define id annotations.
		$tcountstatus->annotate_ids ( 'userid', 'userid' );
		return $subplugin;
	}
}
