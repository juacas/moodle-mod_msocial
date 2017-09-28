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
namespace mod_msocial\view;

use mod_msocial\connector\social_interaction;
use mod_msocial\connector\msocial_connector_plugin;
use msocial\msocial_plugin;
use mod_msocial\plugininfo\msocialconnector;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/msocialviewplugin.php');
require_once($CFG->dirroot . '/mod/msocial/msocialconnectorplugin.php');

/** library class for view the network activity as a sequence diagram extending view plugin base
 * class
 *
 * @package msocialview_sequence
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_view_sequence extends msocial_view_plugin {

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialview_sequence');
    }

    /** Get the settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void */
    public function get_settings(\MoodleQuickForm $mform) {
    }

    /** The msocial has been deleted - cleanup subplugin
     *
     * @global moodle_database $DB
     * @return bool */
    public function delete_instance() {
        global $DB;
        $result = true;
        return $result;
    }

    public function get_subtype() {
        return 'sequence';
    }

    public function get_category() {
        return msocial_plugin::CAT_VISUALIZATION;
    }

    public function get_icon() {
        return new \moodle_url('/mod/msocial/view/sequence/pix/icon.svg');
    }

    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;
        $result = (object) ['messages' => []];
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see msocial_view_plugin::view_set_requirements() */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == 'sequence') {
            // Sequence view.
            $reqs->jquery();
            $reqs->js('/mod/msocial/view/sequence/js/svg-pan-zoom.js', true);
            $reqs->js('/mod/msocial/view/sequence/js/underscore-min.js', true);
            $reqs->js('/mod/msocial/view/sequence/js/snap.svg-min.js', true);
            $reqs->js('/mod/msocial/view/sequence/js/webfont.js', true);
            $reqs->js('/mod/msocial/view/sequence/js/sequence-diagram-min.js', true);
            $reqs->js('/mod/msocial/view/sequence/js/viewlib.js', true);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @global \stdClass $USER
     * @see msocial_view_plugin::render_view() */
    public function render_view($renderer, $reqs, $filter) {
        global $USER, $PAGE;

        $contextmodule = \context_module::instance($this->cm->id);
        $contextcourse = \context_course::instance($this->cm->course);

        $diagram = '';
        $plugins = msocialconnector::get_enabled_connector_plugins($this->msocial);
        echo $filter->render_form($PAGE->url, $plugins);
        $usersstruct = msocial_get_users_by_type($contextcourse);
        list($students, $nonstudents, $activeids, $userrecords) = array_values($usersstruct);
        $filter->set_users($usersstruct);
        $interactions = social_interaction::load_interactions_filter($filter);
        /** @var social_interaction $interaction */
        foreach ($interactions as $interaction) {
            $subtype = $interaction->source;
            if (!isset($plugins[$subtype])) {
                continue;
            }
            if ($interaction->type == social_interaction::MENTION || $interaction->type == social_interaction::REACTION) {
                $arrow = '-->';
            } else {
                $arrow = '->';
            }
            $from = isset($userrecords[$interaction->fromid]) ?
                                fullname($userrecords[$interaction->fromid]) :
                                '[' . ($interaction->nativefromname ? $interaction->nativefromname : $interaction->nativefrom) . ']';
            if ($from == null) {
                $from = '[COMMUNITY]';
            }
            if ($interaction->type == social_interaction::POST) {
                $to = $from; // Represents as a self-message.
            } else {
                $to = isset($userrecords[$interaction->toid]) ?
                        fullname($userrecords[$interaction->toid]) :
                        '[' . ($interaction->nativetoname ? $interaction->nativetoname : $interaction->nativeto) . ']';
            }
            if ($to == null) {
                $to = '[COMMUNITY]';
            }
            $diagram .= $from . $arrow . $to . ":" . $interaction->source . ':' . $interaction->nativetype . "\r\n";
        }
        echo '<div id="diagram" class="diagram" style="max-witdh=1000px">' . $diagram . '</div>';
        $reqs->js_init_call('initview', []);
    }
}
