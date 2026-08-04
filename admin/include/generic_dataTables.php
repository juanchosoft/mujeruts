      <!-- Paginaciòn -->
      <script type="text/javascript" src="admin/js/datatables/jquery.dataTables.min.js"></script>
      <link href="admin/js/datatables/jquery.dataTables.min.css" rel="stylesheet" /> 
      <!-- Fin Paginaciòn -->

      <script>
        $(document).ready(function() {
          $('#dynamictable').DataTable({
            autoWidth: true,
            columnDefs: [{
              targets: ['_all'],
              className: 'mdc-data-table__cell'
            }]
          }); 

        });
      </script>