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
 * library class for tcount viewplugins base class
 *
 * @package tcountview_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_tcount\view;

use tcount\tcount_plugin;

defined('MOODLE_INTERNAL') || die();
require_once ($CFG->dirroot . '/mod/tcount/tcountplugin.php');
require_once ($CFG->dirroot . '/mod/tcount/social/socialinteraction.php');


abstract class tcount_view_plugin extends tcount_plugin {

    /**
     * Constructor for the abstract plugin type class
     *
     * @param tcount $tcount
     * @param string $type
     */
    public final function __construct($tcount) {
        parent::__construct($tcount, 'tcountview');
    }

    /**
     *
     * @return moodle_url url of the icon for this service
     */
    public abstract function get_icon();

    /**
     * Collect information and calculate fresh PKIs if supported.
     *
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        $result = (object) ['messages' => []];
        return $result;
    }

    /**
     * Render the content of the view
     *
     * @param page_requirements_manager $reqs
     * @param core_renderer $renderer
     */
    public abstract function render_view($renderer, $reqs);

    /**
     * Sets the page requirements: javascript, css, etc.
     *
     * @param page_requirements_manager $reqs
     * @param string $viewparam
     */
    public abstract function render_header_requirements($reqs, $viewparam);

    public function calculate_stats($users = null) {
        $stats = new \stdClass();
        $stats->maximums = new \stdClass();
        $stats->users = [];
        return $stats;
    }

    public function calculate_pkis($users, $pkis = []) {
        return [];
    }
    public function is_tracking() {
        return $this->is_enabled();
    }
    public function get_pki_list() {
        $pkis = [];
        return $pkis;
    }
}
