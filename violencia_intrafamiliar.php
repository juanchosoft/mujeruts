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
            --aliada-pink: #E51C9B;
            --aliada-pink-dark: #B41483;
            --aliada-purple: #8F1BA8;
            --aliada-purple-dark: #4A0D60;
            --aliada-red: #C91E2E;
            --aliada-red-dark: #9E1522;
            --aliada-green: #C3D730;

            --aliada-text: #111111;
            --aliada-muted: #4A434D;
            --aliada-white: #FFFFFF;
            --aliada-bg: #FBF8FC;
            --aliada-border: rgba(77, 31, 88, .12);

            --aliada-gradient:
                linear-gradient(
                    135deg,
                    #F64FBA 0%,
                    #E51C9B 46%,
                    #8F1BA8 100%
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
                    rgba(229, 28, 155, .10),
                    transparent 30rem
                ),
                radial-gradient(
                    circle at 100% 20%,
                    rgba(143, 27, 168, .08),
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
           MODAL DE PERMISOS
        ========================================================= */

        .permission-modal {
            position: fixed;
            inset: 0;
            z-index: 99999;

            display: grid;
            place-items: center;

            padding:
                calc(16px + var(--safe-top))
                calc(16px + var(--safe-right))
                calc(16px + var(--safe-bottom))
                calc(16px + var(--safe-left));

            background:
                rgba(34, 15, 39, .58);

            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);

            opacity: 0;
            visibility: hidden;
            pointer-events: none;

            transition:
                opacity .22s ease,
                visibility .22s ease;
        }

        .permission-modal.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .permission-card {
            width: min(100%, 620px);
            max-height: min(90dvh, 780px);

            overflow-y: auto;
            -webkit-overflow-scrolling: touch;

            padding: 22px;

            border:
                1px solid rgba(255,255,255,.90);

            border-radius: 28px;

            background:
                rgba(255,255,255,.985);

            box-shadow:
                0 28px 70px rgba(40,13,47,.24);
        }

        .permission-head {
            text-align: center;
        }

        .permission-head-icon {
            width: 60px;
            height: 60px;

            margin:
                0 auto 13px;

            display: grid;
            place-items: center;

            border-radius: 20px;

            color: #FFFFFF;

            background:
                var(--aliada-gradient);

            box-shadow:
                0 12px 28px
                rgba(216,27,156,.22);
        }

        .permission-head-icon i {
            font-size: 27px;
        }

        .permission-head h2 {
            margin: 0;

            color: #111111;

            font-size:
                clamp(22px, 3.4vw, 30px);

            line-height: 1.08;

            letter-spacing: -.6px;

            font-weight: 950;
        }

        .permission-head p {
            max-width: 500px;

            margin:
                10px auto 0;

            color: #464049;

            font-size: 13px;
            line-height: 1.48;
        }

        .permission-list {
            display: grid;

            gap: 10px;

            margin-top: 19px;
        }

        .permission-item {
            min-width: 0;

            padding: 13px 14px;

            display: grid;

            grid-template-columns:
                42px minmax(0, 1fr);

            align-items: center;

            gap: 11px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 16px;

            background: #FCFAFD;
        }

        .permission-item-icon {
            width: 42px;
            height: 42px;

            display: grid;
            place-items: center;

            border-radius: 13px;

            color: var(--aliada-pink);

            background:
                linear-gradient(
                    135deg,
                    #FDEAF7,
                    #F3EBFB
                );
        }

        .permission-item strong {
            display: block;

            color: #111111;

            font-size: 13px;

            font-weight: 900;
        }

        .permission-item span {
            display: block;

            margin-top: 3px;

            color: #514A54;

            font-size: 11px;

            line-height: 1.35;
        }

        .permission-actions {
            display: grid;

            gap: 9px;

            margin-top: 18px;
        }

        .btn-permission-main,
        .btn-permission-secondary {
            min-height: 52px;

            padding: 11px 15px;

            border-radius: 15px;

            font-weight: 900;

            cursor: pointer;
        }

        .btn-permission-main {
            border: 0;

            color: #FFFFFF;

            background:
                var(--aliada-gradient);

            box-shadow:
                0 12px 26px
                rgba(216,27,156,.20);
        }

        .btn-permission-secondary {
            border:
                1px solid
                var(--aliada-border);

            color: #111111;

            background: #FFFFFF;
        }

        /* =========================================================
           LAYOUT GENERAL
        ========================================================= */

        .mobile-app {
            width: 100%;

            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;

            padding:
                calc(16px + var(--safe-top))
                calc(18px + var(--safe-right))
                calc(20px + var(--safe-bottom))
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

        .top-card {
            position: relative;

            overflow: hidden;

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
        }

        .top-card::after {
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

        .top-card-inner {
            position: relative;

            z-index: 2;

            display: grid;

            grid-template-columns:
                minmax(0, 1fr) 280px;

            align-items: center;

            gap: 18px;

            padding: 17px 19px;
        }

        .brand-row {
            min-width: 0;

            display: grid;

            grid-template-columns:
                100px minmax(0, 1fr);

            align-items: center;

            gap: 14px;
        }

        .brand-logo {
            width: 100px;
            height: 54px;

            padding: 7px 10px;

            display: grid;
            place-items: center;

            border-radius: 17px;

            background: #FFFFFF;

            box-shadow:
                0 9px 20px
                rgba(67,12,76,.14);
        }

        .brand-logo img {
            display: block;

            max-width: 100%;
            max-height: 34px;

            object-fit: contain;
        }

        .brand-copy {
            min-width: 0;
        }

        .brand-eyebrow {
            width: fit-content;

            max-width: 100%;

            margin:
                0 0 4px;

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

        .brand-eyebrow::before {
            display: none;
        }

        .brand-title {
            margin: 7px 0 0;

            color: #FFFFFF;

            font-size:
                clamp(24px, 3.4vw, 32px);

            line-height: 1;

            letter-spacing: -1px;

            font-weight: 950;
        }

        .brand-title span {
            color: #FFFFFF;
        }

        .brand-subtitle {
            max-width: 570px;

            margin: 7px 0 0;

            color:
                rgba(255,255,255,.93);

            font-size: 11.8px;

            line-height: 1.38;

            font-weight: 650;
        }

        .top-actions {
            display: grid;

            grid-template-columns: 1fr;

            gap: 8px;

            margin: 0;
        }

        .top-btn {
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

            text-decoration: none;

            font-size: 12px;

            font-weight: 900;

            cursor: pointer;
        }

        .top-btn.white {
            color: #7C145D;

            background: #FFFFFF;

            border-color: #FFFFFF;
        }

        /* =========================================================
           HERO
        ========================================================= */

        .hero-card {
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

        .hero-badge {
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

        .hero-title {
            margin: 12px 0 0;

            color: #111111;

            font-size:
                clamp(22px, 3vw, 30px);

            line-height: 1.08;

            letter-spacing: -.6px;

            font-weight: 950;
        }

        .hero-text {
            max-width: 720px;

            margin: 8px 0 0;

            color: #433B45;

            font-size: 12.2px;

            line-height: 1.5;
        }

        .permission-status {
            display: flex;

            flex-wrap: wrap;

            gap: 8px;

            margin-top: 14px;
        }

        .permission-pill {
            min-height: 36px;

            padding: 8px 11px;

            display: inline-flex;

            flex-direction: row;

            align-items: center;
            justify-content: center;

            gap: 7px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 999px;

            color: #514A54;

            background: #F9F6FA;

            font-size: 10.5px;

            font-weight: 850;
        }

        .permission-pill i {
            font-size: 13px;

            color: var(--aliada-pink);
        }

        .permission-pill.ok {
            color: #426000;

            border-color:
                rgba(111,147,0,.22);

            background: #F5F9DF;
        }

        .permission-pill.ok i {
            color: #6E8B00;
        }

        /* =========================================================
           ACCIONES RÁPIDAS
        ========================================================= */

        .actions-card {
            margin: 0;

            padding: 20px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 24px;

            background:
                rgba(255,255,255,.98);

            box-shadow:
                0 14px 34px
                rgba(55,18,68,.07);
        }

        .actions-head {
            margin-bottom: 14px;
        }

        .section-title {
            margin: 0;

            color: #111111;

            font-size:
                clamp(21px, 2.6vw, 27px);

            line-height: 1.08;

            letter-spacing: -.5px;

            font-weight: 950;
        }

        .section-text {
            margin: 6px 0 0;

            color: #453E47;

            font-size: 11.7px;

            line-height: 1.45;
        }

        .action-grid {
            margin: 0;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;
        }

        .action-btn {
            min-width: 0;
            min-height: 116px;

            padding: 16px;

            display: block;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 20px;

            color: #111111;

            background: #FFFFFF;

            text-align: left;

            cursor: pointer;

            box-shadow:
                0 8px 22px
                rgba(55,18,68,.045);

            transition:
                transform .18s ease,
                box-shadow .18s ease,
                border-color .18s ease;
        }

        .action-btn:hover,
        .action-btn:focus-visible {
            transform:
                translateY(-2px);

            border-color:
                rgba(229,28,155,.23);

            box-shadow:
                0 14px 30px
                rgba(55,18,68,.075);
        }

        .action-btn-inner {
            min-width: 0;

            min-height: 84px;

            height: 100%;

            padding: 0;

            display: grid;

            grid-template-columns:
                48px minmax(0, 1fr);

            align-items: center;

            gap: 12px;
        }

        .btn-icon {
            width: 48px;
            height: 48px;

            display: grid;
            place-items: center;

            flex: none;

            border-radius: 15px;

            color: var(--aliada-pink);

            background:
                linear-gradient(
                    135deg,
                    #FDEAF7,
                    #F2EBFB
                );

            font-size: 21px;
        }

        .btn-title {
            margin: 0;

            color: #111111;

            font-size: 15px;

            line-height: 1.2;

            font-weight: 950;
        }

        .btn-text {
            margin-top: 5px;

            color: #514A54;

            font-size: 11px;

            line-height: 1.4;
        }

        .action-btn.recording {
            color: #FFFFFF;

            border-color: transparent;

            background:
                linear-gradient(
                    135deg,
                    #D21E45,
                    #A91431
                );

            box-shadow:
                0 12px 28px
                rgba(193,27,61,.20);
        }

        .action-btn.recording .btn-icon {
            color: #C31A3F;

            background: #FFFFFF;
        }

        .action-btn.recording .btn-title,
        .action-btn.recording .btn-text {
            color: #FFFFFF;
        }

        /* Prioridad máxima a Policía / Contacto */

        .action-btn-priority {
            min-height: 132px;

            color: #FFFFFF;

            border-color: transparent;

            box-shadow:
                0 14px 30px
                rgba(174,23,58,.16);
        }

        .action-btn.policia {
            background:
                linear-gradient(
                    135deg,
                    #C91E2E,
                    #9E1522
                );
        }

        .action-btn.emergencia {
            background:
                linear-gradient(
                    135deg,
                    #E44765,
                    #B91E44
                );
        }

        .action-btn-priority .btn-icon {
            color: #B11A3D;

            background: #FFFFFF;
        }

        .action-btn-priority .btn-title,
        .action-btn-priority .btn-text {
            color: #FFFFFF;
        }

        .status-msg {
            min-height: 0;

            margin-top: 12px;

            padding: 0;

            color: #111111;

            font-size: 11.5px;

            line-height: 1.45;
        }

        .status-msg:not(:empty) {
            padding: 11px 13px;

            border-radius: 14px;

            background: #F8F5F9;
        }

        .status-msg.success {
            color: #385200;

            background: #F4F8DF;
        }

        .status-msg.warning {
            color: #7A5600;

            background: #FFF7DE;
        }

        .status-msg.error {
            color: #9F1717;

            background: #FFF0F2;
        }

        .status-msg.info {
            color: #4A3850;

            background: #F7F1F8;
        }

        /* =========================================================
           PREVIEW
        ========================================================= */

        .preview-card {
            margin: 0;

            padding: 20px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 24px;

            background: #FFFFFF;

            box-shadow:
                0 14px 34px
                rgba(55,18,68,.07);
        }

        .preview-head {
            margin-bottom: 14px;
        }

        .preview-grid {
            margin: 0;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0,1fr));

            gap: 12px;
        }

        .preview-box {
            min-width: 0;

            padding: 14px;

            border:
                1px solid
                var(--aliada-border);

            border-radius: 18px;

            background: #FBF9FC;
        }

        .preview-box h3 {
            margin:
                0 0 10px;

            display: flex;

            align-items: center;

            gap: 8px;

            color: #111111;

            font-size: 13px;

            font-weight: 900;
        }

        .preview-area {
            min-height: 110px;

            padding: 14px;

            display: flex;

            align-items: center;
            justify-content: center;

            border:
                1px dashed
                rgba(77,31,88,.14);

            border-radius: 14px;

            color: #514A54;

            background: #FFFFFF;

            text-align: center;

            font-size: 11px;

            line-height: 1.4;
        }

        .preview-box audio,
        .preview-box video,
        .preview-box img {
            width: 100%;
            max-width: 100%;

            border-radius: 14px;

            background: #111111;
        }

        .preview-box img {
            display: block;

            object-fit: contain;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .footer-mobile {
            padding:
                6px 10px 2px;

            text-align: center;
        }

        .footer-mobile p {
            margin: 0;

            color: #403842;

            font-size: 10.5px;

            line-height: 1.5;
        }

        .footer-mobile strong {
            color: #111111;
        }

        /* =========================================================
           iPAD / TABLET VERTICAL
        ========================================================= */

        @media
        (min-width: 700px)
        and (orientation: portrait) {

            .mobile-app {
                padding:
                    calc(20px + var(--safe-top))
                    calc(22px + var(--safe-right))
                    calc(22px + var(--safe-bottom))
                    calc(22px + var(--safe-left));
            }

            .mobile-shell {
                width: min(100%, 900px);

                gap: 14px;
            }

            .top-card-inner {
                grid-template-columns:
                    minmax(0, 1fr) 220px;

                padding: 16px 18px;
            }

            .brand-row {
                grid-template-columns:
                    94px minmax(0, 1fr);

                gap: 13px;
            }

            .brand-logo {
                width: 94px;
                height: 51px;
            }

            .brand-title {
                font-size:
                    clamp(25px, 3.4vw, 31px);
            }

            .brand-subtitle {
                font-size: 11.5px;
            }

            .top-btn {
                min-height: 44px;

                font-size: 11.5px;
            }

            .hero-card,
            .actions-card,
            .preview-card {
                padding:
                    20px 22px;
            }

            .action-grid {
                grid-template-columns:
                    repeat(2, minmax(0,1fr));

                gap: 14px;
            }

            .action-btn {
                min-height: 124px;

                padding: 17px;
            }

            .action-btn-priority {
                min-height: 138px;
            }

            .preview-grid {
                grid-template-columns:
                    repeat(2, minmax(0,1fr));
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

            .top-card-inner {
                grid-template-columns:
                    minmax(0,1fr) 300px;
            }

            .top-actions {
                grid-template-columns:
                    repeat(3, 1fr);
            }

            .action-grid {
                grid-template-columns:
                    repeat(3, minmax(0,1fr));
            }

            .action-btn {
                min-height: 132px;
            }

            .action-btn-priority {
                min-height: 146px;
            }

            .preview-grid {
                grid-template-columns:
                    repeat(2, minmax(0,1fr));
            }
        }

        /* =========================================================
           CELULAR / iPHONE
        ========================================================= */

        @media
        (max-width: 699px) {

            .mobile-app {
                padding:
                    calc(8px + var(--safe-top))
                    calc(8px + var(--safe-right))
                    calc(14px + var(--safe-bottom))
                    calc(8px + var(--safe-left));
            }

            .mobile-shell {
                width: 100%;

                gap: 9px;
            }

            .top-card {
                border-radius: 20px;
            }

            .top-card-inner {
                grid-template-columns: 1fr;

                gap: 10px;

                padding: 11px 12px;
            }

            .brand-row {
                grid-template-columns:
                    72px minmax(0,1fr);

                gap: 9px;
            }

            .brand-logo {
                width: 72px;
                height: 39px;

                padding: 5px 7px;

                border-radius: 12px;
            }

            .brand-logo img {
                max-height: 23px;
            }

            .brand-eyebrow {
                padding: 4px 7px;

                margin-bottom: 3px;

                font-size: 7px;

                white-space: normal;
            }

            .brand-title {
                margin-top: 5px;

                font-size:
                    clamp(18px, 5.8vw, 23px);
            }

            .brand-subtitle {
                margin-top: 5px;

                font-size: 10px;

                line-height: 1.3;
            }

            .top-actions {
                grid-template-columns:
                    repeat(3, 1fr);

                gap: 7px;
            }

            .top-btn {
                min-height: 42px;

                padding: 7px 5px;

                flex-direction: column;

                gap: 3px;

                border-radius: 12px;

                font-size: 9.4px;
            }

            .top-btn i {
                font-size: 15px;
            }

            .hero-card {
                padding: 14px;

                border-radius: 18px;
            }

            .hero-badge {
                padding: 6px 9px;

                font-size: 9.3px;
            }

            .hero-title {
                margin-top: 9px;

                font-size: 18px;
            }

            .hero-text {
                font-size: 10.7px;
            }

            .permission-status {
                gap: 6px;

                margin-top: 11px;
            }

            .permission-pill {
                min-height: 33px;

                padding: 7px 9px;

                font-size: 9.5px;
            }

            .actions-card {
                padding: 14px;

                border-radius: 19px;
            }

            .section-title {
                font-size: 19px;
            }

            .section-text {
                font-size: 10.5px;
            }

            .action-grid {
                grid-template-columns: 1fr;

                gap: 9px;
            }

            .action-btn {
                min-height: 0;

                padding: 13px;

                border-radius: 17px;
            }

            .action-btn-inner {
                min-height: 0;

                grid-template-columns:
                    43px minmax(0,1fr);

                gap: 10px;
            }

            .btn-icon {
                width: 43px;
                height: 43px;

                border-radius: 13px;

                font-size: 18px;
            }

            .btn-title {
                font-size: 13.5px;
            }

            .btn-text {
                margin-top: 3px;

                font-size: 10.2px;
            }

            .action-btn-priority {
                min-height: 72px;
            }

            .preview-card {
                padding: 14px;

                border-radius: 19px;
            }

            .preview-grid {
                grid-template-columns: 1fr;

                gap: 9px;
            }

            .preview-box {
                padding: 12px;

                border-radius: 16px;
            }

            .preview-area {
                min-height: 90px;
            }

            .footer-mobile {
                font-size: 9.8px;
            }

            .permission-card {
                width: min(100%, 520px);

                max-height:
                    calc(
                        100dvh
                        - 18px
                        - var(--safe-top)
                        - var(--safe-bottom)
                    );

                padding: 18px;

                border-radius: 22px;
            }

            .permission-head-icon {
                width: 54px;
                height: 54px;

                border-radius: 17px;
            }

            .permission-head h2 {
                font-size: 22px;
            }

            .permission-head p {
                font-size: 11.5px;
            }

            .permission-item {
                padding: 11px 12px;

                grid-template-columns:
                    39px minmax(0,1fr);
            }

            .permission-item-icon {
                width: 39px;
                height: 39px;

                border-radius: 12px;
            }

            .btn-permission-main,
            .btn-permission-secondary {
                min-height: 48px;

                font-size: 12px;
            }
        }

        /* iPhone pequeño */
        @media
        (max-width: 390px) {

            .brand-row {
                grid-template-columns:
                    66px minmax(0,1fr);
            }

            .brand-logo {
                width: 66px;
                height: 36px;
            }

            .brand-title {
                font-size: 17.5px;
            }

            .top-btn {
                font-size: 8.8px;
            }

            .permission-pill {
                flex:
                    1 1 calc(50% - 6px);
            }
        }

        /* iPhone horizontal */
        @media
        (max-height: 520px)
        and (orientation: landscape)
        and (max-width: 932px) {

            .mobile-app {
                padding-top:
                    calc(7px + var(--safe-top));
            }

            .top-card-inner {
                grid-template-columns:
                    minmax(0,1fr) 230px;

                padding: 9px 12px;
            }

            .brand-row {
                grid-template-columns:
                    68px minmax(0,1fr);
            }

            .brand-logo {
                width: 68px;
                height: 37px;
            }

            .top-actions {
                grid-template-columns: 1fr;
            }

            .top-btn {
                min-height: 36px;

                flex-direction: row;

                font-size: 9px;
            }

            .action-grid {
                grid-template-columns:
                    repeat(2, minmax(0,1fr));
            }

            .permission-card {
                max-height:
                    calc(100dvh - 12px);

                padding: 14px 18px;
            }

            .permission-list {
                grid-template-columns:
                    repeat(3, minmax(0,1fr));
            }

            .permission-item {
                grid-template-columns: 1fr;

                text-align: center;
            }

            .permission-item-icon {
                margin-inline: auto;
            }
        }

        @supports (-webkit-touch-callout: none) {
            body,
            .mobile-app {
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
                Acompañamiento, orientación y protección universitaria.<br>
                Desarrollado por <strong>SpiderSoftware S.A.S.</strong>
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