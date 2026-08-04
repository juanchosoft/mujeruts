<?php
require_once './admin/include/generic_classes.php';

date_default_timezone_set('America/Bogota');

$modulo = 'Estadísticas de Emergencias';

$db = new DbConection();
$pdo = $db->openConect();

$totalEmergencias = 0;
$totalMes = 0;
$totalAtendidas = 0;
$totalEnAtencion = 0;
$totalNoAtendidas = 0;
$totalUsuarias = 0;
$totalUsuariosPanel = 0;
$promedioMinutosAtencion = 0;

$chartMesLabels = [];
$chartMesData = [];

$chartEstadoLabels = ['No atendida', 'En atención', 'Atendida'];
$chartEstadoData = [0, 0, 0];

$chartUsuariosLabels = [];
$chartUsuariosData = [];

$chartDiasLabels = [];
$chartDiasData = [];

$resumenUsuarios = [];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Totales
    $sqlTotales = "SELECT 
                    COUNT(*) AS total_emergencias,
                    SUM(CASE WHEN DATE(fecha_registro) >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS total_mes,
                    SUM(CASE WHEN COALESCE(estado_atencion_visualizador, 'no_atendida') = 'atendida' THEN 1 ELSE 0 END) AS total_atendidas,
                    SUM(CASE WHEN COALESCE(estado_atencion_visualizador, 'no_atendida') = 'en_atencion' THEN 1 ELSE 0 END) AS total_en_atencion,
                    SUM(CASE WHEN COALESCE(estado_atencion_visualizador, 'no_atendida') = 'no_atendida' THEN 1 ELSE 0 END) AS total_no_atendidas,
                    COUNT(DISTINCT usuario_id) AS total_usuarias
                  FROM tbl_alerta_policia";
    $stmtTotales = $pdo->prepare($sqlTotales);
    $stmtTotales->execute();
    $totales = $stmtTotales->fetch(PDO::FETCH_ASSOC);

    $totalEmergencias = (int)($totales['total_emergencias'] ?? 0);
    $totalMes = (int)($totales['total_mes'] ?? 0);
    $totalAtendidas = (int)($totales['total_atendidas'] ?? 0);
    $totalEnAtencion = (int)($totales['total_en_atencion'] ?? 0);
    $totalNoAtendidas = (int)($totales['total_no_atendidas'] ?? 0);
    $totalUsuarias = (int)($totales['total_usuarias'] ?? 0);
    // Usuarios del panel registrados en tabla usuarios_panel
    $sqlUsuariosPanel = "SELECT COUNT(*) AS total FROM usuarios_panel";
    $stmtUsuariosPanel = $pdo->prepare($sqlUsuariosPanel);
    $stmtUsuariosPanel->execute();
    $totalUsuariosPanel = (int)($stmtUsuariosPanel->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $chartEstadoData = [$totalNoAtendidas, $totalEnAtencion, $totalAtendidas];

    // Promedio tiempo atención en minutos
    $sqlPromedio = "SELECT 
                        AVG(TIMESTAMPDIFF(MINUTE, fecha_registro, fecha_atencion)) AS promedio_minutos
                    FROM tbl_alerta_policia
                    WHERE fecha_registro IS NOT NULL
                      AND fecha_atencion IS NOT NULL
                      AND COALESCE(estado_atencion_visualizador, 'no_atendida') = 'atendida'";
    $stmtProm = $pdo->prepare($sqlPromedio);
    $stmtProm->execute();
    $promRow = $stmtProm->fetch(PDO::FETCH_ASSOC);
    $promedioMinutosAtencion = (float)($promRow['promedio_minutos'] ?? 0);

    // Emergencias por mes
    $sqlMes = "SELECT 
                    DATE_FORMAT(fecha_registro, '%Y-%m') AS periodo,
                    COUNT(*) AS total
               FROM tbl_alerta_policia
               WHERE fecha_registro IS NOT NULL
               GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
               ORDER BY periodo ASC";
    $stmtMes = $pdo->prepare($sqlMes);
    $stmtMes->execute();
    $rowsMes = $stmtMes->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsMes as $row) {
        $chartMesLabels[] = $row['periodo'];
        $chartMesData[] = (int)$row['total'];
    }

    // Emergencias atendidas por usuario
    $sqlUsuarios = "SELECT 
                        COALESCE(g.usuario_panel_nombre, 'Sin asignar') AS usuario_nombre,
                        COUNT(*) AS total_atendidas
                    FROM tbl_alerta_policia_gestion g
                    GROUP BY COALESCE(g.usuario_panel_nombre, 'Sin asignar')
                    ORDER BY total_atendidas DESC, usuario_nombre ASC
                    LIMIT 10";
    $stmtUsuarios = $pdo->prepare($sqlUsuarios);
    $stmtUsuarios->execute();
    $rowsUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsUsuarios as $row) {
        $chartUsuariosLabels[] = $row['usuario_nombre'];
        $chartUsuariosData[] = (int)$row['total_atendidas'];
    }

    // Últimos 30 días
    $sqlDias = "SELECT 
                    DATE(fecha_registro) AS fecha_dia,
                    COUNT(*) AS total
                FROM tbl_alerta_policia
                WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(fecha_registro)
                ORDER BY fecha_dia ASC";
    $stmtDias = $pdo->prepare($sqlDias);
    $stmtDias->execute();
    $rowsDias = $stmtDias->fetchAll(PDO::FETCH_ASSOC);

    $mapDias = [];
    foreach ($rowsDias as $row) {
        $mapDias[$row['fecha_dia']] = (int)$row['total'];
    }

    for ($i = 29; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-{$i} days"));
        $chartDiasLabels[] = date('d/m', strtotime($fecha));
        $chartDiasData[] = $mapDias[$fecha] ?? 0;
    }

    // Resumen por usuario
    $sqlResumenUsuarios = "SELECT
                                COALESCE(g.usuario_panel_nombre, 'Sin asignar') AS usuario_nombre,
                                COALESCE(g.usuario_panel_rol, 'Sin rol') AS rol,
                                COUNT(*) AS total_gestiones,
                                MAX(g.fecha_gestion) AS ultima_gestion
                           FROM tbl_alerta_policia_gestion g
                           GROUP BY COALESCE(g.usuario_panel_nombre, 'Sin asignar'), COALESCE(g.usuario_panel_rol, 'Sin rol')
                           ORDER BY total_gestiones DESC, usuario_nombre ASC";
    $stmtResumenUsuarios = $pdo->prepare($sqlResumenUsuarios);
    $stmtResumenUsuarios->execute();
    $resumenUsuarios = $stmtResumenUsuarios->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $totalUsuariosPanel = 0;
    $chartMesLabels = [];
    $chartMesData = [];
    $chartUsuariosLabels = [];
    $chartUsuariosData = [];
    $chartDiasLabels = [];
    $chartDiasData = [];
    $resumenUsuarios = [];
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function minutosBonitos($minutos) {
    $minutos = (int)round((float)$minutos);
    if ($minutos <= 0) {
        return 'Sin datos';
    }

    $horas = floor($minutos / 60);
    $mins = $minutos % 60;

    if ($horas > 0) {
        return $horas . ' h ' . $mins . ' min';
    }

    return $mins . ' min';
}

