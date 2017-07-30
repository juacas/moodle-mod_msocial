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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
defined ( 'MOODLE_INTERNAL' ) || die ();
class backup_msocialconnector_twitter_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to msocial element
     *
     * @return backup_subplugin_element
     */
    protected function define_msocial_subplugin_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value ( 'userinfo' );
        // Create XML elements.
        $subplugin = $this->get_subplugin_element ();
        $subpluginwrapper = new backup_nested_element ( $this->get_recommended_name () );

        $msocialstatuses = new backup_nested_element ( 'tweets' );
        $msocialstatus = new backup_nested_element ( 'status', array (), array (
                'userid',
                'tweetid',
                'twitterusername',
                'hashtag',
                'status',
                'retweets',
                'favs'
        ) );
        // TODO: user's connection token must be backed-up? It may be a security issue.
        $twittertoken = new backup_nested_element ( 'token', array (), array (
                'token',
                'token_secret',
                'username'
        ) );
        $subplugin->add_child ( $subpluginwrapper );
        $subpluginwrapper->add_child ( $twittertoken );
        $subpluginwrapper->add_child ( $msocialstatuses );
        $msocialstatuses->add_child ( $msocialstatus );
        // Map tables...
        $twittertoken->set_source_table ( 'msocial_twitter_tokens', array (
                'msocial' => backup::VAR_ACTIVITYID
        ) );
        if ($userinfo) {
            $msocialstatus->set_source_table ( 'msocial_tweets', array (
                    'msocial' => backup::VAR_ACTIVITYID
            ) );
        }
        // Define id annotations.
        $msocialstatus->annotate_ids ( 'userid', 'userid' );
        return $subplugin;
    }
}
