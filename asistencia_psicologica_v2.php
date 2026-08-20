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
    <!-- Vista autocontenida: el layout responsive se controla aquí para iPad/tablet/iPhone. -->

    <style>
        :root {
            --aliada-pink: #E51C9B;
            --aliada-pink-dark: #B41483;
            --aliada-purple: #8E1AA7;
            --aliada-purple-dark: #4A0D60;
            --aliada-text: #111111;
            --aliada-muted: #514855;
            --aliada-white: #FFFFFF;
            --aliada-bg: #FBF8FC;
            --aliada-border: rgba(77, 31, 88, .12);
            --aliada-gradient: linear-gradient(135deg, #F64FBA 0%, #E51C9B 46%, #8E1AA7 100%);
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
        }

        * {
            box-sizing: border-box;
        }

        html {
            width: 100%;
            min-height: 100%;
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            width: 100%;
            min-width: 320px;
            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;
            margin: 0;
            overflow-x: hidden;
            color: var(--aliada-text);
            background:
                radial-gradient(circle at 10% -5%, rgba(229, 28, 155, .10), transparent 31rem),
                radial-gradient(circle at 100% 20%, rgba(142, 26, 167, .08), transparent 31rem),
                linear-gradient(180deg, #FBF7FC 0%, #FFFFFF 58%, #FAF7FB 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        button {
            font: inherit;
        }

        /* =========================================================
           ESTRUCTURA GENERAL - CHAT FIRST
        ========================================================= */
        .app-chat-mobile {
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;
            padding:
                calc(14px + var(--safe-top))
                calc(16px + var(--safe-right))
                calc(18px + var(--safe-bottom))
                calc(16px + var(--safe-left));
        }

        .mobile-shell {
            width: min(100%, 1040px);
            min-height: calc(100dvh - 32px - var(--safe-top) - var(--safe-bottom));
            margin-inline: auto;
            display: grid;
            grid-template-rows: auto auto minmax(640px, 1fr) auto;
            gap: 12px;
        }

        /* =========================================================
           CABECERA COMPACTA
        ========================================================= */
        .topbar-mobile {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            background:
                radial-gradient(circle at 98% 0%, rgba(255,255,255,.16), transparent 12rem),
                var(--aliada-gradient);
            box-shadow: 0 18px 42px rgba(116, 21, 132, .18);
        }

        .topbar-mobile::after {
            content: "";
            position: absolute;
            width: 190px;
            height: 190px;
            right: -78px;
            top: -102px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            pointer-events: none;
        }

        .topbar-mobile-inner {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 250px;
            align-items: center;
            gap: 18px;
            padding: 17px 19px;
        }

        .brand-mobile {
            min-width: 0;
            display: grid;
            grid-template-columns: 100px minmax(0, 1fr);
            align-items: center;
            gap: 14px;
        }

        .brand-mobile-logo {
            width: 100px;
            height: 54px;
            padding: 7px 10px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            background: #FFFFFF;
            box-shadow: 0 9px 20px rgba(67, 12, 76, .14);
        }

        .brand-mobile-logo img {
            display: block;
            max-width: 100%;
            max-height: 34px;
            object-fit: contain;
        }

        .brand-mobile-copy {
            min-width: 0;
        }

        .brand-mobile-eyebrow {
            width: fit-content;
            max-width: 100%;
            display: block;
            margin: 0 0 4px;
            padding: 5px 9px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,.30);
            border-radius: 999px;
            background: rgba(255,255,255,.15);
            color: #FFFFFF;
            font-size: 8.6px;
            line-height: 1.15;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .brand-mobile-title {
            margin: 7px 0 0;
            color: #FFFFFF;
            font-size: clamp(24px, 3.4vw, 33px);
            line-height: 1;
            letter-spacing: -1px;
            font-weight: 950;
        }

        .brand-mobile-title span {
            color: #FFFFFF;
        }

        .brand-mobile-subtitle {
            max-width: 590px;
            margin: 7px 0 0;
            color: rgba(255,255,255,.93);
            font-size: 11.8px;
            line-height: 1.38;
            font-weight: 650;
        }

        .topbar-mobile-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .btn-mobile-top {
            width: 100%;
            min-height: 48px;
            padding: 10px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid rgba(255,255,255,.32);
            border-radius: 14px;
            color: #FFFFFF;
            background: rgba(255,255,255,.14);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(63,12,72,.09);
        }

        .btn-mobile-top.white {
            color: #7C145D;
            background: #FFFFFF;
            border-color: #FFFFFF;
        }

        .btn-mobile-top i {
            font-size: 17px;
        }

        /* =========================================================
           INFO + CONTROLES DE VOZ
        ========================================================= */
        .info-strip {
            min-width: 0;
            padding: 12px 14px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--aliada-border);
            border-radius: 19px;
            background: rgba(255,255,255,.97);
            box-shadow: 0 10px 28px rgba(57,20,65,.06);
        }

        .info-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: var(--aliada-pink);
            background: linear-gradient(135deg, #FDE9F7, #F3EAFB);
        }

        .info-icon i {
            font-size: 20px;
        }

        .info-copy {
            min-width: 0;
        }

        .info-copy strong {
            display: block;
            color: #111111;
            font-size: 13.5px;
            line-height: 1.2;
            font-weight: 950;
        }

        .info-copy span {
            display: block;
            margin-top: 4px;
            color: #3D3540;
            font-size: 11.2px;
            line-height: 1.35;
        }

        .alma-audio-controls {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
        }

        .alma-control-button {
            position: relative;
            isolation: isolate;
            min-width: 46px;
            height: 44px;
            padding: 0 12px;
            border: 1px solid rgba(216, 27, 156, .22);
            border-radius: 999px;
            background: #FFFFFF;
            color: #C21888;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-family: inherit;
            font-size: 11.5px;
            font-weight: 800;
            cursor: pointer;
            box-shadow:
                0 7px 18px rgba(132,20,96,.10),
                inset 0 1px 0 rgba(255,255,255,.92);
            transition:
                transform .2s ease,
                color .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }

        .alma-control-button i {
            width: 18px;
            font-size: 17px;
            line-height: 1;
            text-align: center;
        }

        .alma-control-button::after {
            content: '';
            position: absolute;
            inset: -4px;
            z-index: -1;
            border: 2px solid transparent;
            border-radius: inherit;
            opacity: 0;
            transform: scale(.88);
            pointer-events: none;
        }

        .alma-control-button:active {
            transform: scale(.98);
        }

        .alma-control-button.activo {
            color: #FFFFFF;
            border-color: transparent;
            background: linear-gradient(135deg, #E321A4 0%, #B71382 52%, #8F0F67 100%);
            box-shadow:
                0 9px 22px rgba(183,19,130,.26),
                inset 0 1px 0 rgba(255,255,255,.22);
        }

        .alma-control-button.escuchando {
            color: #FFFFFF;
            border-color: transparent;
            background: linear-gradient(135deg, #FF4D67 0%, #E52343 55%, #C81432 100%);
            animation: pulsoMicrofono 1.1s infinite;
        }

        .alma-control-button.escuchando::after {
            border-color: rgba(229,35,67,.38);
            animation: anilloAudio 1.15s infinite ease-out;
        }

        .alma-control-button.hablando::after {
            border-color: rgba(216,27,156,.35);
            animation: anilloAudio 1.05s infinite ease-out;
        }

        .alma-control-button.hablando i {
            animation: parlanteActivo .62s infinite alternate ease-in-out;
        }

        .alma-control-button:disabled {
            opacity: .5;
            cursor: not-allowed;
            animation: none;
        }

        @keyframes pulsoMicrofono {
            0% {
                box-shadow:
                    0 0 0 0 rgba(229,35,67,.40),
                    0 8px 22px rgba(200,20,50,.24);
            }
            70% {
                box-shadow:
                    0 0 0 12px rgba(229,35,67,0),
                    0 8px 22px rgba(200,20,50,.24);
            }
            100% {
                box-shadow:
                    0 0 0 0 rgba(229,35,67,0),
                    0 8px 22px rgba(200,20,50,.24);
            }
        }

        @keyframes anilloAudio {
            0% { opacity: .75; transform: scale(.88); }
            100% { opacity: 0; transform: scale(1.20); }
        }

        @keyframes parlanteActivo {
            from { transform: scale(.92) translateX(-1px); }
            to { transform: scale(1.16) translateX(1px); }
        }

        /* =========================================================
           CHAT PROPIO - GRANDE Y CON ALTO CONTRASTE
        ========================================================= */
        .chat-panel {
            min-width: 0;
            min-height: 640px;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--aliada-border);
            border-radius: 24px;
            background: #FFFFFF;
            box-shadow: 0 18px 42px rgba(55,18,68,.10);
        }

        .chat-frame {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: #FBF9FC;
        }

        #chat-mensajes {
            flex: 1 1 auto;
            min-height: 0;
            margin: 0;
            padding: 20px;
            list-style: none;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 13px;
            background:
                radial-gradient(circle at 100% 0%, rgba(229,28,155,.025), transparent 12rem),
                #FBF9FC;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        .chat-mensaje {
            max-width: 78%;
            padding: 14px 16px;
            border-radius: 18px;
            font-size: 16px;
            line-height: 1.58;
            font-weight: 450;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            box-shadow: 0 7px 18px rgba(55,18,68,.045);
        }

        .chat-mensaje p {
            margin: 0;
            color: inherit;
        }

        .chat-mensaje.asistente {
            align-self: flex-start;
            color: #111111;
            background: #FFFFFF;
            border: 1px solid rgba(65,31,72,.08);
            border-bottom-left-radius: 5px;
        }

        .chat-mensaje.usuaria {
            align-self: flex-end;
            color: #FFFFFF;
            background: linear-gradient(135deg, #D81B9C, #7B1FA2);
            border-bottom-right-radius: 5px;
        }

        .chat-mensaje.sistema {
            align-self: center;
            max-width: 92%;
            color: #2D2730;
            background: rgba(123,31,162,.08);
            border: 1px solid rgba(123,31,162,.08);
            font-size: 12.5px;
            text-align: center;
            box-shadow: none;
        }

        .chat-escribiendo {
            align-self: flex-start;
            display: inline-flex;
            gap: 5px;
            padding: .75rem .95rem;
            border: 1px solid rgba(65,31,72,.08);
            border-radius: 17px;
            background: #FFFFFF;
            box-shadow: 0 7px 18px rgba(55,18,68,.045);
        }

        .chat-escribiendo span {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #B41682;
            opacity: .4;
            animation: parpadeoPunto 1s infinite;
        }

        .chat-escribiendo span:nth-child(2) { animation-delay: .15s; }
        .chat-escribiendo span:nth-child(3) { animation-delay: .3s; }

        @keyframes parpadeoPunto {
            0%, 100% { opacity: .25; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-2px); }
        }

        /* Acciones de emergencia conservadas y mejoradas visualmente */
        .chat-emergencia {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 13px;
            border: 1px solid rgba(220,38,38,.22);
            border-radius: 17px;
            background: rgba(220,38,38,.055);
        }

        .chat-emergencia-titulo {
            color: #9F1717;
            font-size: 12.5px;
            font-weight: 950;
        }

        .chat-emergencia-boton {
            min-height: 44px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 12px;
            border: none;
            font-size: 13px;
            font-weight: 850;
            cursor: pointer;
            text-decoration: none;
        }

        .chat-emergencia-boton.policia {
            background: #C91E2E;
            color: #FFFFFF;
        }

        .chat-emergencia-boton.contacto {
            background: #FFFFFF;
            color: #A91827;
            border: 1px solid rgba(220,38,38,.30);
        }

        .chat-emergencia-boton.evidencias {
            background: #FFFFFF;
            color: var(--aliada-pink-dark);
            border: 1px solid var(--aliada-border);
        }

        /* Input grande, fijo visualmente al pie del chat */
        .chat-input-bar {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 52px;
            align-items: end;
            gap: 9px;
            padding: 12px 14px;
            border-top: 1px solid rgba(216,27,156,.12);
            background: #FFFFFF;
        }

        .chat-input-bar textarea {
            width: 100%;
            min-height: 54px;
            max-height: 132px;
            padding: 14px 16px;
            resize: none;
            border: 1.5px solid rgba(216,27,156,.16);
            border-radius: 17px;
            outline: none;
            color: #111111;
            background: #FFFFFF;
            font-family: inherit;
            font-size: 16px;
            line-height: 1.45;
            font-weight: 500;
            caret-color: var(--aliada-pink);
            box-shadow: inset 0 1px 2px rgba(49,14,57,.025);
        }

        .chat-input-bar textarea::placeholder {
            color: #706873;
            opacity: 1;
        }

        .chat-input-bar textarea:focus {
            border-color: rgba(216,27,156,.48);
            box-shadow: 0 0 0 4px rgba(216,27,156,.09);
        }

        .btn-enviar-chat {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border: 0;
            border-radius: 16px;
            color: #FFFFFF;
            background: var(--aliada-gradient);
            font-size: 17px;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(216,27,156,.22);
        }

        .btn-enviar-chat:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        /* =========================================================
           FOOTER
        ========================================================= */
        .footer-mobile {
            padding: 2px 10px 0;
            color: #403842;
            text-align: center;
            font-size: 10.5px;
            line-height: 1.5;
        }

        .footer-mobile p {
            margin: 0;
        }

        /* =========================================================
           ESTADO FLOTANTE DE VOZ
        ========================================================= */
        .alma-voice-status {
            position: fixed;
            left: 50%;
            bottom: calc(18px + var(--safe-bottom));
            z-index: 99999;
            max-width: calc(100vw - 30px);
            padding: 11px 17px;
            border-radius: 24px;
            background: rgba(28,28,33,.95);
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
                opacity .25s ease,
                visibility .25s ease,
                transform .25s ease;
            box-shadow: 0 9px 28px rgba(0,0,0,.22);
        }

        .alma-voice-status.visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, 0);
        }

        .alma-voice-status.error {
            background: rgba(183,28,28,.97);
        }

        /* =========================================================
           iPAD / TABLET VERTICAL
        ========================================================= */
        @media (min-width: 700px) and (orientation: portrait) {
            .app-chat-mobile {
                padding:
                    calc(18px + var(--safe-top))
                    calc(20px + var(--safe-right))
                    calc(18px + var(--safe-bottom))
                    calc(20px + var(--safe-left));
            }

            .mobile-shell {
                width: min(100%, 900px);
                min-height: calc(100dvh - 36px - var(--safe-top) - var(--safe-bottom));
                grid-template-rows: auto auto minmax(720px, 1fr) auto;
                gap: 12px;
            }

            .topbar-mobile-inner {
                grid-template-columns: minmax(0, 1fr) 220px;
                gap: 16px;
                padding: 15px 17px;
            }

            .brand-mobile {
                grid-template-columns: 94px minmax(0,1fr);
                gap: 13px;
            }

            .brand-mobile-logo {
                width: 94px;
                height: 51px;
            }

            .brand-mobile-title {
                font-size: clamp(25px, 3.4vw, 31px);
            }

            .brand-mobile-subtitle {
                font-size: 11.5px;
            }

            .btn-mobile-top {
                min-height: 46px;
                font-size: 12px;
            }

            .chat-panel {
                min-height: 720px;
            }

            #chat-mensajes {
                padding: 22px;
            }

            .chat-mensaje {
                max-width: 76%;
                font-size: 16.5px;
                line-height: 1.6;
            }
        }

        /* =========================================================
           iPAD / TABLET HORIZONTAL
        ========================================================= */
        @media (min-width: 900px) and (orientation: landscape) {
            .mobile-shell {
                width: min(100%, 1120px);
                grid-template-rows: auto auto minmax(580px, 1fr) auto;
            }

            .topbar-mobile-inner {
                grid-template-columns: minmax(0, 1fr) 260px;
            }

            .chat-panel {
                min-height: 580px;
            }

            .chat-mensaje {
                max-width: 72%;
            }
        }

        /* =========================================================
           CELULAR / iPHONE
        ========================================================= */
        @media (max-width: 699px) {
            .app-chat-mobile {
                padding:
                    calc(8px + var(--safe-top))
                    calc(8px + var(--safe-right))
                    calc(14px + var(--safe-bottom))
                    calc(8px + var(--safe-left));
            }

            .mobile-shell {
                width: 100%;
                min-height: calc(100dvh - 22px - var(--safe-top) - var(--safe-bottom));
                grid-template-rows: auto auto minmax(560px, 1fr) auto;
                gap: 9px;
            }

            .topbar-mobile {
                border-radius: 20px;
            }

            .topbar-mobile-inner {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 11px 12px;
            }

            .brand-mobile {
                grid-template-columns: 72px minmax(0,1fr);
                gap: 9px;
            }

            .brand-mobile-logo {
                width: 72px;
                height: 39px;
                padding: 5px 7px;
                border-radius: 12px;
            }

            .brand-mobile-logo img {
                max-height: 23px;
            }

            .brand-mobile-eyebrow {
                padding: 4px 7px;
                margin-bottom: 3px;
                font-size: 7px;
                white-space: normal;
            }

            .brand-mobile-title {
                margin-top: 5px;
                font-size: clamp(18px, 5.8vw, 23px);
            }

            .brand-mobile-subtitle {
                margin-top: 5px;
                font-size: 10px;
                line-height: 1.3;
            }

            .topbar-mobile-actions {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .btn-mobile-top {
                min-height: 42px;
                padding: 8px 9px;
                border-radius: 12px;
                font-size: 10.8px;
            }

            .info-strip {
                padding: 10px;
                grid-template-columns: 39px minmax(0,1fr) auto;
                gap: 9px;
                border-radius: 17px;
            }

            .info-icon {
                width: 39px;
                height: 39px;
                border-radius: 12px;
            }

            .info-copy strong {
                font-size: 11.8px;
            }

            .info-copy span {
                font-size: 9.8px;
            }

            .alma-audio-controls {
                gap: 5px;
            }

            .alma-control-button {
                width: 40px;
                min-width: 40px;
                height: 40px;
                padding: 0;
            }

            .alma-control-button span {
                display: none;
            }

            .chat-panel {
                min-height: 560px;
                border-radius: 19px;
            }

            #chat-mensajes {
                padding: 14px;
                gap: 10px;
            }

            .chat-mensaje {
                max-width: 86%;
                padding: 12px 13px;
                border-radius: 16px;
                font-size: 14.5px;
                line-height: 1.52;
            }

            .chat-input-bar {
                grid-template-columns: minmax(0,1fr) 48px;
                gap: 8px;
                padding: 10px;
            }

            .chat-input-bar textarea {
                min-height: 50px;
                max-height: 116px;
                padding: 12px 14px;
                border-radius: 15px;
                font-size: 16px; /* evita zoom automático en iOS */
            }

            .btn-enviar-chat {
                width: 48px;
                height: 48px;
                min-width: 48px;
                border-radius: 14px;
            }

            .footer-mobile {
                font-size: 9.8px;
            }
        }

        /* iPhone SE / pantallas estrechas */
        @media (max-width: 390px) {
            .mobile-shell {
                grid-template-rows: auto auto minmax(510px, 1fr) auto;
            }

            .chat-panel {
                min-height: 510px;
            }

            .brand-mobile {
                grid-template-columns: 66px minmax(0,1fr);
            }

            .brand-mobile-logo {
                width: 66px;
                height: 36px;
            }

            .brand-mobile-title {
                font-size: 17.5px;
            }

            .info-copy span {
                display: none;
            }
        }

        /* iPhone horizontal */
        @media (max-height: 520px) and (orientation: landscape) and (max-width: 932px) {
            .mobile-shell {
                min-height: auto;
                grid-template-rows: auto auto minmax(450px, 1fr) auto;
            }

            .topbar-mobile-inner {
                grid-template-columns: minmax(0,1fr) 210px;
                padding: 9px 12px;
            }

            .brand-mobile {
                grid-template-columns: 68px minmax(0,1fr);
            }

            .brand-mobile-logo {
                width: 68px;
                height: 37px;
            }

            .topbar-mobile-actions {
                grid-template-columns: 1fr;
            }

            .btn-mobile-top {
                min-height: 38px;
            }

            .chat-panel {
                min-height: 450px;
            }
        }

        @supports (-webkit-touch-callout: none) {
            body,
            .app-chat-mobile {
                min-height: -webkit-fill-available;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
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
                    Desarrollado por <strong>SpiderSoftware S.A.S.</strong>
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
