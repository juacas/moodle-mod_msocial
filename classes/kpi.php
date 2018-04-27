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

/** library class for msocial social kpi structure
 *
 * @package msocialconnector
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial;

defined('MOODLE_INTERNAL') || die();

class kpi {

    /** Moodle userid this kpi refers to.
     * @var int */
    public $userid;
    public $msocial;
    public $historical = false;
    public $timestamp;
    protected static $basefields = ['id', 'name', 'timestamp', 'historical', 'msocial', 'userid'];

    public function __construct($userid, $msocialid, array $kpiinfos = []) {
        $this->userid = $userid;
        $this->msocial = $msocialid;
        $this->timestamp = time();
        // Reset to 0 to avoid nulls.
        foreach ($kpiinfos as $kpiinforeset) {
            $this->{$kpiinforeset->name} = 0;
        }
    }

    /**
     * @return true if the kpi fields are 0. fields 'id', 'name', 'msocial', 'timestamp',
     *         'historical' and starting with max_ are ignored. */
    public function seems_inactive() {
        foreach ($this as $prop => $value) {
            $isbasefield = array_search($prop, self::$basefields) !== false;
            $ismaxfield = strpos($prop, 'max_') === 0;
            if (!$isbasefield &&
                !$ismaxfield &&
                $value ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return only kpi-related fields
     * @return string[] property-values array */
    public function as_array() {
        $result = [];
        foreach ($this as $prop => $value) {
            if (array_search($prop, self::$basefields) === false) {
                $result[$prop] = $value;
            }
        }
        return $result;
    }

    /**
     * Load kpi-fields from the database record.
     * @param \stdClass $record database record with kpi data.
     * @return \mod_msocial\kpi */
    public static function from_record($record) {
        $kpi = new kpi($record->userid, $record->msocial);
        foreach ($record as $prop => $value) {
            if (array_search($prop, self::$basefields) === false) {
                $kpi->{$prop} = $value;
            }
        }
        return $kpi;
    }
}

class kpi_info {
    const KPI_INDIVIDUAL = true;
    const KPI_AGREGATED = false;
    /** This kpi is calculated by dedicated code of a plugin, not from recorded interactions.
     * @var string */
    const KPI_CUSTOM = 'custom_kpi';
    /**
     * This kpi is calculated by summarizing interaction table
     * @var string
     */
    const KPI_CALCULATED = 'stats_kpi';
    /** Measures a person indicator or not.
     *
     * @var boolean */
    public $individual = true;

    /** Name of the Key Performance Indicator (KPI).
     *
     * @var string */
    public $name;

    /** Description of the Key Performance Indicator (KPI).
     *
     * @var variant */
    public $description;

    /** Interaction Type for aggregation.
     * @var string */
    public $interaction_type;

    /** Query for native types.
     * I.e. "nativetype = 'LIKE' OR nativetype = 'HAHA'"
     * @var string */
    public $interaction_nativetype_query;

    /** Source of the interactions aggregated.
     * Groups by this field.
     * @var string */
    public $interaction_source;

    /**
     * @param string $name
     * @param string $description
     * @param string $individual
     * @param string $interactiontype
     * @param string $interactionnativetypequery
     * @param string $interactionsource */
    public function __construct($name, $description = null, $individual = self::KPI_INDIVIDUAL, $generated = self::KPI_CALCULATED,
                                $interactiontype = null, $interactionnativetypequery = '*', $interactionsource = 'fromid') {
        $this->name = $name;
        $this->description = $description;
        $this->individual = $individual;
        $this->generated = $generated;
        $this->interaction_type = $interactiontype;
        $this->interaction_source = $interactionsource;
        $this->interaction_nativetype_query = $interactionnativetypequery;
    }
}