<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/admin/classes/DbConection.php';

$pageTitle       = 'Aliada UTS | Apoyo psicológico con IA';
$pageDescription = 'Aliada UTS, plataforma de apoyo psicológico con IA para brindar escucha, orientación y acompañamiento seguro.';
$currentYear     = date('Y');

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
    $nombre   = trim((string) $_SESSION['nombre']);
    $apellido = trim((string) ($_SESSION['apellido'] ?? ''));
    $nombreUsuario = trim($nombre . ' ' . $apellido);
} elseif (!empty($_SESSION['session_user']['nombre'])) {
    $nombre   = trim((string) ($_SESSION['session_user']['nombre'] ?? ''));
    $apellido = trim((string) ($_SESSION['session_user']['ape'] ?? ''));
    $nombreUsuario = trim($nombre . ' ' . $apellido);
} elseif (!empty($_SESSION['nickname'])) {
    $nombreUsuario = trim((string) $_SESSION['nickname']);
} elseif (!empty($_SESSION['session_user']['nickname'])) {
    $nombreUsuario = trim((string) $_SESSION['session_user']['nickname']);
}

if ($nombreUsuario === '') {
    $nombreUsuario = 'Usuaria';
}

/*
 * Contacto de emergencia de la usuaria, para el botón "Avisar a mi contacto"
 * (mismo dato y misma consulta que ya usa emergencia.php).
 */
$contactoEmergenciaTel = '';
$nombreContacto = '';

try {
    $db = new DbConection();
    $pdo = $db->openConect();

    $stmt = $pdo->prepare('SELECT contacto_emergencia, nombre_contacto FROM tbl_usuarios WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioDB) {
        $contactoEmergencia = trim((string) ($usuarioDB['contacto_emergencia'] ?? ''));
        $nombreContacto = trim((string) ($usuarioDB['nombre_contacto'] ?? ''));

        if ($contactoEmergencia !== '') {
            $contactoEmergenciaTel = preg_replace('/[^0-9+]/', '', $contactoEmergencia);
        }
    }
} catch (Throwable $e) {
    $contactoEmergenciaTel = '';
    $nombreContacto = '';
}

if (empty($_SESSION['elevenlabs_csrf'])) {
    $_SESSION['elevenlabs_csrf'] = bin2hex(random_bytes(32));
}

$nombreIA = 'Violeta IA';

