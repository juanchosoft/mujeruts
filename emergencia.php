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
            --aliada-pink: #E51C9B;
            --aliada-pink-dark: #B41483;
            --aliada-purple: #8F1BA8;
            --aliada-purple-dark: #4A0D60;
            --aliada-red: #C91E2E;
            --aliada-red-dark: #9E1522;
            --aliada-blue: #3B82F6;
            --aliada-blue-dark: #4F46E5;

            --aliada-text: #111111;
            --aliada-muted: #4B444E;
            --aliada-white: #FFFFFF;
            --aliada-bg: #FBF8FC;
            --aliada-border: rgba(77,31,88,.12);

            --aliada-gradient:
                linear-gradient(
                    135deg,
                    #F64FBA 0%,
                    #E51C9B 46%,
                    #8F1BA8 100%
                );

            --aliada-danger:
                linear-gradient(
                    135deg,
                    #D6243A 0%,
                    #B81631 100%
                );

            --aliada-emergency:
                linear-gradient(
                    135deg,
                    #3B82F6 0%,
                    #4F46E5 100%
                );

            --safe-top: env(safe-area-inset-top, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
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
                radial-gradient(
                    circle at 10% -4%,
                    rgba(229,28,155,.10),
                    transparent 30rem
                ),
                radial-gradient(
                    circle at 100% 20%,
                    rgba(143,27,168,.08),
                    transparent 30rem
                ),
                linear-gradient(
                    180deg,
                    #FBF7FC 0%,
                    #FFFFFF 58%,
                    #FAF7FB 100%
                );

            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        a {
            touch-action: manipulation;
        }

        button {
            font: inherit;
        }

        /* =========================================================
           LAYOUT GENERAL
        ========================================================= */

        .app-mobile {
            width: 100%;

            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;

            padding:
                calc(16px + var(--safe-top))
                calc(18px + var(--safe-right))
                calc(98px + var(--safe-bottom))
                calc(18px + var(--safe-left));
        }

        .mobile-shell {
            width: min(100%, 1080px);
            margin-inline: auto;
            display: grid;
            gap: 13px;
        }

        /* =========================================================
           CABECERA
        ========================================================= */

        .topbar-mobile {
            position: relative;
            z-index: 20;

            overflow: hidden;

            padding: 0;

            border-radius: 27px;

            background:
                radial-gradient(
                    circle at 100% 0%,
                    rgba(255,255,255,.16),
                    transparent 12rem
                ),
                var(--aliada-gradient);

            box-shadow:
                0 18px 42px
                rgba(116,21,132,.18);

            -webkit-backdrop-filter: none;
            backdrop-filter: none;
        }

        .topbar-mobile::after {
            content: "";

            position: absolute;

            width: 190px;
            height: 190px;

            right: -82px;
            top: -105px;

            border-radius: 50%;

            background:
                rgba(255,255,255,.08);

            pointer-events: none;
        }

        .topbar-mobile-inner {
            position: relative;
            z-index: 2;

            display: grid;

            grid-template-columns:
                minmax(0,1fr) 240px;

            align-items: center;

            gap: 18px;

            padding: 17px 19px;

            border-radius: 0;

            background: transparent;

            box-shadow: none;
        }

        .brand-mobile {
            min-width: 0;

            display: grid;

            grid-template-columns:
                100px minmax(0,1fr);

            align-items: center;

            gap: 14px;
        }

        .brand-mobile-logo {
            width: 100px;
            height: 54px;

            min-width: 0;

            padding: 7px 10px;

            display: grid;
            place-items: center;

            border-radius: 17px;

            background: #FFFFFF;

            box-shadow:
                0 9px 20px
                rgba(67,12,76,.14);
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

            border:
                1px solid
                rgba(255,255,255,.30);

            border-radius: 999px;

            background:
                rgba(255,255,255,.15);

            color: #FFFFFF;

            font-size: 8.4px;
            line-height: 1.15;

            font-weight: 900;

            letter-spacing: .04em;

            text-transform: uppercase;
        }

        .brand-mobile-eyebrow::before {
            display: none;
        }

        .brand-mobile-title {
            margin: 7px 0 0;

            color: #FFFFFF;

            font-size:
                clamp(24px, 3.4vw, 32px);

            line-height: 1;

            letter-spacing: -1px;

            font-weight: 950;
        }

        .brand-mobile-title span {
            color: #FFFFFF;
        }

        .brand-mobile-subtitle {
            max-width: 570px;

            margin: 7px 0 0;

            color:
                rgba(255,255,255,.93);

            font-size: 11.8px;

            line-height: 1.38;

            font-weight: 650;
        }

        .topbar-mobile-actions {
            display: grid;

            grid-template-columns: 1fr;

            gap: 8px;

            margin: 0;
        }

        .btn-mobile-top {
            min-height: 46px;

            padding: 10px 13px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            border:
                1px solid
                rgba(255,255,255,.30);

            border-radius: 14px;

            color: #FFFFFF;

            background:
                rgba(255,255,255,.13);

            font-size: 12px;

            font-weight: 900;

            cursor: pointer;
        }

        .btn-mobile-top.white {
            color: #7C145D;

            background: #FFFFFF;

            border-color: #FFFFFF;
        }

        /* =========================================================
           TARJETAS BASE
        ========================================================= */

        .summary-card,
        .location-card,
        .actions-card,
        .footer-card {
            margin: 0;

            padding: 18px 20px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 22px;

            background:
                rgba(255,255,255,.97);

            box-shadow:
                0 10px 28px
                rgba(57,20,65,.055);
        }

        .summary-badge,
        .location-badge {
            width: fit-content;

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 7px 10px;

            margin: 0;

            border-radius: 999px;

            color: #A41971;

            background: #FCEAF6;

            font-size: 10px;

            font-weight: 900;
        }

        .summary-title,
        .location-title,
        .section-title {
            margin: 12px 0 0;

            color: #111111;

            font-size:
                clamp(21px, 2.8vw, 28px);

            line-height: 1.08;

            letter-spacing: -.55px;

            font-weight: 950;
        }

        .summary-text,
        .location-subtitle,
        .section-text {
            max-width: 760px;

            margin: 8px 0 0;

            color: #433B45;

            font-size: 12px;

            line-height: 1.5;
        }

        .status-mini {
            margin-top: 12px;

            padding: 11px 13px;

            border:
                1px solid
                rgba(216,27,156,.14);

            border-radius: 14px;

            color: #4A3850;

            background: #F7F1F8;

            font-size: 11.5px;

            line-height: 1.4;

            font-weight: 850;

            text-align: left;
        }

        .status-mini.success {
            color: #385200;

            border-color:
                rgba(111,147,0,.18);

            background: #F4F8DF;
        }

        .status-mini.warning {
            color: #7A5600;

            border-color:
                rgba(245,158,11,.18);

            background: #FFF7DE;
        }

        .status-mini.error {
            color: #9F1717;

            border-color:
                rgba(201,30,46,.18);

            background: #FFF0F2;
        }

        /* =========================================================
           MAPA / UBICACIÓN
        ========================================================= */

        .map-wrap {
            width: 100%;

            margin-top: 14px;

            padding: 6px;

            overflow: hidden;

            border:
                1px solid
                rgba(216,27,156,.14);

            border-radius: 20px;

            background: #FFFFFF;

            box-shadow:
                0 12px 28px
                rgba(55,18,68,.07);
        }

        #map {
            width: 100%;

            height: 390px;
            min-height: 390px;

            overflow: hidden;

            border-radius: 16px;

            background: #FCE7F3;
        }

        .leaflet-container {
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .location-info {
            margin-top: 12px;
        }

        .location-box {
            padding: 13px 14px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 16px;

            background: #FBF9FC;
        }

        .location-label {
            display: block;

            margin-bottom: 6px;

            color: #6B5D6F;

            font-size: 9.5px;

            font-weight: 950;

            letter-spacing: .07em;

            text-transform: uppercase;
        }

        .location-value {
            color: #111111;

            font-size: 12.5px;

            line-height: 1.42;

            font-weight: 800;

            overflow-wrap: anywhere;
        }

        .coords-box {
            margin-top: 7px;

            color: #514A54;

            font-size: 10.8px;

            line-height: 1.4;

            font-weight: 650;

            overflow-wrap: anywhere;
        }

        /* =========================================================
           ACCIONES
        ========================================================= */

        .actions-card {
            padding: 20px;
        }

        .action-grid {
            margin-top: 14px;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;
        }

        .action-btn {
            width: 100%;

            min-height: 116px;

            padding: 16px;

            display: grid;

            grid-template-columns:
                52px minmax(0, 1fr) 32px;

            align-items: center;

            gap: 12px;

            position: relative;

            overflow: hidden;

            border: 0;

            border-radius: 20px;

            color: #FFFFFF;

            text-align: left;

            cursor: pointer;

            box-shadow:
                0 14px 30px
                rgba(44,0,52,.16);

            transition:
                transform .18s ease,
                box-shadow .18s ease;
        }

        .action-btn:hover,
        .action-btn:focus-visible {
            transform:
                translateY(-2px);

            box-shadow:
                0 18px 36px
                rgba(44,0,52,.20);
        }

        .action-btn::before {
            content: "";

            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    180deg,
                    rgba(255,255,255,.16),
                    transparent 55%
                );

            pointer-events: none;
        }

        .action-btn::after {
            content: "054";

            position: relative;

            z-index: 2;

            width: 32px;
            height: 32px;

            display: grid;
            place-items: center;

            border-radius: 999px;

            color: #B21B56;

            background: #FFFFFF;

            font-family:
                "Font Awesome 6 Free";

            font-size: 12px;

            font-weight: 900;
        }

        .action-btn.police {
            background:
                var(--aliada-danger);
        }

        .action-btn.emergency {
            background:
                var(--aliada-emergency);
        }

        .action-btn.evidence {
            grid-column:
                1 / -1;

            color: #FFFFFF;

            background:
                var(--aliada-gradient);
        }

        .action-btn.disabled {
            opacity: .72;

            filter: grayscale(.08);
        }

        .action-icon {
            width: 52px;
            height: 52px;

            min-width: 52px;

            display: grid;
            place-items: center;

            position: relative;

            z-index: 2;

            border:
                1px solid
                rgba(255,255,255,.20);

            border-radius: 17px;

            color: #FFFFFF;

            background:
                rgba(255,255,255,.18);

            font-size: 20px;
        }

        .action-copy {
            min-width: 0;

            position: relative;

            z-index: 2;
        }

        .action-copy strong {
            display: block;

            color: #FFFFFF;

            font-size: 15.5px;

            line-height: 1.15;

            font-weight: 950;
        }

        .action-copy span {
            display: block;

            margin-top: 5px;

            color:
                rgba(255,255,255,.88);

            font-size: 11px;

            line-height: 1.35;

            font-weight: 650;
        }

        .quick-note {
            margin-top: 12px;

            padding: 11px 13px;

            border:
                1px solid
                rgba(216,27,156,.14);

            border-radius: 14px;

            color: #4A3850;

            background: #F7F1F8;

            font-size: 11px;

            line-height: 1.45;

            font-weight: 700;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .footer-card {
            padding: 15px 18px;

            text-align: center;
        }

        .footer-title {
            margin: 0;

            color: #111111;

            font-size: 14px;

            font-weight: 950;
        }

        .footer-text,
        .footer-copy {
            margin: 6px 0 0;

            color: #514A54;

            font-size: 10.5px;

            line-height: 1.45;
        }

        .footer-copy strong {
            color: #111111;
        }

        /* =========================================================
           BARRA INFERIOR
        ========================================================= */

        .bottom-nav {
            position: fixed;

            left: 50%;

            bottom:
                calc(
                    8px
                    + var(--safe-bottom)
                );

            z-index: 60;

            width:
                min(
                    calc(
                        100%
                        - 20px
                        - var(--safe-left)
                        - var(--safe-right)
                    ),
                    660px
                );

            min-height: 66px;

            padding: 7px;

            display: grid;

            grid-template-columns:
                1fr 1.25fr 1fr;

            gap: 7px;

            transform:
                translateX(-50%);

            border:
                1px solid
                rgba(255,255,255,.92);

            border-radius: 22px;

            background:
                rgba(255,255,255,.94);

            -webkit-backdrop-filter:
                blur(20px);

            backdrop-filter:
                blur(20px);

            box-shadow:
                0 16px 38px
                rgba(55,18,68,.15);
        }

        .bottom-btn {
            min-width: 0;
            min-height: 50px;

            padding: 7px 9px;

            display: flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            border: 0;

            border-radius: 15px;

            color: #111111;

            background: transparent;

            text-align: center;

            font-size: 10.5px;

            font-weight: 900;

            cursor: pointer;
        }

        .bottom-btn.primary {
            color: #FFFFFF;

            background:
                var(--aliada-danger);

            box-shadow:
                0 9px 21px
                rgba(201,30,46,.18);
        }

        /* =========================================================
           iPAD / TABLET VERTICAL
        ========================================================= */

        @media
        (min-width: 700px)
        and (orientation: portrait) {

            .app-mobile {
                padding:
                    calc(20px + var(--safe-top))
                    calc(22px + var(--safe-right))
                    calc(106px + var(--safe-bottom))
                    calc(22px + var(--safe-left));
            }

            .mobile-shell {
                width: min(100%, 900px);

                gap: 14px;
            }

            .topbar-mobile-inner {
                grid-template-columns:
                    minmax(0, 1fr) 210px;

                padding: 16px 18px;
            }

            .brand-mobile {
                grid-template-columns:
                    94px minmax(0,1fr);

                gap: 13px;
            }

            .brand-mobile-logo {
                width: 94px;
                height: 51px;
            }

            .brand-mobile-title {
                font-size:
                    clamp(25px, 3.4vw, 31px);
            }

            .brand-mobile-subtitle {
                font-size: 11.5px;
            }

            .btn-mobile-top {
                min-height: 44px;

                font-size: 11.5px;
            }

            .summary-card,
            .location-card,
            .actions-card,
            .footer-card {
                padding:
                    20px 22px;
            }

            #map {
                height: 440px;
                min-height: 440px;
            }

            .action-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 14px;
            }

            .action-btn {
                min-height: 126px;
            }

            .bottom-nav {
                width:
                    min(
                        calc(100% - 36px),
                        640px
                    );
            }
        }

        /* =========================================================
           iPAD / TABLET HORIZONTAL
        ========================================================= */

        @media
        (min-width: 900px)
        and (orientation: landscape) {

            .mobile-shell {
                width: min(100%, 1120px);
            }

            .topbar-mobile-inner {
                grid-template-columns:
                    minmax(0,1fr) 280px;
            }

            .topbar-mobile-actions {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .location-card {
                display: grid;

                grid-template-columns:
                    minmax(0, 1.45fr)
                    minmax(280px, .55fr);

                grid-template-areas:
                    "badge info"
                    "title info"
                    "subtitle info"
                    "map info";

                column-gap: 18px;
            }

            .location-badge {
                grid-area: badge;
            }

            .location-title {
                grid-area: title;
            }

            .location-subtitle {
                grid-area: subtitle;
            }

            .map-wrap {
                grid-area: map;
            }

            .location-info {
                grid-area: info;

                margin-top: 0;

                display: flex;

                align-items: stretch;
            }

            .location-box {
                width: 100%;

                display: flex;

                flex-direction: column;

                justify-content: center;
            }

            #map {
                height: 430px;
                min-height: 430px;
            }

            .action-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

            .action-btn.evidence {
                grid-column: auto;
            }

            .action-btn {
                min-height: 138px;

                grid-template-columns:
                    48px minmax(0,1fr);

                align-content: center;
            }

            .action-btn::after {
                display: none;
            }

            .bottom-nav {
                width:
                    min(
                        calc(100% - 40px),
                        680px
                    );
            }
        }

        /* =========================================================
           CELULAR / iPHONE
        ========================================================= */

        @media
        (max-width: 699px) {

            .app-mobile {
                padding:
                    calc(8px + var(--safe-top))
                    calc(8px + var(--safe-right))
                    calc(94px + var(--safe-bottom))
                    calc(8px + var(--safe-left));
            }

            .mobile-shell {
                width: 100%;

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
                grid-template-columns:
                    72px minmax(0,1fr);

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

                font-size:
                    clamp(18px, 5.8vw, 23px);
            }

            .brand-mobile-subtitle {
                margin-top: 5px;

                font-size: 10px;

                line-height: 1.3;
            }

            .topbar-mobile-actions {
                grid-template-columns:
                    repeat(2, 1fr);

                gap: 8px;
            }

            .btn-mobile-top {
                min-height: 42px;

                padding: 8px 9px;

                border-radius: 12px;

                font-size: 10.8px;
            }

            .summary-card,
            .location-card,
            .actions-card,
            .footer-card {
                padding: 14px;

                border-radius: 18px;
            }

            .summary-badge,
            .location-badge {
                padding: 6px 9px;

                font-size: 9.3px;
            }

            .summary-title,
            .location-title,
            .section-title {
                margin-top: 9px;

                font-size: 18px;
            }

            .summary-text,
            .location-subtitle,
            .section-text {
                font-size: 10.7px;
            }

            .status-mini {
                margin-top: 10px;

                padding: 10px;

                border-radius: 12px;

                font-size: 10.5px;
            }

            .map-wrap {
                margin-top: 11px;

                padding: 4px;

                border-radius: 16px;
            }

            #map {
                height: 300px;
                min-height: 300px;

                border-radius: 13px;
            }

            .location-box {
                padding: 11px 12px;

                border-radius: 14px;
            }

            .location-value {
                font-size: 11.5px;
            }

            .coords-box {
                font-size: 10px;
            }

            .actions-card {
                padding: 14px;
            }

            .action-grid {
                grid-template-columns: 1fr;

                gap: 9px;
            }

            .action-btn {
                min-height: 76px;

                padding: 12px;

                grid-template-columns:
                    44px minmax(0,1fr) 30px;

                gap: 10px;

                border-radius: 17px;
            }

            .action-btn.evidence {
                grid-column: auto;
            }

            .action-icon {
                width: 44px;
                height: 44px;

                min-width: 44px;

                border-radius: 13px;

                font-size: 17px;
            }

            .action-copy strong {
                font-size: 13.5px;
            }

            .action-copy span {
                margin-top: 3px;

                font-size: 10.2px;
            }

            .action-btn::after {
                width: 30px;
                height: 30px;
            }

            .quick-note {
                font-size: 10.5px;
            }

            .footer-title {
                font-size: 13px;
            }

            .footer-text,
            .footer-copy {
                font-size: 9.8px;
            }

            .bottom-nav {
                width:
                    calc(
                        100%
                        - 14px
                        - var(--safe-left)
                        - var(--safe-right)
                    );

                min-height: 63px;

                bottom:
                    calc(
                        6px
                        + var(--safe-bottom)
                    );

                padding: 6px;

                border-radius: 19px;
            }

            .bottom-btn {
                min-height: 49px;

                flex-direction: column;

                gap: 3px;

                padding: 5px;

                border-radius: 13px;

                font-size: 9.2px;
            }

            .bottom-btn i {
                font-size: 17px;
            }
        }

        /* iPhone pequeño */
        @media
        (max-width: 390px) {

            .brand-mobile {
                grid-template-columns:
                    66px minmax(0,1fr);
            }

            .brand-mobile-logo {
                width: 66px;
                height: 36px;
            }

            .brand-mobile-title {
                font-size: 17.5px;
            }

            #map {
                height: 270px;
                min-height: 270px;
            }
        }

        /* iPhone horizontal */
        @media
        (max-height: 520px)
        and (orientation: landscape)
        and (max-width: 932px) {

            .app-mobile {
                padding-top:
                    calc(
                        7px
                        + var(--safe-top)
                    );
            }

            .topbar-mobile-inner {
                grid-template-columns:
                    minmax(0,1fr) 220px;

                padding: 9px 12px;
            }

            .brand-mobile {
                grid-template-columns:
                    68px minmax(0,1fr);
            }

            .brand-mobile-logo {
                width: 68px;
                height: 37px;
            }

            .topbar-mobile-actions {
                grid-template-columns: 1fr;
            }

            .btn-mobile-top {
                min-height: 36px;
            }

            .action-grid {
                grid-template-columns:
                    repeat(2, minmax(0,1fr));
            }

            .action-btn.evidence {
                grid-column: 1 / -1;
            }

            #map {
                height: 260px;
                min-height: 260px;
            }
        }

        @supports
        (-webkit-touch-callout: none) {

            body,
            .app-mobile {
                min-height:
                    -webkit-fill-available;
            }
        }

        @media
        (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior:
                    auto !important;

                animation-duration:
                    .001ms !important;

                animation-iteration-count:
                    1 !important;

                transition-duration:
                    .001ms !important;
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
                © <?php echo $currentYear; ?> <strong>Aliada UTS</strong><br>
                Desarrollado por <strong>SpiderSoftware S.A.S.</strong>
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

</body>
</html>