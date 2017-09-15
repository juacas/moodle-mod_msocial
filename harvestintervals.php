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

/** library class for msocial social_interaction
 *
 * @package msocialconnector
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial\connector;

defined('MOODLE_INTERNAL') || die();

class harvest_intervals {
    /**
     * @var int $preferredinterval preferred interval between automatic harvesting, expressed in seconds.
     * Usually in the range of days.
     */
    public $preferredinterval;
    /**
     * @var int $ratelimit API imposed rate limit to apply in automatic harvesting, expressed in API calls.
     */
    public $ratelimit;
    /**
     * @var int $maxinterval maximum time that the API will return items for. i.e. windowed mode.
     */
    public $maxinterval;
    public $ratecontrolpolicy;
    /**
     *
     * @param int $preferredinterval preferred interval between automatic harvesting, expressed in seconds.
     *                                Usually in the range of days.
     * @param int $ratelimit API imposed rate limit to apply in automatic harvesting, expressed in API calls per our.
     * @param int $maxinterval maximum time that the API will return items for. i.e. windowed mode.
     * @param unknown $ratecontrolpolicy
     */
    public function __construct ($preferredinterval, $ratelimit, $maxinterval, $ratecontrolpolicy) {
        $this->preferredinterval = $preferredinterval;
        $this->ratelimit = $ratelimit;
        $this->ratecontrolpolicy = $ratecontrolpolicy;
        $this->maxinterval = $maxinterval;

    }
}