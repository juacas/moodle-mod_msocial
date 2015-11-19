SimileAjax.History.enabled = false;
function init_timeline(Y, tcountid, user) {
    var eventSource = new Timeline.DefaultEventSource();
    var bandInfos = [
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "70%",
            intervalUnit: Timeline.DateTime.WEEK,
            intervalPixels: 100
        }),
        Timeline.createBandInfo({
            eventSource: eventSource,
            width: "30%",
            intervalUnit: Timeline.DateTime.MONTH,
            intervalPixels: 200
        })
    ];
    bandInfos[1].syncWith = 0;
    bandInfos[1].highlight = true;

    tl = Timeline.create(document.getElementById("my-timeline"), bandInfos);
//  Timeline.loadXML("example.xml", function(xml, url) { eventSource.loadXML(xml, url); });
    tl.loadJSON("jsonized.php?id=" + tcountid + "&user=" + user, function (json, url) {
        eventSource.loadJSON(json, url);
    });
}