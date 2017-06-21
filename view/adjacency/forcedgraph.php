<script src="https://d3js.org/d3.v4.js"></script>
<script src="https://d3js.org/d3-selection-multi.v1.js"></script>
<script type="text/javascript" src="view/adjacency/forcedgraph.js"></script>
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
        

        
d3.json("view/adjacency/jsonized.php?id=<?php echo $this->cm->id ?>", function(error, graph) {
  if (error) throw error;
  createGraph(graph);
});

        
</script>