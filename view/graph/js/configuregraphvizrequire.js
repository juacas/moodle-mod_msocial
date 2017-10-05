var url = window.location.href;
var baseurl = url.substring(0,url.indexOf('/mod/msocial'));

requirejs.config({
    paths: {
    	d3: baseurl+'/mod/msocial/view/graph/js/d3/d3.v3',
		"dot-checker": baseurl+'/mod/msocial/view/graph/js/graphviz-d3-renderer/dist/dot-checker',
		"layout-worker": baseurl+'/mod/msocial/view/graph/js/graphviz-d3-renderer/dist/layout-worker',
		worker: baseurl+'/mod/msocial/view/graph/js/requirejs-web-workers/src/worker',
		renderer: baseurl+'/mod/msocial/view/graph/js/graphviz-d3-renderer/dist/renderer',
		'msocialview/graphviz': baseurl+'/mod/msocial/view/graph/js/viewgraphvizlib',
		'svg-pan-zoom' : baseurl+'/mod/msocial/view/graph/js/svg-pan-zoom',
		'hammer' : baseurl+'/mod/msocial/view/graph/js/hammer',
		'saveSvgAsPng' : baseurl+'/mod/msocial/view/graph/js/saveSvgAsPng',
    },
    waitSeconds: 20
});