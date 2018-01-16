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
		initview : function(container, cmid, params, redirect, collapse = 'loops') {
		function updateprogress(params) {
			var maxWidth = 496;
            var minWidth = 20;
            var widthFactor = params.iterations/params.total/2 + loadjsonprogress/200;
            var width = Math.max(minWidth,maxWidth * widthFactor);

            document.getElementById('bar').style.width = width + 'px';
            document.getElementById('text').innerHTML = Math.round(widthFactor*100) + '%';
		}
		var params = $.param(params);
		var loadjsonprogress = 20;
		updateprogress({iterations:0, total:1});

		$.getJSON("view/graph/jsonized.php?include_community=true&id=" + cmid + "&" + params + "&redirect=" + redirect,
		function(jsondata){
			var nodes = [];
			var edges = [];
			var collapsededges = [];
			var loopradio = [];
			loadjsonprogress = 30;
			updateprogress({iterations:0, total:1});
			// Collapse semantically equivalent edges.
			jsondata.links.forEach(function(jsonedge){
				var key;
				// Key is used to collapse edges. Detect duplicates to collapse them.
				if (collapse == 'all' || (collapse == 'loops' && jsonedge.source == jsonedge.target)) {
					key = jsonedge.subtype + 
//							'-' + jsonedge.interactiontype +
							'-' + jsonedge.source +
							'-' + jsonedge.target;				
				} else {
					key = jsonedge.id;
				}
				var edge;
				if (key in collapsededges) {
					edge = collapsededges[key];
					edge.count = edge.count + 1;
					edge.label = jsonedge.subtype +
//								':' +
//								jsonedge.interactiontype +
								'(' + edge.count + ')';
					edge.link = '';
				} else {
					edge = {
							id: jsonedge.id,
							from: jsonedge.source ,
							to: jsonedge.target,
							label: jsonedge.subtype + ':' + jsonedge.interactiontype,
							font: {align: 'middle'},
							arrows: 'to',
							shadow: false,
							color: 'black',
							link: jsonedge.link,
							count: 1,
					};
					if (edge.from == edge.to) {
						if (!(edge.from in loopradio)) {
							loopradio[edge.from] = 10;
						}
						edge.selfReferenceSize = loopradio[edge.from] + 20;
						loopradio[edge.from] = edge.selfReferenceSize;
					}
					collapsededges[key] = edge;
				}
			});
			loadjsonprogress = 50;
			updateprogress({iterations:0, total:1});
			jsondata.nodes.forEach(function(jsonnode){
				var shape;
				var node = { id: jsonnode.id ,
						title: '<a href="">' + jsonnode.name + '</a>',
						label: jsonnode.name,
				//		shape: shape,
						userlink: jsonnode.userlink,
						group: jsonnode.group,
				};
				if (jsonnode.group == 1) {
					node.shape = 'dot';
				} else {
					node.shape = 'circularImage';
//					node.shadow = true;
					node.image = jsonnode.usericon == '' ? 'missing.png' : jsonnode.usericon;
					node.brokenImage = 'https://pbs.twimg.com/profile_images/824716853989744640/8Fcd0bji_400x400.jpg';
					node.borderWidth = 4;
					node.size = 30;
				}
				nodes[node.id] = node;
			});
			loadjsonprogress = 70;
			updateprogress({iterations:0, total:1});
			for (var edge in collapsededges) {
				edges.push(collapsededges[edge]);
			}
			var data = { 
					nodes: nodes,
					edges: edges
			};
			var options = {
		                nodes: {
		                    shape: 'dot',
		                    size: 16
		                },
		                layout:{
		                    randomSeed:34
		                },
		                physics: {
		                    forceAtlas2Based: {
		                        gravitationalConstant: -26,
		                        centralGravity: 0.005,
		                        springLength: 130,
		                        springConstant: 0.18
		                    },
		                    maxVelocity: 156,
		                    minVelocity: 5.5,
		                    solver: 'forceAtlas2Based',
		                    timestep: 0.15,
		                    stabilization: {
		                    	enabled: true,
		                    	updateInterval: 10,
		                    	iterations: 250
		                    	},
		                },
		                edges: {
		                    width: 0.15,
		                    color: {inherit: 'from'},
		                    smooth: {
		                      type: 'dynamic'
		                    }
		                  },
		                interaction: {
		                    tooltipDelay: 200,
		                    hideEdgesOnDrag: true
		                  }
            };
			loadjsonprogress = 100;
			updateprogress({iterations:0, total:1});
			var network = new vis.Network(document.getElementById(container), data, options);
			
			network.on("doubleClick", function (params) {
			        params.event = "[original event]";
			        if (params.nodes.length > 0) {
			        	window.open(this.body.data.nodes.get(params.nodes[0]).userlink, '_blank');
			        } else if (params.edges.length > 0) {
			        	var edge = this.body.data.edges.get(params.edges[0]);
			        	if (edge.link != '') {
			        		window.open(edge.link, '_blank');
			        	}
			        } else {
			        	var options = {
								scale: 0.8,
								animation: true,
						};
						network.fit({animation: options});
			        }
			    });
			network.on("click", function (params) {
				
				if (params.nodes.length > 0) {
					var options = {
							scale: 0.8,
							animation: true,
					};
				    network.focus(params.nodes[0], options);
				} 
			});
			network.on("stabilizationProgress", updateprogress );
            network.once("stabilizationIterationsDone", function() {
                document.getElementById('text').innerHTML = '100%';
                document.getElementById('bar').style.width = '496px';
                document.getElementById('loadingBar').style.opacity = 0;
                // Really clean the dom element.
                setTimeout(function () {document.getElementById('loadingBar').style.display = 'none';}, 500);
            });
		});
		} // End of function init.
	}; // End of init var.
	return init;
});
