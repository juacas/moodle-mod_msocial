/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
Timeline_urlPrefix = document.URL.substr(0, document.URL.lastIndexOf('/')) + '/view/timeline/js/timeline/';
Timeline_ajax_url = document.URL.substr(0, document.URL.lastIndexOf('/')) + "/view/timeline/js/simile-ajax/simile-ajax-api.js"
SimileAjax_urlPrefix = document.URL.substr(0, document.URL.lastIndexOf('/')) + '/view/timeline/js/simile-ajax/';
Timeline_parameters='bundle=false&timeline-use-local-resources=true';
function init_timeline(Y, msocialid, user) {
    if (typeof Timeline != 'undefined'
    	&& typeof Timeline.DefaultEventSource != 'undefined'
    	&& typeof Timeline.GregorianDateLabeller.monthNames != 'undefined'
    	&& Object.keys(Timeline.GregorianDateLabeller.monthNames).length >0 ) {
        deferred_init_timeline(Y, msocialid, user);
    } else {
        setTimeout(init_timeline, 100, Y, msocialid, user);
    }
}
function deferred_init_timeline(Y, msocialid, user) {
    var eventSource = new Timeline.DefaultEventSource();
    SimileAjax.History.enabled = false;
    var theme1 = Timeline.ClassicTheme.create();
    theme1.autoWidth = true; // Set the Timeline's "width" automatically.
    // Set autoWidth on the Timeline's first band's theme,
    // will affect all bands.
    theme1.event.label.offsetFromLine = 10;
    theme1.event.track.offset = 32;
    theme1.event.instant.iconHeight = 32;
    theme1.event.instant.iconWidth = 32;
    theme1.event.instant.icon = "social/twitter/pix/Twitter_icon.png";
    theme1.event.track.height = 8;
    theme1.mouseWheel = 'zoom';

    var theme2 = Timeline.ClassicTheme.create();
    // increase tape height
    theme2.event.tape.height = 6; // px
    theme2.event.track.height = theme2.event.tape.height + 6;
    theme2.mouseWheel = 'zoom';
    var bandInfos = [
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "48px",
            showEventText: false,
            intervalUnit: SimileAjax.DateTime.MONTH,
            intervalPixels: 200,
            theme: theme2,
            layout: 'overview', // original, overview, detailed
            highlight: true,
        }),
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "85%",
            intervalUnit: SimileAjax.DateTime.DAY,
            intervalPixels: 100,
            theme: theme1,
            layout: 'original', // original, overview, detailed
            align: "Top",
            zoomIndex: 6,
            zoomSteps: new Array(
                    {pixelsPerInterval: 280, unit: SimileAjax.DateTime.HOUR},
                    {pixelsPerInterval: 140, unit: SimileAjax.DateTime.HOUR},
                    {pixelsPerInterval: 70, unit: SimileAjax.DateTime.HOUR},
                    {pixelsPerInterval: 35, unit: SimileAjax.DateTime.HOUR},
                    {pixelsPerInterval: 400, unit: SimileAjax.DateTime.DAY},
                    {pixelsPerInterval: 200, unit: SimileAjax.DateTime.DAY},
                    {pixelsPerInterval: 100, unit: SimileAjax.DateTime.DAY}, // DEFAULT zoomIndex
                    {pixelsPerInterval: 50, unit: SimileAjax.DateTime.DAY},
                    {pixelsPerInterval: 400, unit: SimileAjax.DateTime.MONTH},
                    {pixelsPerInterval: 200, unit: SimileAjax.DateTime.MONTH},
                    {pixelsPerInterval: 100, unit: SimileAjax.DateTime.MONTH}
            )
        }),
    ];
    bandInfos[0].syncWith = 1;
    bandInfos[0].highlight = true;

    tl = Timeline.create(document.getElementById("my-timeline"), bandInfos, Timeline.HORIZONTAL);
    tl.loadJSON("view/timeline/jsonized.php?id=" + msocialid + "&user=" + user, function (json, url) {
        eventSource.loadJSON(json, url);
        tl.getBand(1).setMaxVisibleDate(eventSource.getLatestDate());
        tl.getBand(1).setMinVisibleDate(eventSource.getEarliestDate());
// Calculate the minimum size required to display all activities in band 0
        var tracksNeeded = tl.getBand(1)._eventTracksNeeded;
        var trackIncrement = tl.getBand(1)._eventTrackIncrement;
        var widgetHeight = Math.max((tracksNeeded * trackIncrement) / 1.80, 300);
// Resize widget's containing DIV using jQuery
        $("#my-timeline").height(widgetHeight);
        tl.layout();
        tl.finishedEventLoading(); // Automatically set new size of the div
    });
}
