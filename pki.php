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

/** library class for msocial social pki structure
 *
 * @package msocialconnector
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial;

use msocial\msocial_plugin;
use core_calendar\local\event\proxies\std_proxy;

defined('MOODLE_INTERNAL') || die();

class pki {

    /** Moodle userid this pki refers to.
     * @var int */
    public $user;
    public $msocial;
    public $historical = false;
    public $timestamp;
    protected static $_base_fields = ['id', 'name', 'timestamp', 'historical','msocial','user'];

    public function __construct($userid, $msocialid) {
        $this->user = $userid;
        $this->msocial = $msocialid;
        $this->timestamp = time();
    }

    /**
     * @param unknown $user
     * @param unknown $stat
     * @param msocial_plugin $msocialplugin
     * @param pki $pki existent pki. For chaining calls. Assumes user and msocialid are coherent.
     * @return \mod_msocial\connector\pki_info[] */
    public static function from_stat($user, $stat, $stataggregated, $msocialplugin, $pki = null) {
        $pki = $pki == null ? new pki($user, $msocialplugin->msocial->id) : $pki;
        foreach ($stat as $propname => $value) {
            $pki->{$propname} = $value;
        }
        foreach ($stataggregated as $propname => $value) {
            $pki->{$propname} = $value;
        }

        return $pki;
    }

    /**
     * @return true if the pki fields are 0. fields 'id', 'name', 'msocial', 'timestamp',
     *         'historical' and starting with max_ are ignored. */
    public function seems_inactive() {
        foreach ($this as $prop => $value) {
            if ($value !== 0 && array_search($prop, self::$_base_fields) !== false && strpos($prop, 'max_') !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return only pki-related fields
     * @return string[] property-values array */
    public function as_array() {
        $result = [];
        foreach ($this as $prop => $value) {
            if (array_search($prop, self::$_base_fields) === false) {
                $result[$prop] = $value;
            }
        }
        return $result;
    }

    /**
     * Load pki-fields from the database record.
     * @param \stdClass $record database record with pki data.
     * @return \mod_msocial\pki */
    public static function from_record($record) {
        $pki = new pki($record->user, $record->msocial);
        foreach ($record as $prop => $value) {
            if (array_search($prop, self::$_base_fields) === false) {
                $pki->{$prop} = $value;
            }
        }
        return $pki;
    }
}

class pki_info {
    const PKI_INDIVIDUAL = true;
    const PKI_AGREGATED = false;
    /** This pki is calculated by dedicated code of a plugin, not from recorded interactions.
     * @var string */
    const PKI_CUSTOM = 'custom_pki';
    /** Measures a person indicator or not.
     *
     * @var boolean */
    public $individual = true;

    /** Name of the PKI.
     *
     * @var string */
    public $name;

    /** Description of the PKI.
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
     * @param string $interaction_type
     * @param string $interaction_nativetype_query
     * @param string $interaction_source */
    public function __construct($name, $description = null, $individual = self::PKI_INDIVIDUAL, $interaction_type = null, $interaction_nativetype_query = '*',
            $interaction_source = 'fromid') {
        $this->name = $name;
        $this->value = $description;
        $this->individual = $individual;
        $this->interaction_type = $interaction_type;
        $this->interaction_source = $interaction_source;
        $this->interaction_nativetype_query = $interaction_nativetype_query;
    }
}