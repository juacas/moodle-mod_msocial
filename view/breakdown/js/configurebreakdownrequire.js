var url = window.location.href;
var baseurl = url.substring(0,url.indexOf('/mod/msocial'));

requirejs.config({
    paths: {	
		'd3': baseurl+'/mod/msocial/view/breakdown/js/d3.v5.min',
		'msocialview/breakdown': baseurl+'/mod/msocial/view/breakdown/js/viewbreakdownlib',
    },
    waitSeconds: 20
});