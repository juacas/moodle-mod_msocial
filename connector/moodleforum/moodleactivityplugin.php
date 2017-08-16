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
/*
 * **************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
namespace mod_msocial\connector;

use mod_msocial\pki_info;
use moodleactivity\GraphNodes\GraphEdge;
use moodleactivity\moodleactivity as moodleactivity;
use msocial\msocial_plugin;
use mod_msocial\social_user;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once ($CFG->dirroot . '/mod/msocial/msocialconnectorplugin.php');

/** library class for social network moodleactivity plugin extending social plugin base class
 *
 * @package msocialconnector_moodleactivity
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
abstract class msocial_connector_moodleactivity extends msocial_connector_plugin {
    const CONFIG_ACTIVITIES = 'activities';
    const CONFIG_ACTIVITY_NAMES = 'activitynames';

    /**
     * @return true if the plugin is making searches in the social network */
    public function is_tracking() {
        return $this->is_enabled();
    }

    /** Get the instance settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void */
    public function get_settings(\MoodleQuickForm $mform) {
    }

    public function get_category() {
        return msocial_plugin::CAT_ANALYSIS;
    }

    /**
     * @global \core_renderer $OUTPUT
     * @global \moodle_database $DB */
    public function render_header() {
        global $OUTPUT, $USER;
        $this->remap_linked_activities(); // debug
        if ($this->is_enabled()) {
            $subtype = $this->get_subtype();
            $icon = $this->get_icon();
            $messages = [];
            $notifications = [];
            $icondecoration = \html_writer::img($icon->out(), $this->get_name() . ' icon.', ['height' => 16]) . ' ';
            $context = \context_module::instance($this->cm->id);
            $activities = $this->get_config(self::CONFIG_ACTIVITIES);
            if (has_capability('mod/msocial:manage', $context)) {
                $linktoselect = \html_writer::link(
                        new \moodle_url("/mod/msocial/connector/$subtype/activitychoice.php", ['id' => $this->cm->id]),
                        'Select forums.');
            } else {
                $linktoselect = '';
            }

            if ($activities) {
                $cminfo = get_fast_modinfo($this->cm->course);
                $linkforum = [];
                foreach (explode(',', $activities) as $actid) {
                    $activity = $cminfo->cms[$actid];
                    $forumurl = $activity->url;
                    $info = $activity->get_formatted_name();
                    $linkforum[] = \html_writer::link($forumurl, $info);
                }
                $messages[] = get_string('onlyasetofactivities', "msocialconnector_$subtype") . ' (' . implode(', ', $linkforum) .
                         ') ' . $linktoselect;
            } else {
                $messages[] = get_string('allactivities', "msocialconnector_$subtype") . ' ' . $linktoselect;
            }
            if (has_capability('mod/msocial:manage', $context)) {
                $messages[] = get_string('harvest', "msocialconnector_$subtype") . $OUTPUT->action_icon(
                        new \moodle_url('/mod/msocial/harvest.php', ['id' => $this->cm->id, 'subtype' => $subtype]),
                        new \pix_icon('a/refresh', get_string('harvest', "msocialconnector_$subtype")));
            }
            $this->notify($notifications, self::NOTIFY_WARNING);
            $this->notify($messages, self::NOTIFY_NORMAL);
        }
    }

    /** Place social-network user information or a link to connect.
     * Moodle internal users don't need to be detailed.
     *
     * @global object $USER
     * @global object $COURSE
     * @param object $user user record
     * @return string message with the linking info of the user */
    public function render_user_linking($user) {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\connector\msocial_connector_plugin::users_are_local() */
    public function users_are_local() {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\connector\msocial_connector_plugin::get_user_url() */
    public function get_user_url($user) {
        if (isset($user->id)) {
            $userid = $user->id;
        } else {
            $userid = (int) $user;
        }
        if ($userid) {
            $link = $this->get_social_user_url((object) ['userid' => $userid]);
        } else {
            $link = null;
        }
        return $link;
    }

    public function get_social_user_url(social_user $userid) {
        return new \moodle_url("/user/view.php", ['id' => $userid->userid]) . out();
    }

    public function get_interaction_url(social_interaction $interaction) {
        // /groups/1670848226578336/permalink/1670848496578309/?comment_id=1670848556578303
        $parts = explode('_', $interaction->uid);
        $modname = $this->get_mod_name();
        if (count($parts) == 2) { // TODO...
            $url = new \moodle_url("/mod/$modname/view.php", ['id' => $parts[0], 'post' => $parts[1]]);
        } else {
            $url = new \moodle_url("/mod/$modname/view.php", ['id' => $parts[0]]);
        }

        return $url;
    }
    /**
     *
     */
    abstract protected function get_mod_name();

    public function set_linked_activities($activities) {
        $this->set_config(self::CONFIG_ACTIVITIES, join(',', array_keys($activities)));
        $this->set_config(self::CONFIG_ACTIVITY_NAMES, json_encode($activities));
    }

    /** Try to redetect forums by id or by name is the ids are not valid (i.e.
     * after a isolated backup of the msocial module) */
    public function remap_linked_activities() {
        $actnames = json_decode($this->get_config(self::CONFIG_ACTIVITY_NAMES), true);
        $cmodinfo = get_fast_modinfo($this->msocial->course);
        $resolved = [];
        if ($actnames) {
            foreach ($actnames as $id => $name) {
                if (isset($cmodinfo->cms[$id]) && $cmodinfo->cms[$id]->modname == $this->get_mod_name()) {
                    $resolved[$id] = $name;
                } else {
                    // Search by name.
                    foreach ($cmodinfo->cms as $id => $cminfo) {
                        if ($cminfo->modname == $this->get_mod_name() && $cminfo->name == $name) {
                            $resolved[$id] = $name;
                        }
                    }
                }
            }
        }
        $this->set_linked_activities($resolved);
    }

    public function get_connection_token() {
        return '';
    }

    public function set_connection_token($token) {
    }

    public function unset_connection_token() {
    }
}