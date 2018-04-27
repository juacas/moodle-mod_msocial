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
require_once('socialgraph.php');
require_once('vendor/autoload.php');


$onlystudents = false;
$context = context_module::instance($this->cm->id);
$usersstruct = msocial_get_users_by_type($contextcourse);
$users = $usersstruct->userrecords;
global $CFG;
$cm = $this->cm;
$shownativeids = has_capability('mod/msocial:manage', $context);
$duplicatededges = [];
$collapseedges = $filter->collapse;
$filter->set_users($usersstruct);
$interactions = social_interaction::load_interactions_filter($filter);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($this->msocial);
$socialgraph = new SocialMatrix();
foreach ($interactions as $interaction) {
    if (!isset($plugins[$interaction->source]) || $plugins[$interaction->source]->is_enabled() == false) {
        continue;
    }
    /** @var Edge $edge */
    $graphviztoattr = [];
    $graphvizfromattr = [];
    if ($interaction->fromid == null || !isset($users[$interaction->fromid])) {
        $from = "[$interaction->nativefromname]";
        $fromgroup = 1;
        if ($shownativeids) {
            $graphvizfromattr['graphviz.URL'] = "socialusers.php?action=selectmapuser&source=$interaction->source&" .
            "id=$cm->id&nativeid=$interaction->nativefrom&nativename=$interaction->nativefromname&redirect=$redirect";
        }
    } else {
        $from = msocial_get_visible_fullname($users[$interaction->fromid], $this->msocial);
        $userlinkfrom = (new moodle_url('/mod/msocial/socialusers.php',
                ['action' => 'showuser',
                                'user' => $interaction->fromid,
                                'id' => $cm->id,
                ]))->out(false);
        $graphvizfromattr['graphviz.URL'] = $userlinkfrom;
        $fromgroup = 0;
    }
    if ($interaction->toid == null || !isset($users[$interaction->toid])) {
        $to = "[$interaction->nativetoname]";
        if ($shownativeids) {
            $graphviztoattr['graphviz.URL'] = "socialusers.php?action=selectmapuser&source=$interaction->source&" .
            "id=$cm->id&nativeid=$interaction->nativeto&nativename=$interaction->nativetoname&redirect=$redirect";
        }
        $togroup = 1;
    } else {
        $to = msocial_get_visible_fullname($users[$interaction->toid], $this->msocial);
        $userlinkto = (new moodle_url('/mod/msocial/socialusers.php',
                ['action' => 'showuser',
                                'user' => $interaction->toid,
                                'id' => $cm->id,
                ]))->out(false);
        $graphviztoattr['graphviz.URL'] = $userlinkto;
        $togroup = 0;
    }
    if ($to == null) {
        $to = 'Community';
        $graphviztoattr['graphviz.shape'] = 'box';
        $togroup = 1;
    }
    $graphvizfromattr['graphviz.label'] = $from;
    $graphviztoattr['graphviz.label'] = $to;
    $type = $interaction->type;
    $source = $interaction->source;

    $edgelabel = $source;
    if (!$collapseedges) {
        $edgelabel .= ':' . $type;
    }
    $edgekey = $interaction->nativefrom . '-' . $interaction->nativeto . '-' . $edgelabel;
    if (isset($duplicatededges[$edgekey])) {
        $edge = $duplicatededges[$edgekey];
        $edge->setFlow($edge->getFlow() + 1);
        $fromvertex = $edge->getVertexStart();
        $tovertex = $edge->getVertexEnd();
    } else {
        list($fromvertex, $edge, $tovertex) = $socialgraph->register_interaction($interaction,
                                              ['graphviz.label' => $edgelabel], $graphvizfromattr, $graphviztoattr);
        if ($edge) {
            $duplicatededges[$edgekey] = $edge;
            $edge->setFlow(1);
        }
    }
//     if ($interaction->nativefrom == $interaction->nativeto && $edge) {
//         $duplicatededges[$edgekey] = $edge;
//     }
    if ($fromvertex) {
        $fromvertex->setGroup($fromgroup);
    }
    if ($tovertex) {
        $tovertex->setGroup($togroup);
    }
}
$dot = new Dot();
$graph = $socialgraph->get_graph();
$graph->getAttributeBag()->setAttribute('graphviz.graph.rankdir', 'LR');
$graph->getAttributeBag()->setAttribute('graphviz.graph.size', "10,10");
$dotsource = $dot->getOutput($graph);
$dotsource = str_replace('label = 0', 'label = "Course users"', $dotsource);
$dotsource = str_replace('label = 1', 'label = "External users"', $dotsource);
/* @var $OUTPUT \core_renderer */
echo '<div id="graph" width="100%" height="1000"></div>';
echo "\n";
echo $OUTPUT->container($dotsource, 'hidden', 'dot_src');

/** @var page_requirements_manager $reqs */
$reqs->js('/mod/msocial/view/graph/js/hammer.js', false);
$reqs->js('/mod/msocial/view/graph/js/configuregraphvizrequire.js', false);
global $CFG;
$reqs->js_call_amd('msocialview/graphviz', 'initview', ['#graph', '#dot_src']);

