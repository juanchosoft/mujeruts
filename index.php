<?php
include './admin/include/head.php';
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
    $final =  str_replace(basename($_SERVER["SCRIPT_FILENAME"], '.php') . ".php", "", $url);
    $exists = strpos($final, "?");
    if ($exists == !false) {
        $final =  substr($final, 0, $exists);
        return $final;
    } else {
        return $final;
    }
}

require_once './admin/include/generic_classes.php';
include './admin/classes/Ciudad.php';
include './admin/classes/Estado.php';
require './admin/classes/Departamento.php';
include './admin/db/colores.php';
include './admin/classes/Main.php';
include './admin/classes/Detalle.php';
include './admin/classes/Cuenta.php';
include './admin/classes/Cuentapro.php';
include './admin/classes/Secreinversion.php';
include './admin/classes/Munnovisitados.php';




// Obtener permisos
/* $permissions = [
    'view' => SessionData::getPermission(70),
    'create' => SessionData::getPermission(71),
    'edit' => SessionData::getPermission(72),
   
];

// Validación de permiso de visualización
if (!$permissions['view']) {
    require_once 'permiso_denegado.php';
    exit;
} */

//informacion del mail
$arr = Main::getDataMain(null);
$isvalid = $arr['output']['valid'];
$visitas = $arr['output']['visitas'];
$apoyos = $arr['output']['apoyos'];
$municipios = $arr['output']['municipios'];
$veredas = $arr['output']['veredas'];
$provincia = $arr['output']['provincia'];
$porcentaje_veredas = $arr['output']['porcentaje_veredas'];
$porcentaje_municipios = $arr['output']['porcentaje_municipios'];
$inversionsec = $arr['output']['inversionsec'];
$valorproyectos = $arr['output']['valorproyectos'];
$secretaria = $arr['output']['secretaria'];
$sumaproyectos = $arr['output']['sumaproyectos'];
$visitarpendiente = 87 - $municipios;


$departamento = new Departamento();
$santander = $departamento->getAll(["id" => 21]);
$santander = $santander["output"]["response"]["0"];
$code = null;
$mapa = null;

if (isset($_GET['depto_id']) && in_array($_GET['depto_id'], [1, 12, 21])) {
    switch ($_GET['depto_id']) {

        case '21':
            $code = $santander["codigo_departamento"];
            $mapa = "admin/mapa-santander/mapa.php";
            break;
    }
}
if (!is_null($code)) {
    $arr = Ciudad::getAll(array('codigo_departamento' => $code));
    $finalMunicipios = $arr['output']['response'];
    $arrApoyoDep = Ciudad::getApoyoByCodigoDepartamento(array('codigo_departamento' => $code));
}
?>

<body class="dashboard-body">
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
                                <h5 class="m-b-10">Dashboard </h5>