$saludoBienvenida =
    'Hola, ' . $nombreUsuario . '. ' .
    'Soy Violeta IA, tu orientadora psicológica virtual. ' .
    'Este acompañamiento es brindado con inteligencia artificial. ' .
    'Puedes escribirme o usar el micrófono. ¿Cómo te sientes hoy?';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="robots" content="index,follow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="assets/css/asistencia_psicologica.css?v=<?= time(); ?>" rel="stylesheet">

    <style>
        /*
        |--------------------------------------------------------------------------
        | BOTONES DE CONTROL DE VOZ (micrófono / parlante)
        |--------------------------------------------------------------------------
        */
        .info-strip {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-copy {
            flex: 1;
            min-width: 0;
        }

        .alma-audio-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .alma-control-button {
            position: relative;
            isolation: isolate;

            min-width: 50px;
            height: 48px;
            padding: 0 15px;

            border: 1px solid rgba(216, 27, 156, 0.22);
            border-radius: 999px;

            background:
                linear-gradient(#FFFFFF, #FFFFFF) padding-box,
                linear-gradient(135deg, rgba(216, 27, 156, 0.35), rgba(155, 18, 111, 0.12)) border-box;

            color: #C21888;

            font-family: inherit;
            font-size: 13px;
            font-weight: 750;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            cursor: pointer;

            box-shadow:
                0 7px 20px rgba(132, 20, 96, 0.11),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);

            transition:
                transform 0.2s ease,
                color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .alma-control-button i {
            width: 20px;
            font-size: 18px;
            line-height: 1;
            text-align: center;
            filter: drop-shadow(0 2px 3px rgba(126, 14, 91, 0.16));
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .alma-control-button::after {
            content: '';
            position: absolute;
            inset: -4px;
            z-index: -1;
            border: 2px solid transparent;
            border-radius: inherit;
            opacity: 0;
            transform: scale(0.88);
            pointer-events: none;
        }

        .alma-control-button:active {
            transform: translateY(0) scale(0.98);
        }

        .alma-control-button.activo {
            color: #FFFFFF;
            border-color: transparent;
            background: linear-gradient(135deg, #E321A4 0%, #B71382 52%, #8F0F67 100%);
            box-shadow:
                0 10px 24px rgba(183, 19, 130, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.24);
        }

        .alma-control-button.escuchando {
            color: #FFFFFF;
            border-color: transparent;
            background: linear-gradient(135deg, #FF4D67 0%, #E52343 55%, #C81432 100%);
            animation: pulsoMicrofono 1.1s infinite;
        }

        .alma-control-button.escuchando::after {
            border-color: rgba(229, 35, 67, 0.38);
            animation: anilloAudio 1.15s infinite ease-out;
        }

        .alma-control-button.hablando::after {
            border-color: rgba(216, 27, 156, 0.35);
            animation: anilloAudio 1.05s infinite ease-out;
        }

        .alma-control-button.hablando i {
            animation: parlanteActivo 0.62s infinite alternate ease-in-out;
        }

        .alma-control-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            animation: none;
        }

        @keyframes pulsoMicrofono {
            0% { box-shadow: 0 0 0 0 rgba(229, 35, 67, 0.40), 0 8px 22px rgba(200, 20, 50, 0.24); }
            70% { box-shadow: 0 0 0 12px rgba(229, 35, 67, 0), 0 8px 22px rgba(200, 20, 50, 0.24); }
            100% { box-shadow: 0 0 0 0 rgba(229, 35, 67, 0), 0 8px 22px rgba(200, 20, 50, 0.24); }
        }

        @keyframes anilloAudio {
            0% { opacity: 0.75; transform: scale(0.88); }
            100% { opacity: 0; transform: scale(1.20); }
        }

        @keyframes parlanteActivo {
            from { transform: scale(0.92) translateX(-1px); }
            to { transform: scale(1.16) translateX(1px); }
        }

        /*
        |--------------------------------------------------------------------------
        | CHAT PROPIO (burbujas)
        |--------------------------------------------------------------------------
        */
        .chat-panel {
            display: flex;
            flex-direction: column;
        }

        .chat-frame {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        #chat-mensajes {
            flex: 1 1 auto;
            min-height: 0;
            margin: 0;
            padding: 16px 14px;
            list-style: none;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            -webkit-overflow-scrolling: touch;
        }

        .chat-mensaje {
            max-width: 84%;
            padding: 0.75rem 0.85rem;
            border-radius: 16px;
            font-size: 14.5px;
            line-height: 1.5;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .chat-mensaje p {
            margin: 0;
        }

        .chat-mensaje.asistente {
            align-self: flex-start;
            background: #FCE7F3;
            color: #2B1237;
            border-bottom-left-radius: 4px;
        }

        .chat-mensaje.usuaria {
            align-self: flex-end;
            background: linear-gradient(135deg, #D81B9C, #7B1FA2);
            color: #FFFFFF;
            border-bottom-right-radius: 4px;
        }

        .chat-mensaje.sistema {
            align-self: center;
            background: rgba(123, 31, 162, 0.08);
            color: var(--aliada-muted);
            font-size: 12.5px;
            text-align: center;
        }

        .chat-escribiendo {
            align-self: flex-start;
            display: inline-flex;
            gap: 5px;
            padding: 0.7rem 0.9rem;
            border-radius: 16px;
            background: #FCE7F3;
        }

        .chat-escribiendo span {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #B41682;
            opacity: 0.4;
            animation: parpadeoPunto 1s infinite;
        }

        .chat-escribiendo span:nth-child(2) { animation-delay: 0.15s; }
        .chat-escribiendo span:nth-child(3) { animation-delay: 0.3s; }

        @keyframes parpadeoPunto {
            0%, 100% { opacity: 0.25; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-2px); }
        }

        .chat-emergencia {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border-radius: 16px;
            background: rgba(220, 38, 38, 0.06);
            border: 1px solid rgba(220, 38, 38, 0.22);
        }

        .chat-emergencia-titulo {
            font-size: 12.5px;
            font-weight: 900;
            color: #B91C1C;
        }

        .chat-emergencia-boton {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 12px;
            border: none;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
        }

        .chat-emergencia-boton.policia {
            background: #DC2626;
            color: #FFFFFF;
        }

        .chat-emergencia-boton.contacto {
            background: #FFFFFF;
            color: #B91C1C;
            border: 1px solid rgba(220, 38, 38, 0.35);
        }

        .chat-emergencia-boton.evidencias {
            background: #FFFFFF;
            color: var(--aliada-fucsia-dark);
            border: 1px solid var(--aliada-border);
        }

        .chat-input-bar {
            flex: 0 0 auto;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid rgba(216, 27, 156, 0.12);
            background: #FFF5FB;
        }

        .chat-input-bar textarea {
            flex: 1;
            min-height: 44px;
            max-height: 110px;
            padding: 11px 14px;
            border-radius: 18px;
            border: 1px solid rgba(216, 27, 156, 0.16);
            background: #FFFFFF;
            color: #2B1237;
            font-family: inherit;
            font-size: 14px;
            line-height: 1.4;
            resize: none;
        }

        .chat-input-bar textarea::placeholder {
            color: #9B7AA6;
        }

        .chat-input-bar textarea:focus {
            outline: none;
            border-color: rgba(216, 27, 156, 0.4);
        }

        .btn-enviar-chat {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #FF4FB8, #D81B9C, #7B1FA2);
            color: #FFFFFF;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-enviar-chat:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        /*
        |--------------------------------------------------------------------------
        | MENSAJE FLOTANTE DE ESTADO
        |--------------------------------------------------------------------------
        */
        .alma-voice-status {
            position: fixed;
            left: 50%;
            bottom: calc(22px + env(safe-area-inset-bottom));
            z-index: 99999;

            max-width: calc(100vw - 30px);
            padding: 11px 17px;

            border-radius: 24px;
            background: rgba(28, 28, 33, 0.95);
            color: #FFFFFF;

            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            text-align: center;

            opacity: 0;
            visibility: hidden;
            pointer-events: none;

            transform: translate(-50%, 15px);

            transition:
                opacity 0.25s ease,
                visibility 0.25s ease,
                transform 0.25s ease;

            box-shadow: 0 9px 28px rgba(0, 0, 0, 0.22);
        }

        .alma-voice-status.visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, 0);
        }

        .alma-voice-status.error {
            background: rgba(183, 28, 28, 0.97);
        }

        @media (max-width: 600px) {
            .alma-control-button {
                width: 44px;
                min-width: 44px;
                height: 44px;
                padding: 0;
            }

            .alma-control-button span {
                display: none;
            }

            .alma-audio-controls {
                gap: 5px;
            }

            .info-strip {
                gap: 8px;
            }
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
                                Apoyo <span>psicológico con IA</span>
                            </h1>

                            <p class="brand-mobile-subtitle">
                                Escucha, orientación y acompañamiento emocional seguro.
                            </p>
                        </div>
                    </div>

                    <div class="topbar-mobile-actions">
                        <button
                            type="button"
                            class="btn-mobile-top white"
                            onclick="window.history.back();"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>Volver</span>
                        </button>

                        <a href="panel_usuaria.php" class="btn-mobile-top">
                            <i class="fa-solid fa-house"></i>
                            <span>Inicio</span>
                        </a>
                    </div>

                </div>
            </header>

            <section class="info-strip">

                <div class="info-icon">
                    <i class="fa-solid fa-brain"></i>
                </div>

                <div class="info-copy">
                    <strong>
                        Habla con <?= htmlspecialchars($nombreIA, ENT_QUOTES, 'UTF-8'); ?>
                    </strong>

                    <span>
                        Recibe orientación psicológica cercana, respetuosa y segura para tu bienestar emocional.
                    </span>
                </div>

                <div class="alma-audio-controls">

                    <button
                        type="button"
                        id="btn-microfono"
                        class="alma-control-button"
                        title="Grabar mensaje de voz"
                        aria-pressed="false"
                    >
                        <i class="fa-solid fa-microphone-lines"></i>
                        <span>Hablar</span>
                    </button>

                    <button
                        type="button"
                        id="btn-parlante"
                        class="alma-control-button"
                        title="Activar respuestas por audio"
                        aria-pressed="false"
                    >
                        <i class="fa-solid fa-volume-xmark"></i>
                        <span>Audio</span>
                    </button>

                </div>

            </section>

            <section class="chat-panel">
                <div class="chat-frame">
                    <ul id="chat-mensajes"></ul>
                </div>

                <form id="chat-form" class="chat-input-bar">
                    <textarea
                        id="chat-texto"
                        rows="1"
                        maxlength="4000"
                        placeholder="Escribe o activa el micrófono para hablar..."
                    ></textarea>

                    <button type="submit" id="btn-enviar" class="btn-enviar-chat" title="Enviar">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </section>

            <footer class="footer-mobile">
                <p>
                    © <?= $currentYear; ?> <strong>Aliada UTS</strong>
                    · Orientación psicológica con IA.<br>

                    Desarrollado por <strong>Helios Electronics</strong> ·

                    <a href="https://helios.com.co" target="_blank" rel="noopener noreferrer">
                        helios.com.co
                    </a>
                </p>
            </footer>

        </div>
    </main>

    <div
        id="alma-voice-status"
        class="alma-voice-status"
        role="status"
        aria-live="polite"
    ></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const csrfToken = <?= json_encode($_SESSION['elevenlabs_csrf'], JSON_UNESCAPED_SLASHES); ?>;
            const saludoBienvenida = <?= json_encode($saludoBienvenida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const contactoEmergenciaTel = <?= json_encode($contactoEmergenciaTel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const nombreUsuario = <?= json_encode($nombreUsuario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

            const AUDIO_STORAGE_KEY = 'aliada_uts_audio_psicologia_activo';
            const DURACION_MAXIMA_GRABACION_MS = 60000;
            const UMBRAL_VOLUMEN_VOZ = 0.02;
            const SILENCIO_MS_PARA_DETENER = 1300;
            // Velocidad nativa en ElevenLabs (voice_settings.speed, rango 0.7-1.2) + un pequeño extra por playbackRate del navegador.
            const VELOCIDAD_ELEVENLABS = 1.15;
            const VELOCIDAD_AUDIO_RESPUESTA = 1.08;
            const RETARDO_REACTIVAR_ESCUCHA_MS = 400;

            const listaMensajes = document.getElementById('chat-mensajes');
            const formulario = document.getElementById('chat-form');
            const campoTexto = document.getElementById('chat-texto');
            const botonEnviar = document.getElementById('btn-enviar');
            const btnMicrofono = document.getElementById('btn-microfono');
            const btnParlante = document.getElementById('btn-parlante');
            const voiceStatus = document.getElementById('alma-voice-status');

            let conversacionId = null;
            let esperandoRespuesta = false;
            let audioActivo = localStorage.getItem(AUDIO_STORAGE_KEY) === '1';

            let grabadora = null;
            let fragmentosAudio = [];
            let grabando = false;
            let temporizadorLimiteGrabacion = null;
            let temporizadorEstado = null;

            /*
             * Detección de silencio (Web Audio API) para detener la
             * grabación sola cuando la usuaria deja de hablar.
             */
            let audioContextGrabacion = null;
            let analizadorGrabacion = null;
            let bufferAnalisisVoz = null;
            let cuadroDeteccionSilencio = null;
            let huboVozEnGrabacionActual = false;
            let silencioIniciadoEn = null;

            let colaAudio = [];
            let reproduciendoAudio = false;
            let audioActual = null;
            let parrafoEnRevelado = null;

            function mostrarEstado(mensaje, esError = false, duracion = 3200) {
                clearTimeout(temporizadorEstado);
                voiceStatus.textContent = mensaje;
                voiceStatus.classList.toggle('error', Boolean(esError));
                voiceStatus.classList.add('visible');
                temporizadorEstado = setTimeout(() => {
                    voiceStatus.classList.remove('visible');
                }, duracion);
            }

            function desplazarAlFinal() {
                listaMensajes.scrollTop = listaMensajes.scrollHeight;
            }

            function agregarBurbuja(rol, texto) {
                const li = document.createElement('li');
                li.className = 'chat-mensaje ' + rol;

                const parrafo = document.createElement('p');
                parrafo.textContent = texto;
                li.appendChild(parrafo);

                listaMensajes.appendChild(li);
                desplazarAlFinal();
                return parrafo;
            }

            function mostrarIndicadorEscribiendo() {
                const li = document.createElement('li');
                li.className = 'chat-escribiendo';
                li.id = 'indicador-escribiendo';
                li.innerHTML = '<span></span><span></span><span></span>';
                listaMensajes.appendChild(li);
                desplazarAlFinal();
            }

            function quitarIndicadorEscribiendo() {
                const indicador = document.getElementById('indicador-escribiendo');
                if (indicador) {
                    indicador.remove();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | BOTONES DE EMERGENCIA (nivel de riesgo 3-4)
            |--------------------------------------------------------------------------
            | Misma lógica de guardado que emergencia.php contra
            | admin/ajax/guardar_alerta.php, para no crear una tercera
            | implementación del botón de pánico.
            */
            function obtenerUbicacionActual() {
                return new Promise((resolve) => {
                    if (!navigator.geolocation) {
                        resolve(null);
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (posicion) => resolve(posicion.coords),
                        () => resolve(null),
                        { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 }
                    );
                });
            }

            async function guardarAlertaChat(tipo, descripcion) {
                const coords = await obtenerUbicacionActual();

                const formData = new FormData();
                formData.append('tipo', tipo);
                formData.append('usuario_id', <?= json_encode($usuarioId); ?>);
                formData.append('nombre_usuario', nombreUsuario);
                formData.append('latitud', coords ? coords.latitude : '');
                formData.append('longitud', coords ? coords.longitude : '');
                formData.append('precision_gps', coords ? coords.accuracy : '');
                formData.append('descripcion', descripcion);

                try {
                    await fetch('admin/ajax/guardar_alerta.php', { method: 'POST', body: formData });
                } catch (error) {
                    // La llamada de emergencia debe continuar aunque falle el registro.
                }
            }

            function agregarBotonesEmergencia() {
                const contenedor = document.createElement('li');
                contenedor.className = 'chat-emergencia';

                contenedor.innerHTML = `
                    <span class="chat-emergencia-titulo">Esto puede ser una emergencia. Acciones rápidas:</span>
                    <a href="tel:123" class="chat-emergencia-boton policia" id="btn-llamar-policia">
                        <i class="fa-solid fa-shield-halved"></i> Llamar a Policía (123)
                    </a>
                    ${contactoEmergenciaTel
                        ? `<a href="tel:${contactoEmergenciaTel}" class="chat-emergencia-boton contacto" id="btn-avisar-contacto">
                               <i class="fa-solid fa-phone"></i> Avisar a mi contacto de confianza
                           </a>`
                        : ''}
                    <a href="mis_evidencias.php" class="chat-emergencia-boton evidencias">
                        <i class="fa-solid fa-folder-open"></i> Ver mis evidencias
                    </a>
                `;

                listaMensajes.appendChild(contenedor);
                desplazarAlFinal();

                const btnPolicia = contenedor.querySelector('#btn-llamar-policia');
                if (btnPolicia) {
                    btnPolicia.addEventListener('click', (evento) => {
                        evento.preventDefault();
                        guardarAlertaChat('policia', 'La usuaria activó llamada a Policía 123 desde el chat psicológico de Aliada UTS.')
                            .finally(() => { window.location.href = 'tel:123'; });
                    });
                }

                const btnContacto = contenedor.querySelector('#btn-avisar-contacto');
                if (btnContacto) {
                    btnContacto.addEventListener('click', (evento) => {
                        evento.preventDefault();
                        guardarAlertaChat('contacto_emergencia', 'La usuaria activó llamada a su contacto de emergencia desde el chat psicológico de Aliada UTS.')
                            .finally(() => { window.location.href = 'tel:' + contactoEmergenciaTel; });
                    });
                }
            }

            /*
            |--------------------------------------------------------------------------
            | REPRODUCCIÓN DE AUDIO (ElevenLabs TTS)
            |--------------------------------------------------------------------------
            */
            function extraerFrasesHablables(texto) {
                return texto
                    .replace(/\s+/g, ' ')
                    .trim()
                    .split(/(?<=[.!?…])\s+/)
                    .filter((frase) => frase.trim() !== '');
            }

            function decodificarAudioBase64(base64) {
                const binario = atob(base64);
                const bytes = new Uint8Array(binario.length);

                for (let i = 0; i < binario.length; i++) {
                    bytes[i] = binario.charCodeAt(i);
                }

                return new Blob([bytes], { type: 'audio/mpeg' });
            }

            /*
             * Se pide siempre la alineación carácter por carácter de ElevenLabs
             * (con_tiempos) para poder revelar el texto palabra por palabra en
             * sincronía real con el audio, en vez de mostrar el fragmento
             * completo de una sola vez.
             */
            async function solicitarAudioConAlineacion(texto) {
                const respuesta = await fetch('elevenlabs_tts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        text: texto,
                        con_tiempos: true,
                        velocidad: VELOCIDAD_ELEVENLABS
                    })
                });

                if (!respuesta.ok) {
                    throw new Error('ElevenLabs TTS respondió con error.');
                }

                const datos = await respuesta.json();
                const blob = decodificarAudioBase64(datos.audio_base64);

                return {
                    url: URL.createObjectURL(blob),
                    alignment: datos.alignment || null
                };
            }

            /*
             * Convierte la alineación por carácter de ElevenLabs en palabras con
             * su tiempo de inicio (segundos), agrupando caracteres hasta el
             * siguiente espacio.
             */
            function calcularPalabrasConTiempos(alignment) {
                if (!alignment || !Array.isArray(alignment.characters)) {
                    return null;
                }

                const palabras = [];
                let palabraActual = '';
                let inicioPalabra = null;

                alignment.characters.forEach((caracter, indice) => {
                    const inicio = alignment.character_start_times_seconds[indice] ?? 0;

                    if (caracter.trim() === '') {
                        if (palabraActual !== '') {
                            palabras.push({ texto: palabraActual, inicio: inicioPalabra });
                            palabraActual = '';
                            inicioPalabra = null;
                        }
                        return;
                    }

                    if (inicioPalabra === null) {
                        inicioPalabra = inicio;
                    }
                    palabraActual += caracter;
                });

                if (palabraActual !== '') {
                    palabras.push({ texto: palabraActual, inicio: inicioPalabra });
                }

                return palabras;
            }

            function revelarFragmentoDeTexto(fragmento) {
                if (!parrafoEnRevelado) {
                    return;
                }

                const separador = parrafoEnRevelado.textContent.trim() === '' ? '' : ' ';
                parrafoEnRevelado.textContent += separador + fragmento;
                desplazarAlFinal();
            }

            /*
             * Revela cada palabra justo cuando el audio llega a su tiempo de
             * inicio real (dato de ElevenLabs), no antes ni después — esto es
             * lo que logra el efecto de "subtítulo" avanzando al ritmo del audio.
             * Se usa setInterval en vez de requestAnimationFrame porque este
             * último se frena si la pestaña pierde foco/visibilidad, y el audio
             * puede seguir sonando aunque la usuaria mire otra ventana.
             */
            function revelarPorPalabraSincronizado(audioElemento, palabras) {
                let indice = 0;

                const intervalo = setInterval(() => {
                    if (audioActual !== audioElemento) {
                        clearInterval(intervalo);
                        return;
                    }

                    while (indice < palabras.length && audioElemento.currentTime >= palabras[indice].inicio) {
                        revelarFragmentoDeTexto(palabras[indice].texto);
                        indice += 1;
                    }

                    if (indice >= palabras.length || audioElemento.ended) {
                        for (; indice < palabras.length; indice += 1) {
                            revelarFragmentoDeTexto(palabras[indice].texto);
                        }
                        clearInterval(intervalo);
                    }
                }, 60);
            }

            function finalizarRevelado() {
                if (colaAudio.length === 0) {
                    parrafoEnRevelado = null;
                    reactivarEscuchaSiCorresponde();
                }
            }

            async function reproducirSiguienteAudio() {
                if (reproduciendoAudio || colaAudio.length === 0) {
                    return;
                }

                reproduciendoAudio = true;
                btnParlante.classList.add('hablando');

                const textoFragmento = colaAudio.shift();
                let yaSeRevelo = false;

                try {
                    const { url: urlAudio, alignment } = await solicitarAudioConAlineacion(textoFragmento);
                    audioActual = new Audio(urlAudio);
                    audioActual.playbackRate = VELOCIDAD_AUDIO_RESPUESTA;

                    audioActual.addEventListener('ended', () => {
                        URL.revokeObjectURL(urlAudio);
                        reproduciendoAudio = false;
                        btnParlante.classList.remove('hablando');
                        finalizarRevelado();
                        reproducirSiguienteAudio();
                    });

                    await audioActual.play();

                    /*
                     * La revelación empieza después de que el audio ya está
                     * sonando de verdad (currentTime avanzando), nunca antes.
                     */
                    const palabras = calcularPalabrasConTiempos(alignment);
                    yaSeRevelo = true;

                    if (palabras && palabras.length > 0) {
                        revelarPorPalabraSincronizado(audioActual, palabras);
                    } else {
                        revelarFragmentoDeTexto(textoFragmento);
                    }
                } catch (error) {
                    // Si falla el audio de un fragmento, el texto se muestra igual para no dejar la burbuja vacía.
                    if (!yaSeRevelo) {
                        revelarFragmentoDeTexto(textoFragmento);
                    }
                    reproduciendoAudio = false;
                    btnParlante.classList.remove('hablando');
                    finalizarRevelado();
                    reproducirSiguienteAudio();
                }
            }

            function encolarRespuestaHablada(texto, parrafoDestino) {
                if (!audioActivo) {
                    return;
                }

                const frases = extraerFrasesHablables(texto);

                if (frases.length === 0) {
                    if (parrafoDestino) {
                        parrafoDestino.textContent = texto;
                    }
                    reactivarEscuchaSiCorresponde();
                    return;
                }

                parrafoEnRevelado = parrafoDestino || null;
                colaAudio.push(...frases);
                reproducirSiguienteAudio();
            }

            /*
            |--------------------------------------------------------------------------
            | CONVERSACIÓN FLUIDA: reactivar el micrófono al terminar el audio
            |--------------------------------------------------------------------------
            | Solo aplica con el modo audio activo: al terminar de reproducirse
            | la respuesta hablada, se vuelve a escuchar automáticamente sin
            | que la usuaria tenga que tocar el micrófono otra vez.
            */
            function reactivarEscuchaSiCorresponde() {
                if (!audioActivo || grabando || esperandoRespuesta) {
                    return;
                }

                setTimeout(() => {
                    if (audioActivo && !grabando && !esperandoRespuesta) {
                        iniciarGrabacion();
                    }
                }, RETARDO_REACTIVAR_ESCUCHA_MS);
            }

            function actualizarBotonParlante() {
                btnParlante.classList.toggle('activo', audioActivo);
                btnParlante.setAttribute('aria-pressed', audioActivo ? 'true' : 'false');
                btnParlante.querySelector('i').className = audioActivo
                    ? 'fa-solid fa-volume-high'
                    : 'fa-solid fa-volume-xmark';
            }

            btnParlante.addEventListener('click', () => {
                audioActivo = !audioActivo;
                localStorage.setItem(AUDIO_STORAGE_KEY, audioActivo ? '1' : '0');
                actualizarBotonParlante();
                mostrarEstado(audioActivo ? 'Respuestas por audio activadas.' : 'Respuestas por audio desactivadas.');
            });

            actualizarBotonParlante();

            /*
            |--------------------------------------------------------------------------
            | ENVÍO DE MENSAJES AL ENDPOINT DEL CHAT
            |--------------------------------------------------------------------------
            */
            async function enviarMensajeAlServidor(mensaje, origen) {
                const respuesta = await fetch('api/chat_psicologico.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        conversacion_id: conversacionId,
                        mensaje: mensaje,
                        origen: origen
                    })
                });

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    throw new Error(datos.error || 'No fue posible obtener respuesta de Violeta IA.');
                }

                return datos;
            }

            async function enviarMensaje(texto, origen) {
                const mensaje = texto.trim();

                if (mensaje === '' || esperandoRespuesta) {
                    return;
                }

                esperandoRespuesta = true;
                botonEnviar.disabled = true;

                agregarBurbuja('usuaria', mensaje);
                campoTexto.value = '';
                mostrarIndicadorEscribiendo();

                try {
                    const datos = await enviarMensajeAlServidor(mensaje, origen);

                    conversacionId = datos.conversacion_id;

                    quitarIndicadorEscribiendo();

                    if (audioActivo) {
                        const parrafoRespuesta = agregarBurbuja('asistente', '');
                        encolarRespuestaHablada(datos.respuesta_texto, parrafoRespuesta);
                    } else {
                        agregarBurbuja('asistente', datos.respuesta_texto);
                    }

                    /*
                     * DESHABILITADO TEMPORALMENTE (a petición del cliente):
                     * no mostrar el panel de 3 botones de emergencia para no
                     * interrumpir la conversación. El auto-registro en
                     * tbl_alerta_policia y el aviso por WhatsApp al contacto de
                     * confianza en riesgo nivel 4 (ChatRiesgoService, backend)
                     * NO dependen de esto y se siguen ejecutando igual.
                     * Para reactivar el panel de botones, descomentar:
                     *
                     * if (datos.requiere_botones_emergencia) {
                     *     agregarBotonesEmergencia();
                     * }
                     */
                } catch (error) {
                    quitarIndicadorEscribiendo();
                    agregarBurbuja('sistema', error.message || 'Hubo un problema de conexión. Por favor intenta de nuevo.');
                } finally {
                    esperandoRespuesta = false;
                    botonEnviar.disabled = false;
                    campoTexto.focus();
                }
            }

            formulario.addEventListener('submit', (evento) => {
                evento.preventDefault();
                enviarMensaje(campoTexto.value, 'texto');
            });

            campoTexto.addEventListener('keydown', (evento) => {
                if (evento.key === 'Enter' && !evento.shiftKey) {
                    evento.preventDefault();
                    formulario.requestSubmit();
                }
            });

            campoTexto.addEventListener('input', () => {
                campoTexto.style.height = 'auto';
                campoTexto.style.height = Math.min(campoTexto.scrollHeight, 110) + 'px';
            });

            /*
            |--------------------------------------------------------------------------
            | CAPTURA DE VOZ (grabación + ElevenLabs Speech-to-Text)
            |--------------------------------------------------------------------------
            | La grabación se detiene sola cuando detecta silencio tras haber
            | escuchado voz (Web Audio API sobre el mismo stream del micrófono),
            | y el mensaje transcrito se envía automáticamente, sin que la
            | usuaria tenga que tocar el botón de nuevo ni el de enviar. Un
            | segundo toque al micrófono mientras graba sigue funcionando como
            | corte manual (por ejemplo si terminó de hablar antes de la pausa
            | de silencio configurada).
            */
            function actualizarBotonMicrofono() {
                btnMicrofono.classList.toggle('escuchando', grabando);
                btnMicrofono.setAttribute('aria-pressed', grabando ? 'true' : 'false');
            }

            function calcularVolumenActual() {
                analizadorGrabacion.getByteTimeDomainData(bufferAnalisisVoz);

                let sumaCuadrados = 0;
                for (let i = 0; i < bufferAnalisisVoz.length; i++) {
                    const muestra = (bufferAnalisisVoz[i] - 128) / 128;
                    sumaCuadrados += muestra * muestra;
                }

                return Math.sqrt(sumaCuadrados / bufferAnalisisVoz.length);
            }

            function monitorearSilencio() {
                if (!grabando || !analizadorGrabacion) {
                    return;
                }

                const volumen = calcularVolumenActual();

                if (volumen > UMBRAL_VOLUMEN_VOZ) {
                    huboVozEnGrabacionActual = true;
                    silencioIniciadoEn = null;
                } else if (huboVozEnGrabacionActual) {
                    if (silencioIniciadoEn === null) {
                        silencioIniciadoEn = Date.now();
                    } else if (Date.now() - silencioIniciadoEn >= SILENCIO_MS_PARA_DETENER) {
                        detenerGrabacion();
                        return;
                    }
                }

                cuadroDeteccionSilencio = requestAnimationFrame(monitorearSilencio);
            }

            function iniciarDeteccionSilencio(flujo) {
                const AudioContextClase = window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClase) {
                    // Sin Web Audio API no hay corte automático; queda el límite de 60s y el toque manual.
                    return;
                }

                audioContextGrabacion = new AudioContextClase();
                const fuente = audioContextGrabacion.createMediaStreamSource(flujo);
                analizadorGrabacion = audioContextGrabacion.createAnalyser();
                analizadorGrabacion.fftSize = 2048;
                fuente.connect(analizadorGrabacion);

                bufferAnalisisVoz = new Uint8Array(analizadorGrabacion.fftSize);
                huboVozEnGrabacionActual = false;
                silencioIniciadoEn = null;

                monitorearSilencio();
            }

            function detenerDeteccionSilencio() {
                if (cuadroDeteccionSilencio !== null) {
                    cancelAnimationFrame(cuadroDeteccionSilencio);
                    cuadroDeteccionSilencio = null;
                }

                if (audioContextGrabacion) {
                    audioContextGrabacion.close().catch(() => {});
                    audioContextGrabacion = null;
                }

                analizadorGrabacion = null;
                bufferAnalisisVoz = null;
            }

            async function iniciarGrabacion() {
                if (!navigator.mediaDevices || !window.MediaRecorder) {
                    mostrarEstado('Tu navegador no permite grabar audio.', true);
                    return;
                }

                try {
                    const flujo = await navigator.mediaDevices.getUserMedia({ audio: true });

                    fragmentosAudio = [];
                    grabadora = new MediaRecorder(flujo, { mimeType: 'audio/webm' });

                    grabadora.addEventListener('dataavailable', (evento) => {
                        if (evento.data.size > 0) {
                            fragmentosAudio.push(evento.data);
                        }
                    });

                    grabadora.addEventListener('stop', () => {
                        flujo.getTracks().forEach((pista) => pista.stop());
                        detenerDeteccionSilencio();
                        procesarGrabacion();
                    });

                    grabadora.start();
                    grabando = true;
                    actualizarBotonMicrofono();
                    mostrarEstado('Te escucho... el mensaje se enviará solo cuando dejes de hablar.');

                    iniciarDeteccionSilencio(flujo);

                    temporizadorLimiteGrabacion = setTimeout(() => {
                        detenerGrabacion();
                    }, DURACION_MAXIMA_GRABACION_MS);
                } catch (error) {
                    mostrarEstado('No fue posible acceder al micrófono. Revisa los permisos.', true);
                }
            }

            function detenerGrabacion() {
                clearTimeout(temporizadorLimiteGrabacion);

                if (grabadora && grabadora.state !== 'inactive') {
                    grabadora.stop();
                }

                grabando = false;
                actualizarBotonMicrofono();
            }

            async function procesarGrabacion() {
                if (fragmentosAudio.length === 0) {
                    mostrarEstado('No se detectó audio en la grabación.', true);
                    return;
                }

                const blobAudio = new Blob(fragmentosAudio, { type: 'audio/webm' });

                if (blobAudio.size < 800) {
                    mostrarEstado('La grabación fue demasiado corta.', true);
                    return;
                }

                mostrarEstado('Transcribiendo tu voz...');

                const formData = new FormData();
                formData.append('audio', blobAudio, 'grabacion.webm');

                try {
                    const respuesta = await fetch('elevenlabs_stt.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': csrfToken },
                        body: formData
                    });

                    const datos = await respuesta.json();

                    if (!respuesta.ok) {
                        throw new Error(datos.error || 'No fue posible transcribir el audio.');
                    }

                    const textoTranscrito = (datos.texto || '').trim();

                    if (textoTranscrito === '') {
                        mostrarEstado('No se detectó texto en tu mensaje de voz.', true);
                        return;
                    }

                    const textoPrevio = campoTexto.value.trim();
                    const mensajeAEnviar = textoPrevio === '' ? textoTranscrito : textoPrevio + ' ' + textoTranscrito;
                    campoTexto.value = '';

                    mostrarEstado('Enviando tu mensaje de voz...');
                    enviarMensaje(mensajeAEnviar, 'voz');
                } catch (error) {
                    mostrarEstado(error.message || 'No fue posible transcribir el audio.', true);
                }
            }

            btnMicrofono.addEventListener('click', () => {
                if (grabando) {
                    detenerGrabacion();
                } else {
                    iniciarGrabacion();
                }
            });

            agregarBurbuja('asistente', saludoBienvenida);
        });
    </script>
    <?php include __DIR__ . '/videollamada_banner.php'; ?>
</body>
</html>
