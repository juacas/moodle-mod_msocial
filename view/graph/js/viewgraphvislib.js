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
				var shape;
				var node = { id: jsonnode.id ,
						title: '<a href="fdsfds">' + jsonnode.name + '</a>',
						label: jsonnode.name,
						shape: shape,
						userlink: jsonnode.userlink,
						group: jsonnode.group,
				};
				if (jsonnode.group == 1) {
					node.shape = 'dot';
				} else {
					node.shape = 'circularImage';
					node.shadow = true;
					node.image = jsonnode.usericon == '' ? 'missing.png' : jsonnode.usericon;
					node.brokenImage = 'https://pbs.twimg.com/profile_images/824716853989744640/8Fcd0bji_400x400.jpg';
					node.borderWidth = 4;
					node.size = 30;
				}
				nodes[node.id] = node;
			});
			jsondata.links.forEach(function(jsonedge){
				var edge = { 
							id: jsonedge.id,
							from: jsonedge.source ,
							to: jsonedge.target,
							label: jsonedge.subtype + ':' + jsonedge.interactiontype,
							font: {align: 'middle'},
							arrows: 'to',
							shadow: false,
							color: 'black',
							link: jsonedge.link,
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
			network.on("doubleClick", function (params) {
			        params.event = "[original event]";
			        if (params.nodes.length > 0) {
			        	window.open(this.body.data.nodes.get(params.nodes[0]).userlink, '_blank');
			        }
			        if (params.edges.length > 0) {
			        	window.open(this.body.data.edges.get(params.edges[0]).link, '_blank');
			        }
			    });
		});
		
		
		
		} // End of function init.
	}; // End of init var.
	return init;
});
