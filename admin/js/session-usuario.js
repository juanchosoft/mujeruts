$(function () {
  cargaData();
  function cargaData() {
    tablaSession = $("#dynamictable").DataTable({
      order: [[0, "desc"]],
      dom: "lrtip",
      processing: true,
      serverSide: true,
      responsive: true,
      columnDefs: [
        {
          targets: ["_all"],
          className: "mdc-data-table__cell",
        },
      ],
      ajax: {
        url: "./admin/controllers/usuarioCtrl.php",
        type: "POST",
        contentType: "application/json",
        data: function (d) {
          return JSON.stringify({
            method: "getAllInicioSession",
            data: d,
          });
        },
      },
      columns: [
        {
          data: "id",
        },
        {
          data: "dtcreate",
        },
        {
          data: "nickname",
        },
        {
          data: "nombre_completo",
        },
        {
          data: "ip",
        },
        {
          data: "navegador",
        },
      ],
    });
    $("#customSearch")
      .off()
      .on("keyup", function () {
        if (tablaSession) {
          tablaSession.search(this.value).draw();
        }
      });
  }
});
