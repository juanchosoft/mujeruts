<?php
require_once './admin/include/generic_classes.php';

date_default_timezone_set('America/Bogota');

$modulo = 'Dashboard Emergencias';

$db = new DbConection();
$pdo = $db->openConect();

/* =========================
   CONSULTAS DASHBOARD
========================= */
$totalAlertasHoy   = 0;
$totalAtendidas    = 0;
$totalEnProceso    = 0;
$totalUsuarios     = 0;
$ultimaAlerta      = null;
$alertasRecientes  = [];

try {
    // Alertas del día
    $sqlHoy = "SELECT COUNT(*) AS total
               FROM tbl_alerta_policia
               WHERE DATE(fecha_registro) = CURDATE()";
    $stmtHoy = $pdo->prepare($sqlHoy);
    $stmtHoy->execute();
    $totalAlertasHoy = (int)($stmtHoy->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Atendidas
    $sqlAtendidas = "SELECT COUNT(*) AS total
                     FROM tbl_alerta_policia
                     WHERE COALESCE(estado_atencion_visualizador, 'no_atendida') = 'atendida'";
    $stmtAt = $pdo->prepare($sqlAtendidas);
    $stmtAt->execute();
    $totalAtendidas = (int)($stmtAt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // En proceso
    $sqlProceso = "SELECT COUNT(*) AS total
                   FROM tbl_alerta_policia
                   WHERE COALESCE(estado_atencion_visualizador, 'no_atendida') IN ('no_atendida','en_atencion')";
    $stmtPr = $pdo->prepare($sqlProceso);
    $stmtPr->execute();
    $totalEnProceso = (int)($stmtPr->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Usuarios registrados
    $sqlUsuarios = "SELECT COUNT(DISTINCT usuario_id) AS total
                    FROM tbl_alerta_policia";
    $stmtUs = $pdo->prepare($sqlUsuarios);
    $stmtUs->execute();
    $totalUsuarios = (int)($stmtUs->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Última alerta para iniciar el mapa
    $sqlUltima = "SELECT a.*,
                         g.usuario_panel_nombre,
                         g.usuario_panel_usuario,
                         g.usuario_panel_correo,
                         g.usuario_panel_rol,
                         g.reporte,
                         g.tarea_realizada,
                         g.cuadrante_enviado,
                         g.gestion_realizada,
                         g.fecha_gestion
                  FROM tbl_alerta_policia a
                  LEFT JOIN tbl_alerta_policia_gestion g ON g.alerta_id = a.id
                  ORDER BY a.id DESC
                  LIMIT 1";
    $stmtUlt = $pdo->prepare($sqlUltima);
    $stmtUlt->execute();
    $ultimaAlerta = $stmtUlt->fetch(PDO::FETCH_ASSOC);

    // Emergencias recientes con gestión y usuario
    $sqlRecientes = "SELECT 
                        a.id,
                        a.usuario_id,
                        a.nombre_usuaria,
                        a.celular_usuaria,
                        a.contacto_emergencia,
                        a.nombre_contacto_emergencia,
                        a.direccion_detectada,
                        a.latitud,
                        a.longitud,
                        a.precision_metros,
                        a.fecha_registro,
                        a.fecha_atencion,
                        COALESCE(a.estado_atencion_visualizador, 'no_atendida') AS estado_atencion_visualizador,
                        g.usuario_panel_nombre,
                        g.usuario_panel_usuario,
                        g.usuario_panel_correo,
                        g.usuario_panel_rol,
                        g.reporte,
                        g.tarea_realizada,
                        g.cuadrante_enviado,
                        g.gestion_realizada,
                        g.fecha_gestion
                     FROM tbl_alerta_policia a
                     LEFT JOIN tbl_alerta_policia_gestion g ON g.alerta_id = a.id
                     ORDER BY a.id DESC
                     LIMIT 8";
    $stmtRec = $pdo->prepare($sqlRecientes);
    $stmtRec->execute();
    $alertasRecientes = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $totalAlertasHoy  = 0;
    $totalAtendidas   = 0;
    $totalEnProceso   = 0;
    $totalUsuarios    = 0;
    $ultimaAlerta     = null;
    $alertasRecientes = [];
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatFecha($fecha) {
    if (empty($fecha)) return 'Sin fecha';
    $ts = strtotime($fecha);
    if (!$ts) return e($fecha);
    return date('d/m/Y h:i A', $ts);
}

function formatHora($fecha) {
    if (empty($fecha)) return 'Sin hora';
    $ts = strtotime($fecha);
    if (!$ts) return 'Sin hora';
    return date('h:i A', $ts);
}

function formatSoloFecha($fecha) {
    if (empty($fecha)) return 'Sin fecha';
    $ts = strtotime($fecha);
    if (!$ts) return 'Sin fecha';
    return date('d/m/Y', $ts);
}

function estadoLabel($estado) {
    $estado = strtolower(trim((string)$estado));
    if ($estado === 'atendida') return 'Atendida';
    if ($estado === 'en_atencion') return 'En atención';
    return 'No atendida';
}

function estadoClass($estado) {
    $estado = strtolower(trim((string)$estado));
    if ($estado === 'atendida') return 'estado-badge estado-ok';
    if ($estado === 'en_atencion') return 'estado-badge estado-proceso';
    return 'estado-badge estado-no';
}

/* =========================
   MAPA - SANTANDER
========================= */
$defaultLat = 6.6936;
$defaultLng = -73.4860;

$mapLat = $defaultLat;
$mapLng = $defaultLng;
$mapNombre = 'Ubicación general';
$mapEstado = 'Sin información';
$mapUsuario = 'Sin asignar';
$mapReporte = 'Sin gestión registrada';
$mapTarea = 'Sin gestión registrada';
$mapCuadrante = 'Sin gestión registrada';
$mapGestion = 'Sin gestión registrada';
$mapHoraRecepcion = 'Sin hora';
$mapHoraAtencion = 'Sin hora';
$mapHoraGestion = 'Sin hora';
$mapFechaRecepcion = 'Sin fecha';
$mapFechaAtencion = 'Sin fecha';
$mapFechaGestion = 'Sin fecha';

if (!empty($ultimaAlerta)) {
    $lat = isset($ultimaAlerta['latitud']) ? str_replace(',', '.', $ultimaAlerta['latitud']) : '';
    $lng = isset($ultimaAlerta['longitud']) ? str_replace(',', '.', $ultimaAlerta['longitud']) : '';

    if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
        $mapLat = (float)$lat;
        $mapLng = (float)$lng;
    }

    $mapNombre = $ultimaAlerta['nombre_usuaria'] ?? 'Usuaria';
    $mapEstado = estadoLabel($ultimaAlerta['estado_atencion_visualizador'] ?? 'no_atendida');
    $mapUsuario = $ultimaAlerta['usuario_panel_nombre'] ?? 'Sin asignar';
    $mapReporte = $ultimaAlerta['reporte'] ?: 'Sin gestión registrada';
    $mapTarea = $ultimaAlerta['tarea_realizada'] ?: 'Sin gestión registrada';
    $mapCuadrante = $ultimaAlerta['cuadrante_enviado'] ?: 'Sin gestión registrada';
    $mapGestion = $ultimaAlerta['gestion_realizada'] ?: 'Sin gestión registrada';
    $mapHoraRecepcion = formatHora($ultimaAlerta['fecha_registro'] ?? '');
    $mapHoraAtencion = formatHora($ultimaAlerta['fecha_atencion'] ?? '');
    $mapHoraGestion = formatHora($ultimaAlerta['fecha_gestion'] ?? '');
    $mapFechaRecepcion = formatSoloFecha($ultimaAlerta['fecha_registro'] ?? '');
    $mapFechaAtencion = formatSoloFecha($ultimaAlerta['fecha_atencion'] ?? '');
    $mapFechaGestion = formatSoloFecha($ultimaAlerta['fecha_gestion'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Emergencias | Violeta Santander</title>
    <link rel="stylesheet" href="assets/css/dash.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .alert-table tbody tr{
            cursor:pointer;
            transition:.2s ease;
        }
        .alert-table tbody tr:hover{
            transform:translateY(-1px);
            filter:brightness(1.04);
        }
        .alert-table tbody tr.active-row td{
            border-top:1px solid rgba(255,45,117,.35) !important;
            border-bottom:1px solid rgba(255,45,117,.35) !important;
            background:linear-gradient(135deg, rgba(255,45,117,.10), rgba(255,255,255,.03)) !important;
        }
        .alert-table tbody tr.active-row td:first-child{
            border-left:1px solid rgba(255,45,117,.35) !important;
        }
        .alert-table tbody tr.active-row td:last-child{
            border-right:1px solid rgba(255,45,117,.35) !important;
        }

        .usuario-mini{
            display:flex;
            flex-direction:column;
            gap:2px;
        }
        .usuario-mini .nombre{
            color:#fff;
            font-size:13px;
            font-weight:800;
            line-height:1.2;
        }
        .usuario-mini .sub{
            color:#b9dff0;
            font-size:11px;
            line-height:1.2;
        }

        .estado-no{
            background:rgba(239,68,68,.16);
            border:1px solid rgba(239,68,68,.24);
            color:#ffe1e1;
        }

        .gestion-section{
            padding: 0 18px 20px;
        }

        .gestion-shell{
            margin-top:18px;
            border-radius:22px;
            border:1px solid rgba(255,255,255,.08);
            background:
                radial-gradient(circle at top left, rgba(139,61,255,.14), transparent 24%),
                linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
            box-shadow:0 18px 45px rgba(0,0,0,.18);
            overflow:hidden;
        }

        .gestion-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            padding:18px 18px 14px;
            border-bottom:1px solid rgba(255,255,255,.08);
        }

        .gestion-header h3{
            margin:0;
            color:#fff;
            font-size:18px;
            font-weight:900;
        }

        .gestion-header p{
            margin:6px 0 0;
            color:#c7b7d9;
            font-size:13px;
        }

        .gestion-pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 12px;
            border-radius:999px;
            background:rgba(255,255,255,.05);
            border:1px solid rgba(255,255,255,.08);
            color:#fff;
            font-size:12px;
            font-weight:800;
        }

        .gestion-times{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:14px;
            padding:18px;
            border-bottom:1px solid rgba(255,255,255,.08);
        }

        .time-card{
            position:relative;
            overflow:hidden;
            border-radius:18px;
            padding:16px;
            min-height:120px;
            border:1px solid rgba(255,255,255,.08);
            background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.025));
            box-shadow:0 12px 30px rgba(0,0,0,.16);
        }

        .time-card::before{
            content:'';
            position:absolute;
            inset:0 auto 0 0;
            width:4px;
            background:linear-gradient(180deg, #ff2d75, #8b3dff);
        }

        .time-card .label{
            color:#c7b7d9;
            font-size:11px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.9px;
            margin-bottom:10px;
        }

        .time-card .hora{
            color:#fff;
            font-size:26px;
            font-weight:900;
            line-height:1;
            margin-bottom:8px;
        }

        .time-card .fecha{
            color:#d9ebff;
            font-size:13px;
            font-weight:700;
            line-height:1.3;
        }

        .gestion-grid{
            display:grid;
            grid-template-columns:repeat(2, 1fr);
            gap:16px;
            padding:18px;
        }

        .gestion-card{
            background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
            border:1px solid rgba(255,255,255,.08);
            border-radius:20px;
            padding:18px;
            box-shadow:0 12px 30px rgba(0,0,0,.16);
        }

        .gestion-card.full{
            grid-column:1 / -1;
        }

        .gestion-card .k{
            display:flex;
            align-items:center;
            gap:8px;
            color:#f6d4ff;
            font-size:12px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.8px;
            margin-bottom:10px;
        }

        .gestion-card .v{
            color:#fff;
            font-size:14px;
            line-height:1.75;
            white-space:pre-wrap;
            word-break:break-word;
        }

        .gestion-card.highlight{
            background:
                radial-gradient(circle at top right, rgba(255,45,117,.12), transparent 28%),
                linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.025));
        }

        @media (max-width: 992px){
            .gestion-times{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 768px){
            .gestion-grid{
                grid-template-columns:1fr;
            }

            .gestion-header{
                flex-direction:column;
                align-items:flex-start;
            }

            .time-card .hora{
                font-size:22px;
            }
        }
    </style>
</head>

<body class="dashboard-body">
    <div class="loader-bg" id="globalLoader">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

<?php
$modulo = 'Dashboard Emergencias';
include './admin/include/navbar.php';
include './admin/include/header.php';
?>

    <div class="pcoded-main-container">
        <div class="pcoded-content">            

            <div class="violeta-wrap">
                <div class="dashboard-hero">
                    <div class="dashboard-title">
                        <h1>Centro de Monitoreo de Alertas</h1>
                        <p>Visualiza en tiempo real el comportamiento de las alertas registradas, consulta estados y revisa la gestión completa de cada caso.</p>
                    </div>
                </div>
               
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Alertas del día</div>
                            <div class="stat-icon icon-alerta"><i class="fas fa-triangle-exclamation"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalAlertasHoy, 0, ',', '.'); ?></div>
                        <div class="stat-sub">Registros creados hoy en la tabla de alertas</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Atendidas</div>
                            <div class="stat-icon icon-ok"><i class="fas fa-circle-check"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalAtendidas, 0, ',', '.'); ?></div>
                        <div class="stat-sub">Casos con gestión cerrada</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">En proceso</div>
                            <div class="stat-icon icon-proceso"><i class="fas fa-spinner"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalEnProceso, 0, ',', '.'); ?></div>
                        <div class="stat-sub">Casos no atendidos o en atención</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Usuarios registrados</div>
                            <div class="stat-icon icon-user"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalUsuarios, 0, ',', '.'); ?></div>
                        <div class="stat-sub">Conteo por usuaria distinta registrada</div>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="panel-card">
                        <div class="panel-head">
                            <div>
                                <h3>Mapa de ubicación de emergencia</h3>
                                <p>Al dar clic en una emergencia reciente, se ubicará en el mapa y se mostrará toda la gestión abajo.</p>
                            </div>
                            <div class="pulse-legend">
                                <span class="dot"></span>
                                Punto activo
                            </div>
                        </div>

                        <div id="map"></div>

                        <div class="map-info">
                            <div class="info-box">
                                <div class="k">Usuaria</div>
                                <div class="v" id="mapInfoNombre"><?php echo e($mapNombre); ?></div>
                            </div>

                            <div class="info-box">
                                <div class="k">Estado</div>
                                <div class="v" id="mapInfoEstado"><?php echo e($mapEstado); ?></div>
                            </div>

                            <div class="info-box">
                                <div class="k">Usuario atendió</div>
                                <div class="v" id="mapInfoUsuario"><?php echo e($mapUsuario); ?></div>
                            </div>
                        </div>

                        <div class="gestion-section">
                            <div class="gestion-shell">
                                <div class="gestion-header">
                                    <div>
                                        <h3>Resumen de gestión del caso</h3>
                                        <p>Consulta rápidamente la recepción, la atención y el cierre operativo del caso seleccionado.</p>
                                    </div>
                                    <div class="gestion-pill">
                                        <i class="fas fa-shield-heart"></i>
                                        Seguimiento institucional
                                    </div>
                                </div>

                                <div class="gestion-times">
                                    <div class="time-card">
                                        <div class="label">Hora de recepción</div>
                                        <div class="hora" id="horaRecepcion"><?php echo e($mapHoraRecepcion); ?></div>
                                        <div class="fecha" id="fechaRecepcion"><?php echo e($mapFechaRecepcion); ?></div>
                                    </div>

                                    <div class="time-card">
                                        <div class="label">Hora de atención</div>
                                        <div class="hora" id="horaAtencion"><?php echo e($mapHoraAtencion); ?></div>
                                        <div class="fecha" id="fechaAtencion"><?php echo e($mapFechaAtencion); ?></div>
                                    </div>

                                    <div class="time-card">
                                        <div class="label">Hora de gestión</div>
                                        <div class="hora" id="horaGestion"><?php echo e($mapHoraGestion); ?></div>
                                        <div class="fecha" id="fechaGestion"><?php echo e($mapFechaGestion); ?></div>
                                    </div>
                                </div>

                                <div class="gestion-grid">
                                    <div class="gestion-card highlight">
                                        <div class="k"><i class="fas fa-file-lines"></i> Reporte</div>
                                        <div class="v" id="gestionReporte"><?php echo e($mapReporte); ?></div>
                                    </div>

                                    <div class="gestion-card">
                                        <div class="k"><i class="fas fa-list-check"></i> Tarea realizada</div>
                                        <div class="v" id="gestionTarea"><?php echo e($mapTarea); ?></div>
                                    </div>

                                    <div class="gestion-card">
                                        <div class="k"><i class="fas fa-car-side"></i> Cuadrante enviado</div>
                                        <div class="v" id="gestionCuadrante"><?php echo e($mapCuadrante); ?></div>
                                    </div>

                                    <div class="gestion-card full highlight">
                                        <div class="k"><i class="fas fa-clipboard-check"></i> Gestión realizada</div>
                                        <div class="v" id="gestionRealizada"><?php echo e($mapGestion); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-card">
                        <div class="panel-head">
                            <div>
                                <h3>Emergencias recientes</h3>
                                <p>Últimos registros con estado, fecha, hora de atención y usuario que atendió.</p>
                            </div>
                            <div class="badge-soft">
                                <i class="fas fa-clock"></i>
                                Últimos 8 registros
                            </div>
                        </div>

                        <div class="table-wrap">
                            <?php if (!empty($alertasRecientes)): ?>
                                <table class="alert-table" id="tablaRecientes">
                                    <thead>
                                        <tr>
                                            <th>Usuaria</th>
                                            <th>Fecha</th>
                                            <th>Atención</th>
                                            <th>Usuario</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alertasRecientes as $index => $row): ?>
                                            <?php
                                                $lat = str_replace(',', '.', (string)($row['latitud'] ?? ''));
                                                $lng = str_replace(',', '.', (string)($row['longitud'] ?? ''));
                                                $usuarioAtiende = $row['usuario_panel_nombre'] ?? 'Sin asignar';
                                                $estadoTexto = estadoLabel($row['estado_atencion_visualizador'] ?? 'no_atendida');
                                                $estadoClase = estadoClass($row['estado_atencion_visualizador'] ?? 'no_atendida');
                                            ?>
                                            <tr
                                                class="<?php echo $index === 0 ? 'active-row' : ''; ?>"
                                                data-id="<?php echo e($row['id']); ?>"
                                                data-nombre="<?php echo e($row['nombre_usuaria'] ?? 'Sin nombre'); ?>"
                                                data-lat="<?php echo e($lat); ?>"
                                                data-lng="<?php echo e($lng); ?>"
                                                data-estado="<?php echo e($estadoTexto); ?>"
                                                data-usuario="<?php echo e($usuarioAtiende); ?>"
                                                data-reporte="<?php echo e($row['reporte'] ?: 'Sin gestión registrada'); ?>"
                                                data-tarea="<?php echo e($row['tarea_realizada'] ?: 'Sin gestión registrada'); ?>"
                                                data-cuadrante="<?php echo e($row['cuadrante_enviado'] ?: 'Sin gestión registrada'); ?>"
                                                data-gestion="<?php echo e($row['gestion_realizada'] ?: 'Sin gestión registrada'); ?>"
                                                data-hora-recepcion="<?php echo e(formatHora($row['fecha_registro'] ?? '')); ?>"
                                                data-fecha-recepcion="<?php echo e(formatSoloFecha($row['fecha_registro'] ?? '')); ?>"
                                                data-hora-atencion="<?php echo e(formatHora($row['fecha_atencion'] ?? '')); ?>"
                                                data-fecha-atencion="<?php echo e(formatSoloFecha($row['fecha_atencion'] ?? '')); ?>"
                                                data-hora-gestion="<?php echo e(formatHora($row['fecha_gestion'] ?? '')); ?>"
                                                data-fecha-gestion="<?php echo e(formatSoloFecha($row['fecha_gestion'] ?? '')); ?>"
                                            >
                                                <td><?php echo e($row['nombre_usuaria'] ?? 'Sin nombre'); ?></td>
                                                <td><?php echo e(formatFecha($row['fecha_registro'] ?? '')); ?></td>
                                                <td><?php echo e(formatFecha($row['fecha_atencion'] ?? '')); ?></td>
                                                <td>
                                                    <div class="usuario-mini">
                                                        <span class="nombre"><?php echo e($usuarioAtiende); ?></span>
                                                        <span class="sub"><?php echo e($row['usuario_panel_rol'] ?? 'Sin rol'); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="<?php echo e($estadoClase); ?>">
                                                        <?php echo e($estadoTexto); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-box">
                                    No hay alertas registradas para mostrar.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'admin/include/gerenic_script.php'; ?>

