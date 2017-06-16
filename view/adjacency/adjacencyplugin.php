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
namespace mod_tcount\view;

use mod_tcount\plugininfo\tcountsocial;
use tcount\tcount_plugin;
use mod_tcount\social\social_interaction;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/tcount/tcountviewplugin.php');

/**
 * library class for view the network activity as a sequence diagram extending view plugin base class
 *
 * @package tcountview_adjacency
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tcount_view_adjacency extends tcount_view_plugin {

    /**
     * Get the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tcountview_adjacency');
    }

    /**
     * Allows the plugin to update the defaultvalues passed in to
     * the settings form (needed to set up draft areas for editor
     * and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $defaultvalues['tcountview_adjacency_enabled'] = $this->get_config('enabled');
        return;
    }

    /**
     * Get the settings for the plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(\MoodleQuickForm $mform) {
    }

    /**
     * Save the settings for table plugin
     *
     * @param \stdClass $data
     * @return bool
     */
    public function save_settings(\stdClass $data) {
        if (isset($data->tcountview_adjacency_enabled)) {
            $this->set_config('enabled', $data->tcountview_adjacency_enabled);
        }
        return true;
    }

    /**
     * The tcount has been deleted - cleanup subplugin
     *
     * @global moodle_database $DB
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $result = true;
        return $result;
    }

    public function get_subtype() {
        return 'adjacency';
    }
    public function get_category(){
        return tcount_plugin::CAT_VISUALIZATION;
    }
    public function get_icon() {
        return new \moodle_url('/mod/tcount/view/adjacency/pix/icon.svg');
    }

    public function get_pki_list() {
        $pkis = [];
        return $pkis;
    }

    /**
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message
     */
    public function harvest() {
        global $DB;
        $result = (object) ['messages' => []];
        return $result;
    }
    /**
     *
     * {@inheritdoc}
     *
     * @see tcount_view_plugin::view_set_requirements()
     */
    public function render_header_requirements($reqs, $viewparam) {
        if ($viewparam == $this->get_subtype()) {
            // adjacency matrix view.
            $reqs->js('/mod/tcount/view/adjacency/js/viewlib.js');   
        }
    }
    /**
     *
     * {@inheritdoc}
     *
     * @global \stdClass $USER
     * @see tcount_view_plugin::render_view()
     */
    public function render_view($renderer, $reqs) {
        global $USER;
 
        $contextmodule = \context_module::instance($this->cm->id);
        $contextcourse = \context_course::instance($this->cm->course);
        echo '<script src="//d3js.org/d3.v2.min.js" charset="utf-8"></script>';
        echo '<style>.background {
  fill: #eee;
}

line {
  stroke: #fff;
}

text.active {
  fill: red;
}
</style>';
       echo '<aside style="margin-top:80px;">
<p>Order: <select id="order">
  <option value="name">by Name</option>
  <option value="count">by Frequency</option>
  <option value="group">student or external user </option>
</select>';
        echo '<div id="diagram" class="diagram" style="max-witdh=1000px"></div>';
        $reqs->js_init_call('initview',[$this->cm->id], false);
        
        
        
        echo '<script src="https://d3js.org/d3.v4.min.js"></script>';
        
        echo '<style>

.links line {
  stroke: #999;
  stroke-opacity: 0.6;
}

.nodes circle {
  stroke: #fff;
  stroke-width: 1.5px;
}

</style>
<svg id="graph" width="960" height="600"></svg>
<script>

var svg = d3.select("#graph"),
    width = +svg.attr("width"),
    height = +svg.attr("height");

var color = d3.scaleOrdinal(d3.schemeCategory20);

var simulation = d3.forceSimulation()
    .force("link", d3.forceLink().id(function(d) { return d.id; }))
    .force("charge", d3.forceManyBody())
    .force("center", d3.forceCenter(width / 2, height / 2));

d3.json("view/adjacency/jsonized.php?id='.$this->cm->id.'", function(error, graph) {
  if (error) throw error;

  var link = svg.append("g")
      .attr("class", "links")
    .selectAll("line")
    .data(graph.links)
    .enter().append("line")
      .attr("stroke-width", function(d) { return Math.sqrt(d.value); });

  var node = svg.append("g")
      .attr("class", "nodes")
    .selectAll("circle")
    .data(graph.nodes)
    .enter().append("circle")
      .attr("r", 5)
      .attr("fill", function(d) { return color(d.group); })
      .call(d3.drag()
          .on("start", dragstarted)
          .on("drag", dragged)
          .on("end", dragended));

  node.append("title")
      .text(function(d) { return d.id; });

  simulation
      .nodes(graph.nodes)
      .on("tick", ticked);

  simulation.force("link")
      .links(graph.links);

  function ticked() {
    link
        .attr("x1", function(d) { return d.source.x; })
        .attr("y1", function(d) { return d.source.y; })
        .attr("x2", function(d) { return d.target.x; })
        .attr("y2", function(d) { return d.target.y; });

    node
        .attr("cx", function(d) { return d.x; })
        .attr("cy", function(d) { return d.y; });
  }
});

function dragstarted(d) {
  if (!d3.event.active) simulation.alphaTarget(0.3).restart();
  d.fx = d.x;
  d.fy = d.y;
}

function dragged(d) {
  d.fx = d3.event.x;
  d.fy = d3.event.y;
}

function dragended(d) {
  if (!d3.event.active) simulation.alphaTarget(0);
  d.fx = null;
  d.fy = null;
}

</script>';
    }
}
