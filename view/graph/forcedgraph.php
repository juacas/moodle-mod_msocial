<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
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
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

$script  = <<< HTML
<script src="view/graph/js/d3/d3.v4.min.js"></script>
<script src="view/graph/js/d3/d3-selection-multi.v1.js"></script>
<script type="text/javascript" src="view/graph/js/viewforcedgraphlib.js"></script>
<!--

//-->
</script>
<style>

.links line {
/*   stroke: #999; */
  stroke-opacity: 0.6;
}

.nodes circle {
  stroke: #fff;
  stroke-width: 1.5px;
}
.node {}

.link {
/* stroke: #999;  */
stroke-opacity: .6; stroke-width: 1px; }
</style>
<svg id="graph" width="800" height="600"></svg>
<script>
HTML;

$cmid = $this->cm->id;
$msocial = $this->msocial;
$params = $filter->get_filter_params_url();
$params['include_community'] = false;
$params['id'] = $cmid;
$params['redirect'] = $redirect;
$jsonized = (new moodle_url('/mod/msocial/view/graph/jsonized.php', $params))->out(false);

echo $script;
echo "d3.json(\"$jsonized\", function(error, graph) { ";
echo "  if (error) throw error;";
echo "  init_forced_graph_view(graph);";
echo "});";
echo "</script>";