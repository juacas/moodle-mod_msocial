<style>
@import url(view/graph/chord.css);

#circle circle {
	fill: none;
	pointer-events: all;
}

.group path {
	fill-opacity: .5;
}

path.chord {
	stroke: #000;
	stroke-width: .25px;
}

#circle:hover path.fade {
	display: none;
}
</style>
<script src="//d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script src="//d3js.org/queue.v1.min.js"></script>
<script src="view/graph/chord.js"></script>
<div id="chord"></div>
<script>
var jsonurl = "view/graph/jsonized.php?id=<?php echo $this->cm->id ?>";
create_chord(jsonurl);

</script>