requirejs.config({
    paths: {
    	d3: '/mod/msocial/view/graph/js/d3/d3.v3',
		"dot-checker": '/mod/msocial/view/graph/js/graphviz-d3-renderer/dist/dot-checker',
		"layout-worker": '/mod/msocial/view/graph/js/graphviz-d3-renderer/dist/layout-worker',
		worker: '/mod/msocial/view/graph/js/requirejs-web-workers/src/worker',
		renderer: '/mod/msocial/view/graph/js/graphviz-d3-renderer/dist/renderer',
		'msocialview/graphviz': '/mod/msocial/view/graph/js/graphviz',
    },
    waitSeconds: 20
});