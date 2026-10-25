<?php
require_once './admin/include/generic_classes.php';
require_once './admin/classes/AnaliticaService.php';
require_once './admin/classes/AnaliticaConsultas.php';

date_default_timezone_set('America/Bogota');

$rolPanel = strtolower(trim((string) ($_SESSION['panel_user']['rol'] ?? '')));

if ($rolPanel !== 'administrador') {
    header('Location: visualizador_emergencias.php');
    exit;
}

if (empty($_SESSION['analitica_panel_csrf'])) {
    $_SESSION['analitica_panel_csrf'] = bin2hex(random_bytes(32));
}

$modulo = 'Analítica de uso';
$nombrePanel = (string) ($_SESSION['panel_user']['nombre_completo'] ?? $_SESSION['panel_user']['usuario'] ?? 'Administradora');

$db = new DbConection();
$pdo = $db->openConect();
$consultas = new AnaliticaConsultas($pdo);

try {
    $servicio = new AnaliticaService($pdo, (new ConfigLoader())->getConfigAnalitica());
    $servicio->cerrarInactivas();

    if (AnaliticaService::mantenimientoPendiente()) {
        $servicio->ejecutarMantenimiento();
    }
} catch (Throwable $e) {
    error_log('analitica.php mantenimiento: ' . $e->getMessage());
}

