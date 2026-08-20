<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

$pageTitle       = 'Aliada UTS | Emergencia Institucional';
$pageDescription = 'Aliada UTS, plataforma institucional para proteger, orientar y acompañar a la mujer universitaria con herramientas de apoyo, seguridad y respuesta inmediata.';
$currentYear     = date('Y');

/*
|--------------------------------------------------------------------------
| Nombre de la usuaria logueada
|--------------------------------------------------------------------------
*/
$nombreUsuario = '';

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

/*
|--------------------------------------------------------------------------
| ID de usuario desde sesión
|--------------------------------------------------------------------------
*/
$usuarioId = 0;

if (!empty($_SESSION['id'])) {
    $usuarioId = (int) $_SESSION['id'];
} elseif (!empty($_SESSION['usuario_id'])) {
    $usuarioId = (int) $_SESSION['usuario_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $usuarioId = (int) $_SESSION['user_id'];
} elseif (!empty($_SESSION['session_user']['id'])) {
    $usuarioId = (int) $_SESSION['session_user']['id'];
}

/*
|--------------------------------------------------------------------------
| Contacto de emergencia
|--------------------------------------------------------------------------
*/
$contactoEmergencia    = '';
$contactoEmergenciaTel = '';
$nombreContacto        = '';

try {
    if (!class_exists('DbConection')) {
        require_once './admin/classes/DbConection.php';
    }

    if (class_exists('DbConection') && $usuarioId > 0) {
        $db  = new DbConection();
        $pdo = $db->openConect();

        $sql = "SELECT contacto_emergencia, nombre_contacto
                FROM tbl_usuarios
                WHERE id = :id
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioDB) {
            $contactoEmergencia = trim((string) ($usuarioDB['contacto_emergencia'] ?? ''));
            $nombreContacto     = trim((string) ($usuarioDB['nombre_contacto'] ?? ''));

            if ($contactoEmergencia !== '') {
                $contactoEmergenciaTel = preg_replace('/[^0-9+]/', '', $contactoEmergencia);
            }
        }
    }
} catch (Throwable $e) {
    $contactoEmergencia    = '';
    $contactoEmergenciaTel = '';
    $nombreContacto        = '';
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
    <link rel="preconnect" href="https://unpkg.com">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            --aliada-blue: linear-gradient(135deg, #38BDF8 0%, #6366F1 100%);
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

        .app-mobile {
            width: 100%;
            min-height: 100dvh;
            padding: calc(10px + var(--safe-top)) 10px calc(88px + var(--safe-bottom));
        }

        .mobile-shell {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
        }

        .topbar-mobile {
            position: sticky;
            top: 0;
            z-index: 40;
            padding-bottom: 10px;
            background:
                linear-gradient(180deg, rgba(255, 245, 251, .98), rgba(255, 245, 251, .86) 72%, rgba(255, 245, 251, 0));
            backdrop-filter: blur(12px);
        }

        .topbar-mobile-inner {
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

        .topbar-mobile-inner::after {
            content: "";
            position: absolute;
            width: 160px;
            height: 160px;
            right: -75px;
            top: -75px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
        }

        .brand-mobile {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mobile-logo {
            width: 68px;
            height: 68px;
            min-width: 68px;
            border-radius: 22px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            box-shadow: 0 14px 30px rgba(74, 14, 99, .24);
        }

        .brand-mobile-logo img {
            width: 100%;
            max-width: 56px;
            max-height: 56px;
            object-fit: contain;
            display: block;
        }

        .brand-mobile-copy {
            min-width: 0;
            flex: 1;
        }

        .brand-mobile-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 6px;
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

        .brand-mobile-eyebrow::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #FFD6EF;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .12);
        }

        .brand-mobile-title {
            margin: 0;
            color: #ffffff;
            font-size: clamp(21px, 6.6vw, 28px);
            line-height: 1.03;
            font-weight: 950;
            letter-spacing: -1px;
        }

        .brand-mobile-title span {
            color: #FFD6EF;
        }

        .brand-mobile-subtitle {
            margin: 7px 0 0;
            color: rgba(255, 255, 255, .85);
            font-size: 11.8px;
            line-height: 1.35;
            font-weight: 600;
        }

        .topbar-mobile-actions {
            position: relative;
            z-index: 2;
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .btn-mobile-top {
            min-height: 44px;
            border: 0;
            border-radius: 16px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .22);
            color: #ffffff;
            font-size: 12.5px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(74, 14, 99, .14);
        }

        .btn-mobile-top.white {
            background: #ffffff;
            color: var(--aliada-fucsia-dark);
        }

        .btn-mobile-top:active {
            transform: scale(.98);
        }

        .summary-card,
        .location-card,
        .actions-card,
        .footer-card {
            margin-top: 10px;
            padding: 14px;
            border-radius: 26px;
            background: var(--aliada-card);
            border: 1px solid var(--aliada-border);
            box-shadow: 0 12px 32px rgba(123, 31, 162, .10);
        }

        .summary-badge,
        .location-badge {
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

        .summary-title,
        .location-title,
        .section-title {
            margin: 0;
            color: var(--aliada-text);
            font-size: 18px;
            line-height: 1.18;
            font-weight: 950;
            letter-spacing: -.4px;
        }

        .summary-text,
        .location-subtitle,
        .section-text {
            margin: 8px 0 0;
            color: var(--aliada-muted);
            font-size: 12.5px;
            line-height: 1.45;
        }

        .status-mini {
            margin-top: 11px;
            padding: 11px 12px;
            border-radius: 17px;
            background: linear-gradient(135deg, rgba(255, 79, 184, .12), rgba(123, 31, 162, .08));
            border: 1px solid rgba(216, 27, 156, .15);
            color: var(--aliada-fucsia-dark);
            font-size: 12px;
            line-height: 1.4;
            font-weight: 850;
            text-align: center;
        }

        .status-mini.success {
            color: #15803D;
            background: rgba(34, 197, 94, .10);
            border-color: rgba(34, 197, 94, .20);
        }

        .status-mini.warning {
            color: #92400E;
            background: rgba(245, 158, 11, .12);
            border-color: rgba(245, 158, 11, .20);
        }

        .status-mini.error {
            color: #B42318;
            background: rgba(239, 68, 68, .10);
            border-color: rgba(239, 68, 68, .20);
        }

        .map-wrap {
            width: 100%;
            margin-top: 13px;
            border-radius: 24px;
            padding: 6px;
            background: #ffffff;
            border: 1px solid rgba(216, 27, 156, .14);
            box-shadow: 0 14px 30px rgba(123, 31, 162, .12);
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 300px;
            min-height: 300px;
            border-radius: 20px;
            background: #FCE7F3;
            overflow: hidden;
        }

        .leaflet-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }

        .location-info {
            margin-top: 12px;
            display: grid;
            gap: 9px;
        }

        .location-box {
            padding: 12px;
            border-radius: 20px;
            background: #FFF5FB;
            border: 1px solid rgba(216, 27, 156, .13);
        }

        .location-label {
            display: block;
            margin-bottom: 6px;
            color: var(--aliada-muted);
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .location-value {
            color: var(--aliada-text);
            font-size: 12.2px;
            line-height: 1.42;
            font-weight: 750;
            word-break: break-word;
        }

        .coords-box {
            margin-top: 7px;
            color: var(--aliada-muted);
            font-size: 10.8px;
            line-height: 1.4;
            font-weight: 650;
            word-break: break-word;
        }

        .actions-card {
            padding-bottom: 15px;
        }

        .action-grid {
            margin-top: 13px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 11px;
        }

        .action-btn {
            width: 100%;
            min-height: 76px;
            border: 0;
            border-radius: 24px;
            padding: 14px;
            color: #ffffff;
            text-decoration: none;
            display: grid;
            grid-template-columns: 52px 1fr 32px;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            line-height: 1.15;
            font-weight: 950;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 16px 34px rgba(44, 0, 52, .20);
            transition: .2s ease;
            text-align: left;
        }

        .action-btn:active {
            transform: scale(.985);
        }

        .action-btn::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, .20), transparent 52%);
            pointer-events: none;
        }

        .action-btn::after {
            content: "\f054";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            background: #ffffff;
            color: #C02676;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            position: relative;
            z-index: 1;
        }

        .action-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .20);
            border: 1px solid rgba(255, 255, 255, .22);
            font-size: 20px;
            position: relative;
            z-index: 1;
        }

        .action-copy {
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .action-copy strong {
            display: block;
            color: #ffffff;
            font-size: 16px;
            font-weight: 950;
            line-height: 1.12;
        }

        .action-copy span {
            display: block;
            margin-top: 4px;
            color: rgba(255, 255, 255, .82);
            font-size: 12px;
            font-weight: 650;
            line-height: 1.35;
        }

        .action-btn.police {
            background: var(--aliada-danger);
        }

        .action-btn.emergency {
            background: var(--aliada-blue);
        }

        .action-btn.evidence {
            background: var(--aliada-gradient);
        }

        .action-btn.disabled {
            opacity: .72;
            filter: grayscale(.1);
        }

        .quick-note {
            margin-top: 12px;
            padding: 12px;
            border-radius: 19px;
            background: linear-gradient(135deg, rgba(255, 79, 184, .12), rgba(123, 31, 162, .08));
            border: 1px solid rgba(216, 27, 156, .15);
            color: var(--aliada-muted);
            font-size: 12px;
            line-height: 1.45;
            font-weight: 700;
        }

        .footer-card {
            text-align: center;
        }

        .footer-title {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 950;
            color: var(--aliada-text);
        }

        .footer-text,
        .footer-copy {
            margin: 0;
            font-size: 11.7px;
            line-height: 1.45;
            color: var(--aliada-muted);
            font-weight: 650;
        }

        .footer-copy {
            margin-top: 10px;
        }

        .footer-copy strong {
            color: var(--aliada-fucsia-dark);
            font-weight: 950;
        }

        .bottom-nav {
            position: fixed;
            left: 10px;
            right: 10px;
            bottom: calc(10px + var(--safe-bottom));
            z-index: 60;
            max-width: 460px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1.15fr 1fr;
            gap: 8px;
            padding: 8px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .90);
            border: 1px solid rgba(216, 27, 156, .14);
            box-shadow: 0 18px 44px rgba(74, 14, 99, .20);
            backdrop-filter: blur(18px);
        }

        .bottom-btn {
            min-height: 48px;
            border-radius: 17px;
            border: 0;
            background: rgba(252, 231, 243, .70);
            color: var(--aliada-fucsia-dark);
            font-size: 11px;
            font-weight: 900;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-align: center;
        }

        .bottom-btn i {
            font-size: 16px;
        }

        .bottom-btn.primary {
            background: var(--aliada-gradient);
            color: #fff;
            box-shadow: 0 12px 26px rgba(216, 27, 156, .28);
        }

        @media (max-width: 370px) {
            .app-mobile {
                padding-left: 8px;
                padding-right: 8px;
            }

            .topbar-mobile-inner,
            .summary-card,
            .location-card,
            .actions-card,
            .footer-card {
                border-radius: 23px;
            }

            .brand-mobile-logo {
                width: 60px;
                height: 60px;
                min-width: 60px;
                border-radius: 20px;
            }

            .brand-mobile-title {
                font-size: 21px;
            }

            .brand-mobile-subtitle {
                font-size: 10.8px;
            }

            .btn-mobile-top {
                min-height: 40px;
                font-size: 11.5px;
            }

            #map {
                height: 270px;
                min-height: 270px;
            }

            .action-btn {
                min-height: 72px;
                border-radius: 21px;
                grid-template-columns: 48px 1fr 30px;
                padding: 12px;
            }

            .action-icon {
                width: 48px;
                height: 48px;
                min-width: 48px;
                font-size: 18px;
            }

            .action-copy strong {
                font-size: 14.5px;
            }

            .action-copy span {
                font-size: 11px;
            }

            .bottom-nav {
                left: 8px;
                right: 8px;
            }
        }
    </style>
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
                        <span class="brand-mobile-eyebrow">Universitaria Tecnológica de Santander</span>
                        <span class="brand-mobile-eyebrow">Aliada UTS</span>
                        <h1 class="brand-mobile-title">
                            Emergencia <span>institucional</span>
                        </h1>
                        <p class="brand-mobile-subtitle">
                            Ubicación, llamada de emergencia y registro inmediato de alerta.
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
                <i class="fa-solid fa-shield-heart"></i>
                Atención inmediata
            </div>

            <h2 class="summary-title">
                <?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>, estamos contigo.
            </h2>

            <p class="summary-text">
                Esta vista permite guardar tu ubicación y activar una llamada de apoyo. Mantén la calma y usa el botón que necesites.
            </p>

            <div class="status-mini" id="statusLocation">
                Obteniendo ubicación segura...
            </div>
        </section>

        <section class="location-card">
            <div class="location-badge">
                <i class="fa-solid fa-location-dot"></i>
                Ubicación actual
            </div>

            <h2 class="location-title">
                Tu ubicación se mostrará en el mapa
            </h2>

            <p class="location-subtitle">
                Si el navegador solicita permiso, acepta para guardar la alerta con mayor precisión.
            </p>

            <div class="map-wrap">
                <div id="map"></div>
            </div>

            <div class="location-info">
                <div class="location-box">
                    <span class="location-label">Estado de ubicación</span>
                    <div class="location-value" id="locationText">
                        Esperando permiso de ubicación...
                    </div>
                    <div class="coords-box" id="coordsText">
                        Latitud: -- · Longitud: -- · Precisión: --
                    </div>
                </div>
            </div>
        </section>

        <section class="actions-card">
            <h2 class="section-title">Acciones rápidas</h2>
            <p class="section-text">
                Al tocar una opción, primero se guarda una alerta con tu ubicación y luego se realiza la llamada.
            </p>

            <div class="action-grid">
                <a href="tel:123" class="action-btn police" id="btnPolicia">
                    <span class="action-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <span class="action-copy">
                        <strong>Llamar Policía 123</strong>
                        <span>Guarda alerta y llama a la línea de emergencia.</span>
                    </span>
                </a>

                <?php if ($contactoEmergenciaTel !== ''): ?>
                    <a href="tel:<?php echo htmlspecialchars($contactoEmergenciaTel, ENT_QUOTES, 'UTF-8'); ?>" class="action-btn emergency" id="btnContactoEmergencia">
                        <span class="action-icon">
                            <i class="fa-solid fa-phone-volume"></i>
                        </span>
                        <span class="action-copy">
                            <strong>Contacto de emergencia</strong>
                            <span><?php echo htmlspecialchars($nombreContacto !== '' ? $nombreContacto : 'Llamar contacto registrado', ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </a>
                <?php else: ?>
                    <button type="button" class="action-btn emergency disabled" id="btnSinContacto">
                        <span class="action-icon">
                            <i class="fa-solid fa-phone-volume"></i>
                        </span>
                        <span class="action-copy">
                            <strong>Contacto de emergencia</strong>
                            <span>No tienes contacto registrado.</span>
                        </span>
                    </button>
                <?php endif; ?>

                <a href="mis_evidencias.php" class="action-btn evidence">
                    <span class="action-icon">
                        <i class="fa-solid fa-folder-open"></i>
                    </span>
                    <span class="action-copy">
                        <strong>Mis evidencias</strong>
                        <span>Consulta audios, fotos, videos y alertas guardadas.</span>
                    </span>
                </a>
            </div>

            <div class="quick-note" id="saveStatus">
                La alerta se guardará con la información disponible de ubicación, fecha y hora.
            </div>
        </section>

        <footer class="footer-card">
            <h3 class="footer-title">Aliada UTS</h3>
            <p class="footer-text">
                Herramienta de acompañamiento, orientación y protección para la comunidad universitaria.
            </p>
            <p class="footer-copy">
                © <?php echo $currentYear; ?> <strong>Aliada UTS</strong>
            </p>
        </footer>

    </div>
</main>

<nav class="bottom-nav">
    <button type="button" class="bottom-btn" onclick="window.history.back();">
        <i class="fa-solid fa-arrow-left"></i>
        Volver
    </button>

    <a href="tel:123" class="bottom-btn primary" id="btnPoliciaBottom">
        <i class="fa-solid fa-shield-halved"></i>
        Policía
    </a>

    <a href="panel_usuaria.php" class="bottom-btn">
        <i class="fa-solid fa-house"></i>
        Inicio
    </a>
</nav>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
'use strict';

const USER_ID = <?php echo (int) $usuarioId; ?>;
const USER_NAME = <?php echo json_encode($nombreUsuario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

let currentLat = null;
let currentLng = null;
let currentAccuracy = null;
let map = null;
let userMarker = null;
let accuracyCircle = null;

const statusLocation = document.getElementById('statusLocation');
const locationText = document.getElementById('locationText');
const coordsText = document.getElementById('coordsText');
const saveStatus = document.getElementById('saveStatus');

const btnPolicia = document.getElementById('btnPolicia');
const btnPoliciaBottom = document.getElementById('btnPoliciaBottom');
const btnContactoEmergencia = document.getElementById('btnContactoEmergencia');
const btnSinContacto = document.getElementById('btnSinContacto');

function setStatus(message, type = '') {
    if (!statusLocation) return;

    statusLocation.textContent = message;
    statusLocation.className = 'status-mini';

    if (type) {
        statusLocation.classList.add(type);
    }
}

function setSaveStatus(message, type = '') {
    if (!saveStatus) return;

    saveStatus.textContent = message;
    saveStatus.style.color = '';
    saveStatus.style.borderColor = '';
    saveStatus.style.background = '';

    if (type === 'success') {
        saveStatus.style.color = '#15803D';
        saveStatus.style.borderColor = 'rgba(34,197,94,.20)';
        saveStatus.style.background = 'rgba(34,197,94,.10)';
    }

    if (type === 'error') {
        saveStatus.style.color = '#B42318';
        saveStatus.style.borderColor = 'rgba(239,68,68,.20)';
        saveStatus.style.background = 'rgba(239,68,68,.10)';
    }

    if (type === 'warning') {
        saveStatus.style.color = '#92400E';
        saveStatus.style.borderColor = 'rgba(245,158,11,.20)';
        saveStatus.style.background = 'rgba(245,158,11,.12)';
    }
}

function initMap() {
    const defaultLat = 7.1193;
    const defaultLng = -73.1227;

    map = L.map('map', {
        zoomControl: true,
        attributionControl: true
    }).setView([defaultLat, defaultLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    userMarker = L.marker([defaultLat, defaultLng]).addTo(map)
        .bindPopup('Ubicación inicial aproximada')
        .openPopup();
}

function updateMap(lat, lng, accuracy) {
    if (!map) return;

    const latLng = [lat, lng];

    map.setView(latLng, 17);

    if (userMarker) {
        userMarker.setLatLng(latLng);
        userMarker.bindPopup('Tu ubicación actual').openPopup();
    } else {
        userMarker = L.marker(latLng).addTo(map).bindPopup('Tu ubicación actual').openPopup();
    }

    if (accuracyCircle) {
        accuracyCircle.setLatLng(latLng);
        accuracyCircle.setRadius(accuracy || 20);
    } else {
        accuracyCircle = L.circle(latLng, {
            radius: accuracy || 20,
            color: '#D81B9C',
            fillColor: '#FF4FB8',
            fillOpacity: 0.15,
            weight: 2
        }).addTo(map);
    }
}

function obtenerUbicacion() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            setStatus('Tu navegador no permite obtener ubicación.', 'error');
            if (locationText) {
                locationText.textContent = 'Geolocalización no disponible en este dispositivo.';
            }
            resolve(null);
            return;
        }

        setStatus('Solicitando permiso de ubicación...', 'warning');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                currentLat = position.coords.latitude;
                currentLng = position.coords.longitude;
                currentAccuracy = position.coords.accuracy;

                updateMap(currentLat, currentLng, currentAccuracy);

                setStatus('Ubicación obtenida correctamente.', 'success');

                if (locationText) {
                    locationText.textContent = 'Ubicación lista para registrar una alerta.';
                }

                if (coordsText) {
                    coordsText.textContent =
                        'Latitud: ' + currentLat.toFixed(6) +
                        ' · Longitud: ' + currentLng.toFixed(6) +
                        ' · Precisión: ' + Math.round(currentAccuracy || 0) + ' m';
                }

                resolve({
                    lat: currentLat,
                    lng: currentLng,
                    accuracy: currentAccuracy
                });
            },
            (error) => {
                let message = 'No fue posible obtener la ubicación.';

                if (error.code === 1) {
                    message = 'Permiso de ubicación denegado. Puedes activarlo desde el navegador.';
                } else if (error.code === 2) {
                    message = 'La ubicación no está disponible en este momento.';
                } else if (error.code === 3) {
                    message = 'La ubicación tardó demasiado en responder.';
                }

                setStatus(message, 'error');

                if (locationText) {
                    locationText.textContent = message;
                }

                if (coordsText) {
                    coordsText.textContent = 'Latitud: -- · Longitud: -- · Precisión: --';
                }

                resolve(null);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 30000
            }
        );
    });
}

