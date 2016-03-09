SimileAjax.History.enabled = false;
function init_timeline(Y, tcountid, user) {
    var eventSource = new Timeline.DefaultEventSource();
    var theme1 = Timeline.ClassicTheme.create();
    theme1.autoWidth = false; // Set the Timeline's "width" automatically.
    // Set autoWidth on the Timeline's first band's theme,
    // will affect all bands.
    theme1.event.label.offsetFromLine = 10;
    theme1.event.track.offset = 32;
    theme1.event.instant.iconHeight = 32;
    theme1.event.instant.iconWidth = 32;
    theme1.event.instant.icon = "pix/Twitter_icon.png";
    theme1.event.track.height = 8;
    var theme2 = Timeline.ClassicTheme.create();
    // increase tape height
    theme2.event.tape.height = 6; // px
    theme2.event.track.height = theme2.event.tape.height + 6;
    var bandInfos = [

        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "15%",
            showEventText: false,
            intervalUnit: Timeline.DateTime.WEEK,
            intervalPixels: 200,
            theme: theme2,
            layout: 'overview', // original, overview, detailed
            highlight: true,
        }),
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "85%",
            intervalUnit: Timeline.DateTime.DAY,
            intervalPixels: 100,
            theme: theme1,
            layout: 'original', // original, overview, detailed
            align: "Top",
            zoomIndex: 6,
            zoomSteps: new Array(
                    {pixelsPerInterval: 280, unit: Timeline.DateTime.HOUR},
            {pixelsPerInterval: 140, unit: Timeline.DateTime.HOUR},
            {pixelsPerInterval: 70, unit: Timeline.DateTime.HOUR},
            {pixelsPerInterval: 35, unit: Timeline.DateTime.HOUR},
            {pixelsPerInterval: 400, unit: Timeline.DateTime.DAY},
            {pixelsPerInterval: 200, unit: Timeline.DateTime.DAY},
            {pixelsPerInterval: 100, unit: Timeline.DateTime.DAY}, // DEFAULT zoomIndex
            {pixelsPerInterval: 50, unit: Timeline.DateTime.DAY},
            {pixelsPerInterval: 400, unit: Timeline.DateTime.MONTH},
            {pixelsPerInterval: 200, unit: Timeline.DateTime.MONTH},
            {pixelsPerInterval: 100, unit: Timeline.DateTime.MONTH}
            )
        }),
    ];
    bandInfos[0].syncWith = 1;
    bandInfos[0].highlight = true;

    tl = Timeline.create(document.getElementById("my-timeline"), bandInfos, Timeline.HORIZONTAL);
    tl.loadJSON("jsonized.php?id=" + tcountid + "&user=" + user, function (json, url) {
        eventSource.loadJSON(json, url);
        tl.layout();
        tl.finishedEventLoading(); // Automatically set new size of the div

    });
}