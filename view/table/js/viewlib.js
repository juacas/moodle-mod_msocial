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
 * @module enhancetable
 * @package mod_msocial/view/table
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

define('msocialview/table',
		[ 'jquery',
		'jqueryui',
		'datatables.net',
		'datatables.colReorder',
		'datatables.fixedHeader',
		'datatables.responsive',
		'datatables.colVis'
		], function($, jqui, datatables) {

	var init = {
		initview : function(containerid,colgroupsconfig) {
			$(document).ready(function() {
				$(containerid).DataTable({
					scrollY : '600px',
					scrollX : true,
					colReorder : true,
					fixedHeader : false,
//					fixedFooter : true,
//					responsive : true,
					paging: true,
					dom: 'Blpftip',
					buttons : [ 
						'colvis',
						'copy',
						'excel',
						'pdf',
						colgroupsconfig
						],
					"pageLength": 25,
					"lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ]
				});
			});
		} // End of function init.
	}; // End of init var.
	return init;
});
