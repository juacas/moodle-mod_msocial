var url = window.location.href;
var baseurl = url.substring(0,url.indexOf('/mod/msocial'));
requirejs.config({
    paths: {
        "msocialview/table": baseurl+'/mod/msocial/view/table/js/viewlib',
        "datatables.net": baseurl+'/mod/msocial/view/table/js/dataTables',
        "datatables.colReorder": baseurl+'/mod/msocial/view/table/js/dataTables.colReorder',
        "datatables.fixedHeader": baseurl+'/mod/msocial/view/table/js/dataTables.fixedHeader',
        "datatables.responsive": baseurl+'/mod/msocial/view/table/js/dataTables.responsive',
        "datatables.net-buttons": baseurl+'/mod/msocial/view/table/js/dataTables.buttons',
        "datatables.colVis": baseurl+'/mod/msocial/view/table/js/buttons.colVis',
    },
    waitSeconds: 20
});