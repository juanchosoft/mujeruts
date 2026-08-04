$(document).on('ready', init);
var q;
function init() {
    q = {};
}
var return_page = 'configuracion.php';
var CONFIGURACION = {
    editdata: function () {
        q = {};
        q.op = "pms_getconf";
        UTIL.callAjaxRqstPOST(q, this.editdatahandler);
    },
    editdatahandler: function (data) {
        UTIL.cursorNormal();
        if (data.output.valid) {
            var res = data.output.response[0];
            $("#idConfig").val(res.id);
            $("#tipo_configuracion_colores").val(res.tipo_configuracion_colores).trigger("change");
            $("#comentarios").val(res.comentarios).trigger("change");
        } else {
            UTIL.mostrarMensajeError(data.output.response.content);
        }
    },
    successMessage: function () {
        UTIL.mostrarMensajeExitoso('Información guardada correctamente');
        setTimeout(function () {
            window.location = return_page;
        }, 1000);
    },
    savedata: function () {
        q = {};
        q.op = "pms_confsave";
        q.id = $("#idConfig").val();
        q.comentarios = $("#comentarios").val();
        q.tipo_configuracion_colores = $("#tipo_configuracion_colores").val();
        UTIL.callAjaxRqstPOST(q, CONFIGURACION.savedataHandler);
    },
    savedataHandler: function (data) {
        UTIL.cursorNormal();
        if (data.output.valid) {
            CONFIGURACION.successMessage();
        } else {
            UTIL.mostrarMensajeError(data.output.response.content);
        }
    },
};