var url = window.location.href;
var baseurl = url.substring(0,url.indexOf('/mod/msocial'));

requirejs.config({
    paths: {	
		'd3': baseurl+'/mod/msocial/view/drops/js/d3.v4.min',
		'event-drops': baseurl+'/mod/msocial/view/drops/js/eventDrops.min',
		'msocialview/drops': baseurl+'/mod/msocial/view/drops/js/viewdropslib',
    },
    waitSeconds: 20
});