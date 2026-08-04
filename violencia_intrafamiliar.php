<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

require_once './admin/classes/DbConection.php';

$pageTitle       = 'Aliada UTS | Protección Universitaria';
$pageDescription = 'Plataforma Aliada UTS para proteger, orientar y acompañar a las mujeres de la comunidad universitaria.';
$currentYear     = date('Y');

$nombreUsuario = '';
$usuarioId = 0;
$contactoEmergencia = '';
$contactoEmergenciaTel = '';
$nombreContacto = '';

if (!empty($_SESSION['nombre_completo'])) {
    $nombreUsuario = trim((string)$_SESSION['nombre_completo']);
} elseif (!empty($_SESSION['nombre'])) {
    $nombreUsuario = trim((string)$_SESSION['nombre']);
} elseif (!empty($_SESSION['session_user']['nombre'])) {
    $nombre = trim((string)($_SESSION['session_user']['nombre'] ?? ''));
    $apellido = trim((string)($_SESSION['session_user']['ape'] ?? ''));
    $nombreUsuario = trim($nombre . ' ' . $apellido);
} elseif (!empty($_SESSION['nickname'])) {
    $nombreUsuario = trim((string)$_SESSION['nickname']);
}

if ($nombreUsuario === '') {
    $nombreUsuario = 'Usuaria';
}

