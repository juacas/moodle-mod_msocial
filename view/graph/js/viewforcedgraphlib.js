/**
 * @module forcedgraph
 * @package mod_msocial/view/graph
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
function init_forced_graph_view(graph) {
	
	var svg = d3.select("#graph");
		
    width = +svg.attr("width"),
    height = +svg.attr("height");
     svg=svg.append("g")
	  .attr("xmlns","http://www.w3.org/2000/svg")
	  .attr("xlink","http://www.w3.org/1999/xlink")
      .attr("id", "graph");
     
 simulation = d3.forceSimulation()
    .force("link", d3.forceLink().id(function(d) { return d.id; }).distance(150).strength(2.5))
    .force("charge", d3.forceManyBody().strength(-1000))
//    .force("center", d3.forceCenter(width / 2, height / 2))
    .force("collide",d3.forceCollide(5))
    .force("X",d3.forceX(width/2).strength(0.25))
    .force("Y",d3.forceY(height/2).strength(0.25))
    ;

	color = d3.scaleOrdinal(d3.schemeCategory20);
	defs = svg.append('defs');
	
	var links = graph.links;
	var nodes = graph.nodes;
	var nodesById = d3.map(nodes,function(node){
		node.sourced=0;
		return node.id;})
	var clusteredlinks = [];
	links.forEach(function (link){
		var n = nodesById.get(link.source);
		n.sourced++;
		var l = clusteredlinks[link.source+'-'+link.target];
		if (l==null){
			link.count=1;
			link.interactions=[];
			clusteredlinks[link.source+'-'+link.target]=link;
			l=link;
		}
		if (l.interactions[link.subtype]==null){
			l.interactions[link.subtype]=[];
		}
		if (l.interactions[link.subtype][link.interactiontype]==null){
			l.interactions[link.subtype][link.interactiontype]=0;
		}
		l.interactions[link.subtype][link.interactiontype]++;
		l.count++;
	});
	links = links.filter(function(d){return d.count>0;});
	link = svg.selectAll(".link").data(links)
			.enter()
				.append("line")
				.attr("class", "link")
				.attr('marker-end', marker)
				.attr('stroke',function(d){
					return color(d.subtype);});
	link.append("title").text(function(d) {
		return d.subtype;
	});

	edgepaths = svg
	.selectAll(".edgepath")
	.data(links)
	.enter()
	.append('path')
			.attr('class', 'edgepath')
			.attr('fill-opacity', 0)
			.attr('stroke-opacity', 0)
			.attr('id', function(d, i) {
				return 'edgepath' + i
			})
			.style("pointer-events", "none")
			.attr('stroke',function(d){
				return color(d.subtype);});
	
	edgelabels = svg.selectAll(".edgelabel")
	.data(links)
	.enter()
	.append('text')
			.style("pointer-events", "none")
			.attr('class', 'edgelabel')
			.attr('id', function(d, i) {
						return 'edgelabel' + i
					})
			.attr('font-size', 10)
			.attr('fill', function (d){
				return color(d.subtype);});

	edgelabels.append('textPath').attr('xlink:href', function(d, i) {
		return '#edgepath' + i
	}).style("text-anchor", "middle")
	.style("pointer-events", "none")
	.attr("startOffset", "50%")
	.text(function(d) {
		var interactiontexts=[];
		Object.entries(d.interactions).forEach(
				([subtype,subtypereactions])=>{
					var reacttext=[];		
					Object.entries(subtypereactions).forEach(
							([i,v])=>{	reacttext.push( i+"("+v+")");});
					interactiontexts.push(subtype+":"+reacttext);
					});
				
		return interactiontexts.join();
	})
	;

	node = svg
		.selectAll(".node")
		.data(graph.nodes)
		.enter()
		.append("g")	 
		.attr("class", "node")
		.call(d3.drag()
				.on("start", dragstarted)
				.on("drag", dragged)
				.on("end", dragended));
	node.append("circle")
		.attr("r", 5)
		.attr("fill", function(d) {
			return "orange";
			});
	node
  		.append("title").text(function(d) {
		return d.name;
	});
	node
		.append("a")
		.attr("xlink:href", function(d,i){
		  var url=nodes[i].userlink;	  
		  return url;
		  })
		.append("text")
		.attr("dy", -3)
		.text(function(d, i) {
			return d.name;
		})
		.attr("text-anchor","middle");

	simulation.nodes(graph.nodes).on("tick", ticked);

	simulation.force("link").links(graph.links);

	function ticked(){
		 node
         .attr("transform", function (d) {
        	 if (d.x < 0){
        		 d.x=0;
        	 }
        	 if (d.x > width){
        		 d.x=width;
        	 }
        	 return "translate(" + d.x + ", " + d.y + ")";});
		link.attr("x1", function(d) {
			return d.source.x;
		}).attr("y1", function(d) {
			return d.source.y;
		}).attr("x2", function(d) {
			return d.target.x;
		}).attr("y2", function(d) {
			return d.target.y;
		});

	  

	   edgepaths.attr('d',linkArc);
//		edgepaths.attr('d', function(d) {
//			return 'M ' + d.source.x + ' ' + d.source.y + ' L ' + d.target.x
//					+ ' ' + d.target.y;
//		});

		edgelabels.attr('transform', function(d) {
			if (d.target.x < d.source.x) {
				var bbox = this.getBBox();

				rx = bbox.x + bbox.width / 2;
				ry = bbox.y + bbox.height / 2;
				return 'rotate(180 ' + rx + ' ' + ry + ')';
			} else {
				return 'rotate(0)';
			}
		});
	}
}
function linkArc(d) {
	  var dx = d.target.x - d.source.x,
	      dy = d.target.y - d.source.y,
	      dr = (d.straight == 0)?Math.sqrt(dx * dx + dy * dy):0;
	      var arcdef = "M" + d.source.x + "," + d.source.y +
	      "A" + dr + "," + dr + " 0 0,1 " + d.target.x + "," + d.target.y;
	      return arcdef;
	}
function dragstarted(d) {
	if (!d3.event.active)
		simulation.alphaTarget(0.3).restart();
	d.fx = d.x;
	d.fy = d.y;
}

function dragged(d) {
	d.fx = d3.event.x;
	d.fy = d3.event.y;
}

function dragended(d) {
	if (!d3.event.active)
		simulation.alphaTarget(0);
	d.fx = null;
	d.fy = null;
}
function marker(link){
	var id='arrowhead'+link.subtype;
	defs.append('marker')
	.attr('id',id)
	.attr('viewBox','-0 -5 10 10')
	.attr('refX',13)
	.attr('refY',0)
	.attr('orient','auto')
	.attr('markerWidth',10)
	.attr('markerHeight',10)
	.attr('xoverflow','visible')
	.append('svg:path')
	.attr('d', 'M 0,-5 L 10 ,0 L 0,5')
	.attr('fill', color(link.subtype))
	.style('stroke','none');
return "url(#"+id+")";
}