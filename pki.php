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

/**
 * library class for tcount social pki structure
 *
 * @package tcountsocial
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_tcount\social;

defined('MOODLE_INTERNAL') || die();


class pki {

    const PKI_INDIVIDUAL = 0;

    const PKI_AGREGATED = 1;

    /**
     * Measures a person indicator or not.
     *
     * @var boolean
     */
    public $individual = true;

    /**
     * Name of the PKI.
     *
     * @var string
     */
    public $name;

    /**
     * Value fo the PKI.
     *
     * @var variant
     */
    public $value;

    function __construct($name, $value = null, $individual = true) {
        $this->name = $name;
        $this->value = $value;
        $this->individual = $individual;
    }
}