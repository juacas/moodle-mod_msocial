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

/** library class for msocial plugins base class
 *
 * @package msocialconnector_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once('plugininfo/msocialbase.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialuser.php');
require_once($CFG->dirroot . '/mod/msocial/classes/kpi.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialinteraction.php');
require_once($CFG->dirroot . '/mod/msocial/classes/filterinteractions.php');
require_once($CFG->dirroot . '/mod/msocial/classes/msocialharvestplugin.php');

use mod_msocial\connector\msocial_connector_plugin;
use mod_msocial\connector\social_interaction;
use mod_msocial\plugininfo\msocialbase;

class harvest_controller
{
    public $msocial;
    /** Constructor f
     *
     * @param \stdClass $msocial
     */
    public final function __construct($msocial) {
        $this->msocial = $msocial;
    }
    /** Executes the harvest procedures of one or all plugins in a msocial instance.
     * First connector plugins, then view plugins.
     * @param \stdClass $msocial module instance
     * @param string $subtype name of the only subplugin to harvest
     */
    public function execute_harvests($subtype = null) {
        $msocial = $this->msocial;
        $enabledplugins = msocialbase::get_enabled_plugins_all_types($msocial);
        if ($subtype) {
            $enabledplugins = [$subtype => $enabledplugins[$subtype]];
        }

        /** @var msocial_plugin $plugin */
        foreach ($enabledplugins as $type => $plugin) {
            try {
                echo ("\nProcessing plugin: $type");

                if ($plugin->is_tracking()) {
                    $result = $plugin->harvest();
                    // Process Interactions and PKIs
                    $result = $this->post_harvest($result, $plugin);
                    if (isset($result->interactions)) {
                        $plugin->store_interactions($result->interactions);
                    }
                    if (isset($result->kpis)) {
                        $plugin->store_kpis($result->kpis, true);
                    }
                    $plugin->set_config(msocial_connector_plugin::LAST_HARVEST_TIME, time());

                    if (isset($result->errors)) {
                        $plugin->notify(array_map(function ($item) {
                            if (isset($item->message)) {
                                return $item->message;
                            } else {
                                return '';
                            }
                        }, $result->errors), msocial_plugin::NOTIFY_ERROR);
                    }
                    $plugin->notify($result->messages, msocial_plugin::NOTIFY_NORMAL);
                    // TODO: Process bad tokens and send advices.
                } else {
                    echo "\n<p>Plugin $type is not tracking. (Disabled, out of time window or some critical configuration missing.)</p>";
                }
            } catch (\Exception $e) {
                mtrace("\n<li>Error processing msocial: $msocial->name. Skipping. " . $e->getMessage() .
                    "\n" . $e->getTraceAsString());
            }
        }
    }
    /**
     * Common tasks after harvesting.
     * Generate Key Performance Indicators (KPIs), store KPIs, mark harvest time, report harvest messages.
     * @param string[] $result
     * @return string[] $result
     */
    protected function post_harvest($result, $plugin) {
        // TODO: define if processsing is needed or not.
	if (! isset($result->kpis)) {
		$result->kpis = [];
	}
        $contextcourse = \context_course::instance($this->msocial->course);
        $usersstruct = msocial_get_users_by_type($contextcourse);
        $result->kpis = $plugin->calculate_kpis($usersstruct, $result->kpis);

        // Message for user: summary.
        $processedinteractions = isset($result->interactions) ? $result->interactions : [];
        $studentinteractions = array_filter($processedinteractions,
            function (social_interaction $interaction) {
                return isset($interaction->fromid) &&
                msocial_time_is_between($interaction->timestamp,
                    (int) $this->msocial->startdate,
                    (int) $this->msocial->enddate);
            });
        $intimeinteractions = array_filter($processedinteractions,
            function (social_interaction $interaction) {
                return msocial_time_is_between($interaction->timestamp,
                    $this->msocial->startdate, $this->msocial->enddate);
            });
        $subtype = $plugin->get_subtype();
        $logmessage = "For module msocial\\connector\\$subtype: \"" . $this->msocial->name .
        "\" (id=" . $this->msocial->id . ") in course (id=" .
        $plugin->msocial->course . ")  Found " . count($processedinteractions) .
        " events. In time period: " . count($intimeinteractions) . ". Students' events: " . count($studentinteractions);
        $result->messages[] = $logmessage;

        return $result;
    }
    /**
     * User's token broken. Maybe expired. Ask the user to relogin.
     * @param \stdClass $socialuser record from msocial_map_user table.
     * @param string $msg
     */
    protected function notify_user_token( $socialuser, $msg) {
        // TODO: Notify users with messagging.
        $this->notify([$msg], self::NOTIFY_WARNING);
    }
}

