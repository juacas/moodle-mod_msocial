/**
 * @module graphviz_social
 * @package mod_msocial/view/graphviz
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
define('msocialview/graphviz', [ 'renderer','svg-pan-zoom' ], function(renderer,svgPanZoom) {

	var init = {
		initview : function(container, dotSourceDiv) {

			// Initialize svg stage. Have to get a return value from
			// renderer.init
			// to properly reset the image.
			var zoomFunc = renderer.init({
				element : container,
//				zoom : [ 0.1, 0.9 ]
			});
			var dotSource = $(dotSourceDiv).text();
			// Update stage with new dot source.
			renderer.render(dotSource);
			setTimeout(function waitSVG() {
				var svgContainer = $("#graph > svg");
				if (svgContainer.length > 0) {
					svgPanZoom("#graph > svg", {
						zoomEnabled : true,
						controlIconsEnabled : true,
						fit : true,
						center : true,
					});
				} else {
					setTimeout(waitSVG, 1000);
				}
			}, 3000);
		} // End of function init.
	}; // End of init var.
	return init;
});