<?php include './admin/include/btn_back.php'; ?>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php"><i class="feather icon-home"></i></a>
                                </li>
                                <li class="breadcrumb-item"><a href="#!"> Información General </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <center>
                        <div class="card flat-card">
                            <div class="row-table">
                                <!-- Total Visitas Departamento -->
                                <div class="col-sm-8 card-body br">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <i class="icon feather icon-server text-c-green mb-1 d-block"
                                                data-bs-toggle="modal" data-bs-target="#modalMunicipiosVisitados"
                                                style="cursor: pointer;" title="Ver más"></i>
                                        </div>
                                        <div class="col-sm-4 text-md-center">
                                            <h5>Total Visitas Departamento</h5>
                                            <h3><?php echo $visitas; ?></h3>
                                        </div>
                                    </div>

                                    <!-- Modal Municipios Visitados -->
                                    <div id="modalMunicipiosVisitados" class="modal fade" tabindex="-1"
                                        aria-labelledby="modalTitle" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="modalTitle">Municipios Visitados</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Cerrar"></button>
                                                </div>
                                                <?php
                                                $arr = Detalle::getAll(null);
                                                $isvalid = $arr['output']['valid'] ?? false;
                                                $data = $arr['output']['response'] ?? [];
                                                ?>
                                                <div class="modal-body">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Provincia</th>
                                                                <th>Municipio</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if ($isvalid && !empty($data)): ?>
                                                                <?php foreach ($data as $item): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($item['provincia']); ?></td>
                                                                        <td><?= htmlspecialchars($item['municipio']); ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="2">No hay datos disponibles.</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Visitas realizadas a Municipios -->
                                <div class="col-sm-8 card-body">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <i class="icon feather icon-server text-c-red mb-1 d-block"
                                                data-toggle="tooltip" data-placement="top" title="ver"
                                                data-bs-target="#exampleModalLong1" data-bs-toggle="modal"
                                                style="cursor: pointer;"></i>
                                        </div>
                                        <div class="col-sm-4 text-md-center">
                                            <h5>Visitas realizadas a Municipios</h5>
                                            <h3><?php echo $municipios; ?></h3>
                                        </div>
                                    </div>
                                    <!-- Modal de Cantidad de Visitas -->
                                    <div id="exampleModalLong1" class="modal fade" tabindex="-1"
                                        aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLongTitle">Cantidad de
                                                        visitas a municipios</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <?php
                                                //Información de visitas muncipios
                                                $arrVisitasMunicipios = Cuenta::getAll(null);
                                                $isvalid = $arrVisitasMunicipios['output']['valid'];
                                                $arrVisitasMunicipios = $arrVisitasMunicipios['output']['response'];
                                                ?>

                                                <div class="modal-body">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Municipio</th>
                                                                <th scope="col">Veces Visitado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (isset($arrVisitasMunicipios) && isset($isvalid) && $isvalid) {
                                                                foreach ($arrVisitasMunicipios as $item) { ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item['municipio']); ?>
                                                                        </td>
                                                                        <td><?php echo htmlspecialchars($item['CuentaDeid']); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php }
                                                            } else { ?>
                                                                <tr>
                                                                    <td colspan="2">No hay datos disponibles.</td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                            <!-- Otras Secciones -->
                            <div class="row-table">
                                <!-- Municipios Restantes -->
                                <div class="col-sm-6 card-body br">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <i class="icon feather icon-navigation text-c-blue mb-1 d-block"
                                                data-toggle="tooltip" data-placement="top" title="ver"
                                                data-bs-target="#exampleModalLong3" data-bs-toggle="modal"
                                                style="cursor: pointer;"></i>
                                        </div>
                                        <div class="col-sm-4 text-md-center">
                                            <h5>Municipios Restantes por visitar</h5>
                                            <h3><?php echo $visitarpendiente; ?></h3>
                                        </div>
                                    </div>

                                    <!-- Modal de Municipios Restantes -->
                                    <div id="exampleModalLong3" class="modal fade" tabindex="-1"
                                        aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLongTitle">Municipios
                                                        Restantes</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php
                                                    //Municipios Restantes por visitar
                                                    $arrMunicipiosRestante = Munnovisitados::getAll(null);
                                                    $isvalid = $arrMunicipiosRestante['output']['valid'];
                                                    $arrMunicipiosRestante = $arrMunicipiosRestante['output']['response'];
                                                    ?>
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Municipio</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (isset($arrMunicipiosRestante) && isset($isvalid) && $isvalid) {
                                                                foreach ($arrMunicipiosRestante as $item) { ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item['municipio']); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php }
                                                            } else { ?>
                                                                <tr>
                                                                    <td>No hay datos disponibles.</td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <!-- Metas Plan Desarrollo -->
                                <div class="col-sm-6 card-body">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <a href="plan_desarrollo.php">
                                                <i class="icon feather icon-file-text text-c-yellow mb-1 d-block"></i>
                                            </a>
                                        </div>
                                        <div class="col-sm-4 text-md-center">
                                            <h4>Metas Plan Desarrollo</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                </center>
            </div>

            <div class="col-md-12">
                <div class="row d-flex justify-content-between">
                    <!-- Visitas realizadas a provincias -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Visitas realizadas a provincias</h5>
                            </div>
                            <div class="card-body">
                                <div id="containerProvincias" style="height: 300px; width: 100%;"></div>
                                <p class="highcharts-description">
                                    Visitas realizadas por mes Provincias
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Visitas por mes a municipios -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Visitas por mes a municipios</h5>
                            </div>
                            <div class="card-body">
                                <div id="containerMunicipios" style="height: 300px; width: 100%;"></div>
                                <p class="highcharts-description">
                                    Visitas realizadas por mes Municipios
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Latest Customers start -->
            <div class="col-xl-12 col-md-12">
                <div class="card table-card">
                    <div class="card-header">
                        <h5>Metas Plan de Desarrollo</h5>
                        <div class="card-header-right">
                            <div class="btn-group card-option">
                                <button type="button" class="btn dropdown-toggle" data-toggle="dropdown"
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
                                                style="display:none"><i class="feather icon-plus"></i> expand</span></a>
                                    </li>
                                    <li class="dropdown-item reload-card"><a href="#!"><i
                                                class="feather icon-refresh-cw"></i> reload</a></li>
                                    <li class="dropdown-item close-card"><a href="#!"><i class="feather icon-trash"></i>
                                            remove</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table tabla-colores">
                                <thead>
                                    <tr>
                                        <th>Desde</th>
                                        <th>hasta</th>
                                        <th>Color</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>
                                            <div class="color-circle"></div>
                                        </td> <!-- Círculo gris por defecto -->
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>2</td>
                                        <td>
                                            <div class="color-circle color-red"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>4</td>
                                        <td>
                                            <div class="color-circle color-yellow"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>6</td>
                                        <td>
                                            <div class="color-circle color-blue"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>7</td>
                                        <td>+</td>
                                        <td>
                                            <div class="color-circle color-green"></div>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Mapa Santander</h5>
                        <div>
                            <!-- <i class="icon feather icon-plus-circle text-c-green mb-1 d-block" id="zoom-in-btn"
                                data-toggle="tooltip" data-placement="top" title="Aumentar"
                                style="cursor: pointer;"></i> -->

                            <!-- Icono para Reducir -->
                            <!-- <i class="icon feather icon-minus-circle text-c-red mb-1 d-block" id="zoom-out-btn"
                                data-toggle="tooltip" data-placement="top" title="Reducir" style="cursor: pointer;"></i> -->
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- ==================================INICIO MAPA==================================  -->
                        <section class="content"
                            style="<?php echo isset($_GET["route_map"]) ? "padding: 0rem !important" : "" ?>">
                            <div class="bonotera">
                                <button id="btnAumentar" class="btn elemento"><i
                                        class=" fas fa-search-plus fa-fw fa-sm text-dark"></i></button>
                                <button id="btnReducir" class="btn elemento"><i
                                        class="fas fa-search-minus fa-fw fa-sm text-dark"></i></button>
                            </div>
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="col-sm-4 d-none">
                                            <div class="form-horizontal">
                                                <input id="filtro" name="filtro" value="municipio">
                                                <div class="form-group">
                                                    <label class="bmd-label-floating">Departamento<b
                                                            class="errLbl">*</b></label>
                                                    <select class="form-control select2" style="width: 100%;"
                                                        id="tbl_departamento_id" name="tbl_departamento_id">
                                                        <option value=""></option>
                                                        <option
                                                            <?php echo !is_null($code) && $_GET["depto_id"] == 21 ? "selected" : "" ?>
                                                            value="<?php echo $santander["id"] ?>">Santander</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12" style="position: static; overflow-x: auto ">
                                        <div id="contenidoTransformado" class="contenido-transformado">

                                            <!-- Main content -->
                                            <div class="cuerpoMapa w-12">
                                                <?php if (!is_null($mapa)) : ?>
                                                    <?php if ($_GET['depto_id'] == 1) : ?>
                                                        <div class="antioquia munis">
                                                            <?php include_once "admin/mapa/mapa.php"; ?>
                                                        </div>
                                                    <?php elseif ($_GET['depto_id'] == 12) : ?>
                                                        <div class="choco munis">
                                                            <?php include_once "admin/mapa-choco/choco.php"; ?>
                                                        </div>
                                                    <?php else : ?>
                                                        <div class="santander munis">
                                                            <?php echo require_once "admin/mapa-santander/mapa.php"; ?>
                                                        </div>
                                                    <?php endif ?>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <!-- ==================================FIN  MAPA==================================  -->
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Warning Section Ends -->

    <script>
        document.getElementById("btnAumentar").onclick = function() {
            aumentarTransform();
        };
        document.getElementById("btnReducir").onclick = function() {
            reducirTransform();
        };

        function aumentarTransform() {
            var elemento = document.getElementById("contenidoTransformado");
            var escalaActual = parseFloat(window.getComputedStyle(elemento).getPropertyValue("transform").split(
                ",")[3]);
            var nuevaEscala = escalaActual + 0.1; // Aumentar la escala en 0.1
            elemento.style.transform = "scale(" + nuevaEscala + ")";
        }

        function reducirTransform() {
            var elemento = document.getElementById("contenidoTransformado");
            var escalaActual = parseFloat(window.getComputedStyle(elemento).getPropertyValue("transform").split(
                ",")[3]);
            var nuevaEscala = escalaActual - 0.1; // Reducir la escala en 0.1
            if (nuevaEscala >= 0.1) { // Evitar escala negativa
                elemento.style.transform = "scale(" + nuevaEscala + ")";
            }
        }
    </script>
    <?php include 'admin/include/gerenic_script.php'; ?>
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>

    <!-- prism Js -->
    <script src="assets/js/plugins/prism.js"></script>
    <script src="assets/js/plugins/apexcharts.min.js"></script>

</body>

</html>