<script>
    const santanderCenter = [6.6936, -73.4860];
    const initialAlertPoint = {
        lat: <?php echo json_encode((float)$mapLat); ?>,
        lng: <?php echo json_encode((float)$mapLng); ?>,
        usuaria: <?php echo json_encode((string)$mapNombre); ?>,
        estado: <?php echo json_encode((string)$mapEstado); ?>,
        usuario: <?php echo json_encode((string)$mapUsuario); ?>,
        reporte: <?php echo json_encode((string)$mapReporte); ?>,
        tarea: <?php echo json_encode((string)$mapTarea); ?>,
        cuadrante: <?php echo json_encode((string)$mapCuadrante); ?>,
        gestion: <?php echo json_encode((string)$mapGestion); ?>,
        horaRecepcion: <?php echo json_encode((string)$mapHoraRecepcion); ?>,
        fechaRecepcion: <?php echo json_encode((string)$mapFechaRecepcion); ?>,
        horaAtencion: <?php echo json_encode((string)$mapHoraAtencion); ?>,
        fechaAtencion: <?php echo json_encode((string)$mapFechaAtencion); ?>,
        horaGestion: <?php echo json_encode((string)$mapHoraGestion); ?>,
        fechaGestion: <?php echo json_encode((string)$mapFechaGestion); ?>
    };

    let leafletMap = null;
    let currentMarker = null;
    let currentCircle = null;

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            initLeafletMap();
            initRecentRows();
            hideGlobalLoader();
        }, 250);
    });

    window.addEventListener('load', function () {
        setTimeout(() => {
            if (leafletMap) {
                leafletMap.invalidateSize();
            }
            hideGlobalLoader();
        }, 400);
    });

    function hideGlobalLoader() {
        const loader = document.getElementById('globalLoader');
        if (loader) {
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            loader.style.pointerEvents = 'none';

            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }
    }

    function initLeafletMap() {
        const mapContainer = document.getElementById('map');

        if (!mapContainer) {
            console.warn('No existe el contenedor #map');
            return;
        }

        if (typeof L === 'undefined') {
            console.error('Leaflet no está cargado.');
            mapContainer.innerHTML = `
                <div style="height:100%;display:flex;align-items:center;justify-content:center;color:#4b5563;font-weight:700;padding:20px;text-align:center;">
                    No fue posible cargar el mapa porque Leaflet no está disponible.
                </div>
            `;
            return;
        }

        if (leafletMap) {
            leafletMap.remove();
            leafletMap = null;
        }

        leafletMap = L.map('map', {
            zoomControl: true,
            attributionControl: true
        });

        renderAlertOnMap(initialAlertPoint);

        setTimeout(() => { leafletMap.invalidateSize(); }, 300);
        setTimeout(() => { leafletMap.invalidateSize(); }, 800);
    }

    function renderAlertOnMap(alertData) {
        if (!leafletMap) return;

        const lat = parseFloat(alertData.lat);
        const lng = parseFloat(alertData.lng);
        const hasValidPoint = !isNaN(lat) && !isNaN(lng);

        leafletMap.setView(
            hasValidPoint ? [lat, lng] : santanderCenter,
            hasValidPoint ? 15 : 8
        );

        if (!leafletMap._hasTileLayer) {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(leafletMap);
            leafletMap._hasTileLayer = true;
        }

        if (currentMarker) {
            leafletMap.removeLayer(currentMarker);
            currentMarker = null;
        }

        if (currentCircle) {
            leafletMap.removeLayer(currentCircle);
            currentCircle = null;
        }

        if (hasValidPoint) {
            const blinkingIcon = L.divIcon({
                className: 'custom-map-icon-wrapper',
                html: '<div class="custom-alert-marker"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            currentMarker = L.marker([lat, lng], {
                icon: blinkingIcon
            }).addTo(leafletMap);

            currentMarker.bindPopup(
                '<div style="max-width:280px;font-family:Inter,Arial,sans-serif;">' +
                    '<div style="font-weight:800;font-size:15px;color:#4b1e75;margin-bottom:6px;">' + escapeHtml(alertData.usuaria) + '</div>' +
                    '<div style="font-size:13px;color:#374151;line-height:1.5;"><strong>Estado:</strong> ' + escapeHtml(alertData.estado) + '</div>' +
                    '<div style="font-size:13px;color:#374151;line-height:1.5;margin-top:6px;"><strong>Usuario:</strong> ' + escapeHtml(alertData.usuario) + '</div>' +
                '</div>'
            ).openPopup();

            currentCircle = L.circle([lat, lng], {
                color: '#ff2d75',
                fillColor: '#ff2d75',
                fillOpacity: 0.10,
                radius: 120
            }).addTo(leafletMap);
        }

        document.getElementById('mapInfoNombre').textContent = alertData.usuaria || 'Sin selección';
        document.getElementById('mapInfoEstado').textContent = alertData.estado || 'Sin información';
        document.getElementById('mapInfoUsuario').textContent = alertData.usuario || 'Sin asignar';

        document.getElementById('horaRecepcion').textContent = alertData.horaRecepcion || 'Sin hora';
        document.getElementById('fechaRecepcion').textContent = alertData.fechaRecepcion || 'Sin fecha';
        document.getElementById('horaAtencion').textContent = alertData.horaAtencion || 'Sin hora';
        document.getElementById('fechaAtencion').textContent = alertData.fechaAtencion || 'Sin fecha';
        document.getElementById('horaGestion').textContent = alertData.horaGestion || 'Sin hora';
        document.getElementById('fechaGestion').textContent = alertData.fechaGestion || 'Sin fecha';

        document.getElementById('gestionReporte').textContent = alertData.reporte || 'Sin gestión registrada';
        document.getElementById('gestionTarea').textContent = alertData.tarea || 'Sin gestión registrada';
        document.getElementById('gestionCuadrante').textContent = alertData.cuadrante || 'Sin gestión registrada';
        document.getElementById('gestionRealizada').textContent = alertData.gestion || 'Sin gestión registrada';
    }

    function initRecentRows() {
        const rows = document.querySelectorAll('#tablaRecientes tbody tr');

        rows.forEach(row => {
            row.addEventListener('click', function () {
                rows.forEach(r => r.classList.remove('active-row'));
                this.classList.add('active-row');

                const data = {
                    lat: this.dataset.lat,
                    lng: this.dataset.lng,
                    usuaria: this.dataset.nombre || 'Sin nombre',
                    estado: this.dataset.estado || 'Sin información',
                    usuario: this.dataset.usuario || 'Sin asignar',
                    reporte: this.dataset.reporte || 'Sin gestión registrada',
                    tarea: this.dataset.tarea || 'Sin gestión registrada',
                    cuadrante: this.dataset.cuadrante || 'Sin gestión registrada',
                    gestion: this.dataset.gestion || 'Sin gestión registrada',
                    horaRecepcion: this.dataset.horaRecepcion || 'Sin hora',
                    fechaRecepcion: this.dataset.fechaRecepcion || 'Sin fecha',
                    horaAtencion: this.dataset.horaAtencion || 'Sin hora',
                    fechaAtencion: this.dataset.fechaAtencion || 'Sin fecha',
                    horaGestion: this.dataset.horaGestion || 'Sin hora',
                    fechaGestion: this.dataset.fechaGestion || 'Sin fecha'
                };

                renderAlertOnMap(data);
            });
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }
</script>
</body>
</html>