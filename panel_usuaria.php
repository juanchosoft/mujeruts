<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

$pageTitle       = 'Aliada UTS | Acompañamiento y Protección';
$pageDescription = 'Plataforma Aliada UTS para orientar, acompañar y proteger a las mujeres de la comunidad universitaria.';
$currentYear     = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <meta name="robots" content="index,follow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">
    <!-- Vista autocontenida: el CSS se define aquí para evitar conflictos en iPad/iPhone. -->

    <style>
        :root {
            --aliada-pink: #E51C9B;
            --aliada-pink-soft: #FFF0F8;
            --aliada-purple: #901CA8;
            --aliada-text: #111111;
            --aliada-muted: #514855;
            --aliada-border: rgba(69, 31, 76, .11);
            --aliada-white: #FFFFFF;
            --aliada-bg: #FBF8FC;
            --aliada-gradient: linear-gradient(135deg, #F64FBA 0%, #E21A9B 48%, #941BA8 100%);
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
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
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
                radial-gradient(circle at 100% 18%, rgba(144, 28, 168, .08), transparent 32rem),
                linear-gradient(180deg, #FBF7FC 0%, #FFFFFF 56%, #FAF7FB 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a {
            color: inherit;
        }

        a,
        button {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        button {
            font: inherit;
        }

        .icon-svg {
            display: block;
            width: 21px;
            height: 21px;
            flex: 0 0 auto;
            pointer-events: none;
        }

        /* =========================================================
           LOADER
        ========================================================= */
        .loader-bg {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .97);
            transition: opacity .24s ease, visibility .24s ease;
        }

        .loader-bg.hide {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .loader-track {
            width: min(230px, 58vw);
            height: 7px;
            overflow: hidden;
            border-radius: 999px;
            background: #EFE4F1;
        }

        .loader-fill {
            width: 44%;
            height: 100%;
            border-radius: inherit;
            background: var(--aliada-gradient);
            animation: aliadaLoader 1s ease-in-out infinite alternate;
        }

        @keyframes aliadaLoader {
            from { transform: translateX(-18%); }
            to { transform: translateX(148%); }
        }

        /* =========================================================
           LAYOUT GENERAL
           El diseño base es TABLET-FIRST.
        ========================================================= */
        .app-mobile {
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;
            padding:
                calc(22px + var(--safe-top))
                calc(24px + var(--safe-right))
                calc(112px + var(--safe-bottom))
                calc(24px + var(--safe-left));
        }

        .mobile-shell {
            width: min(100%, 1040px);
            margin-inline: auto;
        }

        /* =========================================================
           HERO COMPACTO PARA iPAD/TABLET
           En tablet los botones se ubican a la derecha para reducir
           la altura del encabezado de forma real.
        ========================================================= */
        .hero-card {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            background:
                radial-gradient(circle at 100% 0%, rgba(255,255,255,.16), transparent 13rem),
                var(--aliada-gradient);
            box-shadow: 0 20px 48px rgba(112, 22, 129, .18);
        }

        .hero-card::before {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            right: -82px;
            top: -112px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 250px;
            grid-template-areas:
                "heroTop heroActions"
                "heroText heroActions";
            align-items: center;
            gap: 8px 22px;
            padding: 18px 20px;
        }

        .hero-top {
            grid-area: heroTop;
            min-width: 0;
            display: grid;
            grid-template-columns: 106px minmax(0, 1fr);
            align-items: center;
            gap: 15px;
        }

        .logo-box {
            width: 106px;
            height: 56px;
            padding: 7px 10px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            background: #FFFFFF;
            box-shadow: 0 9px 21px rgba(62, 10, 71, .15);
        }

        .logo-box img {
            display: block;
            max-width: 100%;
            max-height: 35px;
            object-fit: contain;
        }

        .hero-copy {
            min-width: 0;
        }

        .eyebrow {
            width: fit-content;
            max-width: 100%;
            margin: 0 0 4px;
            padding: 5px 9px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,.34);
            border-radius: 999px;
            background: rgba(255,255,255,.27);
            color: #111;
            font-size: 8.7px;
            line-height: 1.15;
            font-weight: 900;
            letter-spacing: .035em;
            text-transform: uppercase;
        }

        .hero-title {
            margin: 7px 0 0;
            max-width: 500px;
            color: #111;
            font-size: clamp(24px, 3.3vw, 32px);
            line-height: .98;
            letter-spacing: -1px;
            font-weight: 950;
        }

        .hero-title span {
            color: inherit;
        }

        .hero-text {
            grid-area: heroText;
            max-width: 610px;
            margin: 1px 0 0 121px;
            color: #111;
            font-size: 12px;
            line-height: 1.42;
        }

        .quick-actions {
            grid-area: heroActions;
            display: grid;
            grid-template-columns: 1fr;
            gap: 9px;
            width: 100%;
            margin: 0;
        }

        .quick-btn {
            width: 100%;
            min-height: 50px;
            padding: 11px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid rgba(255,255,255,.38);
            border-radius: 15px;
            color: #111;
            background: rgba(255,255,255,.22);
            box-shadow: 0 10px 22px rgba(63, 12, 72, .11);
            text-decoration: none;
            font-size: 13px;
            font-weight: 950;
            cursor: pointer;
            transition: transform .17s ease, box-shadow .17s ease, background .17s ease;
        }

        .quick-btn:hover,
        .quick-btn:focus-visible {
            transform: translateY(-1px);
            background: rgba(255,255,255,.34);
            box-shadow: 0 14px 27px rgba(63,12,72,.15);
        }

        .quick-btn.white {
            color: #791557;
            background: #FFFFFF;
            border-color: #FFFFFF;
        }

        .quick-btn .icon-svg {
            width: 20px;
            height: 20px;
            stroke-width: 2.4;
        }

        /* =========================================================
           ALERTA INMEDIATA
        ========================================================= */
        .safety-strip {
            position: relative;
            z-index: 5;
            margin: 12px 10px 0;
            padding: 14px 15px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 13px;
            border: 1px solid rgba(216, 38, 91, .14);
            border-radius: 19px;
            background: rgba(255,255,255,.98);
            box-shadow: 0 13px 31px rgba(67,20,77,.07);
            text-decoration: none;
        }

        .safety-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: #C41C4D;
            background: linear-gradient(135deg, #FFF0F3, #FFE4ED);
        }

        .safety-copy {
            min-width: 0;
        }

        .safety-copy strong {
            display: block;
            color: #111;
            font-size: 13px;
            line-height: 1.2;
        }

        .safety-copy span {
            display: block;
            margin-top: 4px;
            color: #4B414D;
            font-size: 11.2px;
            line-height: 1.35;
        }

        .safety-open {
            min-width: 68px;
            padding: 9px 12px;
            border-radius: 12px;
            color: #A51440;
            background: #FFF1F6;
            text-align: center;
            font-size: 10.5px;
            font-weight: 900;
            white-space: nowrap;
        }

        /* =========================================================
           INTRO
        ========================================================= */
        .intro-panel {
            margin-top: 18px;
            padding: 18px 20px;
            display: grid;
            grid-template-columns: auto minmax(0,1fr);
            align-items: start;
            gap: 13px;
            border: 1px solid var(--aliada-border);
            border-radius: 21px;
            background: rgba(255,255,255,.94);
            box-shadow: 0 9px 27px rgba(55,18,68,.045);
        }

        .intro-symbol {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: var(--aliada-pink);
            background: linear-gradient(135deg, #FDEBF7, #F2EAFB);
        }

        .intro-title {
            margin: 0;
            color: #111;
            font-size: clamp(19px, 2.4vw, 24px);
            line-height: 1.14;
            letter-spacing: -.45px;
            font-weight: 950;
        }

        .intro-text {
            max-width: 760px;
            margin: 6px 0 0;
            color: #403742;
            font-size: 12px;
            line-height: 1.5;
        }

        /* =========================================================
           MÓDULOS
        ========================================================= */
        .section-title {
            margin: 23px 2px 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 13px;
        }

        .section-title h2 {
            margin: 0;
            color: #111;
            font-size: clamp(21px, 2.7vw, 27px);
            line-height: 1.08;
            letter-spacing: -.6px;
            font-weight: 950;
        }

        .section-title span {
            flex: 0 0 auto;
            padding: 7px 10px;
            border-radius: 999px;
            color: #111;
            background: #F4E7F3;
            font-size: 10px;
            font-weight: 900;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px;
        }

        .service-link {
            min-width: 0;
            text-decoration: none;
        }

        .service-card {
            position: relative;
            min-height: 168px;
            height: 100%;
            padding: 18px;
            overflow: hidden;
            border: 1px solid var(--aliada-border);
            border-radius: 21px;
            background:
                radial-gradient(circle at 100% 0%, rgba(229,28,155,.035), transparent 8rem),
                #FFFFFF;
            box-shadow: 0 10px 27px rgba(55,18,68,.045);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .service-link:hover .service-card,
        .service-link:focus-visible .service-card {
            transform: translateY(-2px);
            border-color: rgba(229,28,155,.22);
            box-shadow: 0 15px 34px rgba(55,18,68,.075);
        }

        .service-card > div:first-child {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .service-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            color: #B41483;
            background: linear-gradient(135deg, #FDE8F6, #F2EAFB);
        }

        .service-icon .icon-svg {
            width: 22px;
            height: 22px;
        }

        .service-title {
            margin: 14px 40px 0 0;
            color: #111;
            font-size: 15px;
            line-height: 1.25;
            font-weight: 900;
        }

        .service-desc {
            display: block;
            margin-top: 6px;
            color: #403742;
            font-size: 11.5px;
            line-height: 1.44;
        }

        .service-arrow {
            position: absolute;
            right: 14px;
            top: 14px;
            width: 33px;
            height: 33px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            color: #9B2580;
            background: #FFF3F9;
        }

        .service-arrow .icon-svg {
            width: 16px;
            height: 16px;
        }

        .emergency-link {
            grid-column: 1 / -1;
        }

        .service-card.emergency {
            min-height: 112px;
            display: grid;
            grid-template-columns: auto minmax(0,1fr) auto;
            align-items: center;
            gap: 14px;
            color: #FFFFFF;
            border-color: rgba(199,25,71,.13);
            background:
                radial-gradient(circle at 92% 0%, rgba(255,255,255,.15), transparent 12rem),
                linear-gradient(135deg, #C9164F, #E52869);
            box-shadow: 0 13px 32px rgba(190,24,78,.15);
        }

        .service-card.emergency .service-icon {
            color: #C2184E;
            background: #FFFFFF;
        }

        .service-card.emergency .service-title {
            margin: 0;
            color: #FFFFFF;
        }

        .service-card.emergency .service-desc {
            color: rgba(255,255,255,.91);
        }

        .service-card.emergency .service-arrow {
            position: static;
            color: #C2184E;
            background: #FFFFFF;
        }

        /* =========================================================
           AYUDA Y FOOTER
        ========================================================= */
        .help-panel {
            margin-top: 15px;
            padding: 17px 18px;
            display: grid;
            grid-template-columns: auto minmax(0,1fr);
            gap: 12px;
            border: 1px solid rgba(195,215,48,.29);
            border-radius: 19px;
            background:
                radial-gradient(circle at 100% 0%, rgba(195,215,48,.12), transparent 14rem),
                #FCFFF2;
        }

        .help-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: #718200;
            background: rgba(195,215,48,.18);
        }

        .help-panel h3 {
            margin: 0;
            color: #111;
            font-size: 14px;
            font-weight: 950;
        }

        .help-panel p {
            margin: 5px 0 0;
            color: #403D2A;
            font-size: 11px;
            line-height: 1.45;
        }

        .footer-mobile {
            margin-top: 20px;
            padding: 14px 10px 0;
            color: #3E363F;
            text-align: center;
            font-size: 10.5px;
            line-height: 1.55;
        }

        .footer-mobile p {
            margin: 0;
        }

        /* =========================================================
           BOTTOM NAV
        ========================================================= */
        .bottom-nav {
            position: fixed;
            z-index: 900;
            left: 50%;
            bottom: calc(8px + var(--safe-bottom));
            width: min(calc(100% - 22px - var(--safe-left) - var(--safe-right)), 620px);
            min-height: 66px;
            padding: 7px;
            display: grid;
            grid-template-columns: 1fr 1.24fr 1fr;
            gap: 7px;
            transform: translateX(-50%);
            border: 1px solid rgba(255,255,255,.92);
            border-radius: 22px;
            background: rgba(255,255,255,.94);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            box-shadow: 0 16px 38px rgba(55,18,68,.15);
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
            color: #111;
            background: transparent;
            text-decoration: none;
            font-size: 10.5px;
            font-weight: 900;
            cursor: pointer;
        }

        .bottom-btn.primary {
            background: linear-gradient(135deg, #F14CAE, #DF1A91);
            box-shadow: 0 9px 21px rgba(216,27,108,.19);
        }

        /* =========================================================
           iPAD / TABLET EN VERTICAL
           Se usa ORIENTACIÓN, no solo ancho.
           Así un iPad con viewport de 980px sigue recibiendo
           el diseño correcto de tablet vertical.
        ========================================================= */
        @media (min-width: 700px) and (orientation: portrait) {
            .app-mobile {
                padding:
                    calc(20px + var(--safe-top))
                    calc(22px + var(--safe-right))
                    calc(110px + var(--safe-bottom))
                    calc(22px + var(--safe-left));
            }

            .mobile-shell {
                width: min(100%, 900px);
            }

            .hero-inner {
                grid-template-columns: minmax(0,1fr) minmax(210px, 27%);
                gap: 8px 18px;
                padding: 18px 20px;
            }

            .hero-top {
                grid-template-columns: 102px minmax(0,1fr);
                gap: 14px;
            }

            .logo-box {
                width: 102px;
                height: 54px;
            }

            .hero-title {
                font-size: clamp(25px, 3.4vw, 31px);
            }

            .hero-text {
                margin-left: 116px;
                font-size: 11.8px;
            }

            .quick-btn {
                min-height: 49px;
                font-size: 12.5px;
            }

            .services-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }

            .service-card {
                min-height: 166px;
            }
        }

        /* =========================================================
           iPAD / TABLET EN HORIZONTAL
           Solo aquí se habilitan 3 columnas.
        ========================================================= */
        @media (min-width: 900px) and (orientation: landscape) {
            .app-mobile {
                padding:
                    calc(20px + var(--safe-top))
                    calc(24px + var(--safe-right))
                    calc(108px + var(--safe-bottom))
                    calc(24px + var(--safe-left));
            }

            .mobile-shell {
                width: min(100%, 1120px);
            }

            .hero-inner {
                grid-template-columns: minmax(0,1fr) 270px;
            }

            .services-grid {
                grid-template-columns: repeat(3, minmax(0,1fr));
            }

            .service-card {
                min-height: 176px;
            }

            .emergency-link {
                grid-column: 1 / -1;
            }
        }

        /* =========================================================
           CELULAR / iPHONE
        ========================================================= */
        @media (max-width: 699px) {
            .app-mobile {
                padding:
                    calc(8px + var(--safe-top))
                    calc(8px + var(--safe-right))
                    calc(96px + var(--safe-bottom))
                    calc(8px + var(--safe-left));
            }

            .mobile-shell {
                width: 100%;
            }

            .hero-card {
                border-radius: 20px;
            }

            .hero-inner {
                display: grid;
                grid-template-columns: 1fr;
                grid-template-areas:
                    "heroTop"
                    "heroText"
                    "heroActions";
                gap: 0;
                padding: 11px 12px 10px;
            }

            .hero-top {
                grid-template-columns: 74px minmax(0,1fr);
                gap: 9px;
            }

            .logo-box {
                width: 74px;
                height: 39px;
                padding: 5px 7px;
                border-radius: 12px;
            }

            .logo-box img {
                max-height: 23px;
            }

            .eyebrow {
                padding: 4px 7px;
                margin-bottom: 3px;
                font-size: 7.1px;
                white-space: normal;
                line-height: 1.14;
            }

            .hero-title {
                max-width: 230px;
                margin-top: 5px;
                font-size: clamp(18px, 5.8vw, 23px);
            }

            .hero-text {
                margin: 7px 0 0;
                max-width: 100%;
                font-size: 10.2px;
                line-height: 1.32;
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-top: 9px;
            }

            .quick-btn {
                min-height: 43px;
                padding: 9px 9px;
                border-radius: 13px;
                font-size: 11px;
            }

            .quick-btn .icon-svg {
                width: 17px;
                height: 17px;
            }

            .safety-strip {
                margin: 9px 3px 0;
                padding: 10px;
                gap: 9px;
                border-radius: 17px;
            }

            .safety-icon {
                width: 39px;
                height: 39px;
                border-radius: 13px;
            }

            .safety-copy strong {
                font-size: 11.8px;
            }

            .safety-copy span {
                font-size: 10px;
            }

            .safety-open {
                display: none;
            }

            .intro-panel {
                margin-top: 14px;
                padding: 14px;
                gap: 10px;
                border-radius: 18px;
            }

            .intro-symbol {
                width: 38px;
                height: 38px;
                border-radius: 12px;
            }

            .intro-title {
                font-size: 17px;
            }

            .intro-text {
                margin-top: 5px;
                font-size: 10.8px;
            }

            .section-title {
                margin: 19px 2px 10px;
            }

            .section-title h2 {
                font-size: 18.5px;
            }

            .section-title span {
                padding: 6px 8px;
                font-size: 9.3px;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 9px;
            }

            .service-card {
                min-height: 0;
                padding: 13px 47px 13px 13px;
                display: grid;
                grid-template-columns: 42px minmax(0,1fr);
                align-items: center;
                gap: 10px;
                border-radius: 17px;
            }

            .service-card > div:first-child {
                display: contents;
            }

            .service-icon {
                width: 42px;
                height: 42px;
                grid-row: 1 / span 2;
                border-radius: 13px;
            }

            .service-title {
                margin: 0;
                font-size: 13.2px;
                line-height: 1.23;
            }

            .service-desc {
                margin-top: 3px;
                font-size: 10.1px;
                line-height: 1.36;
            }

            .service-arrow {
                right: 10px;
                top: 50%;
                width: 30px;
                height: 30px;
                transform: translateY(-50%);
                border-radius: 10px;
            }

            .service-card.emergency {
                min-height: 0;
                padding: 13px;
                grid-template-columns: 42px minmax(0,1fr) 30px;
                gap: 10px;
                border-radius: 17px;
            }

            .service-card.emergency .service-icon {
                grid-row: auto;
            }

            .service-card.emergency .service-arrow {
                width: 30px;
                height: 30px;
            }

            .help-panel {
                margin-top: 12px;
                padding: 13px;
                gap: 9px;
                border-radius: 17px;
            }

            .help-icon {
                width: 39px;
                height: 39px;
            }

            .help-panel h3 {
                font-size: 13px;
            }

            .help-panel p {
                font-size: 10.2px;
            }

            .footer-mobile {
                margin-top: 17px;
                font-size: 10.2px;
            }

            .bottom-nav {
                width: calc(100% - 14px - var(--safe-left) - var(--safe-right));
                min-height: 63px;
                bottom: calc(6px + var(--safe-bottom));
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

            .bottom-btn .icon-svg {
                width: 18px;
                height: 18px;
            }
        }

        /* iPhone SE / pantallas estrechas */
        @media (max-width: 390px) {
            .hero-top {
                grid-template-columns: 68px minmax(0,1fr);
            }

            .logo-box {
                width: 68px;
                height: 37px;
            }

            .logo-box img {
                max-height: 21px;
            }

            .hero-title {
                max-width: 200px;
                font-size: 17.5px;
            }

            .hero-text {
                font-size: 9.7px;
            }

            .section-title span {
                display: none;
            }
        }

        /* Móvil horizontal */
        @media (max-height: 520px) and (orientation: landscape) and (max-width: 932px) {
            .app-mobile {
                padding-top: calc(7px + var(--safe-top));
            }

            .hero-inner {
                grid-template-columns: minmax(0,1fr) 210px;
                grid-template-areas:
                    "heroTop heroActions"
                    "heroText heroActions";
                gap: 5px 14px;
                padding: 9px 12px;
            }

            .hero-top {
                grid-template-columns: 72px minmax(0,1fr);
            }

            .hero-text {
                margin: 2px 0 0 81px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                margin: 0;
            }

            .quick-btn {
                min-height: 40px;
            }

            .services-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }
        }

        @supports (-webkit-touch-callout: none) {
            body,
            .app-mobile {
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
    <div class="loader-bg" id="pageLoader" aria-hidden="true">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <main class="app-mobile">
        <div class="mobile-shell">

            <header class="hero-card">
                <div class="hero-inner">
                    <div class="hero-top">
                        <div class="logo-box">
                            <img src="assets/img/utslog.png" alt="Aliada UTS">
                        </div>

                        <div class="hero-copy">
                            <div class="eyebrow">Universitaria Tecnológica de Santander</div>
                            <div class="eyebrow">Aliada UTS</div>

                            <h1 class="hero-title">
                                ¿Cómo podemos <span>ayudarte?</span>
                            </h1>
                        </div>
                    </div>

                    <p class="hero-text">
                        Elige una opción para recibir orientación, acompañamiento o activar una ruta de apoyo.
                    </p>

                    <div class="quick-actions">
                        <a href="emergencia.php" class="quick-btn white">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            Emergencia
                        </a>

                        <button type="button" class="quick-btn" onclick="window.history.back();">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
                            Volver
                        </button>
                    </div>
                </div>
            </header>

            <!-- Acceso inmediato: mantiene exactamente el mismo destino emergencia.php -->
            <a href="emergencia.php" class="safety-strip" aria-label="Abrir emergencia institucional">
                <span class="safety-icon">
                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                </span>

                <span class="safety-copy">
                    <strong>¿Necesitas ayuda inmediata?</strong>
                    <span>Accede directamente a la ruta de emergencia institucional.</span>
                </span>

                <span class="safety-open">Abrir</span>
            </a>

            <section class="intro-panel">
                <div class="intro-symbol">
                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3Z"/><path d="m19 14 .8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14Z"/></svg>
                </div>

                <div>
                    <h2 class="intro-title">Atención clara, rápida y segura</h2>
                    <p class="intro-text">
                        Esta plataforma está diseñada para que encuentres ayuda sin complicaciones.
                        Toca el módulo que necesites y continúa con la orientación.
                    </p>
                </div>
            </section>

            <div class="section-title">
                <h2>Módulos de apoyo con IA</h2>
                <span>Toca una opción</span>
            </div>

            <section class="services-grid">

                <a href="asistencia_legal.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18"/><path d="M5 6h14"/><path d="M5 6 2 12h6L5 6Z"/><path d="m19 6-3 6h6l-3-6Z"/><path d="M8 21h8"/></svg>
                            </div>
                            <div>
                                <h3 class="service-title">Orientación legal con IA</h3>
                                <span class="service-desc">
                                    Asistente inteligente para brindar guía jurídica y rutas de protección.
                                </span>
                            </div>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

                <a href="asistencia_psicologica_v2.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 4.5A3.5 3.5 0 0 0 6 8a3.5 3.5 0 0 0-1 6.85A3.5 3.5 0 0 0 8.5 19H10V5.5a2 2 0 0 0-.5-1Z"/><path d="M14.5 4.5A3.5 3.5 0 0 1 18 8a3.5 3.5 0 0 1 1 6.85A3.5 3.5 0 0 1 15.5 19H14V5.5a2 2 0 0 1 .5-1Z"/><path d="M6.5 10.5H10"/><path d="M14 13.5h4"/></svg>
                            </div>
                            <div>
                                <h3 class="service-title">Apoyo psicológico con IA</h3>
                                <span class="service-desc">
                                    Asistente inteligente para escuchar, orientar y acompañar de forma segura.
                                </span>
                            </div>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

                <a href="asistencia_trabajo_social.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-4.4-7-11a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 6.6-7 11-7 11Z"/><path d="M8.5 12h2l1-2 1 4 1-2h2"/></svg>
                            </div>
                            <div>
                                <h3 class="service-title">IA Trabajo social</h3>
                                <span class="service-desc">
                                    Orientación rápida, humana y segura.
                                </span>
                            </div>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

                <a href="asistente_legal.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 18a8 8 0 0 1 16 0"/><path d="M7 18a5 5 0 0 1 10 0"/><path d="M10 18a2 2 0 0 1 4 0"/></svg>
                            </div>
                            <div>
                                <h3 class="service-title">IA de orientación LGBTIQ+</h3>
                                <span class="service-desc">
                                    Asistente inteligente para escuchar, orientar y acompañar con respeto.
                                </span>
                            </div>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

                <a href="maltrato.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 11V5a2 2 0 0 1 4 0v5"/><path d="M12 10V4a2 2 0 0 1 4 0v7"/><path d="M16 10V6a2 2 0 0 1 4 0v8c0 5-3 7-7 7h-1c-3 0-5-1-7-4l-2-3a2 2 0 0 1 3-2l2 2"/></svg>
                            </div>
                            <div>
                                <h3 class="service-title">Reporte de maltrato</h3>
                                <span class="service-desc">
                                    Graba o toma Evidencias de Maltrato
                                </span>
                            </div>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

                <a href="violencia_intrafamiliar.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M12 12v3"/><path d="M12 18h.01"/></svg>
                            </div>
                            <div>
                                <h3 class="service-title">Violencia intrafamiliar</h3>
                                <span class="service-desc">
                                    Graba o toma Evidencias
                                </span>
                            </div>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

                <a href="emergencia.php" class="service-link emergency-link">
                    <article class="service-card emergency">
                        <div class="service-icon">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        </div>

                        <div class="emergency-copy">
                            <h3 class="service-title">Emergencia institucional</h3>
                            <span class="service-desc">
                                Usa esta opción si necesitas activar una alerta inmediata.
                            </span>
                        </div>

                        <div class="service-arrow">
                            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </div>
                    </article>
                </a>

            </section>

            <section class="help-panel">
                <div class="help-icon">
                    <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><path d="M8 6h4a3 3 0 0 1 3 3v0a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v1"/></svg>
                </div>

                <div>
                    <h3>¿No sabes cuál elegir?</h3>
                    <p>
                        Empieza por apoyo psicológico o trabajo social. Desde allí podrán orientarte hacia la ruta adecuada.
                    </p>
                </div>
            </section>

            <footer class="footer-mobile">
                <p>
                    © <?php echo $currentYear; ?> <strong>Aliada UTS</strong><br>
                    Acompañamiento, orientación y protección para la comunidad universitaria.<br>
                    Desarrollado por <strong>SpiderSoftware S.A.S.</strong>
                </p>
            </footer>

        </div>
    </main>

    <nav class="bottom-nav" aria-label="Navegación principal">
        <button type="button" class="bottom-btn" onclick="window.history.back();">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 18-6-6 6-6"/></svg>
            <span>Volver</span>
        </button>

        <a href="emergencia.php" class="bottom-btn primary">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <span>Emergencia</span>
        </a>

        <a href="panel_usuaria.php" class="bottom-btn">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
            <span>Inicio</span>
        </a>
    </nav>

    <script>
        window.addEventListener('load', function () {
            const loader = document.getElementById('pageLoader');

            if (loader) {
                setTimeout(function () {
                    loader.classList.add('hide');
                }, 180);
            }
        });
    </script>
    <?php include __DIR__ . '/videollamada_banner.php'; ?>
</body>
</html>
