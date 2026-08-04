$(document).on("ready", init);
var q, nombre, allFields, tips;
/**
 * se activa para inicializar el documento
 */
function init() {
    q = {};
    // No permitir cerrar el modal, click afuera
    $("#myModalPermisos").modal({ backdrop: "static", keyboard: false });
}

var PERMISOS = {
    editpermission: function(id) {
        q = {};
        q.op = "pms_usrpermission";
        q.id = id;
        $("#id").val(id);
        UTIL.callAjaxRqstPOST(q, this.editpermissionhandler);
    },
    editpermissionhandler: function(data) {
        UTIL.cursorNormal();
        if (data.output.valid) {
            var ava = data.output.available;
            var ass = data.output.assigned;
            var chks = "";
            for (var i in ava) {
                chks += "<tr>";
                chks += "<td>";
                chks += '<div class="form-check">';
                chks += '<label class="form-check-label">';
                chks +=
                    '<input class="form-check-input" type="checkbox" value="' +
                    ava[i].id +
                    '" name="chk' +
                    ava[i].id +
                    '" id="chk' +
                    ava[i].id +
                    '">';
                chks += '<span class="form-check-sign">';
                chks += '<span class="check"></span>';
                chks += "</span>";
                chks += "</label>";
                chks += "</div>";
                chks += "</td>";
                chks += "<td>" + ava[i].id + " - " + ava[i].nombre + "</td>";
                chks += "</tr>";
            }
            $("#permission").empty();
            $("#permission").append(chks);
            $("#formpermission :input").each(function() {
                var p = $(this).attr("id");
                for (var j in ass) {
                    var idchk = "chk" + ass[j].tbl_permiso_id;
                    if (p == idchk) {
                        $(this).attr("checked", "true");
                    }
                }
            });
            $("#myModalPermisos").modal('show');

        } else {
            swal("warning", data.output.response.content, "error");
        }
    },
    getSeleccionarPermisos: function() {
        if ($("#check_permisos").is(":checked")) {
            $("#formpermission :input").each(function() {
                this.checked = true;
            });
        } else {
            $("#formpermission :input").each(function() {
                this.checked = false;
            });
        }
    },
    savepermission: function() {
        var chk = "";
        var inputs = document
            .getElementById("formpermission")
            .getElementsByTagName("input"); // get element by tag name
        for (var i in inputs) {
            if (inputs[i].type == "checkbox") {
                if ($("#" + inputs[i].id).is(":checked")) {
                    chk += $("#" + inputs[i].id).val() + "-";
                }
            }
        }
        q.op = "pms_usrsavepermission";
        q.chk = chk;
        UTIL.callAjaxRqstPOST(q, this.savepermissionhandler);
    },
    checkAll: function() {
        if ($("#check_permisos").is(":checked")) {
            $("#formpermission :input").each(function() {
                this.checked = true;
            });
        } else {
            $("#formpermission :input").each(function() {
                this.checked = false;
            });
        }
    },
    /**
     * Método para guardar los permisos
     */
    savepermission: function() {
        var chk = "";
        var inputs = document
            .getElementById("formpermission")
            .getElementsByTagName("input"); // get element by tag name
        for (var i in inputs) {
            if (inputs[i].type == "checkbox") {
                if ($("#" + inputs[i].id).is(":checked")) {
                    chk += $("#" + inputs[i].id).val() + "-";
                }
            }
        }
        q.op = "pms_usrsavepermission";
        q.chk = chk;
        UTIL.callAjaxRqstPOST(q, this.savepermissionhandler);
    },
    savepermissionhandler: function(data) {
        UTIL.cursorNormal();
        if (data.output.valid) {
            UTIL.mostrarMensajeExitoso('Permisos asignados correctamente');
            setTimeout(function() {
                window.location = return_page;
            }, 1000);
        } else {
            UTIL.mostrarMensajeError(data.output.response.content);
        }

    },
};