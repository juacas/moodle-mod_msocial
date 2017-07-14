function create_chord(jsonurl){
var width = 600,
    height = 400,
    margin = 100,
    outerRadius = Math.min(width, height) / 2 - margin,
    innerRadius = outerRadius - 30;

var formatPercent = d3.format(".1%");

var arc = d3.svg.arc()
    .innerRadius(innerRadius)
    .outerRadius(outerRadius);

var layout = d3.layout.chord()
    .padding(.04)
    .sortSubgroups(d3.descending)
    .sortChords(d3.ascending);

var path = d3.svg.chord()
    .radius(innerRadius);

var svg = d3.select("#chord").append("svg")
    .attr("width", width)
    .attr("height", height)
  .append("g")
  .attr({
            xmlns: "http://www.w3.org/2000/svg",
            xlink: "http://www.w3.org/1999/xlink",
        })
    .attr("id", "circle")
    .attr("transform", "translate(" + (width / 2) + "," + (height / 2) + ")");

svg.append("circle")
    .attr("r", outerRadius);

queue()
    .defer(d3.json, jsonurl)
    .await(ready);

function ready(error, graph) {
  if (error) throw error;
var matrix=[], links=graph.links, nodes=graph.nodes;
for (i=0;i<graph.nodes.length;i++){
	matrix[i]=[];
	nodes[i].color="orange";
	for(j=0;j<graph.nodes.length;j++){
		matrix[i][j]=0;
	}
}
graph.links.forEach(function(link) {
  matrix[link.source][link.target] += 1;
});
for (i=0;i<graph.nodes.length;i++){
	for(j=0;j<graph.nodes.length;j++){
		matrix[i][j]=Math.max(0.0001,matrix[i][j]/links.length);
	}
}
  // Compute the chord layout.
  layout.matrix(matrix);

  var group = svg.selectAll(".group")
      .data(layout.groups)
    .enter().append("g")
      .attr("class", "group")
      .on("mouseover", mouseover);

  // Add a mouseover title.
  group.append("title").text(function(d, i) {
    return nodes[i].name + ": " + formatPercent(d.value) + " of origins";
  });

  // Add the group arc.
  var groupPath = group.append("path")
  .on("mouseover", fade(0))
	.on("mouseout", fade(1))
      .attr("id", function(d, i) { return "group" + i; })
      .attr("d", arc)
      .style("fill", function(d, i) { return nodes[i].color; });

  // Add a text label.
  var groupText = group
  	  .append("a")
  	  .attr("xlink:href", function(d,i){
  		  var url=nodes[i].userlink;	  
  		  return url;
  		  })
  	  .append("text")
      .attr("x", 5)
      .attr("dy", 3)
      .attr("text-anchor",function(d){return d.startAngle > Math.PI?'end':'start'})
      .attr("transform", function(d) {
          if (d.startAngle > Math.PI){
              return "rotate(" + (getMeanAgle(d) * 180 / Math.PI + 90) + ") translate("+(-innerRadius-5)+" 0)";
       		
          } else {
		return "rotate(" + (getMeanAgle(d) * 180 / Math.PI - 90) + ") "
		+ "translate("+innerRadius+", 0)";
          }
	})
    .text(function(d, i) { 
    	return nodes[i].name; });

// groupText.append("textPath")
// .attr("xlink:href", function(d, i) { return "#group" + i; })
// .text(function(d, i) { return nodes[i].name; });
  // Remove the labels that don't fit. :(
// groupText.filter(function(d, i) {
// return true;//groupPath[0][i].getTotalLength() / 2 - 16 <
// this.getComputedTextLength();
// })
// .attr("transform", function(d,i) {
// return "rotate(" + (getMeanAgle(d) * 180 / Math.PI - 90) + ")"
// + "translate("+innerRadius*0+","+2+0*(-60*(d.endAngle-d.startAngle))+")";
// });
// groupText.filter(function(d, i) {
// var totalLength = outerRadius*(d.endAngle-d.startAngle);
// var computedLength=this.getComputedTextLength()
// return totalLength < computedLength ; })
// .attr("width","20px")
// .attr("transform", "rotate(0 outterRadio 30)")
// .attr("transform", function(d,i) {
// return "rotate(" + (getMeanAgle(d) * 180 / Math.PI - 90) + ")"
// + "translate("+innerRadius*0+","+2+0*(-60*(d.endAngle-d.startAngle))+")"; })
   		;
  // Add the chords.
  var chord = svg.selectAll(".chord")
      .data(layout.chords)
    .enter().append("path")
      .attr("class", "chord")
      .style("fill", function(d) { return nodes[d.source.index].color; })
      .attr("d", path);

  // Add an elaborate mouseover title for each chord.
  chord.append("title").text(function(d) {
    return nodes[d.source.index].name
        + " → " + nodes[d.target.index].name
        + ": " + formatPercent(d.source.value)
        + "\n" + nodes[d.target.index].name
        + " → " + nodes[d.source.index].name
        + ": " + formatPercent(d.target.value);
  });
  /** Returns an event handler for fading a given chord group. */
	function fade(opacity) {
		return function(g, i) {
			svg.selectAll("path.chord")
			.filter(function(d) {
				return d.source.index != i && d.target.index != i;
			})
			.transition().duration(800)
			.style("opacity", opacity);
		};
	}
  function mouseover(d, i) {
    chord.classed("fade", function(p) {
      return p.source.index != i
          && p.target.index != i;
    });
  }
  function getMeanAgle(d) {
		return d.startAngle+(d.endAngle-d.startAngle)/2;
	}
}
}