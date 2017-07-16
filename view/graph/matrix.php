<script src="//d3js.org/d3.v3.min.js" charset="utf-8"></script>
<style>.background {
  fill: #eee;
}
        
line {
  stroke: #fff;
}
        
text.active {
  fill: red;
}
</style>
<p>Order: <select id="order">
  <option value="name">by Name</option>
  <option value="count">by Frequency</option>
  <option value="group">student or external user </option>
</select>
<div id="diagram" class="diagram" style="max-witdh=800px"></div>
<?php 
// graph matrix view.
$reqs->js('/mod/tcount/view/graph/js/viewlib.js');  
$reqs->js_init_call('initview',[$this->cm->id], false); ?>
