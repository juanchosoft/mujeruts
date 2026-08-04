<?php
require_once './admin/include/generic_classes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

$modulo = 'Creación de Usuarios';

// Leer flash de sesión
$mensaje     = $_SESSION['flash_msg']  ?? '';
$tipoMensaje = $_SESSION['flash_tipo'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_tipo']);

$db = new DbConection();
$pdo = $db->openConect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Acción: activar, inactivar, eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion    = trim($_POST['accion'] ?? '');
    $target_id = (int)($_POST['target_id'] ?? 0);

    if ($target_id > 0) {
        try {
            if ($accion === 'activar') {
                $s = $pdo->prepare("UPDATE usuarios_panel SET estado = 'activo' WHERE id = :id");
                $s->bindValue(':id', $target_id, PDO::PARAM_INT);
                $s->execute();
                $_SESSION['flash_msg']  = 'Usuario activado.';
                $_SESSION['flash_tipo'] = 'success';
            } elseif ($accion === 'inactivar') {
                $s = $pdo->prepare("UPDATE usuarios_panel SET estado = 'inactivo' WHERE id = :id");
                $s->bindValue(':id', $target_id, PDO::PARAM_INT);
                $s->execute();
                $_SESSION['flash_msg']  = 'Usuario inactivado.';
                $_SESSION['flash_tipo'] = 'success';
            } elseif ($accion === 'eliminar') {
                $s = $pdo->prepare("DELETE FROM usuarios_panel WHERE id = :id");
                $s->bindValue(':id', $target_id, PDO::PARAM_INT);
                $s->execute();
                $_SESSION['flash_msg']  = 'Usuario eliminado.';
                $_SESSION['flash_tipo'] = 'success';
            }
        } catch (Throwable $ex) {
            $_SESSION['flash_msg']  = 'Error al procesar la acción: ' . $ex->getMessage();
            $_SESSION['flash_tipo'] = 'error';
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['accion'])) {
    $edit_id         = (int)($_POST['edit_id'] ?? 0);
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $usuario         = trim($_POST['usuario'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $password        = trim($_POST['password'] ?? '');
    $rol             = trim($_POST['rol'] ?? '');
    $estado          = trim($_POST['estado'] ?? 'activo');

    $modoEdicion = $edit_id > 0;

    if ($nombre_completo === '' || $usuario === '' || $correo === '' || $rol === '' ||
        (!$modoEdicion && $password === '')) {
        $mensaje = 'Todos los campos son obligatorios' . ($modoEdicion ? ' (contraseña opcional).' : '.');
        $tipoMensaje = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El correo electrónico no es válido.';
        $tipoMensaje = 'error';
    } elseif (!in_array($rol, ['Administrador', 'Visor'], true)) {
        $mensaje = 'El rol seleccionado no es válido.';
        $tipoMensaje = 'error';
    } else {
        try {
            if ($modoEdicion) {
                // ── EDITAR ────────────────────────────────────────────────
                if ($password !== '') {
                    $s = $pdo->prepare("UPDATE usuarios_panel SET nombre_completo=:n, usuario=:u, correo=:c, rol=:r, estado=:e, password_hash=:p WHERE id=:id");
                    $s->bindValue(':p', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
                } else {
                    $s = $pdo->prepare("UPDATE usuarios_panel SET nombre_completo=:n, usuario=:u, correo=:c, rol=:r, estado=:e WHERE id=:id");
                }
                $s->bindValue(':n',  $nombre_completo, PDO::PARAM_STR);
                $s->bindValue(':u',  $usuario,         PDO::PARAM_STR);
                $s->bindValue(':c',  $correo,           PDO::PARAM_STR);
                $s->bindValue(':r',  $rol,              PDO::PARAM_STR);
                $s->bindValue(':e',  $estado,           PDO::PARAM_STR);
                $s->bindValue(':id', $edit_id,          PDO::PARAM_INT);
                $s->execute();
                $_SESSION['flash_msg']  = 'Usuario actualizado correctamente.';
                $_SESSION['flash_tipo'] = 'success';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                // ── CREAR ─────────────────────────────────────────────────
                $sqlExiste = "SELECT id FROM usuarios_panel WHERE usuario = :usuario OR correo = :correo LIMIT 1";
                $stmtExiste = $pdo->prepare($sqlExiste);
                $stmtExiste->bindValue(':usuario', $usuario, PDO::PARAM_STR);
                $stmtExiste->bindValue(':correo', $correo, PDO::PARAM_STR);
                $stmtExiste->execute();

                if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {
                        $mensaje = 'Ya existe un usuario o correo registrado.';
                        $tipoMensaje = 'error';
                    } else {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $sqlInsert = "INSERT INTO usuarios_panel (
                                        nombre_completo, usuario, correo,
                                        password_hash, rol, estado, fecha_creacion
                                      ) VALUES (
                                        :nombre_completo, :usuario, :correo,
                                        :password_hash, :rol, :estado, NOW()
                                      )";
                        $stmtInsert = $pdo->prepare($sqlInsert);
                        $stmtInsert->bindValue(':nombre_completo', $nombre_completo, PDO::PARAM_STR);
                        $stmtInsert->bindValue(':usuario',         $usuario,         PDO::PARAM_STR);
                        $stmtInsert->bindValue(':correo',          $correo,          PDO::PARAM_STR);
                        $stmtInsert->bindValue(':password_hash',   $passwordHash,    PDO::PARAM_STR);
                        $stmtInsert->bindValue(':rol',             $rol,             PDO::PARAM_STR);
                        $stmtInsert->bindValue(':estado',          $estado,          PDO::PARAM_STR);
                        $stmtInsert->execute();
                        $_SESSION['flash_msg']  = 'Usuario creado correctamente.';
                        $_SESSION['flash_tipo'] = 'success';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
            } // fin else CREAR
        } catch (Throwable $e) {
            $mensaje = 'Ocurrió un error al guardar el usuario: ' . $e->getMessage();
            $tipoMensaje = 'error';
        }
    }
}

// Cargar lista de usuarios panel (siempre al final para reflejar cambios)
$usuariosPanel = [];
try {
    $stmtLista = $pdo->query("SELECT id, nombre_completo, usuario, correo, rol, estado, fecha_creacion, ultimo_login FROM usuarios_panel ORDER BY id DESC");
    $usuariosPanel = $stmtLista->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    $usuariosPanel = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Usuario | Violeta Santander</title>

    <style>
        :root{
            --vs-sidebar-width: 310px;
            --vs-sidebar-collapsed: 80px;
            --vs-header-height: 92px;
        }

        html, body{
            overflow-x:hidden;
        }

        body.dashboard-body{
            margin:0;
            padding:0;
            background:
                radial-gradient(circle at top left, rgba(217,70,239,.14), transparent 24%),
                radial-gradient(circle at bottom right, rgba(139,61,255,.12), transparent 26%),
                linear-gradient(135deg, #12071c 0%, #1a0f28 35%, #211233 70%, #160c22 100%);
            min-height:100vh;
        }

        .pcoded-main-container{
            margin-left: var(--vs-sidebar-width) !important;
            padding-top: var(--vs-header-height) !important;
            min-height:100vh;
            transition:margin-left .25s ease;
        }

        .pcoded-content{
            padding:22px 24px 24px !important;
        }

        body.nav-collapse .pcoded-main-container{
            margin-left: var(--vs-sidebar-collapsed) !important;
        }

        .hero-card,
        .panel-card{
            background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
            border:1px solid rgba(255,255,255,.08);
            border-radius:24px;
            box-shadow:0 18px 45px rgba(0,0,0,.22);
        }

        .hero-card{
            padding:24px;
            margin-bottom:18px;
        }

        .hero-card h1{
            margin:0 0 8px 0;
            color:#fff;
            font-size:30px;
            font-weight:900;
        }

        .hero-card p{
            margin:0;
            color:#c7b7d9;
            font-size:14px;
        }

        .panel-card{
            padding:24px;
        }

        .form-label{
            color:#eadcff;
            font-weight:700;
            margin-bottom:8px;
        }

        .form-control,
        .form-select{
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.10);
            color:#fff;
            border-radius:14px;
            min-height:48px;
        }

        .form-control:focus,
        .form-select:focus{
            background:rgba(255,255,255,.08);
            color:#fff;
            border-color:rgba(255,45,117,.45);
            box-shadow:0 0 0 4px rgba(255,45,117,.08);
        }

        .btn-violeta{
            background:linear-gradient(135deg, #ff2d55, #ff5a6e);
            border:none;
            color:#fff;
            font-weight:800;
            border-radius:14px;
            padding:12px 18px;
        }

        .btn-violeta:hover{
            color:#fff;
        }

        .btn-cancelar{
            background:transparent;
            border:1px solid rgba(255,255,255,.22);
            color:#c7b7d9;
            border-radius:14px;
            padding:12px 18px;
            font-weight:700;
            transition:.2s ease;
        }

        .btn-cancelar:hover{
            background:rgba(255,255,255,.06);
            color:#fff;
        }

        #heroCard{
            transition: background .3s ease, border-color .3s ease;
        }

        #heroCard.modo-edicion{
            background: linear-gradient(180deg, rgba(255,45,85,.08), rgba(255,255,255,.02));
            border-color: rgba(255,45,85,.22);
        }

        .alert-violeta-success,
        .alert-violeta-error{
            border-radius:16px;
            padding:14px 16px;
            font-weight:700;
            margin-bottom:18px;
            transition: opacity .5s ease, max-height .5s ease, padding .5s ease, margin .5s ease;
            overflow: hidden;
        }

        .alert-violeta-success{
            background:rgba(34,197,94,.14);
            border:1px solid rgba(34,197,94,.24);
            color:#dcfce7;
        }

        .alert-violeta-error{
            background:rgba(239,68,68,.14);
            border:1px solid rgba(239,68,68,.24);
            color:#ffe4e6;
        }

        /* Tabla de usuarios */
        .users-table-card{
            margin-top:18px;
            padding:24px;
        }

        .users-table-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:18px;
            gap:12px;
            flex-wrap:wrap;
        }

        .search-wrap{
            display:flex;
            align-items:center;
            gap:10px;
            background:rgba(255,255,255,.05);
            border:1px solid rgba(255,255,255,.10);
            border-radius:14px;
            padding:0 14px;
            min-width:220px;
        }

        .search-wrap i{
            color:#9da7ba;
            font-size:14px;
            flex-shrink:0;
        }

        .search-wrap input{
            background:transparent;
            border:none;
            outline:none;
            color:#fff;
            font-size:14px;
            padding:10px 0;
            width:100%;
        }

        .search-wrap input::placeholder{ color:#9da7ba; }

        .no-results{
            text-align:center;
            padding:28px;
            color:#9da7ba;
            font-size:14px;
            display:none;
        }

        .users-table-head h2{
            margin:0;
            color:#fff;
            font-size:20px;
            font-weight:900;
        }

        .users-table-head p{
            margin:4px 0 0;
            color:#c7b7d9;
            font-size:13px;
        }

        .badge-count{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:32px;
            height:32px;
            padding:0 10px;
            border-radius:999px;
            background:rgba(255,45,85,.18);
            border:1px solid rgba(255,45,85,.28);
            color:#ff2d55;
            font-size:13px;
            font-weight:900;
        }

        .vs-table-wrap{
            overflow-x:auto;
            border-radius:18px;
            border:1px solid rgba(255,255,255,.07);
        }

        .vs-table{
            width:100%;
            border-collapse:collapse;
        }

        .vs-table thead tr{
            background:rgba(255,255,255,.04);
            border-bottom:1px solid rgba(255,255,255,.07);
        }

        .vs-table th{
            padding:13px 16px;
            color:#9da7ba;
            font-size:11px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.8px;
            white-space:nowrap;
        }

        .vs-table tbody tr{
            border-bottom:1px solid rgba(255,255,255,.05);
            transition:background .18s ease;
        }

        .vs-table tbody tr:last-child{
            border-bottom:none;
        }

        .vs-table tbody tr:hover{
            background:rgba(255,255,255,.03);
        }

        .vs-table td{
            padding:14px 16px;
            color:#e0d9f0;
            font-size:14px;
            vertical-align:middle;
            white-space:nowrap;
        }

        .td-name{
            font-weight:700;
            color:#fff;
        }

        .td-sub{
            color:#9da7ba;
            font-size:12px;
            font-weight:600;
        }

        .rol-chip{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:5px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
        }

        .rol-admin{
            background:rgba(139,92,246,.18);
            border:1px solid rgba(139,92,246,.30);
            color:#c4b5fd;
        }

        .rol-visor{
            background:rgba(59,130,246,.18);
            border:1px solid rgba(59,130,246,.30);
            color:#93c5fd;
        }

        .estado-chip{
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:5px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
        }

        .estado-activo{
            background:rgba(34,197,94,.14);
            border:1px solid rgba(34,197,94,.24);
            color:#86efac;
        }

        .estado-inactivo{
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.20);
            color:#fca5a5;
        }

        .estado-chip .dot{
            width:7px;
            height:7px;
            border-radius:50%;
        }

        .estado-activo .dot{ background:#22c55e; }
        .estado-inactivo .dot{ background:#ef4444; }

        .action-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:34px;
            height:34px;
            border-radius:10px;
            border:none;
            cursor:pointer;
            font-size:14px;
            transition:all .2s ease;
        }

        .action-btn:hover{ transform:translateY(-1px); }

        .btn-activar{
            background:rgba(34,197,94,.14);
            border:1px solid rgba(34,197,94,.24);
            color:#22c55e;
        }

        .btn-inactivar{
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.20);
            color:#ef4444;
        }

        .btn-editar{
            background:rgba(251,191,36,.10);
            border:1px solid rgba(251,191,36,.22);
            color:#fbbf24;
        }

        .btn-editar:hover{
            background:rgba(251,191,36,.20);
            color:#fde68a;
        }

        .btn-eliminar{
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.10);
            color:#9da7ba;
        }

        .btn-eliminar:hover{
            background:rgba(239,68,68,.14);
            border-color:rgba(239,68,68,.24);
            color:#ef4444;
        }

        .empty-row td{
            text-align:center;
            padding:32px;
            color:#9da7ba;
            font-size:14px;
        }

        @media (max-width: 991.98px){
            .pcoded-main-container{
                margin-left:0 !important;
                padding-top:90px !important;
            }

            .pcoded-content{
                padding:18px 14px 20px !important;
            }
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
                        <h5 class="m-b-10 text-white">Creación de Usuarios</h5>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="feather icon-home"></i></a></li>
                            <li class="breadcrumb-item"><a href="#!">Panel de Control</a></li>
                            <li class="breadcrumb-item"><a href="#!">Usuarios</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-card" id="heroCard">
            <h1 id="formTitle">Crear usuarios del panel</h1>
            <p id="formSubtitle">Registra usuarios con rol Administrador o Visor. El rol Visor ingresará directamente al visualizador de emergencias.</p>
        </div>

        <div class="panel-card">
            <?php if ($mensaje !== ''): ?>
                <div id="flashMsg" class="<?php echo $tipoMensaje === 'success' ? 'alert-violeta-success' : 'alert-violeta-error'; ?>">
                    <?php echo e($mensaje); ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" id="formUsuario">
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" id="f_nombre" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" id="f_usuario" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" id="f_correo" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" id="labelPassword">Contraseña</label>
                        <input type="password" name="password" id="f_password" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rol</label>
                        <select name="rol" id="f_rol" class="form-select" required>
                            <option value="">Seleccione un rol</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Visor">Visor</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="estado" id="f_estado" class="form-select" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end align-items-center gap-3 mt-3">
                        <button type="button" id="btnCancelarEdicion" class="btn btn-cancelar" style="display:none;" onclick="cancelarEdicion()">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-violeta" id="btnSubmit">
                            Guardar usuario
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de usuarios del panel -->
        <div class="panel-card users-table-card">
            <div class="users-table-head">
                <div>
                    <h2>Usuarios del panel</h2>
                    <p>Listado de usuarios con acceso al panel de control.</p>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="buscadorUsuarios" placeholder="Buscar por nombre, usuario o correo...">
                    </div>
                    <span class="badge-count" id="contadorUsuarios"><?php echo count($usuariosPanel); ?></span>
                </div>
            </div>

            <div class="vs-table-wrap">
                <table class="vs-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre / Usuario</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th>Último login</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuariosPanel)): ?>
                            <tr class="empty-row">
                                <td colspan="8">No hay usuarios registrados en el panel.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuariosPanel as $u): ?>
                                <?php
                                    $esActivo = strtolower($u['estado']) === 'activo';
                                    $rolLower = strtolower($u['rol'] ?? '');
                                    $fechaCreacion = $u['fecha_creacion'] ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—';
                                    $ultimoLogin   = $u['ultimo_login']   ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : 'Nunca';
                                ?>
                                <tr class="fila-usuario" data-busqueda="<?php echo e(strtolower($u['nombre_completo'] . ' ' . $u['usuario'] . ' ' . $u['correo'])); ?>">
                                    <td class="td-sub"><?php echo e($u['id']); ?></td>
                                    <td>
                                        <div class="td-name"><?php echo e($u['nombre_completo']); ?></div>
                                        <div class="td-sub">@<?php echo e($u['usuario']); ?></div>
                                    </td>
                                    <td class="td-sub"><?php echo e($u['correo']); ?></td>
                                    <td>
                                        <?php if ($rolLower === 'administrador'): ?>
                                            <span class="rol-chip rol-admin"><i class="fas fa-shield-halved"></i> Administrador</span>
                                        <?php else: ?>
                                            <span class="rol-chip rol-visor"><i class="fas fa-eye"></i> Visor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($esActivo): ?>
                                            <span class="estado-chip estado-activo"><span class="dot"></span> Activo</span>
                                        <?php else: ?>
                                            <span class="estado-chip estado-inactivo"><span class="dot"></span> Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="td-sub"><?php echo e($fechaCreacion); ?></td>
                                    <td class="td-sub"><?php echo e($ultimoLogin); ?></td>
                                    <td>
                                        <div style="display:flex;gap:6px;align-items:center;">
                                            <button type="button" class="action-btn btn-editar js-editar"
                                                title="Editar"
                                                data-id="<?php echo (int)$u['id']; ?>"
                                                data-nombre="<?php echo e($u['nombre_completo']); ?>"
                                                data-usuario="<?php echo e($u['usuario']); ?>"
                                                data-correo="<?php echo e($u['correo']); ?>"
                                                data-rol="<?php echo e($u['rol']); ?>"
                                                data-estado="<?php echo e($u['estado']); ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>

                                            <?php if ($esActivo): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return swalEstado(event, this, 'inactivar', '¿Inactivar este usuario?', 'El usuario perderá acceso al sistema.')">
                                                    <input type="hidden" name="accion" value="inactivar">
                                                    <input type="hidden" name="target_id" value="<?php echo e($u['id']); ?>">
                                                    <button type="submit" class="action-btn btn-inactivar" title="Inactivar">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;" onsubmit="return swalEstado(event, this, 'activar', '¿Activar este usuario?', 'El usuario podrá ingresar al sistema.')">
                                                    <input type="hidden" name="accion" value="activar">
                                                    <input type="hidden" name="target_id" value="<?php echo e($u['id']); ?>">
                                                    <button type="submit" class="action-btn btn-activar" title="Activar">
                                                        <i class="fas fa-circle-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" style="display:inline;" onsubmit="return swalEliminar(event, this)">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="target_id" value="<?php echo e($u['id']); ?>">
                                                <button type="submit" class="action-btn btn-eliminar" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="sinResultados" style="display:none;">
                            <td colspan="8" style="text-align:center;padding:28px;color:#9da7ba;font-size:14px;">
                                No se encontraron usuarios con ese criterio.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include './admin/include/gerenic_script.php'; ?>

 <script src="<?php echo Util::versionar('./admin/js/usuarios_panel.js'); ?>"></script>

</body>
</html>