$municipios = $consultas->opcionesMunicipios();
$hoy = (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')));

$configJs = [
    'ajax' => 'admin/ajax/analitica_ajax.php',
    'csrf' => $_SESSION['analitica_panel_csrf'],
    'usuarioPanel' => $nombrePanel,
    'hoy' => $hoy->format('Y-m-d'),
    'inicioMes' => $hoy->format('Y-m-01'),
    'minMotivo' => AnaliticaConsultas::MIN_MOTIVO,
    'maxMotivo' => AnaliticaConsultas::MAX_MOTIVO,
    'maxDias' => AnaliticaConsultas::MAX_DIAS,
    'refrescoSeg' => 30,
    'asistentes' => AnaliticaConsultas::ASISTENTES
];

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analítica de uso | Aliada UTS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/estadistica.css">
    <link rel="stylesheet" href="assets/css/analitica.css?v=20261005">
</head>

<body class="dashboard-body">
<?php
include './admin/include/navbar.php';
include './admin/include/header.php';
?>

<div class="pcoded-main-container">
    <div class="pcoded-content">

        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="m-b-10 text-white">Analítica de uso</h5>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="feather icon-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="#!">Panel de Control</a></li>
                            <li class="breadcrumb-item"><a href="#!">Analítica de uso</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-card an-hero">
            <div>
                <h1>Analítica de uso de la aplicación</h1>
                <p>Sesiones, tiempo en cada asistente, usuarias en línea y origen aproximado de las conexiones. Las personas aparecen con un código (por ejemplo <strong>U-000123</strong>); ver su nombre o la IP completa exige indicar un motivo y queda auditado.</p>
            </div>
            <div class="an-hero-acciones">
                <button type="button" class="an-btn an-btn-primario" id="btnResumenExcel"><i class="fas fa-file-excel" aria-hidden="true"></i> Exportar resumen (Excel)</button>
                <button type="button" class="an-btn" id="btnResumenPdf"><i class="fas fa-file-pdf" aria-hidden="true"></i> Exportar resumen (PDF)</button>
            </div>
        </div>

        <form class="an-filtros panel-card" id="formFiltros" novalidate>
            <div class="an-filtros-campos">
                <label class="an-campo">
                    <span>Desde</span>
                    <input type="date" id="fDesde" name="desde" required>
                </label>
                <label class="an-campo">
                    <span>Hasta</span>
                    <input type="date" id="fHasta" name="hasta" required>
                </label>
                <label class="an-campo">
                    <span>Asistente</span>
                    <select id="fAsistente" name="asistente">
                        <option value="">Todos</option>
                        <?php foreach (AnaliticaConsultas::ASISTENTES as $slug => $nombre): ?>
                            <option value="<?= e($slug); ?>"><?= e($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="an-campo">
                    <span>Dispositivo</span>
                    <select id="fDispositivo" name="dispositivo">
                        <option value="">Todos</option>
                        <?php foreach (AnaliticaConsultas::DISPOSITIVOS as $slug => $nombre): ?>
                            <option value="<?= e($slug); ?>"><?= e($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="an-campo">
                    <span>Municipio registrado</span>
                    <select id="fMunicipio" name="municipio">
                        <option value="">Todos</option>
                        <?php foreach ($municipios as $m): ?>
                            <option value="<?= e($m['codigo']); ?>"><?= e($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="an-filtros-acciones">
                <div class="an-atajos" role="group" aria-label="Rangos rápidos">
                    <button type="button" class="an-btn an-btn-chico" data-rango="hoy">Hoy</button>
                    <button type="button" class="an-btn an-btn-chico" data-rango="7">7 días</button>
                    <button type="button" class="an-btn an-btn-chico" data-rango="30">30 días</button>
                    <button type="button" class="an-btn an-btn-chico" data-rango="mes">Mes actual</button>
                </div>
                <div class="an-filtros-botones">
                    <button type="submit" class="an-btn an-btn-primario"><i class="fas fa-filter" aria-hidden="true"></i> Aplicar</button>
                    <button type="button" class="an-btn" id="btnRestablecer">Restablecer</button>
                </div>
            </div>
            <p class="an-filtro-error" id="filtroError" role="alert" hidden></p>
        </form>
        <p class="an-filtro-error an-estado-global" id="estadoGlobal" role="alert" hidden></p>

        <p class="an-nota">El filtro de asistente limita las sesiones a las que usaron ese asistente; el de dispositivo no aplica a los mensajes de chat; el rango de fechas no afecta a «En línea ahora».</p>

        <section aria-labelledby="tituloKpis">
            <h2 class="an-titulo" id="tituloKpis">Indicadores</h2>
            <div class="an-kpis" id="kpis" aria-live="polite">
                <div class="an-vacio">Cargando indicadores…</div>
            </div>
        </section>

        <section aria-labelledby="tituloTiempoReal">
            <h2 class="an-titulo" id="tituloTiempoReal">En tiempo real <span class="an-actualizado" id="actualizado"></span></h2>
            <div class="an-grid-2">
                <div class="panel-card">
                    <div class="panel-head">
                        <div>
                            <h3>Conexiones por ciudad aproximada</h3>
                            <p>Cada círculo agrupa las usuarias en línea de una ciudad; nunca se muestra una persona individual.</p>
                        </div>
                        <div class="badge-soft"><i class="fas fa-location-dot" aria-hidden="true"></i> <span id="totalEnLinea">0</span> en línea</div>
                    </div>
                    <div class="panel-body">
                        <div id="mapaEnLinea" class="an-mapa" role="img" aria-label="Mapa de usuarias en línea por ciudad"></div>
                        <p class="an-aviso"><i class="fas fa-circle-info" aria-hidden="true"></i> La ciudad se calcula a partir de la IP. En redes móviles de Colombia suele corresponder a la pasarela del operador (a menudo Bogotá o Medellín) y no al lugar real. Compárala con el <strong>municipio registrado</strong> de la usuaria, que suele ser más representativo.</p>
                    </div>
                </div>
                <div class="panel-card">
                    <div class="panel-head">
                        <div>
                            <h3>En línea ahora</h3>
                            <p>Sesiones con actividad en los últimos 150 segundos.</p>
                        </div>
                        <div class="badge-soft"><i class="fas fa-user-clock" aria-hidden="true"></i> Se actualiza cada 30 s</div>
                    </div>
                    <div class="table-wrap an-tabla-simple">
                        <table class="an-tabla" id="tablaEnLinea">
                            <thead>
                                <tr><th>Código</th><th>Está en</th><th>Desde</th><th>Dispositivo</th><th>Ciudad (IP)</th><th>Municipio registrado</th></tr>
                            </thead>
                            <tbody><tr><td colspan="6" class="an-vacio-celda">Cargando…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="tituloAsistentes">
            <h2 class="an-titulo" id="tituloAsistentes">Uso de los asistentes</h2>
            <div class="an-grid-3">
                <div class="panel-card"><div class="panel-head"><div><h3>Tiempo activo</h3><p>Minutos con la pestaña visible.</p></div></div><div class="panel-body"><div class="an-grafica"><canvas id="chTiempo" aria-label="Tiempo activo por asistente" role="img"></canvas></div></div></div>
                <div class="panel-card"><div class="panel-head"><div><h3>Usuarias únicas</h3><p>Personas distintas que lo usaron.</p></div></div><div class="panel-body"><div class="an-grafica"><canvas id="chUsuarias" aria-label="Usuarias únicas por asistente" role="img"></canvas></div></div></div>
                <div class="panel-card"><div class="panel-head"><div><h3>Mensajes enviados</h3><p>Mensajes de las usuarias al chat.</p></div></div><div class="panel-body"><div class="an-grafica"><canvas id="chMensajes" aria-label="Mensajes por asistente" role="img"></canvas></div></div></div>
            </div>
            <div class="panel-card an-mt">
                <div class="panel-head">
                    <div>
                        <h3>Detalle por asistente</h3>
                        <p>Uso, conversaciones, rendimiento y costo estimado de la IA.</p>
                    </div>
                    <div class="badge-soft"><i class="fas fa-table" aria-hidden="true"></i> Resumen por asistente</div>
                </div>
                <div class="table-wrap an-tabla-simple">
                    <table class="an-tabla" id="tablaAsistentes">
                        <thead>
                            <tr>
                                <th>Asistente</th><th>Sesiones</th><th>Usuarias</th><th>Tiempo total</th><th>Tiempo medio por usuaria</th>
                                <th>Mensajes</th><th>% por voz</th><th>Conversaciones</th><th>Msj. por conversación</th><th>% de un solo mensaje</th>
                                <th>Latencia p50</th><th>Latencia p95</th><th>Mensajes sin respuesta</th><th>Tokens (entrada / salida)</th><th>Búsquedas web</th><th>Costo estimado (USD)</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="16" class="an-vacio-celda">Cargando…</td></tr></tbody>
                    </table>
                </div>
                <p class="an-aviso an-aviso-pie" id="avisoCostos"></p>
            </div>
        </section>

        <section aria-labelledby="tituloSesiones">
            <h2 class="an-titulo" id="tituloSesiones">Sesiones en el tiempo</h2>
            <div class="an-grid-2">
                <div class="panel-card">
                    <div class="panel-head"><div><h3>Sesiones, usuarias y registros por día</h3><p>Una sesión empieza al iniciar sesión y termina al salir o por inactividad.</p></div></div>
                    <div class="panel-body"><div class="an-grafica an-grafica-alta"><canvas id="chDias" aria-label="Sesiones por día" role="img"></canvas></div></div>
                </div>
                <div class="panel-card">
                    <div class="panel-head"><div><h3>Dispositivos</h3><p>Tipo de dispositivo de las sesiones.</p></div></div>
                    <div class="panel-body">
                        <div class="an-grafica"><canvas id="chDispositivos" aria-label="Dispositivos" role="img"></canvas></div>
                        <div class="an-listas">
                            <div><h4>Sistemas operativos</h4><ul id="listaSistemas" class="an-lista"></ul></div>
                            <div><h4>Navegadores</h4><ul id="listaNavegadores" class="an-lista"></ul></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-card an-mt">
                <div class="panel-head"><div><h3>Horario de uso</h3><p>Sesiones iniciadas por día de la semana y hora (hora de Colombia). Sirve para planear turnos del equipo.</p></div></div>
                <div class="panel-body an-calor-wrap"><table class="an-calor" id="mapaCalor" aria-label="Mapa de calor de sesiones por día y hora"></table></div>
            </div>

            <div class="an-grid-2 an-mt">
                <div class="panel-card">
                    <div class="panel-head"><div><h3>Ciudad aproximada por IP</h3><p>Sesiones por ciudad según la IP de conexión.</p></div></div>
                    <div class="panel-body"><div class="an-grafica an-grafica-alta"><canvas id="chCiudades" aria-label="Sesiones por ciudad por IP" role="img"></canvas></div><p class="an-aviso" id="avisoGeo"></p></div>
                </div>
                <div class="panel-card">
                    <div class="panel-head"><div><h3>Municipio registrado</h3><p>Sesiones por el municipio que la usuaria indicó al registrarse.</p></div></div>
                    <div class="panel-body"><div class="an-grafica an-grafica-alta"><canvas id="chMunicipios" aria-label="Sesiones por municipio registrado" role="img"></canvas></div></div>
                </div>
            </div>
        </section>

        <section aria-labelledby="tituloMetricas">
            <h2 class="an-titulo" id="tituloMetricas">Otras métricas</h2>
            <div class="an-grid-3" id="metricas"><div class="an-vacio">Cargando métricas…</div></div>
        </section>

        <section aria-labelledby="tituloDetalle">
            <h2 class="an-titulo" id="tituloDetalle">Detalle exportable</h2>
            <div class="panel-card">
                <ul class="nav an-tabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="an-tab active" id="tabSesionesBtn" data-bs-toggle="tab" data-bs-target="#tabSesiones" type="button" role="tab" aria-controls="tabSesiones" aria-selected="true">Sesiones</button></li>
                    <li class="nav-item" role="presentation"><button class="an-tab" id="tabUsoBtn" data-bs-toggle="tab" data-bs-target="#tabUso" type="button" role="tab" aria-controls="tabUso" aria-selected="false">Uso por usuaria y asistente</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabSesiones" role="tabpanel" aria-labelledby="tabSesionesBtn">
                        <p class="an-excede" id="excedeSesiones" hidden></p>
                        <div class="table-wrap">
                            <table class="table table-sm an-dt" id="tablaSesiones" style="width:100%">
                                <thead>
                                    <tr><th>Código</th><th>Inicio</th><th>Fin</th><th>Duración</th><th>Cierre</th><th>Ciudad (IP)</th><th>Municipio registrado</th><th>Dispositivo</th><th>Sistema</th><th>Navegador</th><th>IP</th><th class="no-export">Acciones</th></tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tabUso" role="tabpanel" aria-labelledby="tabUsoBtn">
                        <p class="an-excede" id="excedeUso" hidden></p>
                        <div class="table-wrap">
                            <table class="table table-sm an-dt" id="tablaUso" style="width:100%">
                                <thead>
                                    <tr><th>Código</th><th>Asistente</th><th>Sesiones</th><th>Tiempo activo</th><th>Último uso</th><th class="no-export">Acciones</th></tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <table id="tablaResumen" class="an-oculta" aria-hidden="true">
                <thead><tr><th>Sección</th><th>Indicador</th><th>Valor</th><th>Detalle</th></tr></thead>
                <tbody></tbody>
            </table>
        </section>

    </div>
</div>

<div class="modal fade" id="modalRevelar" tabindex="-1" aria-labelledby="modalRevelarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content an-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRevelarTitulo">Ver dato protegido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="modalRevelarDescripcion" class="an-modal-texto"></p>
                <div id="revelarFormulario">
                    <label for="revelarMotivo" class="an-modal-etiqueta">Motivo (obligatorio, mínimo <?= (int) AnaliticaConsultas::MIN_MOTIVO; ?> caracteres)</label>
                    <textarea id="revelarMotivo" class="form-control an-modal-campo" rows="3" maxlength="<?= (int) AnaliticaConsultas::MAX_MOTIVO; ?>"></textarea>
                    <div class="an-modal-contador"><span id="revelarContador">0</span>/<?= (int) AnaliticaConsultas::MAX_MOTIVO; ?></div>
                    <p class="an-filtro-error" id="revelarError" role="alert" hidden></p>
                </div>
                <div id="revelarResultado" hidden>
                    <div class="an-revelado" id="revelarValor"></div>
                    <p class="an-modal-texto">Se ocultará en <strong id="revelarCuenta">60</strong> s. Esta consulta quedó registrada en la auditoría.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="an-btn" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="an-btn an-btn-primario" id="revelarConfirmar">Ver</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    window.ANALITICA = <?= json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="assets/js/analitica.js?v=20261006b"></script>
</body>
</html>
