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

/**
 * library class for msocial viewplugins base class
 *
 * @package msocialview_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_msocial\view;

use core_renderer;
use moodle_url;
use page_requirements_manager;
use mod_msocial\filter_interactions;
use mod_msocial\msocial_harvestplugin;
use mod_msocial\msocial_plugin;
use mod_msocial\users_struct;
use mod_msocial;

defined('MOODLE_INTERNAL') || die();
require_once('msocialplugin.php');
require_once('socialinteraction.php');


abstract class msocial_view_plugin extends msocial_plugin {

    /**
     * Constructor for the abstract plugin type class
     *
     * @param \stdClass $msocial
     * @param string $type
     */
    public final function __construct($msocial) {
        parent::__construct($msocial, 'msocialview');
    }

    /**
     *
     * @return moodle_url url of the icon for this service
     */
    public abstract function get_icon();

   
    public function get_harvest_plugin() {
        return null;
    }
    /**
     * Render the content of the view
     *
     * @param page_requirements_manager $reqs
     * @param core_renderer $renderer
     * @param filter_interactions $filter
     */
    public abstract function render_view($renderer, $reqs, $filter);

    /**
     * Sets the page requirements: javascript, css, etc.
     *
     * @param page_requirements_manager $reqs
     * @param string $viewparam
     */
    public abstract function render_header_requirements($reqs, $viewparam);
    /**
     *
     * {@inheritDoc}
     * @see \mod_msocial\msocial_plugin::calculate_kpis()
     */
    public function calculate_kpis(users_struct $users, $kpis = []) {
        return $kpis;
    }
    /**
     * {@inheritDoc}
     * @see \mod_msocial\msocial_plugin::preferred_harvest_intervals()
     */
    public function preferred_harvest_intervals() {
        return new mod_msocial\connector\harvest_intervals(15 * 60, 5000, 1 * 3600, 0);
    }

    /**
     *
     * {@inheritDoc}
     * @see \mod_msocial\msocial_plugin::get_kpi_list()
     */
    public function get_kpi_list() {
        $kpis = [];
        return $kpis;
    }
    /**
     *
     * {@inheritDoc}
     * @see \mod_msocial\msocial_plugin::get_sort_order()
     */
    public function get_sort_order() {
        return 10;
    }
}
