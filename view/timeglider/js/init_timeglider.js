
function init_timeglider(Y, tcountid, user) {
	deferred_init_timeglider(Y, tcountid, user);
}
function deferred_init_timeglider(Y, tcountid, user) {
	$(document).ready(function () { 

		var tg1 = $("#my-timeglider").timeline({
				"min_zoom":1, 
				"max_zoom":40, 
				"data_source":"view/timeglider/jsonized.php?id="+tcountid,
				"icon_folder":""
		});
		
    }); // end document-ready
	
}
