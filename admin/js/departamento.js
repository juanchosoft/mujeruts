$(document).on("ready", init);

var q = {};
var informacionMunicipio = {};

function init() {
  q = {};
}

var DEPARTAMENTO = {
  getMunicipiosConParametros: function () {
    if (typeof depSelect !== "undefined" && depSelect !== "") {
      $("#tbl_departamento_id").val(depSelect).trigger("change");
    }

    var departamentoId = $("#tbl_departamento_id").val();

    if (departamentoId && departamentoId !== "seleccione") {
      q = {};
      q.op = "ciudadget";
      q.codigo_departamento = departamentoId;
      UTIL.callAjaxRqstPOST(q, this.getMunicipiosHandler);
    } else {
      $("#tbl_municipio_id").empty().append('<option value="">Seleccione</option>');
    }
  },

  getMunicipiosConDepartamentoPrincipal: function () {
    let q = {
      op: "ciudadget",
      codigo_departamento: UTIL.getDepartamentoPrincipal(),
    };

    UTIL.cursorBusy();

    $.ajax({
      data: q,
      type: "GET",
      dataType: "json",
      url: "admin/ajax/rqst.php",
      success: function (data) {
        UTIL.cursorNormal();

        if (!data.output.valid) {
          UTIL.mostrarMensajeError(data.output.response.content);
          return;
        }

        const res = data.output.response || [];
        const municipioDelUsuario = $("#municipioUsuario").length ? $("#municipioUsuario").val() : "";
        const tipoUsuario = $("#tipoUsuario").length ? $("#tipoUsuario").val() : "";
        let info = '<option value="">Seleccione</option>';

        const esAlcalde =
          tipoUsuario === "Alcalde" || tipoUsuario === "Auxiliar_Alcalde";

        res.forEach((municipio) => {
          if (esAlcalde && municipio.codigo_muncipio !== municipioDelUsuario) {
            return;
          }

          info += `<option value="${municipio.codigo_muncipio}">${municipio.municipio}</option>`;
        });

        $("#tbl_municipio_id").empty().append(info);
      },
      error: function () {
        UTIL.cursorNormal();
        UTIL.mostrarMensajeError("Error al obtener municipios.");
      },
    });
  },

  getMunicipios: function () {
    var departamentoId = $("#tbl_departamento_id").length ? $("#tbl_departamento_id").val() : "";

    if (departamentoId && departamentoId !== "seleccione") {
      q = {};
      q.op = "ciudadget";
      q.codigo_departamento = departamentoId;
      UTIL.callAjaxRqstPOST(q, this.getMunicipiosHandler);
    } else {
      $("#tbl_municipio_id").empty().append('<option value="">Seleccione</option>');
    }
  },

  getMunicipiosHandler: function (data) {
    const depto = $("#tbl_departamento_id").length ? $("#tbl_departamento_id").val() : "";
    const municipioDelUsuario = $("#municipioUsuario").length ? $("#municipioUsuario").val() : "";
    const tipoUsuario = $("#tipoUsuario").length ? $("#tipoUsuario").val() : "";
    const filtro = $("#filtro").length ? $("#filtro").val() : "";

    UTIL.cursorNormal();

    if (data.output.valid) {
      const res = data.output.response || [];
      let info = `<option value="">Seleccione</option>`;

      const generarOpcion = (municipio, rutaMapa, seleccionado) => {
        return `<option value="${municipio.codigo_muncipio}" data-mapa="${rutaMapa || ''}" ${seleccionado}>${municipio.municipio}</option>`;
      };

      res.forEach((municipio) => {
        let rutaMapa = municipio.carpeta_mapa
          ? municipio.carpeta_mapa.replace("mapa-veredas/", `mapa-veredas/${depto}/`)
          : "";

        if (
          (tipoUsuario === "Alcalde" || tipoUsuario === "Auxiliar_Alcalde") &&
          municipioDelUsuario &&
          municipioDelUsuario !== municipio.codigo_muncipio
        ) {
          return;
        }

        const seleccionado =
          typeof munSelect !== "undefined" && munSelect == municipio.codigo_muncipio
            ? "selected"
            : "";

        info += generarOpcion(municipio, rutaMapa, seleccionado);
      });

      $("#tbl_municipio_id").empty().append(info);

      if (filtro === "vereda") {
        const municipioVal = $("#tbl_municipio_id").val();
        if (municipioVal && municipioVal !== "seleccione") {
          DEPARTAMENTO.getVeredasByMunicipioId();
        }
      }
    } else {
      UTIL.mostrarMensajeError(data.output.response.content);
    }
  },

  getVeredasByMunicipioIdInformacionMapa: function (municipioId, veredaIdASetar = 0) {
    q = {};
    q.op = "veredaget";
    q.municipio_id = municipioId;

    UTIL.cursorBusy();
    $.ajax({
      data: q,
      type: "GET",
      dataType: "json",
      url: "admin/ajax/rqst.php",
      success: function (data) {
        q = {};
        UTIL.cursorNormal();

        if (data.output.valid) {
          var info = "";
          var res = data.output.response || [];

          for (var j in res) {
            info +=
              "<option value='" +
              res[j].id +
              "' selected>" +
              res[j].nombre_vereda +
              "</option>";
          }

          $("#tbl_vereda_id").empty().append(info);

          if (veredaIdASetar && veredaIdASetar > 0) {
            $("#tbl_vereda_id").val(veredaIdASetar).trigger("change");
          }
        } else {
          UTIL.mostrarMensajeError(data.output.response.content);
        }
      },
    });
  },

  getVeredasByMunicipioId: function (changeValue = false) {
    if (changeValue) {
      let urlParts = URLToArray(ACTUAL_URL);
      urlParts.mun = $("#tbl_municipio_id").val();

      var newURL = window.location.href.split("?")[0] + "?" + $.param(urlParts);
      location.href = newURL;
      return;
    }

    var departamentoId = $("#tbl_departamento_id").length ? $("#tbl_departamento_id").val() : "";
    var municipioId = $("#tbl_municipio_id").length ? $("#tbl_municipio_id").val() : "";

    if (departamentoId && departamentoId !== "seleccione" && municipioId) {
      q = {};
      q.op = "veredaget";
      q.municipio_id = municipioId;
      UTIL.callAjaxRqstPOST(q, this.getVeredasByMunicipioIdHandler);
    } else {
      $("#tbl_vereda_id").empty().append('<option value="">Seleccione</option>');
    }
  },

  getVeredasByMunicipioIdHandler: function (data) {
    informacionMunicipio = {};
    UTIL.cursorNormal();

    if (data.output.valid) {
      var res = data.output.response || [];
      var info = "<option value=''>Seleccione</option>";
      var filtroVeredaById = $("#filtroVeredaById").length ? $("#filtroVeredaById").val() : "";

      if (data.output.municipio && data.output.municipio.length > 0) {
        informacionMunicipio = data.output.municipio[0];
      }

      for (var j in res) {
        if (filtroVeredaById === "si") {
          info +=
            "<option value='" +
            res[j].id +
            "'>" +
            res[j].nombre_vereda +
            "</option>";
        } else {
          if (
            typeof veredaSelect !== "undefined" &&
            veredaSelect == res[j].nombre_vereda
          ) {
            info +=
              "<option value='" +
              res[j].nombre_vereda +
              "' selected>" +
              res[j].nombre_vereda +
              "</option>";
          } else {
            info +=
              "<option value='" +
              res[j].nombre_vereda +
              "'>" +
              res[j].nombre_vereda +
              "</option>";
          }
        }
      }

      $("#tbl_vereda_id").empty().append(info);
    } else {
      UTIL.mostrarMensajeError(data.output.response.content);
    }
  },

  getMunicipiosByDepartamentoIdV2SeteraCodigoMunicipio: function (departamentoId, municipioSetear = 0) {
    UTIL.cursorBusy();

    $.ajax({
      url: "admin/ajax/rqst.php",
      type: "GET",
      dataType: "json",
      data: {
        op: "ciudadget",
        codigo_departamento: departamentoId,
      },
      success: function (data) {
        UTIL.cursorNormal();

        if (!data.output.valid) {
          UTIL.mostrarMensajeError(data.output.response.content);
          return;
        }

        const res = data.output.response || [];
        const $municipioSelect = $("#tbl_municipio_id");

        const opciones =
          '<option value="">Seleccione</option>' +
          res
            .map(
              (m) =>
                `<option value="${m.codigo_muncipio}">${m.municipio}</option>`
            )
            .join("");

        $municipioSelect.empty().append(opciones);

        if (municipioSetear) {
          setTimeout(function () {
            $municipioSelect.val(municipioSetear).trigger("change");
          }, 10);
        }
      },
      error: function () {
        UTIL.cursorNormal();
        UTIL.mostrarMensajeError("Error al obtener municipios.");
      },
    });
  },

  getMunicipiosOpcionSelectTodos: function () {
    let q = {
      op: "ciudadget",
      codigo_departamento: UTIL.getDepartamentoPrincipal(),
    };

    UTIL.cursorBusy();

    $.ajax({
      data: q,
      type: "GET",
      dataType: "json",
      url: "admin/ajax/rqst.php",
      success: function (data) {
        UTIL.cursorNormal();

        if (!data.output.valid) {
          UTIL.mostrarMensajeError(data.output.response.content);
          return;
        }

        const res = data.output.response || [];
        const depto = $("#tbl_departamento_id").length ? $("#tbl_departamento_id").val() : "";
        const municipioDelUsuario = $("#municipioUsuario").length ? $("#municipioUsuario").val() : "";
        const tipoUsuario = $("#tipoUsuario").length ? $("#tipoUsuario").val() : "";
        const $municipio = $("#tbl_municipio_id");
        let optionsHtml = '<option value="">Seleccione</option>';

        if (
          tipoUsuario === "Secretario_Despacho" ||
          tipoUsuario === "Administrador" ||
          tipoUsuario === "SuperAdministrador"
        ) {
          optionsHtml += `<option value="todos">Todos</option>`;
        }

        const generarOpcion = (municipio, rutaMapa, seleccionado) =>
          `<option value="${municipio.codigo_muncipio}" data-mapa="${rutaMapa || ''}" ${seleccionado}>${municipio.municipio}</option>`;

        res.forEach((municipio) => {
          if (
            (tipoUsuario === "Alcalde" || tipoUsuario === "Auxiliar_Alcalde") &&
            municipioDelUsuario &&
            municipioDelUsuario !== municipio.codigo_muncipio
          ) {
            return;
          }

          const rutaMapa = municipio.carpeta_mapa
            ? municipio.carpeta_mapa.replace("mapa-veredas/", `mapa-veredas/${depto}/`)
            : "";

          const seleccionado =
            typeof munSelect !== "undefined" && munSelect == municipio.codigo_muncipio
              ? "selected"
              : "";

          optionsHtml += generarOpcion(municipio, rutaMapa, seleccionado);
        });

        $municipio.empty().append(optionsHtml);
      },
      error: function () {
        UTIL.cursorNormal();
        UTIL.mostrarMensajeError("Error al obtener municipios.");
      },
    });
  },

  getInformacionDeMunicipioByIdMunicipio: function (codigoMunicipio) {
    if (codigoMunicipio) {
      q = {};
      q.op = "ciudadget";
      q.codigo_muncipio = codigoMunicipio;
      UTIL.callAjaxRqstPOST(q, this.getInformacionDeMunicipioByIdMunicipioHandler);
    } else {
      $("#tbl_municipio_id").empty().append("");
    }
  },

  getInformacionDeMunicipioByIdMunicipioHandler: function (data) {
    informacionMunicipio = {};
    UTIL.cursorNormal();

    if (data.output.valid) {
      informacionMunicipio = (data.output.response && data.output.response[0]) ? data.output.response[0] : {};
    } else {
      informacionMunicipio = {};
      UTIL.mostrarMensajeError(data.output.response.content);
    }
  },
};