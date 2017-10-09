var url = window.location.href;
var baseurl = url.substring(0,url.indexOf('/mod/msocial'));

requirejs.config({
    paths: {
    	
		'vis': baseurl+'/mod/msocial/view/graph/js/vis',
		'msocialview/graphvis': baseurl+'/mod/msocial/view/graph/js/viewgraphvislib',
		'svg-pan-zoom' : baseurl+'/mod/msocial/view/graph/js/svg-pan-zoom',
		'hammer' : baseurl+'/mod/msocial/view/graph/js/hammer',
		'saveSvgAsPng' : baseurl+'/mod/msocial/view/graph/js/saveSvgAsPng',
    },
    waitSeconds: 20
});