if (!empty($_SESSION['id'])) {
    $usuarioId = (int)$_SESSION['id'];
} elseif (!empty($_SESSION['usuario_id'])) {
    $usuarioId = (int)$_SESSION['usuario_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $usuarioId = (int)$_SESSION['user_id'];
} elseif (!empty($_SESSION['session_user']['id'])) {
    $usuarioId = (int)$_SESSION['session_user']['id'];
}

try {
    if ($usuarioId > 0) {
        $db = new DbConection();
        $pdo = $db->openConect();

        $sql = "SELECT contacto_emergencia, nombre_contacto
                FROM tbl_usuarios
                WHERE id = :id
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $contactoEmergencia = trim((string)($row['contacto_emergencia'] ?? ''));
            $nombreContacto = trim((string)($row['nombre_contacto'] ?? ''));
            $contactoEmergenciaTel = preg_replace('/[^0-9+]/', '', $contactoEmergencia);
        }
    }
} catch (Throwable $e) {
    $contactoEmergencia = '';
    $contactoEmergenciaTel = '';
    $nombreContacto = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="robots" content="index,follow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --aliada-violeta: #7B1FA2;
            --aliada-violeta-dark: #4A0E63;
            --aliada-fucsia: #D81B9C;
            --aliada-fucsia-dark: #A50F78;
            --aliada-rosa: #FF4FB8;
            --aliada-rosa-soft: #FFE4F3;
            --aliada-bg: #FFF5FB;
            --aliada-card: rgba(255, 255, 255, .94);
            --aliada-text: #2B1237;
            --aliada-muted: #80658D;
            --aliada-border: rgba(216, 27, 156, .16);
            --aliada-gradient: linear-gradient(135deg, #FF4FB8 0%, #D81B9C 48%, #7B1FA2 100%);
            --aliada-gradient-dark: linear-gradient(145deg, #4A0E63 0%, #7B1FA2 48%, #D81B9C 100%);
            --aliada-danger: linear-gradient(135deg, #EF4444 0%, #D81B9C 45%, #7B1FA2 100%);
            --aliada-shadow: 0 18px 48px rgba(123, 31, 162, .22);
            --safe-top: env(safe-area-inset-top);
            --safe-bottom: env(safe-area-inset-bottom);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at 15% 0%, rgba(255, 79, 184, .30), transparent 28%),
                radial-gradient(circle at 95% 18%, rgba(123, 31, 162, .22), transparent 32%),
                linear-gradient(180deg, #FFF5FB 0%, #FDF2FF 48%, #FFFFFF 100%);
            color: var(--aliada-text);
            overflow-x: hidden;
        }

        body {
            min-height: 100dvh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font-family: inherit;
        }

        .mobile-app {
            width: 100%;
            min-height: 100dvh;
            padding: calc(10px + var(--safe-top)) 10px calc(18px + var(--safe-bottom));
        }

        .mobile-shell {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
        }

        .top-card {
            position: sticky;
            top: 0;
            z-index: 40;
            padding-bottom: 10px;
            background:
                linear-gradient(180deg, rgba(255, 245, 251, .98), rgba(255, 245, 251, .86) 72%, rgba(255, 245, 251, 0));
            backdrop-filter: blur(12px);
        }

        .top-card-inner {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 14px;
            background:
                radial-gradient(circle at 10% 8%, rgba(255, 255, 255, .28), transparent 30%),
                radial-gradient(circle at 100% 100%, rgba(255, 79, 184, .36), transparent 34%),
                var(--aliada-gradient);
            color: #fff;
            box-shadow: var(--aliada-shadow);
        }

        .top-card-inner::after {
            content: "";
            position: absolute;
            width: 160px;
            height: 160px;
            right: -75px;
            top: -75px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
        }

        .brand-row {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 68px;
            height: 68px;
            min-width: 68px;
            border-radius: 22px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            box-shadow: 0 14px 30px rgba(74, 14, 99, .24);
        }

        .brand-logo img {
            width: 100%;
            max-width: 56px;
            max-height: 56px;
            object-fit: contain;
            display: block;
        }

        .brand-copy {
            min-width: 0;
            flex: 1;
        }

        .brand-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0 0 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .22);
            color: #fff;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .brand-eyebrow::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #FFD6EF;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .12);
        }

        .brand-title {
            margin: 0;
            color: #ffffff;
            font-size: clamp(20px, 6vw, 27px);
            line-height: 1.03;
            font-weight: 950;
            letter-spacing: -1px;
        }

        .brand-title span {
            color: #FFD6EF;
        }

        .brand-subtitle {
            margin: 7px 0 0;
            color: rgba(255, 255, 255, .85);
            font-size: 11.8px;
            line-height: 1.35;
            font-weight: 600;
        }

        .top-actions {
            position: relative;
            z-index: 2;
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 7px;
        }

        .top-btn {
            min-height: 42px;
            border: 0;
            border-radius: 15px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .22);
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }

        .top-btn.white {
            background: #fff;
            color: var(--aliada-fucsia-dark);
        }

        .hero-card {
            margin-top: 10px;
            padding: 14px;
            border-radius: 24px;
            background: var(--aliada-card);
            border: 1px solid var(--aliada-border);
            box-shadow: 0 10px 24px rgba(123, 31, 162, .10);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--aliada-rosa-soft);
            color: var(--aliada-fucsia-dark);
            font-size: 11px;
            font-weight: 950;
            margin-bottom: 10px;
        }

        .hero-title {
            margin: 0;
            color: var(--aliada-text);
            font-size: 18px;
            line-height: 1.18;
            font-weight: 950;
            letter-spacing: -.4px;
        }

        .hero-text {
            margin: 8px 0 0;
            color: var(--aliada-muted);
            font-size: 12.5px;
            line-height: 1.45;
        }

        .permission-status {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
        }

        .permission-pill {
            min-height: 38px;
            border-radius: 14px;
            padding: 7px 6px;
            background: #fff;
            border: 1px solid rgba(216, 27, 156, .14);
            color: var(--aliada-muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            font-size: 9.5px;
            font-weight: 900;
            text-align: center;
        }

        .permission-pill i {
            font-size: 14px;
            color: var(--aliada-fucsia);
        }

        .permission-pill.ok {
            background: linear-gradient(135deg, rgba(34,197,94,.10), rgba(255,255,255,.95));
            border-color: rgba(34,197,94,.22);
            color: #15803D;
        }

        .permission-pill.ok i {
            color: #22C55E;
        }

        .actions-card {
            margin-top: 12px;
            padding: 14px;
            border-radius: 26px;
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--aliada-border);
            box-shadow: 0 14px 34px rgba(123, 31, 162, .12);
        }

        .section-title {
            margin: 0;
            color: var(--aliada-text);
            font-size: 18px;
            line-height: 1.15;
            font-weight: 950;
            letter-spacing: -.5px;
        }

        .section-text {
            margin: 5px 0 0;
            color: var(--aliada-muted);
            font-size: 12.4px;
            line-height: 1.42;
        }

        .action-grid {
            margin-top: 13px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px;
        }

        .action-btn {
            width: 100%;
            min-height: 124px;
            border: 0;
            border-radius: 24px;
            padding: 0;
            background: #fff;
            border: 1px solid rgba(216, 27, 156, .14);
            box-shadow: 0 12px 28px rgba(123,31,162,.10);
            color: var(--aliada-text);
            cursor: pointer;
            overflow: hidden;
            text-align: left;
            position: relative;
        }

        .action-btn:active {
            transform: scale(.975);
        }

        .action-btn-inner {
            width: 100%;
            height: 100%;
            min-height: 124px;
            padding: 13px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .btn-icon {
            width: 48px;
            height: 48px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 79, 184, .16), rgba(123, 31, 162, .12));
            color: var(--aliada-fucsia);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .btn-title {
            margin-top: 10px;
            color: var(--aliada-text);
            font-size: 14.5px;
            line-height: 1.14;
            font-weight: 950;
        }

        .btn-text {
            margin-top: 4px;
            color: var(--aliada-muted);
            font-size: 10.8px;
            line-height: 1.3;
            font-weight: 650;
        }

        .action-btn.microfono .btn-icon {
            color: #9333EA;
        }

        .action-btn.foto .btn-icon {
            color: #D81B9C;
        }

        .action-btn.camara .btn-icon {
            color: #7B1FA2;
        }

        .action-btn-priority {
            grid-column: 1 / -1;
            min-height: 104px;
            color: #fff;
            background: var(--aliada-danger);
            border-color: rgba(255, 255, 255, .24);
            box-shadow: 0 18px 40px rgba(239, 68, 68, .22);
        }

        .action-btn-priority .action-btn-inner {
            min-height: 104px;
            display: grid;
            grid-template-columns: 58px 1fr 34px;
            align-items: center;
            gap: 12px;
        }

        .action-btn-priority .action-btn-inner::after {
            content: "\f054";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #fff;
            color: #C02676;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .action-btn-priority .btn-icon {
            width: 58px;
            height: 58px;
            min-width: 58px;
            margin: 0;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .20);
            color: #fff;
            font-size: 24px;
        }

        .action-btn-priority .btn-title {
            color: #fff;
            font-size: 17px;
            margin: 0 0 5px;
        }

        .action-btn-priority .btn-text {
            color: rgba(255,255,255,.84);
            font-size: 12px;
            margin: 0;
        }

        .recording {
            animation: recordingPulse 1.1s infinite;
            background: linear-gradient(135deg, #EF4444, #D81B9C) !important;
            color: #fff;
        }

        .recording .btn-icon {
            background: rgba(255,255,255,.18);
            color: #fff !important;
        }

        .recording .btn-title,
        .recording .btn-text {
            color: #fff;
        }

        @keyframes recordingPulse {
            0%, 100% {
                box-shadow: 0 12px 28px rgba(239,68,68,.22);
            }

            50% {
                box-shadow: 0 18px 44px rgba(239,68,68,.42);
            }
        }

        .status-msg {
            display: none;
            margin-top: 12px;
            padding: 11px 13px;
            border-radius: 17px;
            font-size: 12.4px;
            line-height: 1.4;
            font-weight: 800;
        }

        .status-msg.info,
        .status-msg.success,
        .status-msg.error,
        .status-msg.warning {
            display: block;
        }

        .status-msg.info {
            background: rgba(216,27,156,.10);
            color: var(--aliada-fucsia-dark);
            border: 1px solid rgba(216,27,156,.14);
        }

        .status-msg.success {
            background: rgba(34,197,94,.10);
            color: #15803D;
            border: 1px solid rgba(34,197,94,.18);
        }

        .status-msg.error {
            background: rgba(239,68,68,.10);
            color: #B42318;
            border: 1px solid rgba(239,68,68,.18);
        }

        .status-msg.warning {
            background: rgba(245,158,11,.12);
            color: #92400E;
            border: 1px solid rgba(245,158,11,.20);
        }

        .preview-card {
            margin-top: 12px;
            padding: 14px;
            border-radius: 26px;
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--aliada-border);
            box-shadow: 0 14px 34px rgba(123, 31, 162, .12);
        }

        .preview-grid {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 11px;
        }

        .preview-box {
            padding: 12px;
            border-radius: 20px;
            background: #FFF5FB;
            border: 1px solid rgba(216,27,156,.14);
        }

        .preview-box h3 {
            margin: 0 0 9px;
            color: var(--aliada-text);
            font-size: 14px;
            font-weight: 950;
        }

        .preview-box h3 i {
            color: var(--aliada-fucsia);
            margin-right: 6px;
        }

        .preview-area {
            min-height: 78px;
            border-radius: 16px;
            background: #fff;
            border: 1px dashed rgba(216,27,156,.22);
            color: var(--aliada-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 12px;
            font-size: 12px;
            font-weight: 750;
        }

        audio,
        video,
        img {
            width: 100%;
            max-width: 100%;
            border-radius: 16px;
            background: #000;
        }

        img {
            display: block;
            background: #fff;
        }

        .footer-mobile {
            padding: 18px 8px 4px;
            text-align: center;
        }

        .footer-mobile p {
            margin: 0;
            color: #8B6A94;
            font-size: 11px;
            line-height: 1.4;
        }

        .footer-mobile strong {
            color: var(--aliada-fucsia-dark);
            font-weight: 900;
        }

        .permission-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(43, 18, 55, .58);
            backdrop-filter: blur(10px);
        }

        .permission-modal.show {
            display: flex;
        }

        .permission-card {
            width: 100%;
            max-width: 420px;
            border-radius: 30px;
            padding: 18px;
            background: #fff;
            box-shadow: 0 24px 80px rgba(43,18,55,.38);
            border: 1px solid rgba(255,255,255,.8);
        }

        .permission-head {
            border-radius: 24px;
            padding: 17px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 12%, rgba(255,255,255,.24), transparent 30%),
                var(--aliada-gradient);
        }

        .permission-head-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.24);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 12px;
        }

        .permission-head h2 {
            margin: 0;
            font-size: 22px;
            line-height: 1.1;
            font-weight: 950;
            letter-spacing: -.7px;
        }

        .permission-head p {
            margin: 8px 0 0;
            font-size: 12.8px;
            line-height: 1.45;
            color: rgba(255,255,255,.86);
        }

        .permission-list {
            margin: 14px 0;
            display: grid;
            gap: 9px;
        }

        .permission-item {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 10px;
            align-items: center;
            padding: 11px;
            border-radius: 18px;
            background: #FFF5FB;
            border: 1px solid rgba(216,27,156,.12);
        }

        .permission-item-icon {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            background: var(--aliada-rosa-soft);
            color: var(--aliada-fucsia);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .permission-item strong {
            display: block;
            color: var(--aliada-text);
            font-size: 13px;
            font-weight: 950;
            line-height: 1.15;
        }

        .permission-item span {
            display: block;
            color: var(--aliada-muted);
            font-size: 11.5px;
            line-height: 1.35;
            margin-top: 3px;
        }

        .permission-actions {
            display: grid;
            gap: 9px;
        }

        .btn-permission-main,
        .btn-permission-secondary {
            width: 100%;
            min-height: 52px;
            border-radius: 18px;
            border: 0;
            font-size: 14px;
            font-weight: 950;
            cursor: pointer;
        }

        .btn-permission-main {
            color: #fff;
            background: var(--aliada-gradient);
            box-shadow: 0 15px 34px rgba(216,27,156,.30);
        }

        .btn-permission-secondary {
            color: var(--aliada-fucsia-dark);
            background: var(--aliada-rosa-soft);
            border: 1px solid rgba(216,27,156,.14);
        }

        @media (max-width: 370px) {
            .mobile-app {
                padding-left: 8px;
                padding-right: 8px;
            }

            .brand-logo {
                width: 60px;
                height: 60px;
                min-width: 60px;
                border-radius: 20px;
            }

            .brand-title {
                font-size: 21px;
            }

            .brand-subtitle {
                font-size: 10.8px;
            }

            .top-actions {
                grid-template-columns: 1fr;
            }

            .top-btn {
                min-height: 38px;
            }

            .action-grid {
                gap: 9px;
            }

            .action-btn {
                min-height: 116px;
            }

            .action-btn-inner {
                min-height: 116px;
                padding: 11px;
            }

            .btn-title {
                font-size: 13.2px;
            }

            .btn-text {
                display: none;
            }
        }

        @media (min-width: 768px) {
            body::before {
                content: "Esta vista está optimizada para celular";
                position: fixed;
                inset: 0;
                z-index: 10000;
                background: var(--aliada-gradient-dark);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 30px;
                font-size: 22px;
                line-height: 1.3;
                font-weight: 950;
            }
        }
    </style>
</head>

<body>

<div class="permission-modal" id="permissionModal">
    <div class="permission-card">
        <div class="permission-head">
            <div class="permission-head-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <h2>Activa los permisos de protección</h2>
            <p>
                Para guardar evidencia y activar apoyo inmediato, Aliada UTS necesita acceso a ubicación,
                cámara y micrófono.
            </p>
        </div>

        <div class="permission-list">
            <div class="permission-item">
                <div class="permission-item-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div>
                    <strong>Ubicación</strong>
                    <span>Permite guardar dónde se activó la alerta.</span>
                </div>
            </div>

            <div class="permission-item">
                <div class="permission-item-icon">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <strong>Cámara</strong>
                    <span>Permite tomar fotos o grabar video como evidencia.</span>
                </div>
            </div>

            <div class="permission-item">
                <div class="permission-item-icon">
                    <i class="fa-solid fa-microphone-lines"></i>
                </div>
                <div>
                    <strong>Micrófono</strong>
                    <span>Permite grabar audio o video con sonido.</span>
                </div>
            </div>
        </div>

        <div class="permission-actions">
            <button type="button" class="btn-permission-main" id="btnActivarPermisos">
                <i class="fa-solid fa-lock-open"></i>
                Activar permisos ahora
            </button>

            <button type="button" class="btn-permission-secondary" id="btnOmitirPermisos">
                Continuar y pedirlos después
            </button>
        </div>
    </div>
</div>


<main class="mobile-app">
    <div class="mobile-shell">

        <header class="top-card">
            <div class="top-card-inner">
                <div class="brand-row">
                    <div class="brand-logo">
                        <img src="assets/img/utslog.png" alt="Aliada UTS">
                    </div>

                    <div class="brand-copy">
                        <p class="brand-eyebrow">Universitaria Tecnológica de Santander</p>
                        <p class="brand-eyebrow">Aliada UTS</p>
                        <h1 class="brand-title">
                            Protección <span>Universitaria</span>
                        </h1>
                        <p class="brand-subtitle">
                            Botones rápidos para guardar evidencia y activar apoyo inmediato.
                        </p>
                    </div>
                </div>

                <div class="top-actions">
                    <button type="button" class="top-btn white" onclick="window.history.back();">
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver
                    </button>

                    <a href="./mis_evidencias.php" class="top-btn">
                        <i class="fa-solid fa-folder-open"></i>
                        Evidencias
                    </a>

                    <button type="button" class="top-btn" onclick="window.location.href='logoutusuaria.php';">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <section class="hero-card">
            <div class="hero-badge">
                <i class="fa-solid fa-shield-heart"></i>
                Atención inmediata
            </div>

            <h2 class="hero-title">
                <?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>, Aliada UTS está contigo.
            </h2>

            <p class="hero-text">
                Usa estas opciones para guardar evidencia o comunicarte rápidamente con apoyo de emergencia.
            </p>

            <div class="permission-status">
                <div class="permission-pill" id="pillLocation">
                    <i class="fa-solid fa-location-dot"></i>
                    Ubicación
                </div>
                <div class="permission-pill" id="pillCamera">
                    <i class="fa-solid fa-camera"></i>
                    Cámara
                </div>
                <div class="permission-pill" id="pillMicrophone">
                    <i class="fa-solid fa-microphone-lines"></i>
                    Micrófono
                </div>
            </div>
        </section>

        <section class="actions-card">
            <div class="actions-head">
                <h2 class="section-title">Acciones rápidas</h2>
                <p class="section-text">Toca una opción. La evidencia se guardará automáticamente con ubicación.</p>
            </div>

            <div class="action-grid">

                <button type="button" class="action-btn microfono" id="btnMicrofono">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-microphone-lines"></i></div>
                        <div>
                            <div class="btn-title">Grabar audio</div>
                            <div class="btn-text">Toca para iniciar y vuelve a tocar para detener.</div>
                        </div>
                    </div>
                </button>

                <button type="button" class="action-btn foto" id="btnFoto">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-camera"></i></div>
                        <div>
                            <div class="btn-title">Tomar foto</div>
                            <div class="btn-text">Captura evidencia rápida y segura.</div>
                        </div>
                    </div>
                </button>

                <button type="button" class="action-btn camara" id="btnVideo">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-video"></i></div>
                        <div>
                            <div class="btn-title">Grabar video</div>
                            <div class="btn-text">Registra lo ocurrido con audio.</div>
                        </div>
                    </div>
                </button>

                <button type="button" class="action-btn foto" id="btnPermisos">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <div>
                            <div class="btn-title">Revisar permisos</div>
                            <div class="btn-text">Activa cámara, micrófono y ubicación.</div>
                        </div>
                    </div>
                </button>

                <a href="tel:123" class="action-btn policia action-btn-priority" id="btnPolicia">
                    <div class="action-btn-inner">
                        <div class="btn-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <div>
                            <div class="btn-title">Llamar Policía</div>
                            <div class="btn-text">Guarda alerta y llama al 123.</div>
                        </div>
                    </div>
                </a>

                <?php if ($contactoEmergenciaTel !== ''): ?>
                    <a href="tel:<?php echo htmlspecialchars($contactoEmergenciaTel, ENT_QUOTES, 'UTF-8'); ?>" class="action-btn emergencia action-btn-priority" id="btnEmergencia">
                        <div class="action-btn-inner">
                            <div class="btn-icon"><i class="fa-solid fa-phone-volume"></i></div>
                            <div>
                                <div class="btn-title">Contacto de emergencia</div>
                                <div class="btn-text">
                                    <?php echo htmlspecialchars($nombreContacto !== '' ? $nombreContacto : 'Llamar contacto', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php else: ?>
                    <button type="button" class="action-btn emergencia action-btn-priority" id="btnEmergenciaSinNumero">
                        <div class="action-btn-inner">
                            <div class="btn-icon"><i class="fa-solid fa-phone-volume"></i></div>
                            <div>
                                <div class="btn-title">Contacto de emergencia</div>
                                <div class="btn-text">No tienes un contacto registrado.</div>
                            </div>
                        </div>
                    </button>
                <?php endif; ?>

            </div>

            <div class="status-msg" id="statusMsg"></div>
        </section>

        <section class="preview-card" id="previewCard" style="display:none;">
            <div class="preview-head">
                <h2 class="section-title">Evidencia capturada</h2>
                <p class="section-text">Aquí puedes revisar lo que acabas de registrar.</p>
            </div>

            <div class="preview-grid">
                <div class="preview-box" id="boxAudio" style="display:none;">
                    <h3><i class="fa-solid fa-microphone-lines"></i> Audio</h3>
                    <div class="preview-area" id="audioPreviewText">Aún no se ha grabado audio.</div>
                    <audio id="audioPlayer" controls style="display:none;"></audio>
                </div>

                <div class="preview-box" id="boxCamara" style="display:none;">
                    <h3><i class="fa-solid fa-camera"></i> Vista previa</h3>
                    <div class="preview-area" id="videoPreviewText">Aún no se ha activado la cámara.</div>
                    <video id="videoPreview" autoplay playsinline muted style="display:none;"></video>
                </div>

                <div class="preview-box" id="boxFoto" style="display:none;">
                    <h3><i class="fa-solid fa-image"></i> Foto</h3>
                    <div class="preview-area" id="fotoPreviewText">Aún no se ha tomado ninguna foto.</div>
                    <img id="fotoPreview" alt="Foto capturada" style="display:none;">
                </div>

                <div class="preview-box" id="boxVideoGrabado" style="display:none;">
                    <h3><i class="fa-solid fa-video"></i> Video grabado</h3>
                    <div class="preview-area" id="videoGrabadoText">Aún no se ha grabado ningún video.</div>
                    <video id="videoGrabadoPlayer" controls playsinline style="display:none;"></video>
                </div>
            </div>
        </section>

        <footer class="footer-mobile">
            <p>
                © <?php echo $currentYear; ?> <strong>Aliada UTS</strong><br>
                Acompañamiento, orientación y protección universitaria.
            </p>
        </footer>

    </div>
</main>

<script>
'use strict';

const USER_ID = <?php echo (int)$usuarioId; ?>;
const USER_NAME = <?php echo json_encode($nombreUsuario, JSON_UNESCAPED_UNICODE); ?>;

const PERMISSIONS_KEY = 'aliada_permissions_requested_v1';
const PERMISSIONS_OK_KEY = 'aliada_permissions_ok_v1';

let currentLat = null;
let currentLng = null;
let currentAccuracy = null;

let audioStream = null;
let audioRecorder = null;
let audioChunks = [];
let isRecordingAudio = false;

let cameraStream = null;
let videoRecorder = null;
let videoChunks = [];
let isRecordingVideo = false;

const statusMsg = document.getElementById('statusMsg');
const previewCard = document.getElementById('previewCard');

const btnMicrofono = document.getElementById('btnMicrofono');
const btnFoto = document.getElementById('btnFoto');
const btnVideo = document.getElementById('btnVideo');
const btnPolicia = document.getElementById('btnPolicia');
const btnEmergencia = document.getElementById('btnEmergencia');
const btnEmergenciaSinNumero = document.getElementById('btnEmergenciaSinNumero');
const btnPermisos = document.getElementById('btnPermisos');

const permissionModal = document.getElementById('permissionModal');
const btnActivarPermisos = document.getElementById('btnActivarPermisos');
const btnOmitirPermisos = document.getElementById('btnOmitirPermisos');

const pillLocation = document.getElementById('pillLocation');
const pillCamera = document.getElementById('pillCamera');
const pillMicrophone = document.getElementById('pillMicrophone');

const boxAudio = document.getElementById('boxAudio');
const boxCamara = document.getElementById('boxCamara');
const boxFoto = document.getElementById('boxFoto');
const boxVideoGrabado = document.getElementById('boxVideoGrabado');

const audioPlayer = document.getElementById('audioPlayer');
const videoPreview = document.getElementById('videoPreview');
const fotoPreview = document.getElementById('fotoPreview');
const videoGrabadoPlayer = document.getElementById('videoGrabadoPlayer');

const audioPreviewText = document.getElementById('audioPreviewText');
const videoPreviewText = document.getElementById('videoPreviewText');
const fotoPreviewText = document.getElementById('fotoPreviewText');
const videoGrabadoText = document.getElementById('videoGrabadoText');

function showStatus(message, type = 'info') {
    if (!statusMsg) return;
    statusMsg.textContent = message || '';
    statusMsg.className = 'status-msg ' + type;
}

function showPreviewCard() {
    if (previewCard) previewCard.style.display = 'block';
}

function mostrarSoloPreview(box) {
    showPreviewCard();

    [boxAudio, boxCamara, boxFoto, boxVideoGrabado].forEach(item => {
        if (item) item.style.display = 'none';
    });

    if (box) box.style.display = 'block';

    setTimeout(() => {
        previewCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 150);
}

function abrirModalPermisos() {
    if (permissionModal) {
        permissionModal.classList.add('show');
    }
}

function cerrarModalPermisos() {
    if (permissionModal) {
        permissionModal.classList.remove('show');
    }
}

function marcarPill(pill, ok) {
    if (!pill) return;

    if (ok) {
        pill.classList.add('ok');
    } else {
        pill.classList.remove('ok');
    }
}

function guardarEstadoPermisos({ location = false, camera = false, microphone = false } = {}) {
    const estado = {
        location,
        camera,
        microphone,
        updated_at: new Date().toISOString()
    };

    localStorage.setItem(PERMISSIONS_OK_KEY, JSON.stringify(estado));
    localStorage.setItem(PERMISSIONS_KEY, '1');

    actualizarPillsDesdeEstado(estado);
}

function leerEstadoPermisosGuardado() {
    try {
        return JSON.parse(localStorage.getItem(PERMISSIONS_OK_KEY) || '{}');
    } catch (e) {
        return {};
    }
}

function actualizarPillsDesdeEstado(estado = null) {
    const saved = estado || leerEstadoPermisosGuardado();

    marcarPill(pillLocation, !!saved.location);
    marcarPill(pillCamera, !!saved.camera);
    marcarPill(pillMicrophone, !!saved.microphone);
}

async function consultarPermissionAPI(name) {
    if (!navigator.permissions || !navigator.permissions.query) return null;

    try {
        const result = await navigator.permissions.query({ name });
        return result.state;
    } catch (e) {
        return null;
    }
}

async function refrescarEstadoPermisosReal() {
    const saved = leerEstadoPermisosGuardado();

    const geoState = await consultarPermissionAPI('geolocation');
    const cameraState = await consultarPermissionAPI('camera');
    const microphoneState = await consultarPermissionAPI('microphone');

    const estado = {
        location: geoState ? geoState === 'granted' : !!saved.location,
        camera: cameraState ? cameraState === 'granted' : !!saved.camera,
        microphone: microphoneState ? microphoneState === 'granted' : !!saved.microphone,
        updated_at: new Date().toISOString()
    };

    localStorage.setItem(PERMISSIONS_OK_KEY, JSON.stringify(estado));
    actualizarPillsDesdeEstado(estado);
}

function getLocationSilent() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve(null);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                currentLat = position.coords.latitude;
                currentLng = position.coords.longitude;
                currentAccuracy = position.coords.accuracy;

                const saved = leerEstadoPermisosGuardado();
                guardarEstadoPermisos({
                    ...saved,
                    location: true
                });

                resolve({
                    lat: currentLat,
                    lng: currentLng,
                    accuracy: currentAccuracy
                });
            },
            () => resolve(null),
            {
                enableHighAccuracy: true,
                timeout: 7000,
                maximumAge: 30000
            }
        );
    });
}

