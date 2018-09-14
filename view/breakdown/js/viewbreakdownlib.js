/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
define('msocialview/breakdown', [ 'jquery', 'd3'],
function($) {
	var init = {
		initview : function(container, cmid, params, redirect) {	
		var params = $.param(params);
		$.getJSON("view/breakdown/jsonized.php?id=" + cmid + "&" + params + "&redirect=" + redirect,
		function(data){
			var width = 400,
		    height = width,
		    radius = width / 2,
		    x = d3.scale.linear().range([0, 2 * Math.PI]),
		    y = d3.scale.pow().exponent(1.3).domain([0, 1]).range([0, radius]),
		    padding = 5,
		    duration = 1000;
		var div = d3.select(container);
		div.select("img").remove();
		var vis = div.append("svg")
		    .attr("width", width + padding * 2)
		    .attr("height", height + padding * 2)
		  .append("g")
		    .attr("transform", "translate(" + [radius + padding, radius + padding] + ")");
		
		var arc = d3.svg.arc()
		    .startAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x))); })
		    .endAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx))); })
		    .innerRadius(function(d) { return Math.max(0, d.y ? y(d.y) : d.y); })
		    .outerRadius(function(d) { return Math.max(0, y(d.y + d.dy)); });
//		var partition = d3.layout.partition()
//	    .sort(null)
//	    .value(function(d) { return 5.8 - d.depth; });
		  var partition = d3.layout.partition().value(function(d) {
		    return d.size;
		  });
		  var nodes = partition(data);
		  var path = vis.selectAll("path").data(nodes);
		  path.enter().append("path")
		      .attr("id", function(d, i) { return "path-" + i; })
		      .attr("d", arc)
		      .attr("fill-rule", "evenodd")
		      .style("fill", colour)
		      .on("click", click);
		  var text = vis.selectAll("text").data(nodes);
		  var textEnter = text.enter().append("text")
		      .style("fill-opacity", 1)
		      .style("fill", function(d) {
		        return brightness(d3.rgb(colour(d))) < 125 ? "#eee" : "#000";
		      })
		      .attr("text-anchor", function(d) {
		        return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
		      })
		      .attr("dy", ".2em")
		      .attr("transform", function(d) {
		        var multiline = (d.name || "").split(" ").length > 1,
		            angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
		            rotate = angle + (multiline ? -.5 : 0);
		        return "rotate(" + rotate + ")translate(" + (y(d.y) + padding) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
		      })
		      .on("click", click);
		  textEnter.append("tspan")
		      .attr("x", 0)
		      .text(function(d) { return d.depth ? d.name.split(" ")[0] : ""; });
		  textEnter.append("tspan")
		      .attr("x", 0)
		      .attr("dy", "1em")
		      .text(function(d) { return d.depth ? d.name.split(" ")[1] || "" : ""; });
		  function click(d) {
		    path.transition()
		      .duration(duration)
		      .attrTween("d", arcTween(d));
		    // Somewhat of a hack as we rely on arcTween updating the scales.
		    text.style("visibility", function(e) {
		          return isParentOf(d, e) ? null : d3.select(this).style("visibility");
		        })
		      .transition()
		        .duration(duration)
		        .attrTween("text-anchor", function(d) {
		          return function() {
		            return x(d.x + d.dx / 2) > Math.PI ? "end" : "start";
		          };
		        })
		        .attrTween("transform", function(d) {
		          var multiline = (d.name || "").split(" ").length > 1;
		          return function() {
		            var angle = x(d.x + d.dx / 2) * 180 / Math.PI - 90,
		                rotate = angle + (multiline ? -.5 : 0);
		            return "rotate(" + rotate + ")translate(" + (y(d.y) + padding) + ")rotate(" + (angle > 90 ? -180 : 0) + ")";
		          };
		        })
		        .style("fill-opacity", function(e) { return isParentOf(d, e) ? 1 : 1e-6; })
		        .each("end", function(e) {
		          d3.select(this).style("visibility", isParentOf(d, e) ? null : "hidden");
		        });
		  }
				

		function isParentOf(p, c) {
		  if (p === c) return true;
		  if (p.children) {
		    return p.children.some(function(d) {
		      return isParentOf(d, c);
		    });
		  }
		  return false;
		}
		function colour(d) {
		  if (d.children) {
		    // There is a maximum of two children!
		    var colours = d.children.map(colour),
		        a = d3.hsl(colours[0]),
		        b = d3.hsl(colours[1]);
		    // L*a*b* might be better here...
		    return d3.hsl((a.h + b.h) / 2, a.s * 1.2, a.l / 1.2);
		  }
		  return d.colour || "#ffa";
		}
		// Interpolate the scales!
		function arcTween(d) {
		  var my = maxY(d),
		      xd = d3.interpolate(x.domain(), [d.x, d.x + d.dx]),
		      yd = d3.interpolate(y.domain(), [d.y, my]),
		      yr = d3.interpolate(y.range(), [d.y ? 20 : 0, radius]);
		  return function(d) {
		    return function(t) { x.domain(xd(t)); y.domain(yd(t)).range(yr(t)); return arc(d); };
		  };
		}
		function maxY(d) {
		  return d.children ? Math.max.apply(Math, d.children.map(maxY)) : d.y + d.dy;
		}
		// http://www.w3.org/WAI/ER/WD-AERT/#color-contrast
		function brightness(rgb) {
		  return rgb.r * .299 + rgb.g * .587 + rgb.b * .114;
		}
			});
		}
	}
	var init2 = {
		initview : function(container, cmid, params, redirect) {	
		var params = $.param(params);
		$.getJSON("view/breakdown/jsonized.php?id=" + cmid + "&" + params + "&redirect=" + redirect,
		function(data){
			
		var width = 960,
	    height = 700,
	    radius = (Math.min(width, height) / 2) - 10;

		var formatNumber = d3.format(",d");

		var x = d3.scaleLinear()
		    .range([0, 2 * Math.PI]);

		var y = d3.scaleSqrt()
		    .range([0, radius]);

		var color = d3.scaleOrdinal(d3.schemeCategory20);

		var svg = d3.select(container).append("svg")
		    .attr("width", width)
		    .attr("height", height)
		  .append("g")
		    .attr("transform", "translate(" + width / 2 + "," + (height / 2) + ")");

		  
		  root = d3.hierarchy(data).sum(function(d) { return d.size; });
		  // Calculate the sizes of each arc that we'll draw later.
		  var partition = d3.partition().size([2 * Math.PI, radius]);

		  partition(root);
        var arc = d3.arc()
            .startAngle(function (d) { return d.x0 })
            .endAngle(function (d) { return d.x1 })
            .innerRadius(function (d) { return d.y0 })
            .outerRadius(function (d) { return d.y1 });
        
		  svg.selectAll('g')  // <-- 1
		    .data(root.descendants())
		    .enter().append('g').attr("class", "node")  // <-- 2
		    .append('path')  // <-- 2
		    .attr("display", function (d) { return d.depth ? null : "none"; })
		    .attr("d", arc)
		    .style('stroke', '#fff')
		    .style("fill", function (d) { return color((d.children ? d : d.parent).data.name); })
		     .on("click", click);
		  svg.selectAll(".node")  // <-- 1
		    .append("text")  // <-- 2
		    .attr("transform", function(d) {
		        return "translate(" + arc.centroid(d) + ")rotate(" + computeTextRotation(d) + ")"; }) // <-- 3
		    .attr("dx", "-20")  // <-- 4
		    .attr("dy", ".5em")  // <-- 5
		    .text(function(d) { return d.data.name + "\n" + formatNumber(d.value); });  // <-- 6
		  
		  function computeTextRotation(d) {
			    var angle = (d.x0 + d.x1) / Math.PI * 90;  // <-- 1

			    // Avoid upside-down labels
			    return (angle < 90 || angle > 270) ? angle : angle + 180;  // <--2 "labels aligned with slices"

			    // Alternate label formatting
			    //return (angle < 180) ? angle - 90 : angle + 90;  // <-- 3 "labels as spokes"
			}
//		  svg.selectAll("path")
//		      .data(root.descendants())
//		    .enter().append("path")
//		      .attr("d", arc)
//		      .style('stroke', '#fff')
//		      .style("fill", function(d) { return color((d.children ? d : d.parent).data.name); })
//		      .on("click", click)
//		    .append("title")
//		      .text(function(d) { return d.data.name + "\n" + formatNumber(d.value); });
		
		function click(d) {
		  svg.transition()
		      .duration(750)
		      .tween("scale", function() {
		        var xd = d3.interpolate(x.range(), [d.x0, d.x1]),
		            yd = d3.interpolate(y.domain(), [d.y0, 1]),
		            yr = d3.interpolate(y.range(), [d.y0 ? 20 : 0, radius]);
		        return function(t) { x.domain(xd(t)); y.domain(yd(t)).range(yr(t)); };
		      })
		    .selectAll("path")
		      .attrTween("d", function(d) { return function() { return arc(d); }; });
		}
		
		
		d3.select(self.frameElement).style("height", height + "px");
		
		
//			var width = 960,
//			  height = 700,
//			  radius = Math.min(width, height) / 2;
//
//			var x = d3.scale.linear()
//			  .range([0, 2 * Math.PI]);
//
//			var y = d3.scale.linear()
//			  .range([0, radius]);
//
//			var color = d3.scale.category20c();
//
//			var svg = d3.select(container).append("svg")
//			  .attr("width", width)
//			  .attr("height", height)
//			  .append("g")
//			  .attr("transform", "translate(" + width / 2 + "," + (height / 2 + 10) + ")");
//
//			var partition = d3.layout.partition()
//			  .value(function(d) {
//			    return d.size;
//			  });
//
//			var arc = d3.svg.arc()
//			  .startAngle(function(d) {
//			    return Math.max(0, Math.min(2 * Math.PI, x(d.x)));
//			  })
//			  .endAngle(function(d) {
//			    return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx)));
//			  })
//			  .innerRadius(function(d) {
//			    return Math.max(0, y(d.y));
//			  })
//			  .outerRadius(function(d) {
//			    return Math.max(0, y(d.y + d.dy));
//			  });
//
//			var g = svg.selectAll("g")
//			  .data(partition.nodes(data))
//			  .enter().append("g");   
//
//			var path = g.append("path")
//			  .attr("d", arc).attr("class", function(d) {
//			    return "ring_" + d.depth;
//			  })
//			  .style("fill", function(d) {
//			    return color((d.children ? d : d.parent).name);
//			  })
//			  .on("click", click);
//
//			 var totalSize = path.node().__data__.value;
//
//			var text = g.append("text")
//			  .attr("transform", function(d) {
//			    return "rotate(" + computeTextRotation(d) + ")";
//			  })
//			  .attr("x", function(d) {
//			    return y(d.y);
//			  })
//			  .attr("dx", "6") // margin
//			  .attr("dy", ".35em") // vertical-align
//			  .text(function(d) {
//			    var percentage = (100 * d.value / totalSize).toPrecision(3);
//			    var percentageString = percentage + "%";
//			    if (percentage < 0.1) {
//			      percentageString = "< 0.1%";
//			    }
//			    return d.name +" "+percentageString;
//			  });
//
//			function click(d) {
//			  // fade out all text elements
//			  text.transition().attr("opacity", 0);
//
//			  path.transition()
//			    .duration(750)
//			    .attrTween("d", arcTween(d))
//			    .each("end", function(e, i) {
//			      // check if the animated element's data e lies within the visible angle span given in d
//			      if (e.x >= d.x && e.x < (d.x + d.dx)) {
//			        // get a selection of the associated text element
//			        var arcText = d3.select(this.parentNode).select("text");
//			        // fade in the text element and recalculate positions
//			        arcText.transition().duration(750)
//			          .attr("opacity", 1)
//			          .attr("transform", function() {
//			            return "rotate(" + computeTextRotation(e) + ")"
//			          })
//			          .attr("x", function(d) {
//			            return y(d.y);
//			          });
//			      }
//			    });
//			}
//
//
//			d3.select(self.frameElement).style("height", height + "px");
//
//			// Interpolate the scales!
//			function arcTween(d) {
//			  var xd = d3.interpolate(x.domain(), [d.x, d.x + d.dx]),
//			    yd = d3.interpolate(y.domain(), [d.y, 1]),
//			    yr = d3.interpolate(y.range(), [d.y ? 20 : 0, radius]);
//			  return function(d, i) {
//			    return i ? function(t) {
//			      return arc(d);
//			    } : function(t) {
//			      x.domain(xd(t));
//			      y.domain(yd(t)).range(yr(t));
//			      return arc(d);
//			    };
//			  };
//			}
//
//			function computeTextRotation(d) {
//			  return (x(d.x + d.dx / 2) - Math.PI / 2) / Math.PI * 180;
//			}
			
			
			
			
//			color = d3.scaleOrdinal().range(d3.quantize(d3.interpolateRainbow, data.children.length + 1));
//			format = d3.format(",d");
//			width = 932;
//			height = 900;
//			radius = width / 2;
//			arc = d3.arc()
//			    .startAngle(d => d.x0)
//			    .endAngle(d => d.x1)
//			    .padAngle(d => Math.min((d.x1 - d.x0) / 2, 0.005))
//			    .padRadius(radius / 2)
//			    .innerRadius(d => d.y0)
//			    .outerRadius(d => d.y1 - 1)
//			partition = data => d3.partition().size([2 * Math.PI, radius])
//			  (d3.hierarchy(data)
//			    .sum(d => d.size)
//			    .sort((a, b) => b.value - a.value));
//	
//			  const root = partition(data);
//	
//			  var svg = d3.select(container).append("svg")
//					    .attr("width", width)
//					    .attr("height", height);
//					  
//			  
//			  const g = svg.append("g");
//			  
//			  g.attr({
//					            xmlns: "http://www.w3.org/2000/svg",
//					            xlink: "http://www.w3.org/1999/xlink",
//					        });
//			  
//			  g.append("g")
//			    .attr("fill-opacity", 0.6)
//			    .selectAll("path")
//			    .data(root.descendants().filter(d => d.depth))
//			    .enter().append("path")
//			      .attr("fill", d => { while (d.depth > 1) d = d.parent; return color(d.data.name); })
//			      .attr("d", arc)
//			    .append("title")
//			      .text(d => `${d.ancestors().map(d => d.data.name).reverse().join("/")}\n${format(d.value)}`);
//	
//			  g.append("g")
//			      .attr("pointer-events", "none")
//			      .attr("text-anchor", "middle")
//			    .selectAll("text")
//			    .data(root.descendants().filter(d => d.depth && (d.y0 + d.y1) / 2 * (d.x1 - d.x0) > 10))
//			    .enter().append("text")
//			      .attr("transform", function(d) {
//			        const x = (d.x0 + d.x1) / 2 * 180 / Math.PI;
//			        const y = (d.y0 + d.y1) / 2;
//			        return `rotate(${x - 90}) translate(${y},0) rotate(${x < 180 ? 0 : 180})`;
//			      })
//			      .attr("dy", "0.35em")
//			      .text(d => d.data.name);
//	
//			  document.body.appendChild(svg.node());
//	
//			  const box = g.node().getBBox();
//	
//			  svg.remove()
//			      .attr("width", box.width)
//			      .attr("height", box.height)
//			      .attr("viewBox", `${box.x} ${box.y} ${box.width} ${box.height}`);
//			  svg.node();
		});
		}
	 } // End of var init...
	return init;
	});
