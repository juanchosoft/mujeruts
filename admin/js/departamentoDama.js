$(document).on('ready', init);
var q;

document.addEventListener("DOMContentLoaded", function() {
    const departamentoSelect = document.getElementById("tbl_departamento_id");
    if (departamentoSelect) {
        departamentoSelect.addEventListener("change", function() {
            DEPARTAMENTO.getMunicipios();
        });
    }
});


function init() {
    q = {};
}

var DEPARTAMENTO = {
    emptyTable: function() {
        $("#tableFacArmado").empty().append("");
    },

    // cuando se le manda el municipio y DEPARTAMENTO
    getMunicipiosConParametros: function() {

        if (depSelect != "") {
            $("#tbl_departamento_id").val(depSelect);
        }
        $("#tbl_departamento_id").val(depSelect).trigger('change');

        if (depSelect != "seleccione") {
            q = {};
            q.op = "ciudadget";
            q.codigo_departamento = depSelect;
            UTIL.callAjaxRqstPOST(q, this.getMunicipiosHandler);
            DEPARTAMENTO.emptyTable();
        } else {
            $("#tbl_municipio_id").empty().append('');
        }
    },
    getMunicipios: function() {
        if ($("#tbl_departamento_id").val() != "seleccione") {
            q = {};
            q.op = "ciudadget";
            q.codigo_departamento = $("#tbl_departamento_id").val();
            UTIL.callAjaxRqstPOST(q, this.getMunicipiosHandler);
            DEPARTAMENTO.emptyTable();
        } else {
            $("#tbl_municipio_id").empty().append('');
        }
    },
    getMunicipiosHandler: function(data) {
        var depto = $("#tbl_departamento_id").val();
        UTIL.cursorNormal();
        if (data.output.valid) {
            var res = data.output.response;
            var info = '';
            for (var j in res) {

                var rutaMapa = res[j].carpeta_mapa;

                if (rutaMapa != null) {
                    rutaMapa     = rutaMapa.replace("mapa-veredas/", "mapa-veredas/" + depto + "/");
                }


                if (typeof munSelect != "undefined" && munSelect == res[j].codigo_muncipio) {
                    info += "<option value=" + res[j].codigo_muncipio + " data-mapa='" + rutaMapa + "' selected>" + res[j].municipio + "</option>";
                } else {
                    info += "<option value=" + res[j].codigo_muncipio + " data-mapa='" + rutaMapa + "' >" + res[j].municipio + "</option>";
                }
            }
            $("#tbl_municipio_id").empty().append(info);

            if ($("#filtro").val() === 'vereda') {
                DEPARTAMENTO.getVeredasByMunicipioId();
            }
        } else {
            UTIL.mostrarMensajeError(data.output.response.content);
        }
    },

    getVeredasByMunicipioId: function(changeValue = false) {
        if(changeValue){
            let urlParts = URLToArray(ACTUAL_URL);
            urlParts.mun = $("#tbl_municipio_id").val();

            var newURL = window.location.href.split('?')[0]+"?"+$.param(urlParts);
            location.href = newURL;
        }

        if ($("#tbl_departamento_id").val() != "seleccione") {
            q = {};
            q.op = "veredaget";
            q.municipio_id = $("#tbl_municipio_id").val();
            UTIL.callAjaxRqstPOST(q, this.getVeredasByMunicipioIdHandler);

            DEPARTAMENTO.emptyTable();

        } else {
            $("#tbl_vereda_id").empty().append('');
        }
    },
    getVeredasByMunicipioIdHandler: function(data) {
        UTIL.cursorNormal();
        if (data.output.valid) {
            var res = data.output.response;
            var info = '';
            for (var j in res) {
                // Ingreso de informacion y actualización - trabajando con el id de la vereda y no el nombre
                if ($("#filtroVeredaById").val() === 'si') {
                    info += "<option value='" + res[j].id + "'>" + res[j].nombre_vereda + "</option>";
                } else {
                    if (typeof veredaSelect != "undefined" && veredaSelect == res[j].nombre_vereda) {
                        info += "<option value='" + res[j].nombre_vereda + "' selected>" + res[j].nombre_vereda + "</option>";
                    } else {
                        info += "<option value='" + res[j].nombre_vereda + "'>" + res[j].nombre_vereda + "</option>";
                    }
                }
            }
            $("#tbl_vereda_id").empty().append(info);

            if ($("#factoresInestabilidad").val() === 'si') {
                ESTADO.getFactores();
            }

        } else {
            UTIL.mostrarMensajeError(data.output.response.content);
        }
    },
};