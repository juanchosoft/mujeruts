function soloNumeros(e) {
    key = e.keyCode || e.which;
    tecla = String.fromCharCode(key).toLowerCase();
    letras = "0123456789";
    especiales = [8, 37, 39, 46];

    tecla_especial = false
    for (var i in especiales) {
        if (key == especiales[i]) {
            tecla_especial = true;
            break;
        }
    }

    if (letras.indexOf(tecla) == -1 && !tecla_especial)
        return false;
}

function soloLetras(e) {
    key = e.keyCode || e.which;
    tecla = String.fromCharCode(key).toLowerCase();
    letras = " áéíóúabcdefghijklmnñopqrstuvwxyz";
    especiales = [8, 37, 39, 46];

    tecla_especial = false
    for (var i in especiales) {
        if (key == especiales[i]) {
            tecla_especial = true;
            break;
        }
    }

    if (letras.indexOf(tecla) == -1 && !tecla_especial) {
        return false;
    }
}

//Valdiar que no me ingrese caracteres especiales, y me deje ingresar @
function caracteres_email(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla == 8)
        return true;
    if (tecla == 9)
        return true;
    if (tecla == 11)
        return true;
    patron = /[A-Za-zñÑáéíóúÁÉÍÓÚàèìòùÀÈÌÒÙâêîôûÂÊÎÔÛÑñäëïöüÄËÏÖÜ1234567890_@.-\s\t]/;
    te = String.fromCharCode(tecla);
    return patron.test(te);
}

function sololetras_numeros(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla==8) return true; 
	 if (tecla==9) return true; 
	 if (tecla==11) return true; 
    patron = /[A-Za-zñÑáéíóúÁÉÍÓÚàèìòùÀÈÌÒÙâêîôûÂÊÎÔÛÑñäëïöüÄËÏÖÜ1234567890\s\t]/;

    te = String.fromCharCode(tecla);
    return patron.test(te);
}
//No acepta las ñÑ y tildes por incoveniente en placetopay con este caracteres
function Sololetrasnew(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla==8) return true; 
	 if (tecla==9) return true; 
	 if (tecla==11) return true; 
    patron = /[A-Za-zâêîôûÂÊÎÔÛäëïöüÄËÏÖÜ\s\t]/;

    te = String.fromCharCode(tecla);
    return patron.test(te);
}
