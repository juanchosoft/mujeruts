<?php
require_once './admin/include/generic_classes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

$modulo = 'Emergencias';

$db = new DbConection();
$pdo = $db->openConect();

$rows = [];

try {
    $sql = "SELECT 
                a.id AS alerta_id,
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

                g.id AS gestion_id,
                g.usuario_panel_id,
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
            LEFT JOIN tbl_alerta_policia_gestion g 
                ON g.alerta_id = a.id
            ORDER BY a.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fechaBonita($f) {
    if (!$f) return 'Sin fecha';
    $ts = strtotime($f);
    if (!$ts) return e($f);
    return date('d/m/Y h:i A', $ts);
}

function estadoLabel($estado) {
    $estado = strtolower(trim((string)$estado));
    if ($estado === 'atendida') return 'Atendida';
    if ($estado === 'en_atencion') return 'En atención';
    return 'No atendida';
}

function estadoClass($estado) {
    $estado = strtolower(trim((string)$estado));
    if ($estado === 'atendida') return 'estado-chip estado-ok';
    if ($estado === 'en_atencion') return 'estado-chip estado-proceso';
    return 'estado-chip estado-no';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Emergencias | Violeta Santander</title>
    <link rel="stylesheet" href="assets/css/ver_emergencia.css">

    <style>
        .usuario-mini{
            display:flex;
            flex-direction:column;
            gap:3px;
            min-width:140px;
        }

        .usuario-mini .nombre{
            color:#fff;
            font-size:13px;
            font-weight:800;
            line-height:1.2;
        }

        .usuario-mini .rol{
            color:#b9dff0;
            font-size:11px;
            font-weight:600;
            line-height:1.2;
        }

        .estado-chip{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:110px;
            padding:8px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
        }

        .estado-ok{
            background:rgba(34,197,94,.16);
            border:1px solid rgba(34,197,94,.22);
            color:#dcfce7;
        }

        .estado-proceso{
            background:rgba(245,158,11,.16);
            border:1px solid rgba(245,158,11,.22);
            color:#ffedd5;
        }

        .estado-no{
            background:rgba(239,68,68,.16);
            border:1px solid rgba(239,68,68,.22);
            color:#ffe1e1;
        }
    </style>
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
                        <h5 class="m-b-10 text-white">Emergencias</h5>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="feather icon-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="#!">Panel de Control</a></li>
                            <li class="breadcrumb-item"><a href="#!">Historial</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-card">
            <h1>Historial general de emergencias</h1>
            <p>Consulta todas las emergencias registradas, sin importar si están no atendidas, en atención o atendidas, junto con el detalle de la gestión cuando exista.</p>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <h3>Listado general</h3>
                    <p>Consulta completa del estado y la trazabilidad operativa.</p>
                </div>
                <div class="badge-soft">
                    <i class="fas fa-list"></i>
                    <?php echo count($rows); ?> registros
                </div>
            </div>

            <div class="table-wrap">
                <?php if (!empty($rows)): ?>
                    <table id="tablaAtendidas" class="table-violeta">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuaria</th>
                                <th>Dirección</th>
                                <th>Usuario atendió</th>
                                <th>Fecha alerta</th>
                                <th>Fecha atención</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo e($r['alerta_id']); ?></td>
                                    <td><?php echo e($r['nombre_usuaria']); ?></td>
                                    <td><?php echo e($r['direccion_detectada']); ?></td>
                                    <td>
                                        <div class="usuario-mini">
                                            <span class="nombre"><?php echo e($r['usuario_panel_nombre'] ?: 'Sin asignar'); ?></span>
                                            <span class="rol"><?php echo e($r['usuario_panel_rol'] ?: 'Sin rol'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo e(fechaBonita($r['fecha_registro'])); ?></td>
                                    <td><?php echo e(fechaBonita($r['fecha_atencion'])); ?></td>
                                    <td>
                                        <span class="<?php echo e(estadoClass($r['estado_atencion_visualizador'])); ?>">
                                            <?php echo e(estadoLabel($r['estado_atencion_visualizador'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn-view btnDetalle"
                                            data-alerta_id="<?php echo e($r['alerta_id']); ?>"
                                            data-nombre_usuaria="<?php echo e($r['nombre_usuaria']); ?>"
                                            data-celular_usuaria="<?php echo e($r['celular_usuaria']); ?>"
                                            data-contacto_emergencia="<?php echo e($r['contacto_emergencia']); ?>"
                                            data-nombre_contacto_emergencia="<?php echo e($r['nombre_contacto_emergencia']); ?>"
                                            data-direccion_detectada="<?php echo e($r['direccion_detectada']); ?>"
                                            data-latitud="<?php echo e($r['latitud']); ?>"
                                            data-longitud="<?php echo e($r['longitud']); ?>"
                                            data-precision_metros="<?php echo e($r['precision_metros']); ?>"
                                            data-usuario_panel_nombre="<?php echo e($r['usuario_panel_nombre']); ?>"
                                            data-usuario_panel_usuario="<?php echo e($r['usuario_panel_usuario']); ?>"
                                            data-usuario_panel_correo="<?php echo e($r['usuario_panel_correo']); ?>"
                                            data-usuario_panel_rol="<?php echo e($r['usuario_panel_rol']); ?>"
                                            data-estado="<?php echo e(estadoLabel($r['estado_atencion_visualizador'])); ?>"
                                            data-reporte="<?php echo e($r['reporte']); ?>"
                                            data-tarea_realizada="<?php echo e($r['tarea_realizada']); ?>"
                                            data-cuadrante_enviado="<?php echo e($r['cuadrante_enviado']); ?>"
                                            data-gestion_realizada="<?php echo e($r['gestion_realizada']); ?>"
                                            data-fecha_gestion="<?php echo e(fechaBonita($r['fecha_gestion'])); ?>"
                                            data-fecha_alerta="<?php echo e(fechaBonita($r['fecha_registro'])); ?>"
                                            data-fecha_atencion="<?php echo e(fechaBonita($r['fecha_atencion'])); ?>"
                                        >
                                            <i class="fas fa-eye"></i> Ver detalle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-box">
                        No hay emergencias registradas.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="modal fade modal-violeta" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de emergencia</h5>
                <button type="button" class="btn-close btn-close-white" id="btnCerrarModalX" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-box"><div class="k">ID alerta</div><div class="v" id="d_alerta_id">-</div></div>
                    <div class="detail-box"><div class="k">Estado</div><div class="v" id="d_estado">-</div></div>
                    <div class="detail-box"><div class="k">Usuaria</div><div class="v" id="d_nombre_usuaria">-</div></div>
                    <div class="detail-box"><div class="k">Celular usuaria</div><div class="v" id="d_celular_usuaria">-</div></div>
                    <div class="detail-box"><div class="k">Contacto de emergencia</div><div class="v" id="d_contacto_emergencia">-</div></div>
                    <div class="detail-box"><div class="k">Nombre del contacto</div><div class="v" id="d_nombre_contacto_emergencia">-</div></div>
                    <div class="detail-box"><div class="k">Dirección detectada</div><div class="v" id="d_direccion_detectada">-</div></div>
                    <div class="detail-box"><div class="k">Latitud</div><div class="v" id="d_latitud">-</div></div>
                    <div class="detail-box"><div class="k">Longitud</div><div class="v" id="d_longitud">-</div></div>
                    <div class="detail-box"><div class="k">Precisión en metros</div><div class="v" id="d_precision_metros">-</div></div>

                    <div class="detail-box"><div class="k">Usuario atendió</div><div class="v" id="d_usuario_panel_nombre">-</div></div>
                    <div class="detail-box"><div class="k">Usuario login</div><div class="v" id="d_usuario_panel_usuario">-</div></div>
                    <div class="detail-box"><div class="k">Correo del usuario</div><div class="v" id="d_usuario_panel_correo">-</div></div>
                    <div class="detail-box"><div class="k">Rol del usuario</div><div class="v" id="d_usuario_panel_rol">-</div></div>

                    <div class="detail-box"><div class="k">Fecha alerta</div><div class="v" id="d_fecha_alerta">-</div></div>
                    <div class="detail-box"><div class="k">Fecha atención</div><div class="v" id="d_fecha_atencion">-</div></div>
                    <div class="detail-box"><div class="k">Fecha gestión</div><div class="v" id="d_fecha_gestion">-</div></div>

                    <div class="detail-box full"><div class="k">Reporte</div><div class="v" id="d_reporte">-</div></div>
                    <div class="detail-box full"><div class="k">Tarea realizada</div><div class="v" id="d_tarea_realizada">-</div></div>
                    <div class="detail-box"><div class="k">Cuadrante enviado</div><div class="v" id="d_cuadrante_enviado">-</div></div>
                    <div class="detail-box full"><div class="k">Gestión realizada</div><div class="v" id="d_gestion_realizada">-</div></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" id="btnCerrarModal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php include './admin/include/gerenic_script.php'; ?>

<!-- Bootstrap 5 JS requerido por esta página (modal API) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $.fn.DataTable && document.getElementById('tablaAtendidas')) {
            $('#tablaAtendidas').DataTable({
                pageLength: 10,
                responsive: true,
                ordering: true,
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    zeroRecords: "No se encontraron registros",
                    infoEmpty: "Sin registros disponibles",
                    infoFiltered: "(filtrado de _MAX_ registros)"
                }
            });
        }

        const modalElement = document.getElementById('modalDetalle');
        const modal = new bootstrap.Modal(modalElement);

        document.getElementById('btnCerrarModalX').addEventListener('click', function() { modal.hide(); });
        document.getElementById('btnCerrarModal').addEventListener('click', function()  { modal.hide(); });

        document.querySelectorAll('.btnDetalle').forEach(btn => {
            btn.addEventListener('click', function () {
                setDetail('d_alerta_id', this.dataset.alerta_id);
                setDetail('d_estado', this.dataset.estado);
                setDetail('d_nombre_usuaria', this.dataset.nombre_usuaria);
                setDetail('d_celular_usuaria', this.dataset.celular_usuaria);
                setDetail('d_contacto_emergencia', this.dataset.contacto_emergencia);
                setDetail('d_nombre_contacto_emergencia', this.dataset.nombre_contacto_emergencia);
                setDetail('d_direccion_detectada', this.dataset.direccion_detectada);
                setDetail('d_latitud', this.dataset.latitud);
                setDetail('d_longitud', this.dataset.longitud);
                setDetail('d_precision_metros', this.dataset.precision_metros);

                setDetail('d_usuario_panel_nombre', this.dataset.usuario_panel_nombre);
                setDetail('d_usuario_panel_usuario', this.dataset.usuario_panel_usuario);
                setDetail('d_usuario_panel_correo', this.dataset.usuario_panel_correo);
                setDetail('d_usuario_panel_rol', this.dataset.usuario_panel_rol);

                setDetail('d_reporte', this.dataset.reporte);
                setDetail('d_tarea_realizada', this.dataset.tarea_realizada);
                setDetail('d_cuadrante_enviado', this.dataset.cuadrante_enviado);
                setDetail('d_gestion_realizada', this.dataset.gestion_realizada);
                setDetail('d_fecha_gestion', this.dataset.fecha_gestion);
                setDetail('d_fecha_alerta', this.dataset.fecha_alerta);
                setDetail('d_fecha_atencion', this.dataset.fecha_atencion);

                modal.show();
            });
        });

        function setDetail(id, value) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = value && value.trim() !== '' ? value : '-';
            }
        }
    });
</script>
</body>
</html>