/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
define('msocialview/drops', [ 'jquery','event-drops'],
function($) {
	var init = {
		initview : function(container, cmid, params, redirect) {	
		var params = $.param(params);
		$.getJSON("view/drops/jsonized.php?id=" + cmid + "&" + params + "&redirect=" + redirect,
		function(data){
				var mindate = null;
				var maxdate = null;
				data.forEach(function (serie){
					for(var i=0; i < serie.data.length; i++) {
						var date = new Date(serie.data[i].startdate);
						serie.data[i].date = date;
						if (mindate == null || mindate > date) {
							mindate = date;
						}
						if (maxdate == null || maxdate < date) {
							maxdate = date;
						}
					}
				});
				var colors = d3.schemeCategory10;
				const FONT_SIZE = 16; // in pixels
				const TOOLTIP_WIDTH = 30; // in rem

				// we're gonna create a tooltip per drop to prevent from transition issues
				const showTooltip = (event) => {
				    d3.select('body').selectAll('.tooltip').remove();

				    const tooltip = d3
				        .select('body')
				        .append('div')
				        .attr('class', 'tooltip')
				        .style('opacity', 0); // hide it by default

				    const t = d3.transition().duration(250).ease(d3.easeLinear);

				    tooltip
				        .transition(t)
				        .on('start', () => {
				            d3.select('.tooltip').style('display', 'block');
				        })
				        .style('opacity', 1);

				    const rightOrLeftLimit = FONT_SIZE * TOOLTIP_WIDTH;

				    const direction = d3.event.pageX > rightOrLeftLimit ? 'right' : 'left';

				    const ARROW_MARGIN = 1.65;
				    const ARROW_WIDTH = FONT_SIZE;
				    const left = direction === 'right'
				        ? d3.event.pageX - rightOrLeftLimit
				        : d3.event.pageX - ((ARROW_MARGIN * (FONT_SIZE - ARROW_WIDTH)) / 2);
				    if (event.usericon == '') {
				    	event.usericon = 'pix/Anonymous.png';
				    }
				    tooltip.html(
				        `
				        <div class="commit">
				            <a href="${event.userlink}"><img class="avatar" src="${event.usericon}"/></a>
				            <div class="content">
				                <h3 class="message"><a href="${event.userlink}">${event.username}</a></h3>
				                <p>
				                    <a href="${event.link}" class="author">${event.description}</a>
				                     <span class="date">${event.date}</span>
				                </p>
				            </div>
				        </div>
				    `
				    );

				    tooltip
				        .style('left', `${left}px`)
				        .style('top', `${d3.event.pageY + 16}px`)
				        .classed(direction, true);
				};

				const hideTooltip = () => {
				    const t = d3.transition().duration(1000);

				    d3
				        .select('.tooltip')
				        .transition(t)
				        .on('end', function end() {
				            this.remove();
				        })
				        .style('opacity', 0);
				};
				var eventDropsChart = d3.chart.eventDrops()
												.date(d => d.date)
												.eventLineColor((d, i) => colors[i])
												.mouseover(showTooltip)
												.start(mindate)
												.end(maxdate);
				
				d3.select(container)
				   .datum(data)
				   .call(eventDropsChart);		
			});
		}
	 } // End of var init...
	return init;
	});
