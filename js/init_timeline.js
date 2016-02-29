SimileAjax.History.enabled = false;
function init_timeline(Y, tcountid, user) {
    var eventSource = new Timeline.DefaultEventSource();
    var bandInfos = [
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "85%",
            intervalUnit: Timeline.DateTime.DAY,
            intervalPixels: 100
        }),
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "15%",
            showEventText: false,
            intervalUnit: Timeline.DateTime.WEEK,
            intervalPixels: 200,
            layout: 'overview'
        }),


    ];
    bandInfos[1].syncWith = 0;
    bandInfos[1].highlight = true;

    tl = Timeline.create(document.getElementById("my-timeline"), bandInfos);
    tl.loadJSON("jsonized.php?id=" + tcountid + "&user=" + user, function (json, url) {
        eventSource.loadJSON(json, url);
    });
}