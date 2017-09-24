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



d3.json("view/graph/jsonized.php?include_community=true&int_types=post,reply&<?php
$cmid = $this->cm->id;
$msocial = $this->msocial;
echo "id=$cmid&startdate=$msocial->startdate&enddate=$msocial->enddate&redirect=$redirect";
?>", function(error, graph) {
  if (error) throw error;
  init_forced_graph_view(graph);
});


</script>