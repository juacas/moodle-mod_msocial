// This file is part of Moodle - http:// moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify.
// it under the terms of the GNU General Public License as published by.
// the Free Software Foundation, either version 3 of the License, or.
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,.
// but WITHOUT ANY WARRANTY; without even the implied warranty of.
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the.
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http:// www.gnu.org/licenses/>.
/**
 * @module interaction matrix diagram
 * @package mod_msocial/view/matrix
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

function init_matrix_view(Y, cmid, params, redirect) {
	var params = $.param(params);
	d3.json("view/graph/jsonized.php?include_community=true&id=" + cmid + "&" + params + "&redirect=" + redirect,
			function(interactions) {
				var matrix = [], nodes = interactions.nodes, n = nodes.length;
				// Configure SVG canvas.
				var margin = {
					top : 200,
					right : 0,
					bottom : 10,
					left : 200
				}, width = Math.min(1000, 32 * n), height = Math.min(1000,32 * n);

				var x = d3.scale.ordinal().rangeBands([ 0, width ]), z = d3.scale
						.linear().domain([ 0, 4 ]).clamp(true), c = d3.scale
						.category10().domain(d3.range(10));
				var svg = d3.select("#diagram").append("svg").attr(
						"width", width + margin.left + margin.right)
						.attr("height",
								height + margin.top + margin.bottom)
						// .style("margin-left", 100 + "px")
						.append("g").attr(
								"transform",
								"translate(" + margin.left +
								","	+ margin.top + ")");

				// Compute index per node.
				nodes.forEach(function(node, i) {
					node.index = i;
					node.count = 0;
					matrix[i] = d3.range(n).map(function(j) {
						return {
							x : j,
							y : i,
							z : 0
						};
					});
				});

				// Convert links to matrix; count occurrences.
				interactions.links.forEach(function(link) {
					matrix[link.source][link.target].z += link.value;
					matrix[link.target][link.source].z += link.value;
					matrix[link.source][link.source].z += link.value;
					matrix[link.target][link.target].z += link.value;
					nodes[link.source].count += link.value;
					nodes[link.target].count += link.value;
				});

				// Precompute the orders.
				var orders = {
					name : d3.range(n).sort(
							function(a, b) {
								return d3.ascending(nodes[a].name,
										nodes[b].name);
							}),
					count : d3.range(n).sort(function(a, b) {
						return nodes[b].count - nodes[a].count;
					}),
					group : d3.range(n).sort(function(a, b) {
						return nodes[b].group - nodes[a].group;
					})
				};

				// The default sort order.
				x.domain(orders.name);

				svg.append("rect").attr("class", "background").attr(
						"width", width).attr("height", height);

				var row = svg.selectAll(".row").data(matrix).enter()
						.append("g").attr("class", "row").attr(
								"transform", function(d, i) {
									return "translate(0," + x(i) + ")";
								}).each(row);

				row.append("line").attr("x2", width);

				row.append("a").attr('xlink:href', function(d, i) {
					return nodes[i].userlink;
				}).append("text").attr("x", -6).attr("y",
						x.rangeBand() / 2).attr("dy", ".32em").attr(
						"text-anchor", "end").text(function(d, i) {
					return nodes[i].name;
				});

				var column = svg.selectAll(".column").data(matrix)
						.enter().append("g").attr("class", "column")
						.attr(
								"transform",
								function(d, i) {
									return "translate(" + x(i) + ")rotate(-90)";
								});

				column.append("line").attr("x1", -width);

				column.append("a").attr('xlink:href', function(d, i) {
					return nodes[i].userlink;
				}).append("text").attr("x", 6).attr("y",
						x.rangeBand() / 2).attr("dy", ".32em").attr(
						"text-anchor", "start").text(function(d, i) {
					return nodes[i].name;
				});

				function row(row) {
					var cell = d3
							.select(this)
							.selectAll(".cell")
							.data(row.filter(function(d) {
								return d.z;
							}))
							.enter()
							.append("rect")
							.attr("class", "cell")
							.attr("x", function(d) {
								return x(d.x);
							})
							.attr("width", x.rangeBand())
							.attr("height", x.rangeBand())
							.style("fill-opacity", function(d) {
								return z(d.z);
							})
							.style(
									"fill",
									function(d) {
										return nodes[d.x].group == nodes[d.y].group ? c(nodes[d.x].group)
												: null;
									}).on("mouseover", mouseover).on("mouseout", mouseout);
				}

				function mouseover(p) {
					d3.selectAll(".row text").classed("active",
							function(d, i) {
								return i == p.y;
							});
					d3.selectAll(".column text").classed("active",
							function(d, i) {
								return i == p.x;
							});
				}

				function mouseout() {
					d3.selectAll("text").classed("active", false);
				}

				d3.select("#order").on("change", function() {
					clearTimeout(timeout);
					order(this.value);
				});

				function order(value) {
					x.domain(orders[value]);

					var t = svg.transition().duration(2500);

					t.selectAll(".row").delay(function(d, i) {
						return x(i) * 4;
					}).attr("transform", function(d, i) {
						return "translate(0," + x(i) + ")";
					}).selectAll(".cell").delay(function(d) {
						return x(d.x) * 4;
					}).attr("x", function(d) {
						return x(d.x);
					});

					t.selectAll(".column").delay(function(d, i) {
						return x(i) * 4;
					}).attr("transform", function(d, i) {
						return "translate(" + x(i) + ")rotate(-90)";
					});
				}

				var timeout = setTimeout(function() {
					order("group");
					d3.select("#order").property("selectedIndex", 2)
							.node().focus();
				}, 5000);
			});

} // End of function init.
