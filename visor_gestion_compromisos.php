<?php

include './admin/include/head.php';
require './admin/include/generic_classes.php';

function getUrl()
{
    $port = $_SERVER["SERVER_PORT"];
    $nameServer = $port != "80" ? $_SERVER['SERVER_NAME'] . ":" . $port : $_SERVER['SERVER_NAME'];
    $url = sprintf(
        "%s://%s%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $nameServer,
        $_SERVER['REQUEST_URI']
    );
    $final = str_replace(basename($_SERVER["SCRIPT_FILENAME"], '.php') . ".php", "", $url);
    $exists = strpos($final, "?");
    if ($exists !== false) {
        $final = substr($final, 0, $exists);
    }
    return $final;
}

include './admin/classes/Visitas.php';
// Informacion de visor de gestion de compromisos
$data = Visitas::getVisorGestionDeCompromiso(null);
$isvalid = $data['output']['valid'];
$response =  $data['output']['response'];

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

    <style>
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        #dynamictable {
            width: 100% !important;
            table-layout: auto;
            white-space: normal;
        }

        html,
        body {
            overflow-x: hidden !important;
        }

        #dynamictable td {
            white-space: normal !important;
            word-break: break-word !important;
            max-width: 300px;
            vertical-align: top;
        }

        .card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .card-header {
            font-weight: bold;
        }

        .progress {
            height: 20px;
            border-radius: 10px;
        }

        .progress-bar {
            line-height: 20px;
            font-size: 12px;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .bg-cumplidos {
            background-color: #0d5fa7 !important;
            /* azul oscuro */
            color: white !important;
        }

        .bg-cumplidos small {
            color: white !important;
        }
    </style>

    <!-- [ Header ] end -->
    <!-- [ Main Content ] start -->
    <div class="pcoded-main-container">
        <div class="pcoded-wrapper">
            <div class="pcoded-content">
                <div class="pcoded-inner-content">
                    <div class="main-body">
                        <div class="page-wrapper">
                            <!-- [ breadcrumb ] start -->
                            <div class="page-header">
                                <div class="page-block">
                                    <div class="row align-items-center">
                                        <div class="col-md-12">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="m-b-10">Visor De Gestión De Cumplimiento</h5>
                                                <?php include './admin/include/btn_back.php'; ?>
                                            </div>
                                            <ul class="breadcrumb">
                                                <li class="breadcrumb-item"><a href="index.html"><i
                                                            class="feather icon-home"></i></a></li>
                                                <li class="breadcrumb-item"><a href="#!">Mapa visitas</a></li>
                                                <li class="breadcrumb-item"><a href="#!">Visor De Gestión De
                                                        Cumplimiento</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- [ breadcrumb ] end -->
                            <!-- [ Main Content ] start -->
                            <div class="row">
                                <!-- [ sample-page ] start -->
                                <div class="col-sm-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5>Visor De Gestión De Cumplimiento</h5>
                                            <div class="card-header-right">
                                                <div class="btn-group card-option">
                                                    <button type="button" class="btn dropdown-toggle btn-icon" data-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="feather icon-more-horizontal"></i>
                                                    </button>
                                                    <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                                                        <li class="dropdown-item full-card"><a href="#!"><span><i
                                                                    class="feather icon-maximize"></i> maximize</span><span
                                                                style="display:none"><i class="feather icon-minimize"></i>
                                                                Restore</span></a></li>
                                                        <li class="dropdown-item minimize-card"><a href="#!"><span><i
                                                                    class="feather icon-minus"></i> collapse</span><span
                                                                style="display:none"><i class="feather icon-plus"></i>
                                                                expand</span></a></li>
                                                        <li class="dropdown-item reload-card"><a href="#!"><i
                                                                class="feather icon-refresh-cw"></i> reload</a></li>
                                                        <li class="dropdown-item close-card"><a href="#!"><i
                                                                class="feather icon-trash"></i> remove</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-body">
                                            <div class="card-body table-border-style">
                                                <!-- Tabla de datos -->
                                                <div class="table-responsive tabla-informacion tabla-scroll">
                                                    <table class="table table-hover mb-0" id="dynamictable">
                                                        <thead class="bg-light text-dark">
                                                            <tr class="border-1">
                                                                <th style="text-align: center;">No.</th>
                                                                <th style="text-align: center;">ENTIDAD</th>
                                                                <th style="text-align: center;">TOTAL COMPR.</th>
                                                                <th style="text-align: center; background-color: #f9e79f; color:#000;">EN TRÁMITE</th>
                                                                <th style="text-align: center; background-color: #f5b7b1; color:#000;">CUMPLIDO</th>
                                                                <th style="text-align: center; background-color: #aed6f1; color:#000;">SIN CUMPLIR</th>
                                                                <th style="text-align: center;">CALIFICACIÓN</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (isset($response) && is_array($response) && !empty($response)) {
                                                                $rowNumber = 1; // Inicializar el contador de filas
                                                                foreach ($response as $item) {
                                                                    // Determinar el color de texto para asegurar legibilidad
                                                                    $textColor = '#000000'; // Negro por defecto
                                                                    if (isset($item['color_calificacion']) && in_array($item['color_calificacion'], ['#DC143C', '#73b7ff'])) {
                                                                        // Rojo y Azul son oscuros, usar texto blanco
                                                                        // $textColor = '#FFFFFF';
                                                                    }
                                                            ?>
                                                                    <tr>
                                                                        <td style="text-align: center;"><?php echo $rowNumber++; ?></td>
                                                                        <td><?php echo htmlspecialchars($item['entidad'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </td>
                                                                        <td style="text-align: center;">
                                                                            <?php echo htmlspecialchars($item['total_compromisos'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </td>
                                                                        <td style="text-align: center;">
                                                                            <?php echo htmlspecialchars($item['en_tramite'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </td>
                                                                        <td style="text-align: center;">
                                                                            <?php echo htmlspecialchars($item['cumplido'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </td>
                                                                        <td style="text-align: center;">
                                                                            <?php echo htmlspecialchars($item['sin_cumplir'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </td>
                                                                        <td
                                                                            style="text-align: center; background-color: <?php echo htmlspecialchars($item['color_calificacion'] ?? 'transparent', ENT_QUOTES, 'UTF-8'); ?>; color: <?php echo $textColor; ?>;">
                                                                            <?php echo htmlspecialchars($item['calificacion_porcentaje'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                }
                                                            } else {
                                                                ?>
                                                                <tr>
                                                                    <td colspan="7" style="text-align: center;">No hay datos de
                                                                        compromisos disponibles.</td>
                                                                </tr>
                                                            <?php
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- [ sample-page ] end -->
                            </div>
                            <!-- [ Main Content ] end -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--  Script -->
    <?php if (isset($_GET["route_map"])): ?>
    <?php endif ?>
    <?php include 'admin/include/footer.php'; ?>

    <?php include 'admin/include/gerenic_script.php'; ?>

    <!-- Required Js -->
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>

    <!-- prism Js -->
    <script src="assets/js/plugins/prism.js"></script>
    <script src="assets/js/plugins/apexcharts.min.js"></script>
    <?php include './admin/include/generic_dataTables.php'; ?>

</body>

</html>