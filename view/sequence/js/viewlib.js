// This file is part of Moodle - http:// moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify.
// it under the terms of the GNU General Public License as published by.
// the Free Software Foundation, either version 3 of the License, or.
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,.
// but WITHOUT ANY WARRANTY; without even the implied warranty of.
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the.
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <http:// www.gnu.org/licenses/>.
/**
 * @module sequence diagram
 * @package mod_tcount/view/sequence
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

function initview() {
			$(".diagram").sequenceDiagram({
				theme : "simple",
				"font-size" : 9
			});
			setTimeout(function waitSVG() {
				var svgContainer = $(".diagram > svg");
				if (svgContainer.length > 0) {
					svgPanZoom(".diagram > svg", {
						zoomEnabled : true,
						controlIconsEnabled : true,
						fit : true,
						center : true,
					});
				} else {
					setTimeout(waitSVG, 100);
				}
			}, 10);

		} // End of function init.
