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
use mod_msocial\connector\social_interaction;
use Graphp\GraphViz\Dot;


$onlystudents = false;
$context = context_module::instance($this->cm->id);
$usersstruct = msocial_get_users_by_type($contextcourse);
list($students, $nonstudents, $active, $users) = array_values($usersstruct);
global $CFG;
$cm = $this->cm;
$shownativeids = has_capability('mod/msocial:manage', $context);
$duplicatededges = [];
$filter->set_users($usersstruct);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($this->msocial);

/* @var $OUTPUT \core_renderer */
echo '<div id="graph" style="width:100%;height:1000px; border: 1px solid lightgray;"></div>';
echo "\n";

/** @var page_requirements_manager $reqs */
$reqs->js('/mod/msocial/view/graph/js/hammer.js', false);
$reqs->js('/mod/msocial/view/graph/js/configuregraphvisrequire.js', false);
global $CFG;
$reqs->js_call_amd('msocialview/graphvis', 'initview', ['graph', $cm->id, $filter->get_filter_params_url(), $redirect]);

