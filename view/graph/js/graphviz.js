/**
 * 
 */
define('msocialview/graphviz', [ 'renderer' ], function(
		renderer, svgPanZoom) {

	var init = {
		initview : function(container, dotSourceDiv) {

			// Initialize svg stage. Have to get a return value from
			// renderer.init
			// to properly reset the image.
			var zoomFunc = renderer.init({
				element : container,
				zoom : [ 0.0, 0.0 ]
			});
			var dotSource = $(dotSourceDiv).text();
			// Update stage with new dot source.
			renderer.render(dotSource);
		} // End of function init.
	}; // End of init var.
	return init;
});
