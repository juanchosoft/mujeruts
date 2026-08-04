      <!-- Paginaciòn -->
      <script type="text/javascript" src="admin/js/datatables/jquery.dataTables.min.js"></script>
      <link href="admin/js/datatables/jquery.dataTables.min.css" rel="stylesheet" />
      <!-- Fin Paginaciòn -->

      <script>
        $(document).ready(function() {

          $('#dynamictable').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            responsive: true,
            //searching: false,
            language: {
              search: "Puedes buscar por cualquier columna:",
              searchPlaceholder: "Buscar en la tabla"
            },
            columnDefs: [{
              targets: ['_all'],
              className: 'mdc-data-table__cell',
            }],
            ajax: {
              url: './admin/controllers/alcaldiaCtrl.php',
              type: 'POST',
              contentType: 'application/json',
              data: function(d) {
                return JSON.stringify({
                  method: 'load',
                  data: d
                });
              }
            },
            columns: [{
                data: 'id',
                render: function(data, type, row) {
                  return `<button class="btn btn-sm btn-primary editar-informacion"
                    title="Editar" data-toggle="modal"
                    data-target="#modalEditarInformacion"
                    data-id="${data}"
                    data-fecha="${row.date}"
                    data-departamento="${row.departamento}"
                    data-municipio="${row.municipio}"
                    data-vereda="${row.provincia_id}"
                    data-aporte_municipio="${row.aporte_municipio}"
                    data-aporte_departamento="${row.aporte_departamento}"
                    data-aporte_nacion="${row.aporte_nacion}"
                    data-otros_aportes="${row.otro_aportes}"
                    data-secretaria="${row.tbl_secretaria_id}"
                    data-valor="${row.valor_proyecto}"
                    data-tbl_municipio_id="${row.municipio_id}"
                    data-actor_id="${row.actor_id}"
                    data-observaciones="${row.observaciones}">
                    <i class="feather icon-edit"></i>
                </button>`;
                }
              },
              {
                data: 'id',
                render: function(data) {
                  return `<button type="button" class="btn btn-sm btn-danger" title="Eliminar"
                        onclick="deleteProyecto(${data})">
                        <i class="feather icon-trash"></i>
                    </button>`;
                }
              },
              {
                data: 'date'
              },
              {
                data: 'departamento',
              },
              {
                data: 'municipio',
              },
              {
                data: 'provincia_id',
              },
              {
                data: 'valor_proyecto'
              },
              {
                data: 'nombre'
              },
              {
                data: 'secretaria'
              },
              {
                data: 'observaciones',
                render: function(data) {
                  return `<button type="button" class="btn btn-sm btn-info" title="Ver Observaciones"
                         onclick="verObservaciones('${data}')">
                         <i class="feather icon-eye"></i>
                     </button>`;
                }
              },
              {
                data: null,
                render: function(row) {
                  const fotos = [row.archivo].filter(Boolean); // solo imágenes existentes

                  if (fotos.length > 0) {
                    const fotosJson = JSON.stringify(fotos);
                    return `<button type="button" class="btn btn-sm btn-primary" title="Ver imágenes"
                            onclick='mostrarImagenes(${fotosJson})'>
                            <i class="feather icon-image"></i>
                          </button>`;
                  }
                  return `<button type="button" class="btn btn-sm btn-danger" title="No tiene Imágenes">
                        <i class="feather icon-slash"></i>
                    </button>`;
                }
              }
            ],
            createdRow: function(row, data, dataIndex) {
              $(row).attr('id', 'fila_' + data.id);
            }
          });
        });

        function mostrarImagenes(fotos) {
          const container = document.getElementById("imageContainer");
          container.innerHTML = "";

          fotos.forEach((url) => {
            const col = document.createElement("div");
            col.className = "col-6";

            const link = document.createElement("a");
            link.href = url;
            link.target = "_blank";
            link.rel = "noopener noreferrer"; // Por seguridad

            const img = document.createElement("img");
            img.src = url;
            img.alt = "Imagen";
            img.className = "img-fluid rounded border";
            img.style.maxHeight = "200px";
            img.style.objectFit = "cover";

            link.appendChild(img);
            col.appendChild(link);
            container.appendChild(col);
          });

          const modal = new bootstrap.Modal(document.getElementById("imageModal"));
          modal.show();
        }
      </script>