<?php
require './admin/include/generic_classes.php';
include './admin/classes/Secretarias.php';
$modulo = 'Banco Proyectos';


// Información de secretarias
$arr = Secretarias::getAllproyectos(null);
$isvalid = $arr['output']['valid'];
$arr = $arr['output']['response'];
$arrData = $arr;

?>

<?php include 'admin/include/head.php'; ?>
<!-- Bootstrap CSS -->

    <!-- DataTables Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.3/css/dataTables.bootstrap4.min.css">
    <!-- DataTables Select Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/select/2.0.0/css/select.bootstrap4.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <!-- Popper.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/2.0.3/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.3/js/dataTables.bootstrap4.js"></script>

    <!-- DataTables Select -->
    <script src="https://cdn.datatables.net/select/2.0.0/js/dataTables.select.js"></script>
    <script src="https://cdn.datatables.net/select/2.0.0/js/select.bootstrap4.js"></script>



<body>
    <!-- ============================================================== -->
    <!-- main wrapper -->
    <!-- ============================================================== -->
    <div class="dashboard-main-wrapper">
       <!-- navbar -->
        <!-- ============================================================== -->
        <?php include 'admin/include/navbar.php'; ?>
        <!-- ============================================================== -->
        <!-- end navbar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- left sidebar -->
        <!-- ============================================================== -->
        <?php include 'admin/include/sidebar.php'; ?>
        <!-- ============================================================== -->
        <!-- end left sidebar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- wrapper  -->
        <!-- ============================================================== -->
        <div class="dashboard-wrapper">
            <div class="container-fluid dashboard-content">
                <!-- ============================================================== -->
                <!-- pageheader -->
                <!-- ============================================================== -->
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="page-header">
                            <h2 class="pageheader-title">Detalle Proyectos Secretarias </h2>
                            <p class="pageheader-text"></p>
                            <div class="page-breadcrumb">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Inicio</a></li>
                                        <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Paginas</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Proyectos Secretarias</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ============================================================== -->
                <!-- end pageheader -->
                <!-- ============================================================== -->
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                    <table id="example" class="table table-striped table-bordered" style="width:100%">
                <thead>
                  <tr>
                    <th>Ver</th>
                    <th>Secretaria</th>
                    <th>Suma Proyectos</th>
                  
                  </tr>
                </thead>
                <?php
                          $c = count($arr);
                          if ($isvalid) {
                            for ($i = 0; $i < $c; $i++) {
                             
                              ?>
                    <tr>
                      <td><a href="proyecto_x_secretaria.php?id=<?php echo $arrData[$i]['tbl_secretarias_id']; ?>&secretaria=<?php echo $arrData[$i]['tbl_secretarias_id']; ?>" data-toggle="tooltip" title="Ver"><i class="fas fa-eye"></i></a></td>
                        <td> <?php echo $arr[$i]['secretaria']; ?></td>
                        <td>$ <?php echo number_format($arr[$i]['sumaproyectos']); ?></td>
                        
                     </td>
                    </tr>
                    <?php
                            }
                          }
                          ?>
                  </tbody>
	                                    </table>
                    </div>
                </div>
            </div>
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <?php include 'admin/include/footer.php'; ?>
            <?php include 'admin/include/gerenic_script.php'; ?>
            <script>
                new DataTable('#example', {
                select: true
                });
            </script>
