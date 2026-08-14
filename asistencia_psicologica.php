<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

$pageTitle       = 'Aliada UTS | Apoyo psicológico con IA';
$pageDescription = 'Aliada UTS, plataforma de apoyo psicológico con IA para brindar escucha, orientación y acompañamiento seguro.';
$currentYear     = date('Y');

$nombreUsuario = '';

$idUsuario = '';

if (!empty($_SESSION['id'])) {
    $idUsuario = trim((string) $_SESSION['id']);
}

if ($idUsuario === '') {
    $idUsuario = '0';
}   
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

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="assets/css/asistencia_psicologica.css?v=<?= time(); ?>" rel="stylesheet">

    <style>
        /*
        |--------------------------------------------------------------------------
        | COLORES PRINCIPALES DEL CHAT N8N
        |--------------------------------------------------------------------------
        */
        :root {
            --chat--color-primary: #D81B9C;
            --chat--color-primary-shade-50: #B41682;
            --chat--color-primary-shade-100: #8E1168;

            --chat--color-white: #FFFFFF;
            --chat--color-light: #F8F4F7;
            --chat--color-dark: #1F2937;

            --chat--message--bot--background: #FFFFFF;
            --chat--message--bot--color: #1F2937;
            --chat--message--user--background: #D81B9C;
            --chat--message--user--color: #FFFFFF;

            --chat--input--background: #FFFFFF;
            --chat--input--text-color: #111827;
            --chat--input--placeholder--font-size: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | CORRECCIÓN: TEXTO DEL CAMPO VISIBLE
        |--------------------------------------------------------------------------
        | Estas reglas están después del CSS de n8n y del CSS del proyecto.
        | Por eso pueden corregir un color blanco/transparente heredado.
        */
        #n8n-chat textarea,
        #n8n-chat input[type="text"],
        #n8n-chat [contenteditable="true"],
        #n8n-chat .chat-input textarea,
        #n8n-chat .chat-inputs textarea {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;

            width: 100% !important;
            min-height: 48px !important;

            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
            caret-color: #D81B9C !important;

            background: #FFFFFF !important;

            font-family: inherit !important;
            font-size: 16px !important;
            font-weight: 400 !important;
            line-height: 1.45 !important;

            text-shadow: none !important;
        }

        #n8n-chat textarea::placeholder,
        #n8n-chat input[type="text"]::placeholder {
            color: #6B7280 !important;
            -webkit-text-fill-color: #6B7280 !important;
            opacity: 1 !important;
        }

        #n8n-chat textarea:focus,
        #n8n-chat input[type="text"]:focus,
        #n8n-chat [contenteditable="true"]:focus {
            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
            background: #FFFFFF !important;
            outline: none !important;
        }

        /*
        |--------------------------------------------------------------------------
        | CORRECCIÓN: MENSAJES DEL CHAT VISIBLES
        |--------------------------------------------------------------------------
        */
        #n8n-chat .chat-message,
        #n8n-chat .chat-message-markdown,
        #n8n-chat .chat-message-markdown p,
        #n8n-chat .chat-message-markdown span,
        #n8n-chat .chat-message-markdown li {
            visibility: visible !important;
            opacity: 1 !important;
            font-size: 15px !important;
            line-height: 1.55 !important;
            text-shadow: none !important;
        }

        #n8n-chat .chat-message-from-bot,
        #n8n-chat .chat-message-from-bot .chat-message-markdown,
        #n8n-chat .chat-message-from-bot .chat-message-markdown *,
        #n8n-chat .chat-message-from-bot p,
        #n8n-chat .chat-message-from-bot span,
        #n8n-chat .chat-message-from-bot li {
            color: #1F2937 !important;
            -webkit-text-fill-color: #1F2937 !important;
        }

        #n8n-chat .chat-message-from-user,
        #n8n-chat .chat-message-from-user .chat-message-markdown,
        #n8n-chat .chat-message-from-user .chat-message-markdown *,
        #n8n-chat .chat-message-from-user p,
        #n8n-chat .chat-message-from-user span,
        #n8n-chat .chat-message-from-user li {
            color: #FFFFFF !important;
            -webkit-text-fill-color: #FFFFFF !important;
        }

        #n8n-chat .chat-message-from-bot a {
            color: #B41682 !important;
            -webkit-text-fill-color: #B41682 !important;
            text-decoration: underline !important;
        }

        #n8n-chat .chat-message-from-user a {
            color: #FFFFFF !important;
            -webkit-text-fill-color: #FFFFFF !important;
            text-decoration: underline !important;
        }

        /*
        |--------------------------------------------------------------------------
        | CONTROLES DE VOZ
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

            transition:
                transform 0.2s ease,
                filter 0.2s ease;
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

        .alma-control-button:hover {
            transform: translateY(-2px);
            box-shadow:
                0 10px 24px rgba(132, 20, 96, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.95);
        }

        .alma-control-button:hover i {
            transform: scale(1.08);
        }

        .alma-control-button:active {
            transform: translateY(0) scale(0.98);
        }

        .alma-control-button.activo {
            color: #FFFFFF;
            border-color: transparent;

            background:
                linear-gradient(135deg, #E321A4 0%, #B71382 52%, #8F0F67 100%);

            box-shadow:
                0 10px 24px rgba(183, 19, 130, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.24);
        }

        .alma-control-button.activo i {
            filter: drop-shadow(0 2px 4px rgba(64, 4, 45, 0.25));
        }

        .alma-control-button.escuchando {
            color: #FFFFFF;
            border-color: transparent;

            background:
                linear-gradient(135deg, #FF4D67 0%, #E52343 55%, #C81432 100%);

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
            0% {
                box-shadow:
                    0 0 0 0 rgba(229, 35, 67, 0.40),
                    0 8px 22px rgba(200, 20, 50, 0.24);
            }

            70% {
                box-shadow:
                    0 0 0 12px rgba(229, 35, 67, 0),
                    0 8px 22px rgba(200, 20, 50, 0.24);
            }

            100% {
                box-shadow:
                    0 0 0 0 rgba(229, 35, 67, 0),
                    0 8px 22px rgba(200, 20, 50, 0.24);
            }
        }

        @keyframes anilloAudio {
            0% {
                opacity: 0.75;
                transform: scale(0.88);
            }

            100% {
                opacity: 0;
                transform: scale(1.20);
            }
        }

        @keyframes parlanteActivo {
            from {
                transform: scale(0.92) translateX(-1px);
            }

            to {
                transform: scale(1.16) translateX(1px);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TEXTO SINCRONIZADO CON LA VOZ
        |--------------------------------------------------------------------------
        | n8n conserva internamente su respuesta completa. Solo se oculta
        | visualmente la fuente mientras ElevenLabs la va pronunciando.
        */
        #n8n-chat .alma-sync-source {
            display: none !important;
        }

        #n8n-chat .alma-sync-text {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;

            min-height: 1.55em;

            color: #1F2937 !important;
            -webkit-text-fill-color: #1F2937 !important;

            font-family: inherit !important;
            font-size: 15px !important;
            font-weight: 400 !important;
            line-height: 1.55 !important;

            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        #n8n-chat .alma-sync-text.escribiendo::after {
            content: '';
            display: inline-block;

            width: 2px;
            height: 1.05em;
            margin-left: 3px;

            vertical-align: -0.12em;

            border-radius: 2px;
            background: #D81B9C;

            animation: cursorAlma 0.8s steps(1) infinite;
        }

        @keyframes cursorAlma {
            0%,
            48% {
                opacity: 1;
            }

            49%,
            100% {
                opacity: 0;
            }
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
                        title="Activar conversación por voz"
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
                    <div id="n8n-chat"></div>
                </div>
            </section>

      <footer class="footer-mobile">
    <p>
        © <?= $currentYear; ?> <strong>Aliada UTS</strong>
        · Orientación legal con IA.<br>

        Desarrollado por <strong>Helios Electronics</strong> ·

        <a
            href="https://helios.com.co"
            target="_blank"
            rel="noopener noreferrer"
        >
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

    <script type="module">
        import {
            createChat
        } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

        document.addEventListener('DOMContentLoaded', () => {

            const nombreUsuario = <?= json_encode(
                $nombreUsuario,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ); ?>;

            const saludoBienvenida = <?= json_encode(
                $saludoBienvenida,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ); ?>;

            const elevenLabsToken = <?= json_encode(
                $_SESSION['elevenlabs_csrf'],
                JSON_UNESCAPED_SLASHES
            ); ?>;

            const idUsuario = <?= json_encode(
                $idUsuario,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ); ?>;

            createChat({
                webhookUrl: 'https://n8n.spidersoftwareia.com/webhook/8df39cfc-b6b0-4815-afe2-aeea64794c3b/chat',

                webhookConfig: {
                    method: 'POST',
                    headers: {}
                },

                target: '#n8n-chat',
                mode: 'fullscreen',

                chatInputKey: 'chatInput',
                chatSessionKey: 'sessionId',

                loadPreviousSession: true,

                metadata: {
                    asistente: 'Violeta IA',
                    proyecto: 'Aliada UTS',
                    modulo: 'Apoyo psicológico con IA',
                    nombre_usuario: nombreUsuario,
                    id_usuario: idUsuario
                },

                showWelcomeScreen: false,
                defaultLanguage: 'es',

                initialMessages: [
                    saludoBienvenida
                ],

                i18n: {
                    es: {
                        title: 'Violeta IA 💜',
                        subtitle: 'Apoyo psicológico cercano, seguro y confidencial.',
                        footer: '',
                        getStarted: 'Nueva conversación',
                        inputPlaceholder: 'Escribe o activa el micrófono para hablar...'
                    }
                },

                /*
                 * Debe estar en true para que n8n escriba la respuesta
                 * progresivamente. El Chat Trigger del flujo también debe
                 * tener Response Mode configurado como Streaming.
                 */
                enableStreaming: true
            });

            const chatRoot =
                document.getElementById('n8n-chat');

            const btnMicrofono =
                document.getElementById('btn-microfono');

            const btnParlante =
                document.getElementById('btn-parlante');

            const voiceStatus =
                document.getElementById('alma-voice-status');

            const SpeechRecognitionClass =
                window.SpeechRecognition ||
                window.webkitSpeechRecognition ||
                null;

            /*
             * En celular el reconocimiento continuo puede volver a entregar
             * fragmentos ya procesados y duplicar el dictado.
             */
            const ES_DISPOSITIVO_MOVIL =
                navigator.userAgentData?.mobile === true ||
                /Android|iPhone|iPad|iPod|Mobile/i.test(
                    navigator.userAgent || ''
                ) ||
                window.matchMedia?.('(pointer: coarse)').matches === true;

            const sintetizadorNavegador =
                window.speechSynthesis || null;

            const AUDIO_STORAGE_KEY =
                'aliada_uts_audio_psicologia_activo';

            let reconocimiento = null;

            let campoChat = null;
            let botonEnviar = null;

            let modoConversacion = false;
            let microfonoEscuchando = false;

            let audioActivo =
                localStorage.getItem(AUDIO_STORAGE_KEY) === '1';

            let esperandoRespuesta = false;
            let respuestaEnProceso = false;
            let respuestaTerminada = false;

            let enviandoMensajeVoz = false;
            let almaHablando = false;

            let textoAntesDeHablar = '';
            let textoFinal = '';
            let textoIntermedio = '';
            let motivoDetencion = '';

            /*
             * Evita que el mismo dictado se envíe dos veces cuando
             * el celular dispara onend y el temporizador casi juntos.
             */
            let envioAutomaticoSolicitado = false;
            let ultimoMensajeVozEnviado = '';

            let temporizadorSilencio = null;
            let temporizadorReinicio = null;
            let temporizadorEstado = null;
            let temporizadorFinRespuesta = null;

            /*
            |--------------------------------------------------------------------------
            | CONTROL DE TEXTO EN STREAMING
            |--------------------------------------------------------------------------
            */
            let elementoRespuestaActual = null;
            let textoRespuestaAnterior = '';
            let bufferTextoAudio = '';

            /*
             * Mensaje que ya existía antes de enviar la pregunta.
             * Evita confundir una respuesta anterior con la nueva.
             */
            let elementoBotAntesDeEnviar = null;
            let textoBotAntesDeEnviar = '';

            /*
            |--------------------------------------------------------------------------
            | COLA DE AUDIO ELEVENLABS
            |--------------------------------------------------------------------------
            */
            let colaAudio = [];
            let reproduciendoAudio = false;
            let audioActual = null;
            let controladorPeticionAudio = null;
            let idConversacionAudio = 0;

            /*
            |--------------------------------------------------------------------------
            | VISTA DE TEXTO SINCRONIZADA CON EL AUDIO
            |--------------------------------------------------------------------------
            */
            let elementoTextoSincronizado = null;
            let fuenteTextoSincronizado = null;
            let visorTextoSincronizado = null;

            function mostrarEstado(
                mensaje,
                error = false,
                duracion = 2800
            ) {
                clearTimeout(temporizadorEstado);

                voiceStatus.textContent = mensaje;
                voiceStatus.classList.toggle('error', Boolean(error));
                voiceStatus.classList.add('visible');

                temporizadorEstado = setTimeout(() => {
                    voiceStatus.classList.remove('visible');
                }, duracion);
            }

            function buscarControlesChat() {
                campoChat =
                    chatRoot.querySelector('textarea[data-test-id="chat-input"]') ||
                    chatRoot.querySelector('.chat-inputs textarea') ||
                    chatRoot.querySelector('.chat-input textarea') ||
                    chatRoot.querySelector('textarea');

                botonEnviar =
                    chatRoot.querySelector('.chat-input-send-button') ||
                    chatRoot.querySelector('button[type="submit"]') ||
                    chatRoot.querySelector('button[aria-label*="Enviar"]') ||
                    chatRoot.querySelector('button[aria-label*="Send"]');

                return Boolean(campoChat);
            }

            function escribirEnCampo(texto) {
                if (!campoChat && !buscarControlesChat()) {
                    return;
                }

                const valor =
                    String(texto || '')
                        .replace(/\s+/g, ' ')
                        .trim();

                const descriptor =
                    Object.getOwnPropertyDescriptor(
                        HTMLTextAreaElement.prototype,
                        'value'
                    );

                if (descriptor?.set) {
                    descriptor.set.call(campoChat, valor);
                } else {
                    campoChat.value = valor;
                }

                /*
                 * Un evento input sencillo evita que algunos celulares
                 * interpreten el contenido completo como texto nuevo
                 * y terminen duplicando palabras.
                 */
                campoChat.dispatchEvent(
                    new Event('input', {
                        bubbles: true
                    })
                );

                campoChat.dispatchEvent(
                    new Event('change', {
                        bubbles: true
                    })
                );

                /*
                 * No se abre forzosamente el teclado virtual al dictar.
                 */
                if (
                    !ES_DISPOSITIVO_MOVIL ||
                    document.activeElement === campoChat
                ) {
                    campoChat.focus();

                    try {
                        campoChat.setSelectionRange(
                            valor.length,
                            valor.length
                        );
                    } catch (error) {
                        // Algunos navegadores móviles no lo permiten.
                    }
                }

                campoChat.scrollTop =
                    campoChat.scrollHeight;
            }

            function actualizarBotonMicrofono() {
                const icono = btnMicrofono.querySelector('i');

                btnMicrofono.classList.toggle(
                    'activo',
                    modoConversacion
                );

                btnMicrofono.classList.toggle(
                    'escuchando',
                    microfonoEscuchando
                );

                btnMicrofono.setAttribute(
                    'aria-pressed',
                    modoConversacion ? 'true' : 'false'
                );

                if (!SpeechRecognitionClass) {
                    btnMicrofono.disabled = true;
                    icono.className = 'fa-solid fa-microphone-slash';
                    btnMicrofono.title =
                        'Este navegador no permite reconocimiento de voz';
                    return;
                }

                btnMicrofono.disabled = false;

                if (microfonoEscuchando) {
                    icono.className = 'fa-solid fa-microphone-lines';
                    btnMicrofono.title =
                        'Violeta está escuchando. Pulsa para detener.';
                    return;
                }

                icono.className =
                    modoConversacion
                        ? 'fa-solid fa-microphone-lines'
                        : 'fa-solid fa-microphone';

                btnMicrofono.title =
                    modoConversacion
                        ? 'Conversación por voz activa'
                        : 'Activar conversación por voz';
            }

            function actualizarBotonParlante() {
                const icono = btnParlante.querySelector('i');

                btnParlante.classList.toggle(
                    'activo',
                    audioActivo
                );

                btnParlante.classList.toggle(
                    'hablando',
                    almaHablando
                );

                btnParlante.setAttribute(
                    'aria-pressed',
                    audioActivo ? 'true' : 'false'
                );

                icono.className =
                    almaHablando
                        ? 'fa-solid fa-wave-square'
                        : (
                            audioActivo
                                ? 'fa-solid fa-volume-high'
                                : 'fa-solid fa-volume-xmark'
                        );

                btnParlante.title =
                    audioActivo
                        ? 'Desactivar respuestas por audio'
                        : 'Activar respuestas por audio';
            }

            function normalizarDictado(texto) {
                return String(texto || '')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function combinarSinRepetir(textoBase, textoNuevo) {
                const base =
                    normalizarDictado(textoBase);

                const nuevo =
                    normalizarDictado(textoNuevo);

                if (!base) {
                    return nuevo;
                }

                if (!nuevo) {
                    return base;
                }

                const baseMinuscula =
                    base.toLocaleLowerCase('es');

                const nuevoMinuscula =
                    nuevo.toLocaleLowerCase('es');

                if (
                    baseMinuscula === nuevoMinuscula ||
                    baseMinuscula.endsWith(
                        ' ' + nuevoMinuscula
                    )
                ) {
                    return base;
                }

                /*
                 * El navegador puede convertir un resultado intermedio
                 * en final y volver a entregar la frase completa.
                 */
                if (
                    nuevoMinuscula.startsWith(
                        baseMinuscula + ' '
                    )
                ) {
                    return nuevo;
                }

                if (
                    baseMinuscula.startsWith(
                        nuevoMinuscula + ' '
                    )
                ) {
                    return base;
                }

                const palabrasBase =
                    base.split(' ');

                const palabrasNuevas =
                    nuevo.split(' ');

                const maximoSolapamiento =
                    Math.min(
                        palabrasBase.length,
                        palabrasNuevas.length
                    );

                /*
                 * Se exige un solapamiento mínimo de dos palabras para
                 * no borrar repeticiones que la usuaria sí quiso decir.
                 */
                for (
                    let cantidad = maximoSolapamiento;
                    cantidad >= 2;
                    cantidad--
                ) {
                    const finalBase =
                        palabrasBase
                            .slice(-cantidad)
                            .join(' ')
                            .toLocaleLowerCase('es');

                    const inicioNuevo =
                        palabrasNuevas
                            .slice(0, cantidad)
                            .join(' ')
                            .toLocaleLowerCase('es');

                    if (finalBase === inicioNuevo) {
                        return [
                            ...palabrasBase,
                            ...palabrasNuevas.slice(cantidad)
                        ].join(' ');
                    }
                }

                return base + ' ' + nuevo;
            }

            function unirResultadosSinRepetir(partes) {
                return partes.reduce(
                    (acumulado, parte) => {
                        return combinarSinRepetir(
                            acumulado,
                            parte
                        );
                    },
                    ''
                );
            }

            function construirMensajeReconocido() {
                const textoSesion =
                    combinarSinRepetir(
                        textoFinal,
                        textoIntermedio
                    );

                return combinarSinRepetir(
                    textoAntesDeHablar,
                    textoSesion
                );
            }

            function finalizarDictadoYEnviar() {
                if (
                    envioAutomaticoSolicitado ||
                    enviandoMensajeVoz ||
                    esperandoRespuesta ||
                    respuestaEnProceso
                ) {
                    return;
                }

                const mensajeReconocido =
                    construirMensajeReconocido();

                if (!mensajeReconocido) {
                    return;
                }

                envioAutomaticoSolicitado = true;
                motivoDetencion = 'envio-procesado';

                clearTimeout(temporizadorSilencio);
                escribirEnCampo(mensajeReconocido);

                /*
                 * En móvil se guarda primero el texto y luego se aborta
                 * el reconocimiento. Así no dependemos de onend.
                 */
                if (
                    reconocimiento &&
                    microfonoEscuchando
                ) {
                    try {
                        reconocimiento.abort();
                    } catch (error) {
                        console.warn(
                            'No fue posible cerrar el dictado:',
                            error
                        );
                    }
                }

                mostrarEstado(
                    'Enviando tu mensaje a Violeta IA...'
                );

                setTimeout(() => {
                    enviarMensajeChat(
                        mensajeReconocido
                    );
                }, ES_DISPOSITIVO_MOVIL ? 220 : 120);
            }

            function enviarMensajeChat(
                mensajeForzado = ''
            ) {
                if (!campoChat && !buscarControlesChat()) {
                    envioAutomaticoSolicitado = false;

                    mostrarEstado(
                        'No se encontró el campo del chat.',
                        true
                    );

                    return;
                }

                const mensaje =
                    normalizarDictado(
                        mensajeForzado ||
                        campoChat.value
                    );

                if (!mensaje) {
                    enviandoMensajeVoz = false;
                    envioAutomaticoSolicitado = false;
                    return;
                }

                /*
                 * Se vuelve a escribir para que Vue/n8n tenga el valor
                 * actualizado antes de habilitar el botón de enviar.
                 */
                escribirEnCampo(mensaje);

                esperandoRespuesta = true;
                respuestaEnProceso = false;
                respuestaTerminada = false;
                enviandoMensajeVoz = true;

                elementoBotAntesDeEnviar =
                    obtenerUltimoMensajeBot();

                textoBotAntesDeEnviar =
                    elementoBotAntesDeEnviar
                        ? normalizarTextoDom(
                            elementoBotAntesDeEnviar.innerText ||
                            elementoBotAntesDeEnviar.textContent ||
                            ''
                        )
                        : '';

                elementoRespuestaActual = null;
                textoRespuestaAnterior = '';
                bufferTextoAudio = '';

                cancelarAudioActual(false);

                let intentos = 0;
                let mensajeEnviado = false;

                function finalizarEstadoEnvio(
                    enviado
                ) {
                    enviandoMensajeVoz = false;
                    envioAutomaticoSolicitado = false;

                    if (enviado) {
                        ultimoMensajeVozEnviado =
                            mensaje;
                    } else {
                        esperandoRespuesta = false;

                        mostrarEstado(
                            'No pude enviarlo automáticamente. Pulsa el botón Enviar.',
                            true,
                            4500
                        );
                    }
                }

                function intentarEnviar() {
                    if (mensajeEnviado) {
                        return;
                    }

                    intentos++;
                    buscarControlesChat();

                    if (
                        botonEnviar &&
                        !botonEnviar.disabled
                    ) {
                        mensajeEnviado = true;
                        botonEnviar.click();

                        setTimeout(() => {
                            finalizarEstadoEnvio(true);
                        }, 700);

                        return;
                    }

                    /*
                     * En celular Vue puede tardar más en habilitar
                     * el botón. Se espera hasta cuatro segundos.
                     */
                    if (intentos <= 32) {
                        setTimeout(
                            intentarEnviar,
                            125
                        );

                        return;
                    }

                    /*
                     * Respaldo para versiones del chat que usan formulario.
                     */
                    const formulario =
                        campoChat?.closest('form');

                    if (formulario) {
                        const botonFormulario =
                            formulario.querySelector(
                                'button[type="submit"]:not([disabled])'
                            );

                        if (botonFormulario) {
                            mensajeEnviado = true;
                            botonFormulario.click();

                            setTimeout(() => {
                                finalizarEstadoEnvio(true);
                            }, 700);

                            return;
                        }

                        const eventoEnvio =
                            new Event('submit', {
                                bubbles: true,
                                cancelable: true
                            });

                        formulario.dispatchEvent(
                            eventoEnvio
                        );

                        setTimeout(() => {
                            const campoVacio =
                                !normalizarDictado(
                                    campoChat?.value || ''
                                );

                            finalizarEstadoEnvio(
                                campoVacio
                            );
                        }, 800);

                        return;
                    }

                    finalizarEstadoEnvio(false);
                }

                setTimeout(
                    intentarEnviar,
                    ES_DISPOSITIVO_MOVIL ? 320 : 180
                );
            }

            async function solicitarPermisoMicrofono() {
                if (
                    !navigator.mediaDevices ||
                    !navigator.mediaDevices.getUserMedia
                ) {
                    return true;
                }

                try {
                    const stream =
                        await navigator.mediaDevices.getUserMedia({
                            audio: true
                        });

                    stream.getTracks().forEach((track) => {
                        track.stop();
                    });

                    return true;
                } catch (error) {
                    console.error(
                        'Permiso de micrófono rechazado:',
                        error
                    );

                    mostrarEstado(
                        'Debes permitir el acceso al micrófono.',
                        true,
                        5000
                    );

                    return false;
                }
            }

            function detenerMicrofono(motivo = 'manual') {
                clearTimeout(temporizadorSilencio);
                clearTimeout(temporizadorReinicio);

                motivoDetencion = motivo;

                if (!reconocimiento || !microfonoEscuchando) {
                    return;
                }

                try {
                    if (
                        motivo === 'parlante' ||
                        motivo === 'cancelar'
                    ) {
                        reconocimiento.abort();
                    } else {
                        reconocimiento.stop();
                    }
                } catch (error) {
                    console.warn(
                        'No fue posible detener el micrófono:',
                        error
                    );
                }
            }

            function iniciarMicrofono(retraso = 0) {
                clearTimeout(temporizadorReinicio);

                temporizadorReinicio = setTimeout(() => {
                    if (
                        !modoConversacion ||
                        esperandoRespuesta ||
                        respuestaEnProceso ||
                        almaHablando ||
                        microfonoEscuchando ||
                        enviandoMensajeVoz ||
                        !reconocimiento
                    ) {
                        return;
                    }

                    buscarControlesChat();

                    const valorActualCampo =
                        normalizarDictado(
                            campoChat?.value || ''
                        );

                    textoAntesDeHablar =
                        valorActualCampo ===
                        ultimoMensajeVozEnviado
                            ? ''
                            : valorActualCampo;

                    textoFinal = '';
                    textoIntermedio = '';
                    motivoDetencion = '';
                    envioAutomaticoSolicitado = false;

                    try {
                        reconocimiento.start();
                    } catch (error) {
                        console.warn(
                            'El reconocimiento ya estaba iniciando:',
                            error
                        );
                    }
                }, retraso);
            }

            function configurarReconocimiento() {
                if (!SpeechRecognitionClass) {
                    actualizarBotonMicrofono();
                    return;
                }

                reconocimiento =
                    new SpeechRecognitionClass();

                reconocimiento.lang = 'es-CO';
                reconocimiento.interimResults = true;

                /*
                 * En escritorio se conserva continuous.
                 * En móvil una sola intervención evita duplicaciones.
                 */
                reconocimiento.continuous =
                    !ES_DISPOSITIVO_MOVIL;

                reconocimiento.maxAlternatives = 1;

                reconocimiento.onstart = () => {
                    microfonoEscuchando = true;
                    textoFinal = '';
                    textoIntermedio = '';
                    envioAutomaticoSolicitado = false;

                    actualizarBotonMicrofono();

                    mostrarEstado(
                        'Te escucho. Habla con naturalidad.'
                    );
                };

                reconocimiento.onresult = (event) => {
                    const resultadosFinales = [];
                    const resultadosIntermedios = [];

                    /*
                     * Se reconstruye toda la sesión en cada evento.
                     * No se acumula sobre textoFinal, porque en móvil
                     * pueden regresar nuevamente resultados anteriores.
                     */
                    for (
                        let indice = 0;
                        indice < event.results.length;
                        indice++
                    ) {
                        const frase =
                            normalizarDictado(
                                event.results[indice][0]
                                    ?.transcript || ''
                            );

                        if (!frase) {
                            continue;
                        }

                        if (
                            event.results[indice].isFinal
                        ) {
                            resultadosFinales.push(
                                frase
                            );
                        } else {
                            resultadosIntermedios.push(
                                frase
                            );
                        }
                    }

                    textoFinal =
                        unirResultadosSinRepetir(
                            resultadosFinales
                        );

                    textoIntermedio =
                        unirResultadosSinRepetir(
                            resultadosIntermedios
                        );

                    const textoCompleto =
                        construirMensajeReconocido();

                    escribirEnCampo(textoCompleto);

                    clearTimeout(
                        temporizadorSilencio
                    );

                    temporizadorSilencio =
                        setTimeout(() => {
                            if (
                                textoFinal ||
                                textoIntermedio
                            ) {
                                finalizarDictadoYEnviar();
                            }
                        }, ES_DISPOSITIVO_MOVIL ? 1300 : 1500);
                };

                reconocimiento.onerror = (event) => {
                    microfonoEscuchando = false;
                    actualizarBotonMicrofono();

                    const tipoError =
                        String(event.error || '');

                    if (
                        tipoError === 'not-allowed' ||
                        tipoError === 'service-not-allowed'
                    ) {
                        modoConversacion = false;
                        actualizarBotonMicrofono();

                        mostrarEstado(
                            'Debes permitir el acceso al micrófono.',
                            true,
                            5000
                        );

                        return;
                    }

                    if (tipoError === 'audio-capture') {
                        modoConversacion = false;
                        actualizarBotonMicrofono();

                        mostrarEstado(
                            'No se encontró un micrófono disponible.',
                            true,
                            4500
                        );

                        return;
                    }

                    if (
                        tipoError === 'aborted' ||
                        tipoError === 'no-speech'
                    ) {
                        return;
                    }

                    console.error(
                        'Error de reconocimiento:',
                        tipoError
                    );

                    mostrarEstado(
                        'No pude escuchar correctamente. Intenta nuevamente.',
                        true
                    );
                };

                reconocimiento.onend = () => {
                    microfonoEscuchando = false;
                    actualizarBotonMicrofono();

                    const motivoFinal =
                        motivoDetencion;

                    if (
                        motivoFinal === 'envio-procesado'
                    ) {
                        motivoDetencion = '';
                        return;
                    }

                    if (
                        motivoFinal === 'parlante' ||
                        motivoFinal === 'cancelar'
                    ) {
                        textoFinal = '';
                        textoIntermedio = '';
                        motivoDetencion = '';
                        return;
                    }

                    if (motivoFinal === 'manual') {
                        motivoDetencion = '';
                        return;
                    }

                    /*
                     * En celular el servicio puede terminar solo antes
                     * de que se complete el temporizador de silencio.
                     */
                    if (
                        modoConversacion &&
                        !esperandoRespuesta &&
                        !respuestaEnProceso &&
                        !enviandoMensajeVoz &&
                        construirMensajeReconocido()
                    ) {
                        setTimeout(
                            finalizarDictadoYEnviar,
                            ES_DISPOSITIVO_MOVIL
                                ? 120
                                : 250
                        );

                        return;
                    }

                    if (
                        modoConversacion &&
                        !esperandoRespuesta &&
                        !respuestaEnProceso &&
                        !almaHablando &&
                        !enviandoMensajeVoz
                    ) {
                        iniciarMicrofono(
                            ES_DISPOSITIVO_MOVIL
                                ? 500
                                : 700
                        );
                    }
                };
            }

            /*
            |--------------------------------------------------------------------------
            | TEXTO PARA ELEVENLABS
            |--------------------------------------------------------------------------
            */
            function limpiarTextoParaAudio(texto) {
                return String(texto || '')
                    .replace(
                        /\[([^\]]+)\]\(([^)]+)\)/g,
                        '$1'
                    )
                    .replace(/https?:\/\/\S+/gi, ' enlace ')
                    .replace(/```[\s\S]*?```/g, ' ')
                    .replace(/`([^`]+)`/g, '$1')
                    .replace(/[*_#>|~]/g, ' ')
                    .replace(/[⚖️✅❌📌🔹🔸•]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function normalizarTextoDom(texto) {
                return String(texto || '')
                    .replace(/\u00A0/g, ' ')
                    .replace(/[ \t]+/g, ' ')
                    .replace(/\n{3,}/g, '\n\n')
                    .trim();
            }

            /*
            |--------------------------------------------------------------------------
            | MOSTRAR EL TEXTO AL RITMO DEL AUDIO
            |--------------------------------------------------------------------------
            */
            function obtenerTextoOriginalBot(elemento) {
                if (!elemento) {
                    return '';
                }

                /*
                 * Se clona el mensaje y se retira el visor generado por Alma.
                 * Así el MutationObserver solo procesa el texto real de n8n.
                 */
                const clon = elemento.cloneNode(true);

                clon.querySelectorAll('.alma-sync-text').forEach((nodo) => {
                    nodo.remove();
                });

                return normalizarTextoDom(
                    clon.textContent || ''
                );
            }

            function obtenerFuenteVisualBot(elemento) {
                if (!elemento) {
                    return null;
                }

                return (
                    elemento.querySelector('.chat-message-markdown') ||
                    elemento.querySelector('.chat-message-content') ||
                    elemento.querySelector('[class*="message-content"]')
                );
            }

            function prepararTextoSincronizado(elemento) {
                if (!audioActivo || !elemento) {
                    return null;
                }

                if (
                    elementoTextoSincronizado === elemento &&
                    visorTextoSincronizado
                ) {
                    return visorTextoSincronizado;
                }

                const fuente =
                    obtenerFuenteVisualBot(elemento);

                /*
                 * Si n8n cambia sus clases en una versión futura,
                 * no se oculta nada para evitar dañar el chat.
                 */
                if (!fuente) {
                    return null;
                }

                let visor =
                    elemento.querySelector('.alma-sync-text');

                if (!visor) {
                    visor = document.createElement('div');
                    visor.className = 'alma-sync-text';
                    visor.setAttribute('aria-live', 'polite');
                    visor.setAttribute('aria-atomic', 'false');

                    fuente.insertAdjacentElement(
                        'afterend',
                        visor
                    );
                }

                fuente.classList.add('alma-sync-source');
                visor.classList.add('escribiendo');

                elementoTextoSincronizado = elemento;
                fuenteTextoSincronizado = fuente;
                visorTextoSincronizado = visor;

                return visor;
            }

            function completarTextoSincronizado(elemento) {
                if (!elemento) {
                    return;
                }

                const visor =
                    elemento.querySelector('.alma-sync-text');

                if (!visor) {
                    return;
                }

                const textoCompleto =
                    obtenerTextoOriginalBot(elemento);

                if (textoCompleto) {
                    visor.textContent = textoCompleto;
                }

                visor.classList.remove('escribiendo');
            }

            function restaurarTextoOriginal(elemento) {
                if (!elemento) {
                    return;
                }

                const fuente =
                    obtenerFuenteVisualBot(elemento);

                if (fuente) {
                    fuente.classList.remove('alma-sync-source');
                }

                const visor =
                    elemento.querySelector('.alma-sync-text');

                if (visor) {
                    visor.remove();
                }

                if (elementoTextoSincronizado === elemento) {
                    elementoTextoSincronizado = null;
                    fuenteTextoSincronizado = null;
                    visorTextoSincronizado = null;
                }
            }

            function prepararFragmentoSincronizado(
                elemento,
                texto
            ) {
                const visor =
                    prepararTextoSincronizado(elemento);

                const fragmento =
                    limpiarTextoParaAudio(texto);

                if (!visor || !fragmento) {
                    return null;
                }

                const textoBase =
                    visor.textContent.trimEnd();

                const separador =
                    textoBase ? ' ' : '';

                const caracteres =
                    Array.from(fragmento);

                return {
                    visor,
                    textoBase,
                    separador,
                    caracteres,
                    ultimaCantidad: -1
                };
            }

            function actualizarFragmentoConAudio(
                estado,
                audio,
                idAudio
            ) {
                if (
                    !estado ||
                    idAudio !== idConversacionAudio
                ) {
                    return;
                }

                const duracion =
                    Number.isFinite(audio.duration) &&
                    audio.duration > 0
                        ? audio.duration
                        : Math.max(
                            1,
                            estado.caracteres.length / 14
                        );

                const progreso =
                    Math.min(
                        1,
                        Math.max(
                            0,
                            audio.currentTime / duracion
                        )
                    );

                const cantidad =
                    Math.min(
                        estado.caracteres.length,
                        Math.floor(
                            estado.caracteres.length * progreso
                        )
                    );

                if (cantidad === estado.ultimaCantidad) {
                    return;
                }

                estado.ultimaCantidad = cantidad;

                estado.visor.textContent =
                    estado.textoBase +
                    estado.separador +
                    estado.caracteres
                        .slice(0, cantidad)
                        .join('');

                estado.visor.classList.add('escribiendo');

                const contenedorMensajes =
                    chatRoot.querySelector('.chat-messages-list');

                if (contenedorMensajes) {
                    contenedorMensajes.scrollTop =
                        contenedorMensajes.scrollHeight;
                }
            }

            function finalizarFragmentoSincronizado(estado) {
                if (!estado) {
                    return;
                }

                estado.visor.textContent =
                    estado.textoBase +
                    estado.separador +
                    estado.caracteres.join('');
            }

            function escribirFragmentoEstimado(
                elemento,
                texto,
                idAudio
            ) {
                const estado =
                    prepararFragmentoSincronizado(
                        elemento,
                        texto
                    );

                if (!estado) {
                    return Promise.resolve();
                }

                const duracionMs =
                    Math.max(
                        900,
                        estado.caracteres.length * 58
                    );

                const inicio =
                    performance.now();

                return new Promise((resolve) => {
                    function paso(ahora) {
                        if (
                            idAudio !== idConversacionAudio
                        ) {
                            resolve();
                            return;
                        }

                        const progreso =
                            Math.min(
                                1,
                                (ahora - inicio) / duracionMs
                            );

                        const cantidad =
                            Math.floor(
                                estado.caracteres.length *
                                progreso
                            );

                        estado.visor.textContent =
                            estado.textoBase +
                            estado.separador +
                            estado.caracteres
                                .slice(0, cantidad)
                                .join('');

                        estado.visor.classList.add('escribiendo');

                        if (progreso < 1) {
                            requestAnimationFrame(paso);
                        } else {
                            finalizarFragmentoSincronizado(
                                estado
                            );
                            resolve();
                        }
                    }

                    requestAnimationFrame(paso);
                });
            }

            function extraerFragmentoHablable(forzar = false) {
                bufferTextoAudio =
                    bufferTextoAudio.replace(/^\s+/, '');

                if (!bufferTextoAudio) {
                    return '';
                }

                /*
                 * Hablar por frases completas.
                 */
                const coincidencia =
                    bufferTextoAudio.match(
                        /^([\s\S]*?[.!?;](?=\s|$))/
                    );

                if (
                    coincidencia &&
                    coincidencia[1].trim().length >= 18
                ) {
                    const fragmento =
                        coincidencia[1].trim();

                    bufferTextoAudio =
                        bufferTextoAudio.slice(
                            coincidencia[0].length
                        );

                    return fragmento;
                }

                /*
                 * Evita esperar demasiado si el agente genera
                 * una oración muy larga.
                 */
                if (bufferTextoAudio.length >= 180) {
                    const zona =
                        bufferTextoAudio.slice(0, 180);

                    let puntoCorte =
                        Math.max(
                            zona.lastIndexOf(','),
                            zona.lastIndexOf(':'),
                            zona.lastIndexOf(' ')
                        );

                    if (puntoCorte < 80) {
                        puntoCorte = 160;
                    }

                    const fragmento =
                        bufferTextoAudio
                            .slice(0, puntoCorte + 1)
                            .trim();

                    bufferTextoAudio =
                        bufferTextoAudio.slice(
                            puntoCorte + 1
                        );

                    return fragmento;
                }

                if (forzar) {
                    const fragmento =
                        bufferTextoAudio.trim();

                    bufferTextoAudio = '';

                    return fragmento;
                }

                return '';
            }

            function procesarBufferAudio(forzar = false) {
                if (!audioActivo) {
                    if (forzar) {
                        bufferTextoAudio = '';
                    }

                    return;
                }

                let fragmento =
                    extraerFragmentoHablable(forzar);

                while (fragmento) {
                    encolarAudioElevenLabs(fragmento);

                    fragmento =
                        extraerFragmentoHablable(forzar);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PETICIÓN SEGURA AL PUENTE PHP DE ELEVENLABS
            |--------------------------------------------------------------------------
            */
            async function solicitarAudioElevenLabs(texto, idAudio) {
                const respuesta = await fetch(
                    'elevenlabs_tts.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': elevenLabsToken
                        },

                        credentials: 'same-origin',

                        body: JSON.stringify({
                            text: limpiarTextoParaAudio(texto)
                        })
                    }
                );

                if (idAudio !== idConversacionAudio) {
                    throw new Error(
                        'Audio cancelado'
                    );
                }

                if (!respuesta.ok) {
                    let detalle =
                        'No fue posible generar el audio.';

                    try {
                        const errorJson =
                            await respuesta.json();

                        detalle =
                            errorJson.error || detalle;
                    } catch (error) {
                        // La respuesta no era JSON.
                    }

                    throw new Error(detalle);
                }

                return respuesta.blob();
            }

            function encolarAudioElevenLabs(texto) {
                const textoLimpio =
                    limpiarTextoParaAudio(texto);

                if (
                    !audioActivo ||
                    !textoLimpio
                ) {
                    return;
                }

                const idAudio = idConversacionAudio;

                /*
                 * La petición comienza inmediatamente.
                 * Así el siguiente fragmento puede cargarse
                 * mientras se reproduce el anterior.
                 */
                const promesaAudio =
                    solicitarAudioElevenLabs(
                        textoLimpio,
                        idAudio
                    );

                colaAudio.push({
                    idAudio,
                    texto: textoLimpio,
                    promesaAudio,
                    elementoRespuesta: elementoRespuestaActual
                });

                reproducirSiguienteAudio();
            }

            function hablarConVozNavegador(
                texto,
                elementoRespuesta,
                idAudio
            ) {
                const promesaEscritura =
                    escribirFragmentoEstimado(
                        elementoRespuesta,
                        texto,
                        idAudio
                    );

                const promesaVoz =
                    new Promise((resolve) => {
                        if (!sintetizadorNavegador) {
                            resolve();
                            return;
                        }

                        const mensaje =
                            new SpeechSynthesisUtterance(texto);

                        mensaje.lang = 'es-CO';
                        mensaje.rate = 0.96;
                        mensaje.pitch = 1.04;
                        mensaje.volume = 1;

                        mensaje.onend = resolve;
                        mensaje.onerror = resolve;

                        sintetizadorNavegador.speak(mensaje);
                    });

                return Promise.all([
                    promesaVoz,
                    promesaEscritura
                ]);
            }

            async function reproducirSiguienteAudio() {
                if (
                    reproduciendoAudio ||
                    !audioActivo
                ) {
                    comprobarFinRespuesta();
                    return;
                }

                const elemento =
                    colaAudio.shift();

                if (!elemento) {
                    comprobarFinRespuesta();
                    return;
                }

                if (
                    elemento.idAudio !== idConversacionAudio
                ) {
                    reproducirSiguienteAudio();
                    return;
                }

                reproduciendoAudio = true;
                almaHablando = true;
                actualizarBotonParlante();

                let urlAudio = null;
                let estadoEscritura = null;
                let actualizarEscritura = null;

                try {
                    const blob =
                        await elemento.promesaAudio;

                    if (
                        elemento.idAudio !== idConversacionAudio ||
                        !audioActivo
                    ) {
                        throw new Error(
                            'Audio cancelado'
                        );
                    }

                    urlAudio =
                        URL.createObjectURL(blob);

                    audioActual = new Audio(urlAudio);
                    audioActual.preload = 'auto';

                    /*
                     * La fuente original de n8n queda intacta y oculta.
                     * El visor muestra cada carácter de la frase según
                     * el tiempo real de reproducción de ElevenLabs.
                     */
                    estadoEscritura =
                        prepararFragmentoSincronizado(
                            elemento.elementoRespuesta,
                            elemento.texto
                        );

                    actualizarEscritura = () => {
                        actualizarFragmentoConAudio(
                            estadoEscritura,
                            audioActual,
                            elemento.idAudio
                        );
                    };

                    if (estadoEscritura) {
                        audioActual.addEventListener(
                            'timeupdate',
                            actualizarEscritura
                        );

                        audioActual.addEventListener(
                            'loadedmetadata',
                            actualizarEscritura
                        );
                    }

                    await new Promise(
                        (resolve, reject) => {
                            audioActual.onended = () => {
                                finalizarFragmentoSincronizado(
                                    estadoEscritura
                                );

                                resolve();
                            };

                            audioActual.onerror = reject;

                            audioActual.play().catch(reject);
                        }
                    );

                } catch (error) {
                    console.error(
                        'Error de ElevenLabs:',
                        error
                    );

                    /*
                     * Si ElevenLabs falla, se mantiene el respaldo existente
                     * y también se escribe progresivamente.
                     */
                    if (
                        audioActivo &&
                        elemento.idAudio === idConversacionAudio &&
                        error.message !== 'Audio cancelado'
                    ) {
                        mostrarEstado(
                            'ElevenLabs no respondió. Usando la voz del dispositivo.',
                            true,
                            3500
                        );

                        await hablarConVozNavegador(
                            elemento.texto,
                            elemento.elementoRespuesta,
                            elemento.idAudio
                        );
                    }
                } finally {
                    if (
                        audioActual &&
                        actualizarEscritura
                    ) {
                        audioActual.removeEventListener(
                            'timeupdate',
                            actualizarEscritura
                        );

                        audioActual.removeEventListener(
                            'loadedmetadata',
                            actualizarEscritura
                        );
                    }

                    if (urlAudio) {
                        URL.revokeObjectURL(urlAudio);
                    }

                    audioActual = null;
                    reproduciendoAudio = false;

                    if (
                        audioActivo &&
                        elemento.idAudio === idConversacionAudio
                    ) {
                        reproducirSiguienteAudio();
                    } else {
                        comprobarFinRespuesta();
                    }
                }
            }

            function cancelarAudioActual(
                reiniciarMicrofono = true
            ) {
                idConversacionAudio++;

                colaAudio = [];
                bufferTextoAudio = '';

                if (controladorPeticionAudio) {
                    controladorPeticionAudio.abort();
                    controladorPeticionAudio = null;
                }

                if (audioActual) {
                    audioActual.pause();
                    audioActual.currentTime = 0;
                    audioActual = null;
                }

                if (sintetizadorNavegador) {
                    sintetizadorNavegador.cancel();
                }

                reproduciendoAudio = false;
                almaHablando = false;
                actualizarBotonParlante();

                if (
                    reiniciarMicrofono &&
                    modoConversacion &&
                    !esperandoRespuesta &&
                    !respuestaEnProceso
                ) {
                    iniciarMicrofono(500);
                }
            }

            function comprobarFinRespuesta() {
                if (
                    !respuestaTerminada ||
                    reproduciendoAudio ||
                    colaAudio.length > 0
                ) {
                    return;
                }

                almaHablando = false;
                respuestaEnProceso = false;

                completarTextoSincronizado(
                    elementoRespuestaActual
                );

                actualizarBotonParlante();

                if (modoConversacion) {
                    mostrarEstado(
                        'Puedes responder. Violeta te está escuchando.'
                    );

                    iniciarMicrofono(650);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | OBSERVAR RESPUESTA ESCRITA DE N8N EN TIEMPO REAL
            |--------------------------------------------------------------------------
            */
            function obtenerUltimoMensajeBot() {
                const mensajes =
                    chatRoot.querySelectorAll(
                        '.chat-message-from-bot'
                    );

                return mensajes.length
                    ? mensajes[mensajes.length - 1]
                    : null;
            }

            function revisarStreamingN8n() {
                if (
                    !esperandoRespuesta &&
                    !respuestaEnProceso
                ) {
                    return;
                }

                const elemento =
                    obtenerUltimoMensajeBot();

                if (!elemento) {
                    return;
                }

                const textoActual =
                    obtenerTextoOriginalBot(
                        elemento
                    );

                if (!textoActual) {
                    return;
                }

                /*
                 * Mientras n8n todavía no haya creado o modificado
                 * el mensaje nuevo, ignorar la respuesta anterior.
                 */
                if (
                    esperandoRespuesta &&
                    !respuestaEnProceso &&
                    elemento === elementoBotAntesDeEnviar &&
                    textoActual === textoBotAntesDeEnviar
                ) {
                    return;
                }

                /*
                 * Primera parte de una respuesta nueva.
                 */
                if (
                    elemento !== elementoRespuestaActual
                ) {
                    elementoRespuestaActual = elemento;
                    textoRespuestaAnterior = '';
                    bufferTextoAudio = '';

                    esperandoRespuesta = false;
                    respuestaEnProceso = true;
                    respuestaTerminada = false;

                    detenerMicrofono('parlante');

                    /*
                     * Cada respuesta nueva tiene su propia cola.
                     */
                    idConversacionAudio++;
                    colaAudio = [];

                    if (audioActivo) {
                        prepararTextoSincronizado(
                            elemento
                        );
                    }
                }

                let textoNuevo = '';

                if (
                    textoActual.startsWith(
                        textoRespuestaAnterior
                    )
                ) {
                    textoNuevo =
                        textoActual.slice(
                            textoRespuestaAnterior.length
                        );
                } else if (
                    textoActual.length >
                    textoRespuestaAnterior.length
                ) {
                    /*
                     * Respaldo para pequeños cambios de Markdown
                     * durante el streaming.
                     */
                    textoNuevo =
                        textoActual.slice(
                            textoRespuestaAnterior.length
                        );
                }

                textoRespuestaAnterior =
                    textoActual;

                if (textoNuevo) {
                    bufferTextoAudio +=
                        (bufferTextoAudio ? ' ' : '') +
                        textoNuevo;

                    procesarBufferAudio(false);

                    /*
                     * El temporizador solo se reinicia cuando n8n agrega
                     * texto real. La escritura sincronizada no lo altera.
                     */
                    clearTimeout(temporizadorFinRespuesta);

                    temporizadorFinRespuesta =
                        setTimeout(() => {
                            procesarBufferAudio(true);

                            respuestaTerminada = true;

                            if (!audioActivo) {
                                respuestaEnProceso = false;

                                if (modoConversacion) {
                                    iniciarMicrofono(650);
                                }

                                return;
                            }

                            comprobarFinRespuesta();
                        }, 1600);
                }
            }

            function observarRespuestasN8n() {
                const observador =
                    new MutationObserver((mutaciones) => {
                        const hayCambioReal =
                            mutaciones.some((mutacion) => {
                                const objetivo =
                                    mutacion.target.nodeType === Node.TEXT_NODE
                                        ? mutacion.target.parentElement
                                        : mutacion.target;

                                return !objetivo?.closest?.(
                                    '.alma-sync-text'
                                );
                            });

                        if (hayCambioReal) {
                            revisarStreamingN8n();
                        }
                    });

                observador.observe(chatRoot, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }

            /*
            |--------------------------------------------------------------------------
            | BOTÓN DEL MICRÓFONO
            |--------------------------------------------------------------------------
            */
            btnMicrofono.addEventListener(
                'click',
                async () => {
                    if (!SpeechRecognitionClass) {
                        mostrarEstado(
                            'Utiliza Google Chrome o Microsoft Edge para hablar.',
                            true,
                            5000
                        );
                        return;
                    }

                    if (modoConversacion) {
                        modoConversacion = false;

                        detenerMicrofono('manual');
                        actualizarBotonMicrofono();

                        mostrarEstado(
                            'Conversación por voz desactivada.'
                        );

                        return;
                    }

                    const permisoConcedido =
                        await solicitarPermisoMicrofono();

                    if (!permisoConcedido) {
                        return;
                    }

                    cancelarAudioActual(false);

                    modoConversacion = true;
                    esperandoRespuesta = false;
                    respuestaEnProceso = false;
                    respuestaTerminada = false;

                    actualizarBotonMicrofono();

                    mostrarEstado(
                        'Conversación por voz activada.'
                    );

                    iniciarMicrofono(200);
                }
            );

            /*
            |--------------------------------------------------------------------------
            | BOTÓN DEL PARLANTE
            |--------------------------------------------------------------------------
            */
            btnParlante.addEventListener(
                'click',
                () => {
                    audioActivo = !audioActivo;

                    localStorage.setItem(
                        AUDIO_STORAGE_KEY,
                        audioActivo ? '1' : '0'
                    );

                    if (!audioActivo) {
                        cancelarAudioActual(false);

                        restaurarTextoOriginal(
                            elementoRespuestaActual
                        );

                        actualizarBotonParlante();

                        mostrarEstado(
                            'Respuestas por audio desactivadas.'
                        );

                        if (
                            modoConversacion &&
                            !esperandoRespuesta &&
                            !respuestaEnProceso
                        ) {
                            iniciarMicrofono(500);
                        }

                        return;
                    }

                    actualizarBotonParlante();

                    mostrarEstado(
                        'Respuestas por audio activadas.'
                    );
                }
            );

            /*
            |--------------------------------------------------------------------------
            | MENSAJES ESCRITOS MANUALMENTE
            |--------------------------------------------------------------------------
            */
            chatRoot.addEventListener(
                'click',
                (event) => {
                    const boton =
                        event.target.closest(
                            '.chat-input-send-button'
                        ) ||
                        event.target.closest(
                            'button[type="submit"]'
                        );

                    if (!boton) {
                        return;
                    }

                    buscarControlesChat();

                    if (campoChat?.value?.trim()) {
                        esperandoRespuesta = true;
                        respuestaEnProceso = false;
                        respuestaTerminada = false;

                        elementoBotAntesDeEnviar =
                            obtenerUltimoMensajeBot();

                        textoBotAntesDeEnviar =
                            elementoBotAntesDeEnviar
                                ? normalizarTextoDom(
                                    elementoBotAntesDeEnviar.innerText ||
                                    elementoBotAntesDeEnviar.textContent ||
                                    ''
                                )
                                : '';

                        elementoRespuestaActual = null;
                        textoRespuestaAnterior = '';
                        bufferTextoAudio = '';

                        detenerMicrofono('parlante');
                        cancelarAudioActual(false);
                    }
                },
                true
            );

            chatRoot.addEventListener(
                'keydown',
                (event) => {
                    if (
                        event.key !== 'Enter' ||
                        event.shiftKey
                    ) {
                        return;
                    }

                    buscarControlesChat();

                    if (
                        event.target === campoChat &&
                        campoChat.value.trim()
                    ) {
                        esperandoRespuesta = true;
                        respuestaEnProceso = false;
                        respuestaTerminada = false;

                        elementoBotAntesDeEnviar =
                            obtenerUltimoMensajeBot();

                        textoBotAntesDeEnviar =
                            elementoBotAntesDeEnviar
                                ? normalizarTextoDom(
                                    elementoBotAntesDeEnviar.innerText ||
                                    elementoBotAntesDeEnviar.textContent ||
                                    ''
                                )
                                : '';

                        elementoRespuestaActual = null;
                        textoRespuestaAnterior = '';
                        bufferTextoAudio = '';

                        detenerMicrofono('parlante');
                        cancelarAudioActual(false);
                    }
                },
                true
            );

            /*
            |--------------------------------------------------------------------------
            | INICIALIZACIÓN
            |--------------------------------------------------------------------------
            */
            configurarReconocimiento();
            actualizarBotonMicrofono();
            actualizarBotonParlante();

            let intentos = 0;

            const intervaloInicializacion =
                setInterval(() => {
                    intentos++;

                    if (
                        buscarControlesChat() ||
                        intentos >= 60
                    ) {
                        clearInterval(
                            intervaloInicializacion
                        );

                        setTimeout(() => {
                            observarRespuestasN8n();
                        }, 1200);
                    }
                }, 250);

            window.addEventListener(
                'beforeunload',
                () => {
                    modoConversacion = false;

                    clearTimeout(temporizadorSilencio);
                    clearTimeout(temporizadorReinicio);
                    clearTimeout(temporizadorFinRespuesta);

                    if (
                        reconocimiento &&
                        microfonoEscuchando
                    ) {
                        try {
                            reconocimiento.abort();
                        } catch (error) {
                            console.warn(error);
                        }
                    }

                    cancelarAudioActual(false);
                }
            );
        });
    </script>
</body>
</html>
