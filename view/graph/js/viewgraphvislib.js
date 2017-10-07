/**
 * @module graphviz_social
 * @package mod_msocial/view/graphviz
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

define('msocialview/graphvis', [ 'jquery', 'vis','svg-pan-zoom', 'hammer', 'saveSvgAsPng' ],
		function($, vis, svgPanZoom, Hammer, saveSvgAsPng) {

	var init = {
		initview : function(container, cmid, params, redirect) {
		var params = $.param(params);
		$.getJSON("view/graph/jsonized.php?include_community=true&id=" + cmid + "&" + params + "&redirect=" + redirect,
		function(jsondata){
			var nodes = [];
			var edges = [];
			jsondata.nodes.forEach(function(jsonnode){
				var node = { id: jsonnode.id ,
							label: jsonnode.name,
							title: jsonnode.name,
							shadow: true,
							shape: 'circularImage',
							image: jsonnode.usericon == '' ? 'missing.png' : jsonnode.usericon,
							brokenImage: 'https://pbs.twimg.com/profile_images/824716853989744640/8Fcd0bji_400x400.jpg',
							borderWidth:4,
					          size:30,
				};
				nodes.push(node);
			});
			jsondata.links.forEach(function(jsonedge){
				var edge = { from: jsonedge.source ,
							to: jsonedge.target,
							label: jsonedge.subtype + ':' + jsonedge.interactiontype,
							font: {align: 'middle'},
							arrows: 'to',
							shadow: true,
							color: 'black',
				};
				edges.push(edge);
			});
			var data = { 
					nodes: nodes,
					edges: edges
			};
			var options = {
		                nodes: {
		                    shape: 'dot',
		                    size: 16
		                },
		                physics: {
		                    forceAtlas2Based: {
		                        gravitationalConstant: -26,
		                        centralGravity: 0.005,
		                        springLength: 230,
		                        springConstant: 0.18
		                    },
		                    maxVelocity: 146,
		                    solver: 'forceAtlas2Based',
		                    timestep: 0.35,
		                    stabilization: {iterations: 150}
		                }
		            };		
			var network = new vis.Network(document.getElementById(container), data, options);		
			
		});
		
		
		
		} // End of function init.
	}; // End of init var.
	return init;
});