async function solicitarPermisosIniciales() {
    let locationOk = false;
    let cameraOk = false;
    let microphoneOk = false;

    showStatus('Solicitando permisos de protección...', 'info');

    try {
        const location = await getLocationSilent();
        locationOk = !!location;
    } catch (e) {
        locationOk = false;
    }

    try {
        const mediaStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });

        cameraOk = mediaStream.getVideoTracks().length > 0;
        microphoneOk = mediaStream.getAudioTracks().length > 0;

        mediaStream.getTracks().forEach(track => track.stop());
    } catch (e) {
        cameraOk = false;
        microphoneOk = false;
    }

    guardarEstadoPermisos({
        location: locationOk,
        camera: cameraOk,
        microphone: microphoneOk
    });

    cerrarModalPermisos();

    if (locationOk && cameraOk && microphoneOk) {
        showStatus('Permisos activados correctamente. Ya puedes guardar evidencia de forma segura.', 'success');
    } else {
        showStatus('Algunos permisos no fueron activados. Puedes revisarlos desde el botón “Revisar permisos”.', 'warning');
    }

    await refrescarEstadoPermisosReal();
}

async function guardarAlerta(tipo, archivo = null, extra = {}) {
    try {
        await getLocationSilent();

        const formData = new FormData();
        formData.append('tipo', tipo);
        formData.append('usuario_id', USER_ID);
        formData.append('nombre_usuario', USER_NAME);
        formData.append('latitud', currentLat ?? '');
        formData.append('longitud', currentLng ?? '');
        formData.append('precision_gps', currentAccuracy ?? '');
        formData.append('descripcion', extra.descripcion ?? '');

        if (archivo) {
            formData.append('archivo', archivo, extra.nombreArchivo || 'evidencia.webm');
        }

       const response = await fetch('admin/ajax/guardar_alerta.php', {
    method: 'POST',
    body: formData
});

        const text = await response.text();
        let data = null;

        try {
            data = JSON.parse(text);
        } catch (e) {
            data = { valid: response.ok, message: text };
        }

        if (!response.ok || data.valid === false) {
            throw new Error(data.message || 'No fue posible guardar la alerta.');
        }

        showStatus(data.message || 'Información guardada correctamente.', 'success');
        return true;
    } catch (error) {
        showStatus(error.message || 'No fue posible guardar la información.', 'error');
        return false;
    }
}