function fechaBonita($fecha) {
    if (!$fecha) return 'Sin fecha';
    $ts = strtotime($fecha);
    if (!$ts) return e($fecha);
    return date('d/m/Y h:i A', $ts);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estadísticas de Emergencias | Violeta Santander</title>
   <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
 <link rel="stylesheet" href="assets/css/estadistica.css">
   
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
                        <h5 class="m-b-10 text-white">Estadísticas de Emergencias</h5>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="feather icon-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="#!">Panel de Control</a></li>
                            <li class="breadcrumb-item"><a href="#!">Estadísticas</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-card">
            <h1>Centro Analítico de Emergencias</h1>
            <p>Monitorea el comportamiento mensual de las emergencias, revisa estados, rendimiento por usuario y tendencias operativas en una vista ejecutiva más visual y completa.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total emergencias</div>
                <div class="stat-value"><?php echo number_format($totalEmergencias, 0, ',', '.'); ?></div>
                <div class="stat-sub">Todos los registros acumulados</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Emergencias del mes</div>
                <div class="stat-value"><?php echo number_format($totalMes, 0, ',', '.'); ?></div>
                <div class="stat-sub">Registros del mes actual</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Atendidas</div>
                <div class="stat-value"><?php echo number_format($totalAtendidas, 0, ',', '.'); ?></div>
                <div class="stat-sub">Casos con cierre operativo</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">En atención</div>
                <div class="stat-value"><?php echo number_format($totalEnAtencion, 0, ',', '.'); ?></div>
                <div class="stat-sub">Casos en gestión activa</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">No atendidas</div>
                <div class="stat-value"><?php echo number_format($totalNoAtendidas, 0, ',', '.'); ?></div>
                <div class="stat-sub">Casos pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Usuarios del panel</div>
                <div class="stat-value"><?php echo number_format($totalUsuariosPanel, 0, ',', '.'); ?></div>
                <div class="stat-sub">Usuarios registrados en la tabla usuarios_panel</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Promedio atención</div>
                <div class="stat-value" style="font-size:24px;"><?php echo e(minutosBonitos($promedioMinutosAtencion)); ?></div>
                <div class="stat-sub">Tiempo promedio de respuesta</div>
            </div>

        </div>

        <div class="charts-grid">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3>Emergencias por mes</h3>
                        <p>Evolución histórica de registros mes a mes.</p>
                    </div>
                    <div class="badge-soft">
                        <i class="fas fa-chart-column"></i>
                        Tendencia mensual
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap">
                        <canvas id="chartMes"></canvas>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3>Distribución por estado</h3>
                        <p>Comparativo actual entre pendientes, en atención y atendidas.</p>
                    </div>
                    <div class="badge-soft">
                        <i class="fas fa-chart-pie"></i>
                        Estado actual
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap small">
                        <canvas id="chartEstado"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-grid-2">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3>Atendidas por usuario</h3>
                        <p>Top de usuarios con mayor cantidad de gestiones registradas.</p>
                    </div>
                    <div class="badge-soft">
                        <i class="fas fa-users"></i>
                        Rendimiento operativo
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap">
                        <canvas id="chartUsuarios"></canvas>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3>Últimos 30 días</h3>
                        <p>Comportamiento diario reciente de las emergencias registradas.</p>
                    </div>
                    <div class="badge-soft">
                        <i class="fas fa-chart-line"></i>
                        Tendencia diaria
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap">
                        <canvas id="chartDias"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <h3>Resumen por usuario</h3>
                    <p>Detalle consolidado de usuarios con gestiones y fecha de última intervención.</p>
                </div>
                <div class="badge-soft">
                    <i class="fas fa-table"></i>
                    Resumen operativo
                </div>
            </div>

            <div class="table-wrap">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Total gestiones</th>
                            <th>Última gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($resumenUsuarios)): ?>
                            <?php foreach ($resumenUsuarios as $row): ?>
                                <tr>
                                    <td>
                                        <div class="user-box">
                                            <span class="name"><?php echo e($row['usuario_nombre']); ?></span>
                                            <span class="role"><?php echo e($row['rol']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo e($row['rol']); ?></td>
                                    <td><?php echo e(number_format((int)$row['total_gestiones'], 0, ',', '.')); ?></td>
                                    <td><?php echo e(fechaBonita($row['ultima_gestion'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No hay gestiones registradas para mostrar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include './admin/include/gerenic_script.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartMesLabels = <?php echo json_encode($chartMesLabels ?? []); ?>;
    const chartMesData = <?php echo json_encode($chartMesData ?? []); ?>;

    const chartEstadoLabels = <?php echo json_encode($chartEstadoLabels ?? []); ?>;
    const chartEstadoData = <?php echo json_encode($chartEstadoData ?? []); ?>;

    const chartUsuariosLabels = <?php echo json_encode($chartUsuariosLabels ?? []); ?>;
    const chartUsuariosData = <?php echo json_encode($chartUsuariosData ?? []); ?>;

    const chartDiasLabels = <?php echo json_encode($chartDiasLabels ?? []); ?>;
    const chartDiasData = <?php echo json_encode($chartDiasData ?? []); ?>;

    function showEmptyState(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const parent = canvas.parentElement;
        if (!parent) return;

        canvas.style.display = 'none';

        const empty = document.createElement('div');
        empty.className = 'chart-empty';
        empty.textContent = message;
        parent.appendChild(empty);
    }

    function getCanvas(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error('No existe canvas:', canvasId);
            return null;
        }
        return canvas;
    }

    function safeCreateChart(canvasId, config, emptyMessage = 'No hay datos disponibles para este gráfico.') {
        const canvas = getCanvas(canvasId);
        if (!canvas) return;

        if (typeof Chart === 'undefined') {
            console.error('Chart.js no está disponible');
            showEmptyState(canvasId, 'No fue posible cargar la librería de gráficos.');
            return;
        }

        const hasData =
            config &&
            config.data &&
            Array.isArray(config.data.datasets) &&
            config.data.datasets.length > 0 &&
            config.data.datasets.some(ds => Array.isArray(ds.data) && ds.data.length > 0);

        if (!hasData) {
            showEmptyState(canvasId, emptyMessage);
            return;
        }

        try {
            new Chart(canvas, config);
        } catch (error) {
            console.error('Error al crear gráfico', canvasId, error);
            showEmptyState(canvasId, 'Ocurrió un error al dibujar el gráfico.');
        }
    }

    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#d9c9eb';
        Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
        Chart.defaults.font = Chart.defaults.font || {};
        Chart.defaults.font.family = 'Inter, Arial, sans-serif';
    }

    safeCreateChart('chartMes', {
        type: 'bar',
        data: {
            labels: chartMesLabels,
            datasets: [{
                label: 'Emergencias',
                data: chartMesData,
                borderWidth: 0,
                borderRadius: 10,
                backgroundColor: 'rgba(255, 45, 117, 0.85)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    ticks: { color: '#d9c9eb' },
                    grid: { color: 'rgba(255,255,255,.06)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#d9c9eb' },
                    grid: { color: 'rgba(255,255,255,.06)' }
                }
            }
        }
    });

    safeCreateChart('chartEstado', {
        type: 'doughnut',
        data: {
            labels: chartEstadoLabels,
            datasets: [{
                data: chartEstadoData,
                borderWidth: 0,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.9)',
                    'rgba(245, 158, 11, 0.9)',
                    'rgba(34, 197, 94, 0.9)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#d9c9eb' }
                }
            }
        }
    });

    safeCreateChart('chartUsuarios', {
        type: 'bar',
        data: {
            labels: chartUsuariosLabels,
            datasets: [{
                label: 'Atendidas',
                data: chartUsuariosData,
                borderWidth: 0,
                borderRadius: 10,
                backgroundColor: 'rgba(139, 61, 255, 0.88)'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#d9c9eb' },
                    grid: { color: 'rgba(255,255,255,.06)' }
                },
                y: {
                    ticks: { color: '#d9c9eb' },
                    grid: { color: 'rgba(255,255,255,.06)' }
                }
            }
        }
    });

    safeCreateChart('chartDias', {
        type: 'line',
        data: {
            labels: chartDiasLabels,
            datasets: [{
                label: 'Emergencias',
                data: chartDiasData,
                tension: 0.35,
                fill: false,
                borderWidth: 3,
                pointRadius: 3,
                borderColor: 'rgba(56, 189, 248, 0.95)',
                pointBackgroundColor: 'rgba(56, 189, 248, 0.95)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    ticks: { color: '#d9c9eb' },
                    grid: { color: 'rgba(255,255,255,.06)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#d9c9eb' },
                    grid: { color: 'rgba(255,255,255,.06)' }
                }
            }
        }
    });
});

function exportarExcel() {
    const tabla = document.getElementById('tablaResumenUsuarios');
    if (!tabla || typeof XLSX === 'undefined') return;

    const wb = XLSX.utils.table_to_book(tabla, { sheet: "ResumenUsuarios" });
    XLSX.writeFile(wb, 'estadisticas_emergencias.xlsx');
}

async function exportarPDF() {
    if (typeof window.jspdf === 'undefined') return;

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');

    doc.setFontSize(16);
    doc.text('Estadísticas de Emergencias', 14, 14);

    doc.setFontSize(10);
    doc.text('Resumen por usuario', 14, 22);

    doc.autoTable({
        html: '#tablaResumenUsuarios',
        startY: 28,
        theme: 'grid',
        styles: {
            fontSize: 9
        },
        headStyles: {
            fillColor: [90, 30, 120]
        }
    });

    doc.save('estadisticas_emergencias.pdf');
}
</script>



</body>
</html>