var UTIL = {
    getDepartamentoPrincipal: function() {
        return "68";
    },
    getItemHacienda: function() {
        return 4;
    },
    mostrarMensajeError: function(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: mensaje
        });
    },
    mostrarMensajeExitoso: function(mensaje) {
        Swal.fire({
            icon: 'success',
            title: '',
            text: mensaje
        });
    },
    mostrarMensajeValidacion: function(mensaje) {
        Swal.fire({
            icon: 'warning',
            title: 'Importante!',
            text: mensaje
        });
    },

    validarCampos: function(campos) {
        for (const campo of campos) {
            if ($(campo).val() === "" || $(campo).val() === "seleccione") {
                return false;
            }
        }
        return true;
    },

    /**
     * Metodo para obtener los parametros de la URL para Departamento, Municipio y Vereda
     * @returns {JSON} {mun, dep}
     */
    getParamsFromUrlDepartamentoMunicipio: function() {
        const urlParams = new URLSearchParams(window.location.search);
        const mun = urlParams.get('mun');
        const dep = urlParams.get('dep');
        const vereda = urlParams.get('id');
        const pilar = urlParams.get('pilar');
        return { mun, dep, vereda, pilar };
    },

    /**
     * Carga datepicker de jQueryUI
     * @param {String} id, id del campo
     */
    applyDatepicker: function(id) {
        $("#" + id).datepicker($.datepicker.regional["es"]);
        $("#" + id).datepicker({ changeMonth: true, changeYear: true });
        $("#" + id).datepicker("option", "dateFormat", "yy-mm-dd");
    },

    getPorcentajeIva: function(porcentaje) {
        if (parseInt(porcentaje) > 0) {
            switch (parseInt(porcentaje)) {
                case 19:
                    iva = 1.19;
                    break;
                case 10:
                    iva = 1.1;
                    break;
                case 5:
                    iva = 1.05;
                    break;
            }
            return iva;
        }
    },

    /**
     * Hace request por AJAX
     * @param {JSON} ladata, paramétros del request
     * @param {function} successCallBackFn, función que captura la respuesta onSuccess
     */
    callAjaxRqst: function(data, successCallBackFn) {
        this.cursorBusy();
        $.ajax({
            data: data,
            type: "GET",
            dataType: "json",
            url: "admin/ajax/rqst.php",
            success: successCallBackFn,
        });
    },
    /**
     * Hace request por AJAX
     * @param {JSON} ladata, paramétros del request
     * @param {function} successCallBackFn, función que captura la respuesta onSuccess
     */
    callAjaxRqstPOST: function(data, successCallBackFn) {
        this.cursorBusy();
        $.ajax({
            data: data,
            type: "POST",
            dataType: "json",
            url: "admin/ajax/rqst.php",
            success: successCallBackFn,
        });
    },
    /**
     * Limppia un formulario
     * @param {String} id, id del formulario
     */
    clearForm: function(id) {
        $("#" + id + " :input").each(function() {
            if ('button' != $(this).attr('type')) {
                $(this).val('');
            }
        });
        $('select').val('seleccione');

        //Removing the error elements from the from-group,For bootstrapvalidator, this might useful when the form being display via bootstrap modal,
        // Extracción de los elementos de error de un grupo
        $('.form-group').removeClass('has-error has-feedback');
        $('.form-group').find('small.help-block').hide();
        $('.form-group').find('i.form-control-feedback').hide();
    },
    clearForm2: function(id) {
        $("#" + id + " :input").each(function() {
            if ('button' != $(this).attr('type')) {
                $(this).val('');
            }
        });
    },
    /**
     * Pone el cursor ocupado
     */
    cursorBusy: function() {
        $('body').css('cursor', 'wait');
    },
    /**
     * Pone el cursor normal
     */
    cursorNormal: function() {
        $('body').css('cursor', '');
    },
    /**
     * Verifica que un correo esta bien escrito
     * @param {String} email
     * @returns {bool},
     */
    isEmail: function(email) {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    },
    parcejson: function() {
        var str = '{\"registro_fecha\":\"2013-02-10\",\"registro_sistema\":\"nombre sistema\",\"registro_actividad\":\"4\",\"undefined\":\"CARGAR\",\"registro_campo01\":\"2 horas\",\"registro_campo02\":\"0\",\"registro_campo03\":\"2013-02-12\",\"registro_campo04\":\"1\",\"registro_campo05\":\"0\",\"registro_campo06\":\"cerrado autom","nota":"las notas van aqu","fecha":"2013-02-10","tsfecha":"2013-02-10 08:23:13"}';
        var obj = JSON.parse(str);
        alert('registro_fecha1 = ' + obj.registro_fecha);
        alert('registro_fecha2 = ' + obj["registro_fecha"]);
        $("#registro :input").each(function() {
            var idprop = $(this).attr('id');
            if (obj.hasOwnProperty(idprop)) {
                $(this).val(obj[idprop]);
            }
        });
    },
    /**
     * Llena un formulario con un objeto JSON
     * @param {String} id
     * @param {JSON} jo
     */
    populateForm: function(id, jo) {
        $("#" + id + " :input").each(function() {
            var p = $(this).attr('id');
            if (jo.hasOwnProperty(p)) {
                $(this).val(jo[p]);
            }
        });
    },
    /**
     * Carga un estilo a un campo
     * @param {type} id
     */
    setrequirefield: function(id) {
        $("#" + id).addClass("requirefield");
    },
    /**
     * convierte los campos de un formulario en StringJSON
     * @param {type} id, id del formulario
     * @returns {String}, JSON en forma de String
     */
    stringifyFormJson: function(id) {
        var jo = {};
        $("#" + id + " :input").each(function() {
            jo[$(this).attr('id')] = $(this).val();
        });
        return JSON.stringify(jo);
    }
}

// elementos de la lista
var menues = $(".nav li a");
// https://es.stackoverflow.com/questions/3642/agregar-clase-active-en-menu-bootstrap-con-jquery
// manejador de click sobre todos los elementos
menues.click(function() {
    // eliminamos active de todos los elementos
    menues.removeClass("active");
    // activamos el elemento clicado.
    $(this).addClass("active");
});
// funciones para usar con jQueryUI

function updateTips(t) {
    tips.text(t).addClass("ui-state-highlight");
    setTimeout(function() {
        tips.removeClass("ui-state-highlight", 1500);
    }, 500);
}

function checkLength(o, n, min, max) {
    if (o.val().length > max || o.val().length < min) {
        o.addClass("ui-state-error");
        updateTips("Longitud de " + n + " debe estar entre " +
            min + " y " + max + ".");
        return false;
    } else {
        return true;
    }
}

function checkRegexp(o, regexp, n) {
    if (!(regexp.test(o.val()))) {
        o.addClass("ui-state-error");
        updateTips(n);
        return false;
    } else {
        return true;
    }
}

function IsNumberOnly(element) {
    var value = $(element).val();
    if (value === undefined) {
        return null;
    }
    var regExp = "^\\d+$";
    return value.match(regExp);
}

function noPuntoComa(event) {
    var e = event || window.event;
    var key = e.keyCode || e.which;
    if (key === 44 || key === 110 || key === 190 || key === 188) {
        e.preventDefault();
    }
}