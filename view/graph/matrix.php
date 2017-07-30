<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
?>
<script src="view/graph/js/d3/d3.v3.min.js" charset="utf-8"></script>
<style>
.background {
    fill: #eee;
}

line {
    stroke: #fff;
}

text.active {
    fill: red;
}
</style>
<p>
    Order: <select id="order">
        <option value="name">by Name</option>
        <option value="count">by Frequency</option>
        <option value="group">student or external user</option>
    </select>
<div id="diagram" class="diagram" style=""></div>
<?php
// Graph matrix view.
$reqs->js('/mod/msocial/view/graph/js/viewlib.js');
$reqs->js_init_call('initview', [$this->cm->id], false);