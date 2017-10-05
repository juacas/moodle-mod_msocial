/**
 * @module graphviz_social
 * @package mod_msocial/view/graphviz
 * @copyright 2017 Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @author Juan Pablo de Castro <jpdecastro@tel.uva.es>
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

define('msocialview/graphviz', [ 'renderer','svg-pan-zoom', 'hammer', 'saveSvgAsPng' ],
		function(renderer, svgPanZoom, Hammer, saveSvgAsPng) {

	var init = {
		initview : function(container, dotSourceDiv) {

			// Initialize svg stage. Have to get a return value from
			// renderer.init
			// to properly reset the image.
			var zoomFunc = renderer.init({
				element : container,
//				zoom : [ 0.1, 0.9 ]
			});
			var eventsHandler = {
			          haltEventListeners: ['touchstart', 'touchend', 'touchmove', 'touchleave', 'touchcancel']
	        , init: function(options) {
	            var instance = options.instance
	              , initialScale = 1
	              , pannedX = 0
	              , pannedY = 0

	            // Init Hammer
	            // Listen only for pointer and touch events
	            this.hammer = Hammer(options.svgElement, {
	              inputClass: Hammer.SUPPORT_POINTER_EVENTS ? Hammer.PointerEventInput : Hammer.TouchInput
	            })

	            // Enable pinch
	            this.hammer.get('pinch').set({enable: true})

	            // Handle double tap
	            this.hammer.on('doubletap', function(ev){
	              instance.zoomIn()
	            })

	            // Handle pan
	            this.hammer.on('panstart panmove', function(ev){
	              // On pan start reset panned variables
	              if (ev.type === 'panstart') {
	                pannedX = 0
	                pannedY = 0
	              }

	              // Pan only the difference
	              instance.panBy({x: ev.deltaX - pannedX, y: ev.deltaY - pannedY})
	              pannedX = ev.deltaX
	              pannedY = ev.deltaY
	            })

	            // Handle pinch
	            this.hammer.on('pinchstart pinchmove', function(ev){
	              // On pinch start remember initial zoom
	              if (ev.type === 'pinchstart') {
	                initialScale = instance.getZoom()
	                instance.zoom(initialScale * ev.scale)
	              }

	              instance.zoom(initialScale * ev.scale)

	            })

	            // Prevent moving the page on some devices when panning over SVG
	            options.svgElement.addEventListener('touchmove', function(e){ e.preventDefault(); });
	          }

	        , destroy: function(){
	            this.hammer.destroy()
	          }
	        }
			// Update stage with new dot source.
				var dotSource = $(dotSourceDiv).text();
				renderer.renderHandler(function () {
					setTimeout(function () {
						var svgContainer = $("#graph > svg");
						svgContainer.width('100%');

						svgPanZoom("#graph > svg", {
							zoomEnabled : true,
							controlIconsEnabled : true,
							fit : false,
							center : false,
							customEventsHandler : eventsHandler
						});
						// Do not save correctly zoom & pan.
//						saveSvgAsPng.saveSvgAsPng(document.getElementById("graph").getElementsByTagName("svg").item(0), 'diagram.png');
					}, 3000);
				});
				renderer.render(dotSource);
		} // End of function init.
	}; // End of init var.
	return init;
});
