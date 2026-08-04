<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

require_once './admin/classes/DbConection.php';

$pageTitle       = 'Aliada UTS | Mis Evidencias';
$pageDescription = 'Consulta tus audios, fotos y videos guardados en Aliada UTS.';
$currentYear     = date('Y');

$usuarioId = 0;
$nombreUsuario = 'Usuaria';

if (!empty($_SESSION['id'])) {
    $usuarioId = (int) $_SESSION['id'];
} elseif (!empty($_SESSION['usuario_id'])) {
    $usuarioId = (int) $_SESSION['usuario_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $usuarioId = (int) $_SESSION['user_id'];
} elseif (!empty($_SESSION['session_user']['id'])) {
    $usuarioId = (int) $_SESSION['session_user']['id'];
}

if (!empty($_SESSION['nombre_completo'])) {
    $nombreUsuario = trim((string) $_SESSION['nombre_completo']);
} elseif (!empty($_SESSION['nombre'])) {
    $nombreUsuario = trim((string) $_SESSION['nombre']);
} elseif (!empty($_SESSION['session_user']['nombre'])) {
    $nombre   = trim((string) ($_SESSION['session_user']['nombre'] ?? ''));
    $apellido = trim((string) ($_SESSION['session_user']['ape'] ?? ''));
    $nombreUsuario = trim($nombre . ' ' . $apellido);
} elseif (!empty($_SESSION['nickname'])) {
    $nombreUsuario = trim((string) $_SESSION['nickname']);
}

if ($nombreUsuario === '') {
    $nombreUsuario = 'Usuaria';
}

function limpiarTexto($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function formatearFechaHora($fecha)
{
    if (empty($fecha)) {
        return 'Sin fecha registrada';
    }

    try {
        $dt = new DateTime($fecha);
        return $dt->format('d/m/Y · h:i A');
    } catch (Throwable $e) {
        return (string) $fecha;
    }
}

function obtenerClaseEvidencia($tipo)
{
    $tipo = strtolower(trim((string) $tipo));

    if ($tipo === 'audio') {
        return 'audio';
    }

    if ($tipo === 'foto' || $tipo === 'imagen' || $tipo === 'image') {
        return 'foto';
    }

    if ($tipo === 'video') {
        return 'video';
    }

    if ($tipo === 'policia' || $tipo === 'contacto_emergencia') {
        return 'alerta';
    }

    return 'archivo';
}

function obtenerIconoEvidencia($tipo)
{
    $tipoClase = obtenerClaseEvidencia($tipo);

    if ($tipoClase === 'audio') {
        return 'fa-microphone-lines';
    }

    if ($tipoClase === 'foto') {
        return 'fa-image';
    }

    if ($tipoClase === 'video') {
        return 'fa-video';
    }

    if ($tipoClase === 'alerta') {
        return 'fa-shield-heart';
    }

    return 'fa-file-shield';
}

function obtenerTituloEvidencia($tipo)
{
    $tipo = strtolower(trim((string) $tipo));

    if ($tipo === 'audio') {
        return 'Grabación de audio';
    }

    if ($tipo === 'foto' || $tipo === 'imagen' || $tipo === 'image') {
        return 'Foto registrada';
    }

    if ($tipo === 'video') {
        return 'Video registrado';
    }

    if ($tipo === 'policia') {
        return 'Alerta a Policía';
    }

    if ($tipo === 'contacto_emergencia') {
        return 'Llamada a contacto de emergencia';
    }

    return 'Evidencia guardada';
}

function formatearPesoArchivo($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes <= 0) {
        return 'Sin tamaño';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    return number_format($bytes / 1024, 2) . ' KB';
}

function normalizarRutaArchivo($ruta)
{
    $ruta = trim((string) $ruta);

    if ($ruta === '') {
        return '';
    }

    $ruta = str_replace('\\', '/', $ruta);
    $ruta = ltrim($ruta, '/');

    return $ruta;
}

/*
|--------------------------------------------------------------------------
| CONSULTA DE EVIDENCIAS
|--------------------------------------------------------------------------
| Se consulta tbl_alerta_policia porque ahí guarda admin/ajax/guardar_alerta.php
|--------------------------------------------------------------------------
*/
$evidencias = [];
$errorConsulta = '';

try {
    $db = new DbConection();
    $pdo = $db->openConect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $whereUsuario = '';
    $params = [];

    if ($usuarioId > 0) {
        $whereUsuario = 'WHERE usuario_id = :usuario_id';
        $params[':usuario_id'] = $usuarioId;
    }

    $sql = "SELECT 
                id,
                usuario_id,
                nombre_usuaria,
                tipo_alerta AS tipo_evidencia,
                archivo AS ruta_archivo,
                archivo AS nombre_archivo,
                descripcion,
                latitud,
                longitud,
                precision_gps,
                estado,
                fecha_registro
            FROM tbl_alerta_policia
            $whereUsuario
            ORDER BY fecha_registro DESC, id DESC";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }

    $stmt->execute();
    $evidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($evidencias as $key => $item) {
        $ruta = normalizarRutaArchivo($item['ruta_archivo'] ?? '');

        $extension = '';
        $nombreArchivo = 'Evidencia';

        if ($ruta !== '') {
            $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            $nombreArchivo = basename($ruta);
        }

        $tipoOriginal = strtolower(trim((string) ($item['tipo_evidencia'] ?? '')));
        $tipo = $tipoOriginal;

        /*
        |--------------------------------------------------------------------------
        | Detectar tipo por extensión si el tipo viene vacío o diferente
        |--------------------------------------------------------------------------
        */
        if (!in_array($tipo, ['audio', 'foto', 'imagen', 'image', 'video', 'policia', 'contacto_emergencia'], true)) {
            if (in_array($extension, ['mp3', 'wav', 'ogg', 'webm', 'm4a'], true)) {
                $tipo = 'audio';
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $tipo = 'foto';
            } elseif (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'], true)) {
                $tipo = 'video';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Corrección especial:
        | Video grabado desde navegador puede venir como .webm.
        | Si tipo_alerta dice video, se respeta como video.
        |--------------------------------------------------------------------------
        */
        if ($tipoOriginal === 'video') {
            $tipo = 'video';
        }

        if ($tipoOriginal === 'audio') {
            $tipo = 'audio';
        }

        if ($tipoOriginal === 'foto') {
            $tipo = 'foto';
        }

        $tamanoBytes = 0;

        if ($ruta !== '') {
            $rutaFisica = __DIR__ . '/' . $ruta;

            if (file_exists($rutaFisica)) {
                $tamanoBytes = filesize($rutaFisica);
            }
        }

        $evidencias[$key]['tipo_evidencia'] = $tipo;
        $evidencias[$key]['ruta_archivo']   = $ruta;
        $evidencias[$key]['nombre_archivo'] = $nombreArchivo;
        $evidencias[$key]['extension']      = $extension;
        $evidencias[$key]['tamano_bytes']   = $tamanoBytes;
    }

    /*
    |--------------------------------------------------------------------------
    | ORDEN DEFINITIVO DE LAS EVIDENCIAS
    |--------------------------------------------------------------------------
    | Primero: fecha más reciente.
    | Si dos evidencias tienen la misma fecha: ID más alto primero.
    | Así las evidencias antiguas siempre quedan abajo.
    |--------------------------------------------------------------------------
    */
    usort(
        $evidencias,
        static function (array $a, array $b): int {
            $fechaA = trim((string) ($a['fecha_registro'] ?? ''));
            $fechaB = trim((string) ($b['fecha_registro'] ?? ''));

            $timestampA = $fechaA !== ''
                ? strtotime($fechaA)
                : 0;

            $timestampB = $fechaB !== ''
                ? strtotime($fechaB)
                : 0;

            if ($timestampA !== $timestampB) {
                return $timestampB <=> $timestampA;
            }

            $idA = (int) ($a['id'] ?? 0);
            $idB = (int) ($b['id'] ?? 0);

            return $idB <=> $idA;
        }
    );

} catch (Throwable $e) {
    $evidencias = [];
    $errorConsulta = $e->getMessage();
}

$totalAudios  = 0;
$totalFotos   = 0;
$totalVideos  = 0;
$totalAlertas = 0;

foreach ($evidencias as $ev) {
    $tipoClase = obtenerClaseEvidencia($ev['tipo_evidencia'] ?? '');

    if ($tipoClase === 'audio') {
        $totalAudios++;
    } elseif ($tipoClase === 'foto') {
        $totalFotos++;
    } elseif ($tipoClase === 'video') {
        $totalVideos++;
    } elseif ($tipoClase === 'alerta') {
        $totalAlertas++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo limpiarTexto($pageTitle); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?php echo limpiarTexto($pageDescription); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="robots" content="index,follow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="assets/css/evidencias.css">
   
</head>

<body>
<main class="app-mobile">
    <div class="mobile-shell">

        <header class="topbar-mobile">
            <div class="topbar-mobile-inner">
                <div class="brand-mobile">
                    <div class="brand-mobile-logo">
                        <img src="assets/img/utslog.png" alt="Aliada UTS">
                    </div>

                    <div class="brand-mobile-copy">
                        <span class="brand-mobile-eyebrow">Aliada UTS</span>
                        <h1 class="brand-mobile-title">
                            Mis <span>evidencias</span>
                        </h1>
                        <p class="brand-mobile-subtitle">
                            Audios, fotos, videos y alertas guardadas con fecha y hora.
                        </p>
                    </div>
                </div>

                <div class="topbar-mobile-actions">
                    <button type="button" class="btn-mobile-top white" onclick="window.history.back();">
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver
                    </button>

                    <a href="logoutusuaria.php" class="btn-mobile-top">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Salir
                    </a>
                </div>
            </div>
        </header>

        <section class="summary-card">
            <div class="summary-badge">
                <i class="fa-solid fa-folder-shield"></i>
                Archivo personal
            </div>

            <h2 class="summary-title">
                Hola, <?php echo limpiarTexto($nombreUsuario); ?>.
            </h2>

            <p class="summary-text">
                Aquí puedes consultar tus grabaciones, fotografías, videos y alertas registradas.
            </p>

            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fa-solid fa-microphone-lines"></i>
                    <strong><?php echo (int) $totalAudios; ?></strong>
                    <span>Audios</span>
                </div>

                <div class="stat-item">
                    <i class="fa-solid fa-image"></i>
                    <strong><?php echo (int) $totalFotos; ?></strong>
                    <span>Fotos</span>
                </div>

                <div class="stat-item">
                    <i class="fa-solid fa-video"></i>
                    <strong><?php echo (int) $totalVideos; ?></strong>
                    <span>Videos</span>
                </div>

                <div class="stat-item">
                    <i class="fa-solid fa-shield-heart"></i>
                    <strong><?php echo (int) $totalAlertas; ?></strong>
                    <span>Alertas</span>
                </div>
            </div>
        </section>

        <?php if (!empty($errorConsulta) && in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)): ?>
            <section class="debug-card">
                <h3><i class="fa-solid fa-bug"></i> Error consultando evidencias</h3>
                <p><?php echo limpiarTexto($errorConsulta); ?></p>
            </section>
        <?php endif; ?>

        <div class="section-title">
            <h2>Evidencias guardadas</h2>
            <span><?php echo count($evidencias); ?> registros</span>
        </div>

        <?php if (empty($evidencias)): ?>
            <section class="empty-card">
                <div class="empty-icon">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3>No tienes evidencias guardadas</h3>
                <p>
                    Cuando grabes audio, tomes fotos, grabes videos o actives una alerta,
                    aparecerán aquí con su fecha y hora.
                </p>
            </section>
        <?php else: ?>
            <section class="evidence-list">
                <?php foreach ($evidencias as $item): ?>
                    <?php
                        $tipoOriginal  = strtolower(trim((string) ($item['tipo_evidencia'] ?? '')));
                        $tipoClase     = obtenerClaseEvidencia($tipoOriginal);
                        $titulo        = obtenerTituloEvidencia($tipoOriginal);
                        $icono         = obtenerIconoEvidencia($tipoOriginal);
                        $fechaHora     = formatearFechaHora($item['fecha_registro'] ?? '');
                        $rutaArchivo   = normalizarRutaArchivo($item['ruta_archivo'] ?? '');
                        $extension     = strtolower(trim((string) ($item['extension'] ?? '')));
                        $nombreArchivo = trim((string) ($item['nombre_archivo'] ?? 'Evidencia'));
                        $descripcion   = trim((string) ($item['descripcion'] ?? ''));
                        $estado        = trim((string) ($item['estado'] ?? 'pendiente'));
                    ?>

                    <article class="evidence-card evidence-<?php echo limpiarTexto($tipoClase); ?>">
                        <div class="evidence-head">
                            <div class="evidence-icon">
                                <i class="fa-solid <?php echo limpiarTexto($icono); ?>"></i>
                            </div>

                            <div class="evidence-info">
                                <h3 class="evidence-title">
                                    <?php echo limpiarTexto($titulo); ?>
                                </h3>

                                <div class="evidence-date">
                                    <i class="fa-regular fa-clock"></i>
                                    <span><?php echo limpiarTexto($fechaHora); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="evidence-body">
                            <div class="media-box">
                                <?php if ($rutaArchivo !== '' && $tipoClase === 'audio'): ?>
                                    <audio controls preload="metadata" src="<?php echo limpiarTexto($rutaArchivo); ?>"></audio>

                                <?php elseif ($rutaArchivo !== '' && $tipoClase === 'foto'): ?>
                                    <img src="<?php echo limpiarTexto($rutaArchivo); ?>" alt="Foto evidencia tomada el <?php echo limpiarTexto($fechaHora); ?>">

                                <?php elseif ($rutaArchivo !== '' && $tipoClase === 'video'): ?>
                                    <video controls playsinline preload="metadata" src="<?php echo limpiarTexto($rutaArchivo); ?>"></video>

                                <?php elseif ($tipoClase === 'alerta'): ?>
                                    <div class="media-empty">
                                        <i class="fa-solid fa-shield-heart"></i><br><br>
                                        Esta alerta fue registrada sin archivo adjunto.
                                        <?php if ($descripcion !== ''): ?>
                                            <br><br><?php echo limpiarTexto($descripcion); ?>
                                        <?php endif; ?>
                                    </div>

                                <?php else: ?>
                                    <div class="media-empty">
                                        <i class="fa-solid fa-file-shield"></i><br><br>
                                        Evidencia registrada sin archivo visible.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="evidence-meta">
                                <div class="meta-pill">
                                    <span>Archivo</span>
                                    <strong><?php echo limpiarTexto($nombreArchivo); ?></strong>
                                </div>

                                <div class="meta-pill">
                                    <span>Tamaño</span>
                                    <strong><?php echo limpiarTexto(formatearPesoArchivo($item['tamano_bytes'] ?? 0)); ?></strong>
                                </div>

                                <div class="meta-pill">
                                    <span>Tipo</span>
                                    <strong><?php echo limpiarTexto(strtoupper($tipoClase)); ?></strong>
                                </div>

                                <div class="meta-pill">
                                    <span>Estado</span>
                                    <strong><?php echo limpiarTexto($estado); ?></strong>
                                </div>
                            </div>

                            <?php if ($rutaArchivo !== ''): ?>
                                <div class="evidence-actions">
                                    <a class="evidence-btn primary" href="<?php echo limpiarTexto($rutaArchivo); ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="fa-solid fa-eye"></i>
                                        Ver
                                    </a>

                                    <a class="evidence-btn" href="<?php echo limpiarTexto($rutaArchivo); ?>" download>
                                        <i class="fa-solid fa-download"></i>
                                        Descargar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <footer class="footer-mobile">
            <p>
                © <?php echo $currentYear; ?> <strong>Aliada UTS</strong><br>
                Evidencias personales guardadas con fecha y hora.
            </p>
        </footer>

    </div>
</main>

<nav class="bottom-nav">
    <button type="button" class="bottom-btn" onclick="window.history.back();">
        <i class="fa-solid fa-arrow-left"></i>
        Volver
    </button>

    <a href="emergencia.php" class="bottom-btn primary">
        <i class="fa-solid fa-shield-heart"></i>
        Protección
    </a>

    <a href="panel_usuaria.php" class="bottom-btn">
        <i class="fa-solid fa-house"></i>
        Inicio
    </a>
</nav>
</body>
</html>