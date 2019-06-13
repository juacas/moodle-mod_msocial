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

/** library class for msocial harvester class
 *
 * @package msocialconnector_harvester
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial;

use mod_msocial\connector\social_interaction;

global $CFG;
require_once($CFG->dirroot . '/mod/msocial/classes/kpi.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialinteraction.php');
require_once($CFG->dirroot . '/mod/msocial/classes/filterinteractions.php');

interface msocial_harvestplugin
{
    /**
     * Collect information and calculate fresh Key Performance Indicators (KPIs) if supported.
     * @return \stdClass messages generated, interactions, pkis calculated
     */
    public function harvest();
    public function calculate_kpis(users_struct $users, $kpis = []);
    /**
     * Apply the filtering condition to this interaction.
     * @param social_interaction $interaction interaction to check.
     * @param social_interaction[] Other interactions for check relations. indexed by uuid.
     */
    public function check_condition(social_interaction $interaction, array $otherinteractions = null);
}