function resetAudioButton() {
    if (!btnMicrofono) return;

    btnMicrofono.classList.remove('recording');
    btnMicrofono.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-microphone-lines"></i></div>
            <div>
                <div class="btn-title">Grabar audio</div>
                <div class="btn-text">Toca para iniciar y vuelve a tocar para detener.</div>
            </div>
        </div>
    `;
}

function setAudioStopButton() {
    if (!btnMicrofono) return;

    btnMicrofono.classList.add('recording');
    btnMicrofono.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-stop"></i></div>
            <div>
                <div class="btn-title">Detener audio</div>
                <div class="btn-text">Finalizar grabación y guardar.</div>
            </div>
        </div>
    `;
}

function resetVideoButton() {
    if (!btnVideo) return;

    btnVideo.classList.remove('recording');
    btnVideo.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-video"></i></div>
            <div>
                <div class="btn-title">Grabar video</div>
                <div class="btn-text">Registra lo ocurrido con audio.</div>
            </div>
        </div>
    `;
}

function setVideoStopButton() {
    if (!btnVideo) return;

    btnVideo.classList.add('recording');
    btnVideo.innerHTML = `
        <div class="action-btn-inner">
            <div class="btn-icon"><i class="fa-solid fa-stop"></i></div>
            <div>
                <div class="btn-title">Detener video</div>
                <div class="btn-text">Finalizar grabación y guardar.</div>
            </div>
        </div>
    `;
}

async function iniciarAudio() {
    try {
        audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });

        const saved = leerEstadoPermisosGuardado();
        guardarEstadoPermisos({
            ...saved,
            microphone: true
        });

        audioChunks = [];
        audioRecorder = new MediaRecorder(audioStream);

        audioRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        audioRecorder.onstop = async () => {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const url = URL.createObjectURL(blob);

            mostrarSoloPreview(boxAudio);

            audioPreviewText.style.display = 'none';
            audioPlayer.style.display = 'block';
            audioPlayer.src = url;

            if (audioStream) {
                audioStream.getTracks().forEach(track => track.stop());
                audioStream = null;
            }

            await guardarAlerta('audio', blob, {
                nombreArchivo: 'audio_evidencia.webm',
                descripcion: 'Audio guardado desde Aliada UTS.'
            });
        };

        audioRecorder.start();
        isRecordingAudio = true;

        setAudioStopButton();
        mostrarSoloPreview(boxAudio);

        audioPreviewText.style.display = 'flex';
        audioPreviewText.textContent = 'Grabando audio... toca nuevamente para detener.';
        audioPlayer.style.display = 'none';

        showStatus('Grabando audio de evidencia...', 'info');
    } catch (error) {
        showStatus('No fue posible activar el micrófono. Revisa los permisos del navegador.', 'error');
        resetAudioButton();
    }
}

function detenerAudio() {
    if (audioRecorder && audioRecorder.state !== 'inactive') {
        audioRecorder.stop();
    }

    isRecordingAudio = false;
    resetAudioButton();
    showStatus('Procesando audio...', 'info');
}

async function tomarFoto() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: false
        });

        const saved = leerEstadoPermisosGuardado();
        guardarEstadoPermisos({
            ...saved,
            camera: true
        });

        const video = document.createElement('video');
        video.srcObject = stream;
        video.setAttribute('playsinline', 'true');
        video.muted = true;

        await video.play();

        await new Promise(resolve => setTimeout(resolve, 300));

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 1280;
        canvas.height = video.videoHeight || 720;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        stream.getTracks().forEach(track => track.stop());

        canvas.toBlob(async (blob) => {
            if (!blob) {
                showStatus('No fue posible tomar la foto.', 'error');
                return;
            }

            const url = URL.createObjectURL(blob);

            mostrarSoloPreview(boxFoto);

            fotoPreviewText.style.display = 'none';
            fotoPreview.style.display = 'block';
            fotoPreview.src = url;

            await guardarAlerta('foto', blob, {
                nombreArchivo: 'foto_evidencia.jpg',
                descripcion: 'Foto guardada desde Aliada UTS.'
            });
        }, 'image/jpeg', 0.92);

        showStatus('Foto capturada. Guardando evidencia...', 'info');
    } catch (error) {
        showStatus('No fue posible activar la cámara para tomar foto. Revisa los permisos.', 'error');
    }
}

async function iniciarVideo() {
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
            audio: true
        });

        const saved = leerEstadoPermisosGuardado();
        guardarEstadoPermisos({
            ...saved,
            camera: true,
            microphone: true
        });

        videoChunks = [];

        videoPreview.srcObject = cameraStream;
        videoPreview.style.display = 'block';
        videoPreviewText.style.display = 'none';

        mostrarSoloPreview(boxCamara);

        videoRecorder = new MediaRecorder(cameraStream);

        videoRecorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                videoChunks.push(event.data);
            }
        };

        videoRecorder.onstop = async () => {
            const blob = new Blob(videoChunks, { type: 'video/webm' });
            const url = URL.createObjectURL(blob);

            if (videoPreview) {
                videoPreview.pause();
                videoPreview.srcObject = null;
                videoPreview.style.display = 'none';
            }

            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }

            mostrarSoloPreview(boxVideoGrabado);

            videoGrabadoText.style.display = 'none';
            videoGrabadoPlayer.style.display = 'block';
            videoGrabadoPlayer.src = url;

            await guardarAlerta('video', blob, {
                nombreArchivo: 'video_evidencia.webm',
                descripcion: 'Video guardado desde Aliada UTS.'
            });
        };

        videoRecorder.start();
        isRecordingVideo = true;

        setVideoStopButton();

        showStatus('Grabando video de evidencia... toca nuevamente para detener.', 'info');
    } catch (error) {
        showStatus('No fue posible activar cámara y micrófono para video. Revisa los permisos.', 'error');
        resetVideoButton();
    }
}

function detenerVideo() {
    if (videoRecorder && videoRecorder.state !== 'inactive') {
        videoRecorder.stop();
    }

    isRecordingVideo = false;
    resetVideoButton();
    showStatus('Procesando video...', 'info');
}

async function guardarAntesDeLlamar(event, tipo, descripcion) {
    event.preventDefault();

    const href = event.currentTarget.getAttribute('href');

    showStatus('Guardando alerta antes de realizar la llamada...', 'info');

    await guardarAlerta(tipo, null, { descripcion });

    setTimeout(() => {
        window.location.href = href;
    }, 450);
}

if (btnMicrofono) {
    btnMicrofono.addEventListener('click', () => {
        if (isRecordingAudio) {
            detenerAudio();
        } else {
            iniciarAudio();
        }
    });
}

if (btnFoto) {
    btnFoto.addEventListener('click', tomarFoto);
}

if (btnVideo) {
    btnVideo.addEventListener('click', () => {
        if (isRecordingVideo) {
            detenerVideo();
        } else {
            iniciarVideo();
        }
    });
}

if (btnPolicia) {
    btnPolicia.addEventListener('click', (event) => {
        guardarAntesDeLlamar(event, 'policia', 'Se activó llamada a Policía 123 desde Aliada UTS.');
    });
}

if (btnEmergencia) {
    btnEmergencia.addEventListener('click', (event) => {
        guardarAntesDeLlamar(event, 'contacto_emergencia', 'Se activó llamada a contacto de emergencia desde Aliada UTS.');
    });
}

if (btnEmergenciaSinNumero) {
    btnEmergenciaSinNumero.addEventListener('click', () => {
        showStatus('No tienes un contacto de emergencia registrado. Actualiza tu información en el sistema.', 'warning');
    });
}

if (btnPermisos) {
    btnPermisos.addEventListener('click', abrirModalPermisos);
}

if (btnActivarPermisos) {
    btnActivarPermisos.addEventListener('click', solicitarPermisosIniciales);
}

if (btnOmitirPermisos) {
    btnOmitirPermisos.addEventListener('click', () => {
        localStorage.setItem(PERMISSIONS_KEY, '1');
        cerrarModalPermisos();
        showStatus('Puedes continuar. Los permisos se pedirán cuando uses cámara, micrófono o ubicación.', 'warning');
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    actualizarPillsDesdeEstado();
    await refrescarEstadoPermisosReal();

    const yaSolicitados = localStorage.getItem(PERMISSIONS_KEY) === '1';

    if (!yaSolicitados) {
        setTimeout(() => {
            abrirModalPermisos();
        }, 450);
    } else {
        getLocationSilent();
    }
});
</script>

</body>
</html>