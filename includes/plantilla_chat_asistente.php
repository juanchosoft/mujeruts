<?php
/*
 * Plantilla de las páginas de chat de los asistentes migrados a Claude
 * (asistencia_trabajo_social_v2.php, asistencia_legal_v2.php, asistente_legal_v2.php).
 * La página que la incluye define $asistenteSlug y $pagina (textos e identidad visual).
 * Violeta IA (asistencia_psicologica_v2.php) no usa esta plantilla.
 */

if (!isset($asistenteSlug, $pagina) || !is_array($pagina)) {
    http_response_code(500);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../admin/classes/DbConection.php';
require_once __DIR__ . '/../admin/classes/ConfigLoader.php';
require_once __DIR__ . '/../admin/classes/AsistentesRegistro.php';

$definicionAsistente = AsistentesRegistro::obtener($asistenteSlug);
$nombreIA = $definicionAsistente['nombre_ia'];
$currentYear = date('Y');

$idUsuario = trim((string) (
    $_SESSION['id']
    ?? $_SESSION['usuario_id']
    ?? $_SESSION['user_id']
    ?? $_SESSION['session_user']['id']
    ?? ''
));

if ($idUsuario === '' || $idUsuario === '0') {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $idUsuario;

$nombreUsuario = '';

if (!empty($_SESSION['nombre_completo'])) {
    $nombreUsuario = trim((string) $_SESSION['nombre_completo']);
} elseif (!empty($_SESSION['nombre'])) {
    $nombreUsuario = trim((string) $_SESSION['nombre'] . ' ' . (string) ($_SESSION['apellido'] ?? ''));
} elseif (!empty($_SESSION['session_user']['nombre'])) {
    $nombreUsuario = trim((string) ($_SESSION['session_user']['nombre'] ?? '') . ' ' . (string) ($_SESSION['session_user']['ape'] ?? ''));
} elseif (!empty($_SESSION['nickname'])) {
    $nombreUsuario = trim((string) $_SESSION['nickname']);
} elseif (!empty($_SESSION['session_user']['nickname'])) {
    $nombreUsuario = trim((string) $_SESSION['session_user']['nickname']);
}

if ($nombreUsuario === '') {
    $nombreUsuario = 'Usuaria';
}

$contactoEmergenciaTel = '';

try {
    $pdo = (new DbConection())->openConect();
    $stmt = $pdo->prepare('SELECT contacto_emergencia FROM tbl_usuarios WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $contactoEmergencia = trim((string) ($stmt->fetchColumn() ?: ''));

    if ($contactoEmergencia !== '') {
        $contactoEmergenciaTel = preg_replace('/[^0-9+]/', '', $contactoEmergencia);
    }
} catch (Throwable $e) {
    $contactoEmergenciaTel = '';
}

if (empty($_SESSION['elevenlabs_csrf'])) {
    $_SESSION['elevenlabs_csrf'] = bin2hex(random_bytes(32));
}

$botonesEmergencia = false;

try {
    $botonesEmergencia = (new ConfigLoader())->getConfigAsistente($asistenteSlug)['botones_emergencia'];
} catch (Throwable $e) {
    $botonesEmergencia = false;
}

$saludoBienvenida =
    'Hola, ' . $nombreUsuario . '. ' .
    'Estás hablando con ' . $nombreIA . '. Este acompañamiento es brindado con inteligencia artificial. ' .
    'Puedes escribirme o usar el micrófono. ' . $pagina['pregunta_saludo'];

$e = function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

$configChat = [
    'asistente' => $asistenteSlug,
    'endpoint' => 'api/chat_asistente.php',
    'nombreIA' => $nombreIA,
    'csrfToken' => $_SESSION['elevenlabs_csrf'],
    'audioStorageKey' => $pagina['audio_storage_key'],
    'velocidadElevenLabs' => 1.15,
    'velocidadReproduccion' => 1.08,
    'contactoEmergenciaTel' => $contactoEmergenciaTel,
    'botonesEmergencia' => (bool) $botonesEmergencia,
    'usuarioId' => $usuarioId,
    'nombreUsuario' => $nombreUsuario,
    'saludo' => $saludoBienvenida
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <title><?= $e($pagina['titulo']); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?= $e($pagina['descripcion']); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="robots" content="noindex,nofollow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/aliada-chat.css?v=20260928b">

    <style>
        :root {
            --aliada-purple: <?= $e($pagina['color_purpura']); ?>;
            --aliada-purple-dark: <?= $e($pagina['color_purpura_oscuro']); ?>;
            --aliada-gradient: linear-gradient(135deg, #F64FBA 0%, #E51C9B 46%, <?= $e($pagina['color_purpura']); ?> 100%);
        }
    </style>
</head>

<body>
    <main class="app-chat-mobile">
        <div class="mobile-shell">

            <header class="topbar-mobile">
                <div class="topbar-mobile-inner">

                    <div class="brand-mobile">
                        <div class="brand-mobile-logo">
                            <img src="assets/img/utslog.png" alt="Aliada UTS">
                        </div>

                        <div class="brand-mobile-copy">
                            <span class="brand-mobile-eyebrow">Universitaria Tecnológica de Santander</span>
                            <span class="brand-mobile-eyebrow">Aliada UTS</span>

                            <h1 class="brand-mobile-title">
                                <?= $e($pagina['titulo_h1'][0]); ?> <span><?= $e($pagina['titulo_h1'][1]); ?></span>
                            </h1>

                            <p class="brand-mobile-subtitle">
                                <?= $e($pagina['subtitulo']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="topbar-mobile-actions">
                        <button type="button" class="btn-mobile-top white" onclick="window.history.back();">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            <span>Volver</span>
                        </button>

                        <a href="panel_usuaria.php" class="btn-mobile-top">
                            <i class="fa-solid fa-house" aria-hidden="true"></i>
                            <span>Inicio</span>
                        </a>
                    </div>

                </div>
            </header>

            <section class="info-strip">

                <div class="info-icon">
                    <i class="<?= $e($pagina['icono']); ?>" aria-hidden="true"></i>
                </div>

                <div class="info-copy">
                    <strong>Habla con <?= $e($nombreIA); ?></strong>
                    <span><?= $e($pagina['texto_info']); ?></span>
                </div>

                <div class="alma-audio-controls">
                    <button type="button" id="btn-microfono" class="alma-control-button" title="Grabar mensaje de voz" aria-pressed="false">
                        <i class="fa-solid fa-microphone-lines" aria-hidden="true"></i>
                        <span>Hablar</span>
                    </button>

                    <button type="button" id="btn-parlante" class="alma-control-button" title="Activar respuestas por audio" aria-pressed="false">
                        <i class="fa-solid fa-volume-xmark" aria-hidden="true"></i>
                        <span>Audio</span>
                    </button>
                </div>

            </section>

            <section class="chat-panel">
                <div class="chat-frame">
                    <ul id="chat-mensajes" aria-live="polite"></ul>
                </div>

                <form id="chat-form" class="chat-input-bar">
                    <label for="chat-texto" class="visually-hidden" style="position:absolute;left:-9999px;">Escribe tu mensaje</label>
                    <textarea
                        id="chat-texto"
                        rows="1"
                        maxlength="4000"
                        placeholder="Escribe o activa el micrófono para hablar..."
                    ></textarea>

                    <button type="submit" id="btn-enviar" class="btn-enviar-chat" title="Enviar" aria-label="Enviar mensaje">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    </button>
                </form>
            </section>

            <footer class="footer-mobile">
                <p>
                    © <?= $currentYear; ?> <strong>Aliada UTS</strong>
                    · <?= $e($pagina['texto_footer']); ?><br>
                    Desarrollado por <strong>SpiderSoftware S.A.S.</strong>
                </p>
            </footer>

        </div>
    </main>

    <div id="alma-voice-status" class="alma-voice-status" role="status" aria-live="polite"></div>

    <script src="assets/js/aliada-chat.js?v=20260928b"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AliadaChat.iniciar(<?= json_encode($configChat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        });
    </script>
    <?php include __DIR__ . '/../videollamada_banner.php'; ?>
    <?php include __DIR__ . '/../analitica_tracker.php'; ?>
</body>
</html>