async function guardarAlerta(tipo, descripcion) {
    try {
        setSaveStatus('Guardando alerta con tu ubicación...', 'warning');

        await obtenerUbicacion();

        const formData = new FormData();
        formData.append('tipo', tipo);
        formData.append('usuario_id', USER_ID);
        formData.append('nombre_usuario', USER_NAME);
        formData.append('latitud', currentLat ?? '');
        formData.append('longitud', currentLng ?? '');
        formData.append('precision_gps', currentAccuracy ?? '');
        formData.append('descripcion', descripcion || '');

        const response = await fetch('admin/ajax/guardar_alerta.php', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        let data = null;

        try {
            data = JSON.parse(text);
        } catch (e) {
            data = {
                valid: response.ok,
                message: text
            };
        }

        if (!response.ok || data.valid === false) {
            throw new Error(data.message || 'No fue posible guardar la alerta.');
        }

        setSaveStatus(data.message || 'Alerta guardada correctamente.', 'success');
        return true;

    } catch (error) {
        setSaveStatus(error.message || 'No fue posible guardar la alerta.', 'error');
        return false;
    }
}

async function guardarYLlamar(event, tipo, descripcion) {
    event.preventDefault();

    const href = event.currentTarget.getAttribute('href');

    await guardarAlerta(tipo, descripcion);

    setTimeout(() => {
        window.location.href = href;
    }, 450);
}

if (btnPolicia) {
    btnPolicia.addEventListener('click', function (event) {
        guardarYLlamar(event, 'policia', 'La usuaria activó llamada a Policía 123 desde Aliada UTS.');
    });
}

if (btnPoliciaBottom) {
    btnPoliciaBottom.addEventListener('click', function (event) {
        guardarYLlamar(event, 'policia', 'La usuaria activó llamada a Policía 123 desde el botón inferior de Aliada UTS.');
    });
}

if (btnContactoEmergencia) {
    btnContactoEmergencia.addEventListener('click', function (event) {
        guardarYLlamar(event, 'contacto_emergencia', 'La usuaria activó llamada a su contacto de emergencia desde Aliada UTS.');
    });
}

if (btnSinContacto) {
    btnSinContacto.addEventListener('click', function () {
        Swal.fire({
            icon: 'warning',
            title: 'Contacto no registrado',
            text: 'No tienes un contacto de emergencia registrado. Actualiza tus datos para usar esta opción.',
            confirmButtonColor: '#D81B9C'
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initMap();

    setTimeout(function () {
        map.invalidateSize();
    }, 300);

    obtenerUbicacion();
});
</script>

<?php include __DIR__ . '/videollamada_banner.php'; ?>
</body>
</html>