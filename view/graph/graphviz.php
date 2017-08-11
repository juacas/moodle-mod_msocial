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
require_once ('socialgraph.php');
require_once ('vendor/autoload.php');
$interactions = social_interaction::load_interactions($this->msocial->id);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($this->msocial);
$socialgraph = new SocialMatrix();
$context = context_module::instance($this->cm->id);
list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
global $CFG;
$cm = $this->cm;
$shownativeids = has_capability('mod/msocial:manage', $context);
foreach ($interactions as $interaction) {
    if (!isset($plugins[$interaction->source]) || $plugins[$interaction->source]->is_enabled() == false) {
        continue;
    }
    $graphviztoattr = [];
    $graphvizfromattr = [];
    if ($interaction->fromid == null) {
        $from = "[$interaction->nativefromname]";
        $fromgroup = 1;
        if ($shownativeids) {
            $graphvizfromattr['graphviz.URL'] = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&nativeid=$interaction->nativefrom&nativename=$interaction->nativefromname";
        }
    } else {
        $from = fullname($users[$interaction->fromid]);
        $graphvizfromattr['graphviz.URL'] = $CFG->wwwroot . "/user/view.php?id=$interaction->fromid";
        $fromgroup = 0;
    }
    if ($interaction->toid == null) {
        $to = "[$interaction->nativetoname]";
        if ($shownativeids) {
            $graphviztoattr['graphviz.URL'] = "socialusers.php?action=selectmapuser&source=$interaction->source&id=$cm->id&nativeid=$interaction->nativeto&nativename=$interaction->nativetoname";
        }
        $togroup = 1;
    } else {
        $to = fullname($users[$interaction->toid]);
        $graphviztoattr['graphviz.URL'] = $CFG->wwwroot . "/user/view.php?id=$interaction->toid";
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
    list($fromvertex, $edge, $tovertex) = $socialgraph->register_interaction($interaction, ['graphviz.label' => $type],
            $graphvizfromattr, $graphviztoattr);
    $fromvertex->setGroup($fromgroup);
    $tovertex->setGroup($togroup);
    $edge->setFlow(1 / $edge->getWeight());
    $edge->setCapacity(1);
}
$dot = new Dot();
$graph = $socialgraph->get_graph();
$graph->getAttributeBag()->setAttribute('graphviz.graph.rankdir', 'LR');
$graph->getAttributeBag()->setAttribute('graphviz.graph.bgcolor', "#aaaaaa5f");
$graph->getAttributeBag()->setAttribute('graphviz.graph.size', "10,10");
$dotsource = $dot->getOutput($graph);
$dotsource = str_replace('label = 0', 'label = "Course users"', $dotsource);
$dotsource = str_replace('label = 1', 'label = "External users"', $dotsource);
/** @var page_requirements_manager $reqs */
$reqs->js('/mod/msocial/view/graph/js/configuregraphvizrequire.js', false);
global $CFG;
$reqs->js_call_amd('msocialview/graphviz', 'initview', ['#graph', '#dot_src']);
/* @var $OUTPUT \core_renderer */
echo $OUTPUT->container('', '', 'graph');
echo $OUTPUT->container($dotsource, 'hidden', 'dot_src');


