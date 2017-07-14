requirejs.config({
    paths: {
    	d3: '/mod/tcount/view/graph/js/d3/d3',
		"dot-checker": '/mod/tcount/view/graph/js/graphviz-d3-renderer/dist/dot-checker',
		"layout-worker": '/mod/tcount/view/graph/js/graphviz-d3-renderer/dist/layout-worker',
		worker: '/mod/tcount/view/graph/js/requirejs-web-workers/src/worker',
		renderer: '/mod/tcount/view/graph/js/graphviz-d3-renderer/dist/renderer',
		'tcountview/graphviz': '/mod/tcount/view/graph/js/graphviz',
    },
    waitSeconds: 20
});