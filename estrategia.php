<?php
include './admin/include/head.php';
require './admin/include/generic_classes.php';
include './admin/classes/Estrategia.php';

// Permisos
$view = SessionData::getPermission(83);
$create = SessionData::getPermission(84);
$edit = SessionData::getPermission(85);
/* if (!$view) {
    require 'permiso_denegado.php';
} */
$userType = SessionData::getUserType();
$isAdmin = ($userType === Util::Administrador() || $userType === Util::SuperAdministrador());
/* if (!$isAdmin) {
    require 'permiso_denegado.php';
} */

?>

<body class="">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->
    <!-- [ navigation menu ] start -->
    <?php
    include './admin/include/navbar.php';
    ?>
    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->
    <?php
    include './admin/include/header.php';
    ?>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pcoded-main-container">
        <div class="pcoded-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="m-b-10">Estrategia </h5>
                                <?php include './admin/include/btn_back.php'; ?>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a>
                                </li>
                                <li class="breadcrumb-item"><a href="#!">Configuración General / Estrategia</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- [ breadcrumb ] end -->
            <!-- [ Main Content ] start -->

            <div class="contenedor">
                <div class="contenido">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-3">
                            <h5 class="mb-0 text-center w-100">Ingreso y listado de estrategia</h5>
                            <div class="card-header-right ml-auto">
                                <div class="btn-group card-option">
                                    <button type="button" class="btn dropdown-toggle btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="feather icon-more-horizontal"></i>
                                    </button>
                                    <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                                        <li class="dropdown-item full-card">
                                            <a href="#!">
                                                <span><i class="feather icon-maximize"></i> Maximizar</span>
                                                <span style="display:none"><i class="feather icon-minimize"></i> Restaurar</span>
                                            </a>
                                        </li>
                                        <li class="dropdown-item minimize-card">
                                            <a href="#!">
                                                <span><i class="feather icon-minus"></i> Colapsar</span>
                                                <span style="display:none"><i class="feather icon-plus"></i> Expandir</span>
                                            </a>
                                        </li>
                                        <li class="dropdown-item reload-card">
                                            <a href="#!"><i class="feather icon-refresh-cw"></i> Recargar</a>
                                        </li>
                                        <li class="dropdown-item close-card">
                                            <a href="#!"><i class="feather icon-trash"></i> Eliminar</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body table-border-style">
                            <div class="navbar-form buscador-2">
                                <div class="input-group input-primary">
                                    <input type="text" id="customSearch" class="form-control" placeholder="Buscar">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="feather icon-edit"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de datos -->
                            <div class="table-responsive tabla-informacion tabla-scroll">
                                <table class="table table-hover mb-0" id="dynamictable">
                                    <thead class="bg-light text-dark">
                                        <tr class="border-1">
                                            <th>Ingresar</th>
                                            <th>Editar</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                        </tr>
                                    </thead>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Estrategia -->
                <div class="modal fade" id="modalEstrategia" tabindex="-1" role="dialog" aria-labelledby="modalEstrategiaLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <form id="formEstrategia">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="modalEstrategiaLabel">Nueva Estrategia</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>

                                <div class="modal-body" style="padding: 20px;">
                                    <input type="hidden" id="estrategia_id" name="id" />

                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required />
                                    </div>

                                    <div class="form-group">
                                        <label for="descripcion">Descripción</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" id="btnGuardarEstrategia" onclick="guardarEstrategia()">Guardar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


                <!-- Warning Section Ends -->
                <?php include 'admin/include/gerenic_script.php'; ?>
                <!-- Required Js -->
                <script src="assets/js/vendor-all.min.js"></script>
                <script src="assets/js/plugins/bootstrap.min.js"></script>
                <script src="assets/js/pcoded.min.js"></script>
                <script type="text/javascript" src="./admin/js/datatables/jquery.dataTables.min.js"></script>
                <link href="./admin/js/datatables/jquery.dataTables.min.css" rel="stylesheet" />

</body>

</html>
