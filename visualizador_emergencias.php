<?php
require_once './admin/include/generic_classes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

$modulo = 'Visualizador de Emergencias';

$db = new DbConection();
$pdo = $db->openConect();

$alertasPendientes = [];

try {
    $sql = "SELECT
                a.id,
                a.usuario_id,
                a.nombre_usuaria,
                COALESCE(NULLIF(TRIM(a.celular_usuaria), ''), u.celular) AS celular_usuaria,
                COALESCE(NULLIF(TRIM(a.contacto_emergencia), ''), u.contacto_emergencia) AS contacto_emergencia,
                COALESCE(NULLIF(TRIM(a.nombre_contacto_emergencia), ''), u.nombre_contacto) AS nombre_contacto_emergencia,
                a.direccion_detectada,
                a.latitud,
                a.longitud,
                a.precision_metros,
                a.ubicacion_confirmada,
                a.fecha_registro,
                COALESCE(a.estado_atencion_visualizador, 'no_atendida') AS estado_atencion_visualizador
            FROM tbl_alerta_policia a
            LEFT JOIN tbl_usuarios u ON u.id = a.usuario_id
            WHERE COALESCE(a.estado_atencion_visualizador, 'no_atendida') IN ('no_atendida', 'en_atencion')
            ORDER BY
                CASE COALESCE(a.estado_atencion_visualizador, 'no_atendida')
                    WHEN 'en_atencion' THEN 1
                    WHEN 'no_atendida' THEN 2
                    ELSE 3
                END,
                a.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $alertasPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $alertasPendientes = [];
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function fechaBonita($fecha)
{
    if (!$fecha) {
        return 'Sin fecha';
    }

    $timestamp = strtotime($fecha);

    if (!$timestamp) {
        return (string) $fecha;
    }

    return date('d/m/Y h:i A', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Visualizador de Emergencias | Violeta Santander</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIINfQ3ynh1cQ1TrMZKZQ8QJbQr1R9oUQ7M="
        crossorigin=""
    >

    <link rel="stylesheet" href="assets/css/visual_admin.css">

    <style>
        :root {
            --emergency-bg: #160922;
            --emergency-surface: #21102f;
            --emergency-surface-2: #2b183a;
            --emergency-border: rgba(255, 255, 255, 0.11);
            --emergency-text: #ffffff;
            --emergency-muted: #c9b8d8;
            --emergency-primary: #ff3d68;
            --emergency-primary-hover: #f12658;
            --emergency-focus: rgba(194, 72, 255, 0.30);
            --emergency-radius: 20px;
        }

        body.modal-open-emergency {
            overflow: hidden !important;
        }

        /* Evita que el contenido principal quede demasiado estrecho. */
        .viewer-grid {
            align-items: stretch;
        }

        #map {
            min-height: 520px;
        }

        .alert-item {
            cursor: pointer;
            outline: none;
        }

        .alert-item:focus-visible {
            box-shadow: 0 0 0 3px var(--emergency-focus);
        }

        /* =========================================================
           MODAL DE ATENCIÓN
           - Encabezado fijo
           - Cuerpo con scroll independiente
           - Botones inferiores siempre visibles
        ========================================================= */
        /* SweetAlert2 por encima del modal (z-index 10500/10510), si no sus
           diálogos quedan tapados y la interacción se bloquea. */
        .swal2-container {
            z-index: 20000 !important;
        }

        .attention-panel-backdrop {
            position: fixed !important;
            inset: 0 !important;
            z-index: 10500 !important;
            background: rgba(7, 2, 13, 0.72) !important;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }

        .attention-panel-backdrop.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .attention-panel {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            right: auto !important;
            bottom: auto !important;
            z-index: 10510 !important;

            width: min(1120px, calc(100vw - 40px)) !important;
            height: min(880px, calc(100dvh - 32px)) !important;
            max-height: calc(100dvh - 32px) !important;
            min-height: 420px;

            display: flex !important;
            flex-direction: column;
            overflow: hidden !important;

            color: var(--emergency-text);
            background:
                radial-gradient(circle at top right, rgba(131, 53, 185, 0.16), transparent 34%),
                var(--emergency-bg) !important;
            border: 1px solid var(--emergency-border) !important;
            border-radius: var(--emergency-radius) !important;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.55) !important;

            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translate(-50%, calc(-50% + 18px)) scale(0.985) !important;
            transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s ease;
        }

        .attention-panel.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translate(-50%, -50%) scale(1) !important;
        }

        .attention-panel-header {
            position: relative;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-height: 82px;
            padding: 20px 24px;
            background: rgba(34, 15, 49, 0.98) !important;
            border-bottom: 1px solid var(--emergency-border);
            cursor: default !important;
            user-select: none;
        }

        .attention-heading {
            min-width: 0;
        }

        .attention-eyebrow {
            display: block;
            margin-bottom: 4px;
            color: #d8a9ff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.10em;
            text-transform: uppercase;
        }

        .attention-panel-title {
            margin: 0 !important;
            color: #fff;
            font-size: clamp(20px, 2vw, 28px) !important;
            font-weight: 800;
            line-height: 1.15;
        }

        .attention-subtitle {
            margin: 6px 0 0;
            color: var(--emergency-muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .attention-close {
            position: static !important;
            flex: 0 0 46px;
            width: 46px !important;
            height: 46px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            border-radius: 14px !important;
            background: rgba(255, 255, 255, 0.07) !important;
            color: #fff !important;
            font-size: 21px;
            transition: transform 0.18s ease, background 0.18s ease;
        }

        .attention-close:hover {
            background: rgba(255, 255, 255, 0.14) !important;
            transform: scale(1.04);
        }

        .attention-close:focus-visible,
        .contact-action-btn:focus-visible,
        .btn-panel-close:focus-visible,
        .btn-violeta:focus-visible,
        .attention-panel .form-control:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px var(--emergency-focus) !important;
        }

        .attention-form {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .attention-panel-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto !important;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            padding: 22px 24px 26px !important;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(203, 139, 255, 0.55) rgba(255, 255, 255, 0.04);
        }

        .attention-panel-body::-webkit-scrollbar {
            width: 10px;
        }

        .attention-panel-body::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.035);
            border-radius: 999px;
        }

        .attention-panel-body::-webkit-scrollbar-thumb {
            background: rgba(203, 139, 255, 0.48);
            border: 2px solid transparent;
            border-radius: 999px;
            background-clip: padding-box;
        }

        .attention-panel-body::-webkit-scrollbar-thumb:hover {
            background: rgba(203, 139, 255, 0.72);
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .emergency-details {
            margin: 0 0 18px;
            overflow: hidden;
            border: 1px solid var(--emergency-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.035);
        }

        .emergency-details summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            list-style: none;
        }

        .emergency-details summary::-webkit-details-marker {
            display: none;
        }

        .emergency-details summary::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #d9b6f4;
            transition: transform 0.2s ease;
        }

        .emergency-details[open] summary::after {
            transform: rotate(180deg);
        }

        .emergency-details-content {
            padding: 0 16px 16px;
        }

        .snapshot-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px !important;
            margin: 0 !important;
        }

        .snapshot-box {
            min-width: 0;
            min-height: 78px;
            padding: 12px 14px !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 13px !important;
            background: rgba(255, 255, 255, 0.035) !important;
        }

        .snapshot-box .k {
            margin-bottom: 6px;
            color: #d6aef1 !important;
            font-size: 10px !important;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .snapshot-box .v {
            overflow-wrap: anywhere;
            color: #fff !important;
            font-size: 13px !important;
            font-weight: 700;
            line-height: 1.4;
        }

        .contact-section {
            margin-bottom: 22px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 11px;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
        }

        .section-title i {
            color: #d28aff;
        }

        .contact-actions-bar {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px !important;
            margin: 0 !important;
        }

        .contact-action-btn {
            min-width: 0;
            min-height: 46px;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 13px !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            color: #fff !important;
            font-size: 12px !important;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
            text-decoration: none !important;
            white-space: normal;
            transition: transform 0.18s ease, filter 0.18s ease, opacity 0.18s ease;
        }

        button.contact-action-btn {
            cursor: pointer;
            font-family: inherit;
        }

        button.contact-action-btn.is-loading {
            pointer-events: none;
            opacity: 0.82;
            filter: none;
        }

        .contact-action-btn:hover {
            color: #fff !important;
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .contact-action-btn.is-disabled {
            cursor: not-allowed;
            opacity: 0.46 !important;
            pointer-events: none !important;
            filter: grayscale(0.35);
        }

        .action-call {
            background: linear-gradient(135deg, #d7375f, #ae294e) !important;
        }

        .action-whatsapp {
            background: linear-gradient(135deg, #22935c, #187247) !important;
        }

        .action-video {
            background: linear-gradient(135deg, #9f36e7, #cf36e8) !important;
        }

        .action-sms {
            background: linear-gradient(135deg, #ff466d, #f42f58) !important;
        }

        .action-alert-contact {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }

        .video-call-panel {
            margin-top: 14px;
            overflow: hidden;
            border: 1px solid var(--emergency-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.035);
        }

        .video-call-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .video-call-head i {
            color: #d28aff;
        }

        .video-call-close {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 10px;
            background: transparent;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .video-call-close:hover {
            background: rgba(255, 255, 255, 0.10);
        }

        #hmsVideoFrame {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 9;
            max-height: 480px;
            border: 0;
            background: #000;
        }

        @media (max-width: 767.98px) {
            #hmsVideoFrame {
                aspect-ratio: auto;
                height: 60vh;
            }
        }

        /* Ocultar la burbuja flotante del widget de Zadarma, la llamada se
           dispara desde el botón "Llamar". */
        .zdrm-phone,
        .zdrm-webrtc-error {
            display: none !important;
        }

        .call-active-indicator {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 14px;
            padding: 12px 16px;
            border: 1px solid var(--emergency-border);
            border-radius: 14px;
            background: rgba(34, 147, 92, 0.12);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .call-active-indicator.show {
            display: flex;
        }

        .call-active-indicator i {
            color: #22935c;
        }

        .call-active-hangup {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 0;
            border-radius: 10px;
            background: #d7375f;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .call-active-hangup:hover {
            filter: brightness(1.08);
        }

        .form-section {
            padding: 18px;
            border: 1px solid var(--emergency-border);
            border-radius: 17px;
            background: rgba(255, 255, 255, 0.035);
        }

        .form-section-head {
            margin-bottom: 16px;
        }

        .form-section-head h4 {
            margin: 0 0 5px;
            color: #fff;
            font-size: 16px;
            font-weight: 800;
        }

        .form-section-head p {
            margin: 0;
            color: var(--emergency-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .attention-fields-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field-group {
            min-width: 0;
        }

        .field-group.field-full {
            grid-column: 1 / -1;
        }

        .attention-panel .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 7px !important;
            color: #f4eafa !important;
            font-size: 13px !important;
            font-weight: 800;
        }

        .required-mark {
            color: #ff6b88;
        }

        .field-help {
            margin: 7px 2px 0;
            color: #a996b9;
            font-size: 11px;
            line-height: 1.4;
        }

        .attention-panel .form-control {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.14) !important;
            border-radius: 13px !important;
            background: #f7f5f9 !important;
            color: #25152f !important;
            font-size: 14px !important;
            line-height: 1.5;
            box-shadow: none !important;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .attention-panel input.form-control {
            min-height: 48px;
            padding: 12px 14px !important;
        }

        .attention-panel textarea.form-control {
            min-height: 122px;
            max-height: 240px;
            padding: 13px 14px !important;
            resize: vertical;
            overflow-y: auto;
        }

        .attention-panel textarea#gestion_realizada {
            min-height: 150px;
        }

        .attention-panel .form-control::placeholder {
            color: #7f7386;
            opacity: 1;
        }

        .attention-panel .form-control:hover {
            border-color: rgba(207, 139, 255, 0.48) !important;
        }

        .attention-panel .form-control:focus {
            border-color: #bd6ef1 !important;
            background: #fff !important;
            box-shadow: 0 0 0 4px var(--emergency-focus) !important;
        }

        .attention-actions {
            flex: 0 0 auto;
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            gap: 11px;
            min-height: 76px;
            margin: 0 !important;
            padding: 14px 24px calc(14px + env(safe-area-inset-bottom)) !important;
            border-top: 1px solid var(--emergency-border);
            background: rgba(27, 11, 40, 0.98) !important;
            box-shadow: 0 -12px 28px rgba(0, 0, 0, 0.16);
        }

        .btn-panel-close,
        .btn-violeta {
            min-height: 48px;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 !important;
            padding: 11px 20px !important;
            border-radius: 13px !important;
            font-size: 14px !important;
            font-weight: 800 !important;
            transition: transform 0.18s ease, filter 0.18s ease, opacity 0.18s ease;
        }

        .btn-panel-close {
            border: 1px solid rgba(255, 255, 255, 0.16) !important;
            background: transparent !important;
            color: #fff !important;
        }

        .btn-violeta {
            min-width: 180px;
            border: 0 !important;
            background: linear-gradient(135deg, var(--emergency-primary), #ff5478) !important;
            color: #fff !important;
            box-shadow: 0 10px 24px rgba(255, 61, 104, 0.22);
        }

        .btn-panel-close:hover,
        .btn-violeta:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .btn-violeta:disabled {
            cursor: wait;
            opacity: 0.66;
            transform: none;
        }

        .button-spinner {
            width: 16px;
            height: 16px;
            display: none;
            border: 2px solid rgba(255, 255, 255, 0.38);
            border-top-color: #fff;
            border-radius: 50%;
            animation: emergency-spin 0.75s linear infinite;
        }

        .btn-violeta.is-loading .button-spinner {
            display: inline-block;
        }

        @keyframes emergency-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1100px) {
            .snapshot-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .contact-actions-bar {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            #map {
                min-height: 420px;
            }

            .attention-panel {
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100dvh !important;
                max-height: 100dvh !important;
                min-height: 100dvh !important;
                border: 0 !important;
                border-radius: 0 !important;
                transform: translateY(24px) !important;
            }

            .attention-panel.show {
                transform: translateY(0) !important;
            }

            .attention-panel-header {
                min-height: 74px;
                padding: calc(14px + env(safe-area-inset-top)) 16px 14px;
            }

            .attention-subtitle {
                display: none;
            }

            .attention-panel-body {
                padding: 16px !important;
            }

            .snapshot-grid,
            .attention-fields-grid {
                grid-template-columns: 1fr;
            }

            .field-group.field-full {
                grid-column: auto;
            }

            .contact-actions-bar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .contact-action-btn {
                min-height: 50px;
            }

            .form-section {
                padding: 15px;
            }

            .attention-actions {
                padding: 12px 16px calc(12px + env(safe-area-inset-bottom)) !important;
            }

            .btn-panel-close,
            .btn-violeta {
                flex: 1 1 0;
                min-width: 0;
                padding-inline: 12px !important;
            }
        }

        @media (max-width: 430px) {
            .attention-panel-title {
                font-size: 18px !important;
            }

            .attention-close {
                flex-basis: 42px;
                width: 42px !important;
                height: 42px !important;
            }

            .contact-actions-bar {
                grid-template-columns: 1fr;
            }

            .snapshot-box {
                min-height: auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .attention-panel,
            .attention-panel-backdrop,
            .attention-close,
            .contact-action-btn,
            .btn-panel-close,
            .btn-violeta {
                transition: none !important;
            }
        }
    </style>
</head>

<body class="dashboard-body">
<?php
include './admin/include/navbar.php';
include './admin/include/header.php';
?>

<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="m-b-10 text-white">Visualizador de Emergencias</h5>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboard.php"><i class="feather icon-home"></i></a>
                            </li>
                            <li class="breadcrumb-item"><a href="#!">Panel de Control</a></li>
                            <li class="breadcrumb-item"><a href="#!">Visualizador</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="viewer-grid">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3>Mapa de atención de emergencias</h3>
                        <p>Selecciona una emergencia del listado derecho para visualizarla y gestionarla.</p>
                    </div>

                    <div class="badge-soft">
                        <i class="fas fa-location-dot"></i>
                        Vista activa
                    </div>
                </div>

                <div id="map"></div>

                <div class="map-info">
                    <div class="info-box">
                        <div class="k">Usuaria</div>
                        <div class="v" id="infoNombre">Sin selección</div>
                    </div>

                    <div class="info-box">
                        <div class="k">Dirección detectada</div>
                        <div class="v" id="infoDireccion">Selecciona una emergencia para ver su ubicación.</div>
                    </div>

                    <div class="info-box">
                        <div class="k">Estado</div>
                        <div class="v" id="infoEstado">No atendida</div>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3>Emergencias pendientes</h3>
                        <p>Al seleccionar una emergencia se muestra en el mapa y se abre el formulario.</p>
                    </div>

                    <div class="badge-soft">
                        <i class="fas fa-list"></i>
                        <span id="activeCount"><?php echo count($alertasPendientes); ?></span> activas
                    </div>
                </div>

                <div class="list-wrap" id="alertList">
                    <?php if (!empty($alertasPendientes)): ?>
                        <?php foreach ($alertasPendientes as $row): ?>
                            <?php
                            $estado = $row['estado_atencion_visualizador'] ?? 'no_atendida';
                            $latitud = str_replace(',', '.', (string) ($row['latitud'] ?? ''));
                            $longitud = str_replace(',', '.', (string) ($row['longitud'] ?? ''));
                            ?>

                            <div
                                class="alert-item"
                                role="button"
                                tabindex="0"
                                aria-label="Abrir emergencia de <?php echo e($row['nombre_usuaria'] ?: 'usuaria sin nombre'); ?>"
                                data-id="<?php echo e($row['id']); ?>"
                                data-nombre="<?php echo e($row['nombre_usuaria']); ?>"
                                data-celular="<?php echo e($row['celular_usuaria']); ?>"
                                data-contacto="<?php echo e($row['contacto_emergencia']); ?>"
                                data-contacto-nombre="<?php echo e($row['nombre_contacto_emergencia']); ?>"
                                data-direccion="<?php echo e($row['direccion_detectada']); ?>"
                                data-estado="<?php echo e($estado); ?>"
                                data-lat="<?php echo e($latitud); ?>"
                                data-lng="<?php echo e($longitud); ?>"
                                data-precision="<?php echo e($row['precision_metros']); ?>"
                                data-fecha="<?php echo e(fechaBonita($row['fecha_registro'])); ?>"
                            >
                                <div class="alert-top">
                                    <div>
                                        <div class="alert-name">
                                            <?php echo e($row['nombre_usuaria'] ?: 'Sin nombre'); ?>
                                        </div>
                                        <div class="alert-date">
                                            <?php echo e(fechaBonita($row['fecha_registro'])); ?>
                                        </div>
                                    </div>

                                    <?php if ($estado === 'en_atencion'): ?>
                                        <span class="estado-chip estado-atencion">En atención</span>
                                    <?php else: ?>
                                        <span class="estado-chip estado-no">No atendida</span>
                                    <?php endif; ?>
                                </div>

                                <div class="alert-address">
                                    <?php echo e($row['direccion_detectada'] ?: 'Sin dirección'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-box">No hay emergencias pendientes por visualizar.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="attention-panel-backdrop" id="attentionBackdrop" aria-hidden="true"></div>

<section
    class="attention-panel"
    id="attentionPanel"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="attentionPanelTitle"
>
    <header class="attention-panel-header">
        <div class="attention-heading">
            <span class="attention-eyebrow">Centro de atención</span>
            <h3 class="attention-panel-title" id="attentionPanelTitle">Gestión de atención de emergencia</h3>
            <p class="attention-subtitle">Consulta los datos, contacta a la usuaria y registra la gestión realizada.</p>
        </div>

        <button
            type="button"
            class="attention-close"
            id="btnClosePanel"
            aria-label="Cerrar ventana de atención"
            title="Cerrar"
        >
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </header>

    <form id="formAtencion" class="attention-form" novalidate>
        <input type="hidden" name="alerta_id" id="alerta_id">
        <input type="hidden" name="nombre_usuaria" id="form_nombre_usuaria">
        <input type="hidden" name="celular_usuaria" id="form_celular_usuaria">
        <input type="hidden" name="contacto_emergencia" id="form_contacto_emergencia">
        <input type="hidden" name="nombre_contacto_emergencia" id="form_nombre_contacto_emergencia">
        <input type="hidden" name="direccion_detectada" id="form_direccion_detectada">
        <input type="hidden" name="latitud" id="form_latitud">
        <input type="hidden" name="longitud" id="form_longitud">
        <input type="hidden" name="precision_metros" id="form_precision_metros">

        <div class="attention-panel-body" id="attentionPanelBody">
            <details class="emergency-details" open>
                <summary>
                    <span><i class="fas fa-circle-info"></i> Datos de la emergencia</span>
                </summary>

                <div class="emergency-details-content">
                    <div class="snapshot-grid">
                        <div class="snapshot-box">
                            <div class="k">Usuaria</div>
                            <div class="v" id="snap_nombre">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Celular usuaria</div>
                            <div class="v" id="snap_celular">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Contacto de emergencia</div>
                            <div class="v" id="snap_contacto">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Nombre del contacto</div>
                            <div class="v" id="snap_contacto_nombre">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Dirección detectada</div>
                            <div class="v" id="snap_direccion">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Latitud / Longitud</div>
                            <div class="v" id="snap_latlng">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Precisión en metros</div>
                            <div class="v" id="snap_precision">-</div>
                        </div>

                        <div class="snapshot-box">
                            <div class="k">Fecha del registro</div>
                            <div class="v" id="snap_fecha">-</div>
                        </div>
                    </div>
                </div>
            </details>

            <section class="contact-section" aria-labelledby="contactSectionTitle">
                <h4 class="section-title" id="contactSectionTitle">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    Acciones de contacto
                </h4>

                <div class="contact-actions-bar">
                    <button
                        type="button"
                        class="contact-action-btn action-call"
                        id="btnLlamarUsuaria"
                        disabled
                        aria-disabled="true"
                    >
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <span>Llamar</span>
                    </button>

                    <a
                        href="#"
                        class="contact-action-btn action-whatsapp"
                        id="btnWhatsappUsuaria"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="fab fa-whatsapp" aria-hidden="true"></i>
                        <span>WhatsApp</span>
                    </a>

                    <button
                        type="button"
                        class="contact-action-btn action-video"
                        id="btnIniciarVideollamada"
                        disabled
                        aria-disabled="true"
                    >
                        <i class="fas fa-video" aria-hidden="true"></i>
                        <span>Iniciar videollamada</span>
                    </button>

                    <button
                        type="button"
                        class="contact-action-btn action-alert-contact"
                        id="btnAlertarContactoEmergencia"
                        disabled
                        aria-disabled="true"
                    >
                        <i class="fab fa-whatsapp" aria-hidden="true"></i>
                        <span id="btnAlertarContactoEmergenciaLabel">Alertar a contacto de emergencia</span>
                    </button>
                </div>

                <section class="video-call-panel" id="videoCallPanel" hidden>
                    <div class="video-call-head">
                        <span><i class="fas fa-video" aria-hidden="true"></i> Videollamada en curso</span>
                        <button type="button" class="video-call-close" id="btnCerrarVideollamada">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                            Cerrar
                        </button>
                    </div>
                    <iframe
                        id="hmsVideoFrame"
                        title="Videollamada 100ms"
                        allow="camera *; microphone *; display-capture *; autoplay"
                        allowfullscreen
                    ></iframe>
                </section>

                <div class="call-active-indicator" id="callActiveIndicator">
                    <span><i class="fas fa-phone-volume" aria-hidden="true"></i> Llamada en curso</span>
                    <button type="button" class="call-active-hangup" id="btnColgarLlamada">
                        <i class="fas fa-phone-slash" aria-hidden="true"></i>
                        Colgar
                    </button>
                </div>
            </section>

            <section class="form-section" aria-labelledby="managementSectionTitle">
                <div class="form-section-head">
                    <h4 id="managementSectionTitle">Registro de la atención</h4>
                    <p>Los campos marcados con asterisco son obligatorios.</p>
                </div>

                <div class="attention-fields-grid">
                    <div class="field-group field-full">
                        <label class="form-label" for="reporte">
                            <span>Reporte <span class="required-mark">*</span></span>
                        </label>
                        <textarea
                            class="form-control auto-grow"
                            name="reporte"
                            id="reporte"
                            rows="4"
                            maxlength="3000"
                            placeholder="Describe de manera clara la situación reportada por la usuaria."
                            required
                        ></textarea>
                        <p class="field-help">Incluye los hechos principales y la información relevante recibida.</p>
                    </div>

                    <div class="field-group">
                        <label class="form-label" for="tarea_realizada">
                            <span>Tarea realizada <span class="required-mark">*</span></span>
                        </label>
                        <textarea
                            class="form-control auto-grow"
                            name="tarea_realizada"
                            id="tarea_realizada"
                            rows="4"
                            maxlength="3000"
                            placeholder="Indica la acción inmediata realizada por el operador."
                            required
                        ></textarea>
                    </div>

                    <div class="field-group">
                        <label class="form-label" for="gestion_realizada">
                            <span>Gestión realizada <span class="required-mark">*</span></span>
                        </label>
                        <textarea
                            class="form-control auto-grow"
                            name="gestion_realizada"
                            id="gestion_realizada"
                            rows="5"
                            maxlength="5000"
                            placeholder="Registra el seguimiento, las llamadas, remisiones o coordinaciones efectuadas."
                            required
                        ></textarea>
                    </div>

                    <div class="field-group field-full">
                        <label class="form-label" for="cuadrante_enviado">
                            <span>Cuadrante enviado <span class="required-mark">*</span></span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            name="cuadrante_enviado"
                            id="cuadrante_enviado"
                            maxlength="180"
                            autocomplete="off"
                            placeholder="Ejemplo: Cuadrante 12 – CAI Centro"
                            required
                        >
                        <p class="field-help">Indica el cuadrante, unidad, patrulla o institución a la que se remitió el caso.</p>
                    </div>
                </div>
            </section>
        </div>

        <footer class="attention-actions">
            <button type="button" class="btn-panel-close" id="btnCancelPanel">
                <i class="fas fa-xmark" aria-hidden="true"></i>
                Cerrar
            </button>

            <button type="submit" class="btn btn-violeta" id="btnGuardarAtencion">
                <span class="button-spinner" aria-hidden="true"></span>
                <i class="fas fa-floppy-disk save-icon" aria-hidden="true"></i>
                <span class="save-label">Guardar atención</span>
            </button>
        </footer>
    </form>
</section>

<?php include './admin/include/gerenic_script.php'; ?>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>

<script src="https://my.zadarma.com/webphoneWebRTCWidget/v9/js/loader-phone-lib.js?sub_v=1"></script>
<script src="https://my.zadarma.com/webphoneWebRTCWidget/v9/js/loader-phone-fn.js?sub_v=1"></script>

<script>
    'use strict';

    const AJAX_URL_VISUALIZADOR = <?= json_encode('admin/ajax/visualizador_emergencias_ajax.php', JSON_UNESCAPED_SLASHES) ?>;
    const DEFAULT_CENTER = [6.6936, -73.4860];

    let visualMap = null;
    let currentMarker = null;
    let currentCircle = null;
    let selectedAlertId = null;
    let hasUnsavedChanges = false;
    let isSaving = false;
    let isAlertandoContacto = false;
    let contactoEmergenciaDisponible = false;
    let previouslyFocusedElement = null;
    let currentVideollamada = null;
    let isCreandoVideollamada = false;
    let isEnviandoVideoWhatsapp = false;
    let isEnviandoVideoSMS = false;
    let puedeEnviarEnlaceVideollamada = false;
    let comunicacionActivaId = null;
    let zadarmaWidgetListo = false;
    let zadarmaInicializando = null;
    let zadarmaLlamadaActiva = false;
    let zadarmaEstadoInterval = null;
    let isIniciandoLlamada = false;

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        initEvents();
        initAutoGrowTextareas();
    });

    function initMap() {
        const mapElement = document.getElementById('map');

        if (!mapElement || typeof L === 'undefined') {
            console.error('No existe #map o Leaflet no está cargado.');
            return;
        }

        visualMap = L.map('map', {
            zoomControl: true,
            attributionControl: true
        }).setView(DEFAULT_CENTER, 8);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(visualMap);

        setTimeout(function () {
            visualMap.invalidateSize();
        }, 300);

        setTimeout(function () {
            visualMap.invalidateSize();
        }, 900);
    }

    function initEvents() {
        document.querySelectorAll('.alert-item').forEach(function (item) {
            item.addEventListener('click', function () {
                selectAlert(item);
            });

            item.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectAlert(item);
                }
            });
        });

        const form = document.getElementById('formAtencion');
        const btnClose = document.getElementById('btnClosePanel');
        const btnCancel = document.getElementById('btnCancelPanel');
        const backdrop = document.getElementById('attentionBackdrop');

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                guardarAtencion();
            });

            form.addEventListener('input', function (event) {
                if (event.target.matches('textarea, input[type="text"]')) {
                    hasUnsavedChanges = true;
                }
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', requestCloseAttentionPanel);
        }

        if (btnCancel) {
            btnCancel.addEventListener('click', requestCloseAttentionPanel);
        }

        if (backdrop) {
            backdrop.addEventListener('click', requestCloseAttentionPanel);
        }

        const btnAlertarContacto = document.getElementById('btnAlertarContactoEmergencia');

        if (btnAlertarContacto) {
            btnAlertarContacto.addEventListener('click', alertarContactoEmergencia);
        }

        const btnIniciarVideollamada = document.getElementById('btnIniciarVideollamada');
        const btnCerrarVideollamada = document.getElementById('btnCerrarVideollamada');

        if (btnIniciarVideollamada) {
            btnIniciarVideollamada.addEventListener('click', manejarIniciarVideollamada);
        }

        if (btnCerrarVideollamada) {
            btnCerrarVideollamada.addEventListener('click', ocultarPanelVideollamada);
        }

        const btnLlamarUsuaria = document.getElementById('btnLlamarUsuaria');
        const btnColgarLlamada = document.getElementById('btnColgarLlamada');

        if (btnLlamarUsuaria) {
            btnLlamarUsuaria.addEventListener('click', manejarLlamar);
        }

        if (btnColgarLlamada) {
            btnColgarLlamada.addEventListener('click', finalizarLlamadaEnCurso);
        }

        document.addEventListener('keydown', function (event) {
            const panel = document.getElementById('attentionPanel');

            if (event.key === 'Escape' && panel && panel.classList.contains('show')) {
                requestCloseAttentionPanel();
            }
        });

        window.addEventListener('resize', function () {
            if (visualMap) {
                setTimeout(function () {
                    visualMap.invalidateSize();
                }, 100);
            }
        });
    }

    function initAutoGrowTextareas() {
        document.querySelectorAll('textarea.auto-grow').forEach(function (textarea) {
            textarea.addEventListener('input', function () {
                autoGrowTextarea(textarea);
            });
        });
    }

    function autoGrowTextarea(textarea) {
        if (!textarea) {
            return;
        }

        textarea.style.height = 'auto';
        const newHeight = Math.min(Math.max(textarea.scrollHeight, 122), 240);
        textarea.style.height = newHeight + 'px';
        textarea.style.overflowY = textarea.scrollHeight > 240 ? 'auto' : 'hidden';
    }

    function resetAutoGrowTextareas() {
        document.querySelectorAll('textarea.auto-grow').forEach(function (textarea) {
            textarea.style.height = '';
            textarea.style.overflowY = '';
        });
    }

    function obtenerFechaGestionBonita() {
        return new Intl.DateTimeFormat('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'America/Bogota'
        }).format(new Date());
    }

    function agregarRegistroGestion(textoAdicional) {
        const campo = document.getElementById('gestion_realizada');

        if (!campo || !textoAdicional) {
            return;
        }

        const contenidoActual = String(campo.value || '').trim();
        campo.value = contenidoActual
            ? contenidoActual + '\n' + textoAdicional
            : textoAdicional;

        hasUnsavedChanges = true;
        autoGrowTextarea(campo);
    }

    function selectAlert(item) {
        if (!item) {
            return;
        }

        document.querySelectorAll('.alert-item').forEach(function (element) {
            element.classList.remove('active');
            element.setAttribute('aria-pressed', 'false');
        });

        item.classList.add('active');
        item.setAttribute('aria-pressed', 'true');

        const data = {
            id: item.dataset.id || '',
            nombre: item.dataset.nombre || 'Sin nombre',
            celular: item.dataset.celular || '',
            contacto: item.dataset.contacto || '',
            contactoNombre: item.dataset.contactoNombre || '',
            direccion: item.dataset.direccion || 'Sin dirección',
            estado: item.dataset.estado || 'no_atendida',
            lat: parseFloat(item.dataset.lat),
            lng: parseFloat(item.dataset.lng),
            precision: item.dataset.precision || '',
            fecha: item.dataset.fecha || ''
        };

        selectedAlertId = data.id;
        hasUnsavedChanges = false;

        setText('infoNombre', data.nombre);
        setText('infoDireccion', data.direccion);
        setText('infoEstado', data.estado === 'en_atencion' ? 'En atención' : 'No atendida');

        setValue('alerta_id', data.id);
        setValue('form_nombre_usuaria', data.nombre);
        setValue('form_celular_usuaria', data.celular);
        setValue('form_contacto_emergencia', data.contacto);
        setValue('form_nombre_contacto_emergencia', data.contactoNombre);
        setValue('form_direccion_detectada', data.direccion);
        setValue('form_latitud', Number.isNaN(data.lat) ? '' : data.lat);
        setValue('form_longitud', Number.isNaN(data.lng) ? '' : data.lng);
        setValue('form_precision_metros', data.precision);

        setText('snap_nombre', data.nombre || '-');
        setText('snap_celular', data.celular || '-');
        setText('snap_contacto', data.contacto || '-');
        setText('snap_contacto_nombre', data.contactoNombre || '-');
        setText('snap_direccion', data.direccion || '-');
        setText(
            'snap_latlng',
            !Number.isNaN(data.lat) && !Number.isNaN(data.lng)
                ? data.lat + ' / ' + data.lng
                : '-'
        );
        setText('snap_precision', data.precision ? data.precision + ' m' : '-');
        setText('snap_fecha', data.fecha || '-');

        setValue('reporte', '');
        setValue('tarea_realizada', '');
        setValue('cuadrante_enviado', '');
        setValue('gestion_realizada', '');
        resetAutoGrowTextareas();

        prepararAccionesContacto(data);

        if (!Number.isNaN(data.lat) && !Number.isNaN(data.lng)) {
            renderSingleMarker(data.lat, data.lng, data.nombre, data.direccion, data.estado, data.precision);
        } else {
            clearMapSelection();
        }

        openAttentionPanel();
        marcarEnAtencion(data.id, item);
    }

    function prepararAccionesContacto(data) {
        const btnLlamar = document.getElementById('btnLlamarUsuaria');
        const btnWhatsapp = document.getElementById('btnWhatsappUsuaria');
        const btnVideo = document.getElementById('btnIniciarVideollamada');
        const btnAlertarContacto = document.getElementById('btnAlertarContactoEmergencia');

        const telefonoLimpio = limpiarTelefono(data.celular);
        const contactoLimpio = limpiarTelefono(data.contacto);
        const contactoInternacional = obtenerTelefonoInternacional(contactoLimpio);
        const tieneContactoEmergencia = Boolean(
            contactoInternacional && contactoInternacional.length >= 12
        );
        contactoEmergenciaDisponible = tieneContactoEmergencia;
        const telefonoInternacional = obtenerTelefonoInternacional(telefonoLimpio);
        puedeEnviarEnlaceVideollamada = Boolean(telefonoInternacional);

        const mensajeWhatsApp = encodeURIComponent(
            'Hola ' + (data.nombre || '') +
            ', te contactamos desde Violeta Santander para brindar acompañamiento y atención. ' +
            'Este mensaje está relacionado con tu solicitud de apoyo registrada en el sistema.'
        );

        configurarBotonAccion(btnLlamar, Boolean(telefonoInternacional));

        configurarBotonContacto(
            btnWhatsapp,
            telefonoInternacional
                ? 'https://wa.me/' + telefonoInternacional + '?text=' + mensajeWhatsApp
                : '#',
            Boolean(telefonoInternacional)
        );

        // La videollamada se puede iniciar siempre; el envío WhatsApp/SMS
        // depende de que exista celular (se decide en el SweetAlert).
        configurarBotonAccion(btnVideo, true);

        currentVideollamada = null;
        ocultarPanelVideollamada();

        configurarBotonAccion(btnAlertarContacto, tieneContactoEmergencia);
    }

    function configurarBotonAccion(button, enabled) {
        if (!button) {
            return;
        }

        button.disabled = !enabled;
        button.classList.toggle('is-disabled', !enabled);
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        button.tabIndex = enabled ? 0 : -1;
    }

    function setEstadoBotonAlertarContacto(cargando) {
        const btnAlertarContacto = document.getElementById('btnAlertarContactoEmergencia');
        const label = document.getElementById('btnAlertarContactoEmergenciaLabel');
        const icono = btnAlertarContacto ? btnAlertarContacto.querySelector('i') : null;

        if (!btnAlertarContacto || !label) {
            return;
        }

        if (cargando) {
            btnAlertarContacto.disabled = true;
            btnAlertarContacto.classList.add('is-loading');
            btnAlertarContacto.setAttribute('aria-disabled', 'true');
            label.textContent = 'Enviando alerta...';

            if (icono) {
                icono.className = 'fas fa-spinner fa-spin';
            }

            return;
        }

        btnAlertarContacto.classList.remove('is-loading');
        label.textContent = 'Alertar a contacto de emergencia';

        if (icono) {
            icono.className = 'fab fa-whatsapp';
        }

        configurarBotonAccion(btnAlertarContacto, contactoEmergenciaDisponible);
    }

    function alertarContactoEmergencia() {
        const alertaId = document.getElementById('alerta_id')?.value || '';
        const btnAlertarContacto = document.getElementById('btnAlertarContactoEmergencia');

        if (!alertaId || isAlertandoContacto || isSaving) {
            return;
        }

        if (btnAlertarContacto && (btnAlertarContacto.disabled || btnAlertarContacto.classList.contains('is-disabled'))) {
            return;
        }

        isAlertandoContacto = true;
        setEstadoBotonAlertarContacto(true);

        const body = new URLSearchParams();
        body.append('action', 'alertar_contacto_emergencia');
        body.append('alerta_id', alertaId);

        fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
            .then(parseJsonResponse)
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible enviar la alerta por WhatsApp.');
                }

                const nombreContacto = json.contacto_nombre
                    || document.getElementById('form_nombre_contacto_emergencia')?.value
                    || document.getElementById('snap_contacto_nombre')?.textContent
                    || 'contacto de emergencia';

                const telefonoContacto = json.contacto_telefono
                    || document.getElementById('form_contacto_emergencia')?.value
                    || document.getElementById('snap_contacto')?.textContent
                    || '';

                const detalleContacto = telefonoContacto && telefonoContacto !== '-'
                    ? nombreContacto + ' (' + telefonoContacto + ')'
                    : nombreContacto;

                agregarRegistroGestion(
                    '[' + obtenerFechaGestionBonita() + '] ' +
                    'Se envió alerta por WhatsApp al contacto de emergencia: ' +
                    detalleContacto +
                    ' (plantilla notificacion_aliada).'
                );

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Alerta enviada',
                        text: json.message || 'Se notificó al contacto de emergencia por WhatsApp.',
                        confirmButtonColor: '#ff3d68'
                    });
                } else {
                    window.alert(json.message || 'Se notificó al contacto de emergencia por WhatsApp.');
                }
            })
            .catch(function (error) {
                console.error('Error al alertar contacto:', error);

                if (typeof Swal !== 'undefined') {
                    Swal.fire(
                        'Error',
                        error.message || 'No fue posible enviar la alerta por WhatsApp.',
                        'error'
                    );
                } else {
                    window.alert(error.message || 'No fue posible enviar la alerta por WhatsApp.');
                }
            })
            .finally(function () {
                isAlertandoContacto = false;
                setEstadoBotonAlertarContacto(false);
            });
    }

    function ocultarPanelVideollamada() {
        const panel = document.getElementById('videoCallPanel');
        const iframe = document.getElementById('hmsVideoFrame');

        if (panel) {
            panel.hidden = true;
        }

        if (iframe) {
            iframe.src = '';
        }

        if (currentVideollamada) {
            liberarComunicacionActiva();
        }
    }

    function mostrarPanelVideollamada(url) {
        const panel = document.getElementById('videoCallPanel');
        const iframe = document.getElementById('hmsVideoFrame');

        if (!panel || !iframe) {
            return;
        }

        iframe.src = url;
        panel.hidden = false;
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function setBotonVideoCargando(button, cargando, iconoClaseNormal, labelNormal, labelCargando) {
        if (!button) {
            return;
        }

        const icono = button.querySelector('i');
        const label = button.querySelector('span');

        button.classList.toggle('is-loading', cargando);
        button.disabled = cargando;

        if (label) {
            label.textContent = cargando ? labelCargando : labelNormal;
        }

        if (icono) {
            icono.className = cargando ? 'fas fa-spinner fa-spin' : iconoClaseNormal;
        }
    }

    function obtenerOcrearVideollamada() {
        if (currentVideollamada) {
            return Promise.resolve(currentVideollamada);
        }

        const alertaId = document.getElementById('alerta_id')?.value || '';
        const nombreUsuaria = document.getElementById('form_nombre_usuaria')?.value || '';

        if (!alertaId) {
            return Promise.reject(new Error('No hay una emergencia seleccionada.'));
        }

        const body = new URLSearchParams();
        body.append('action', 'iniciar_videollamada');
        body.append('alerta_id', alertaId);
        body.append('nombre_usuaria', nombreUsuaria);

        return fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
            .then(parseJsonResponse)
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible iniciar la videollamada.');
                }

                comunicacionActivaId = json.comunicacion_id;

                currentVideollamada = {
                    roomName: json.room_name,
                    hostUrl: json.host_url,
                    guestUrl: json.guest_url
                };

                return currentVideollamada;
            });
    }

    function preguntarCanalEnvioVideollamada() {
        if (!puedeEnviarEnlaceVideollamada || typeof Swal === 'undefined') {
            return Promise.resolve(null);
        }

        return Swal.fire({
            icon: 'question',
            title: '¿Por dónde deseas enviar el enlace a la usuaria?',
            text: 'Se enviará junto con la videollamada que se va a iniciar.',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'WhatsApp',
            denyButtonText: 'SMS',
            cancelButtonText: 'Solo iniciar la llamada',
            confirmButtonColor: '#22935c',
            denyButtonColor: '#ff466d',
            cancelButtonColor: '#5d496d',
            allowOutsideClick: false
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                return 'enviar_video_whatsapp';
            }

            if (resultado.isDenied) {
                return 'enviar_video_sms';
            }

            return null;
        });
    }

    function manejarIniciarVideollamada() {
        const boton = document.getElementById('btnIniciarVideollamada');

        if (isCreandoVideollamada || (boton && (boton.disabled || boton.classList.contains('is-disabled')))) {
            return;
        }

        isCreandoVideollamada = true;

        preguntarCanalEnvioVideollamada()
            .then(function (canalElegido) {
                setBotonVideoCargando(boton, true, 'fas fa-video', 'Iniciar videollamada', 'Creando sala...');

                return obtenerOcrearVideollamada()
                    .then(function (videollamada) {
                        mostrarPanelVideollamada(videollamada.hostUrl);

                        agregarRegistroGestion(
                            '[' + obtenerFechaGestionBonita() + '] ' +
                            'Se inició una videollamada (100ms) para la atención de esta emergencia.'
                        );

                        if (!canalElegido) {
                            return null;
                        }

                        const labelEnvio = canalElegido === 'enviar_video_whatsapp'
                            ? 'Enviando WhatsApp...'
                            : 'Enviando SMS...';

                        setBotonVideoCargando(boton, true, 'fas fa-video', 'Iniciar videollamada', labelEnvio);

                        return manejarEnviarEnlaceVideollamada(canalElegido);
                    });
            })
            .catch(function (error) {
                console.error('Error al iniciar videollamada:', error);

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', error.message || 'No fue posible iniciar la videollamada.', 'error');
                } else {
                    window.alert(error.message || 'No fue posible iniciar la videollamada.');
                }
            })
            .finally(function () {
                isCreandoVideollamada = false;
                setBotonVideoCargando(boton, false, 'fas fa-video', 'Iniciar videollamada', 'Creando sala...');
            });
    }

    function manejarEnviarEnlaceVideollamada(accion) {
        const esWhatsapp = accion === 'enviar_video_whatsapp';
        const yaEnviando = esWhatsapp ? isEnviandoVideoWhatsapp : isEnviandoVideoSMS;

        if (yaEnviando || !puedeEnviarEnlaceVideollamada) {
            return Promise.resolve(null);
        }

        if (esWhatsapp) {
            isEnviandoVideoWhatsapp = true;
        } else {
            isEnviandoVideoSMS = true;
        }

        const telefono = document.getElementById('form_celular_usuaria')?.value || '';
        const alertaId = document.getElementById('alerta_id')?.value || '';
        const nombreUsuaria = document.getElementById('form_nombre_usuaria')?.value || '';

        return obtenerOcrearVideollamada()
            .then(function (videollamada) {
                const body = new URLSearchParams();
                body.append('action', accion);
                body.append('alerta_id', alertaId);
                body.append('telefono', telefono);
                body.append('nombre_usuaria', nombreUsuaria);
                body.append('guest_url', videollamada.guestUrl);
                body.append('room_name', videollamada.roomName || '');

                return fetch(AJAX_URL_VISUALIZADOR, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                }).then(parseJsonResponse);
            })
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible enviar el enlace de videollamada.');
                }

                agregarRegistroGestion(
                    '[' + obtenerFechaGestionBonita() + '] ' +
                    json.message +
                    ' Enlace: ' + (json.public_url || json.guest_url || '')
                );

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enlace enviado',
                        text: json.message,
                        confirmButtonColor: '#ff3d68'
                    });
                } else {
                    window.alert(json.message);
                }

                return json;
            })
            .catch(function (error) {
                console.error('Error al enviar enlace de videollamada:', error);

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', error.message || 'No fue posible enviar el enlace de videollamada.', 'error');
                } else {
                    window.alert(error.message || 'No fue posible enviar el enlace de videollamada.');
                }

                throw error;
            })
            .finally(function () {
                if (esWhatsapp) {
                    isEnviandoVideoWhatsapp = false;
                } else {
                    isEnviandoVideoSMS = false;
                }
            });
    }

    function liberarComunicacionActiva() {
        const idAnterior = comunicacionActivaId;
        comunicacionActivaId = null;

        if (!idAnterior) {
            return;
        }

        const body = new URLSearchParams();
        body.append('action', 'finalizar_comunicacion');
        body.append('comunicacion_id', idAnterior);

        fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        }).catch(function () {});
    }

    function mostrarIndicadorLlamada(mostrar) {
        const indicador = document.getElementById('callActiveIndicator');

        if (indicador) {
            indicador.classList.toggle('show', mostrar);
        }
    }

    function detenerMonitoreoLlamada() {
        if (zadarmaEstadoInterval) {
            clearInterval(zadarmaEstadoInterval);
            zadarmaEstadoInterval = null;
        }
    }

    function finalizarLlamadaEnCurso() {
        if (window.zdrmWPhI && window.zdrmWPhI.apiWidget) {
            try {
                window.zdrmWPhI.apiWidget.cancel();
            } catch (error) {
                console.error('Error al colgar la llamada:', error);
            }
        }

        detenerMonitoreoLlamada();

        if (!zadarmaLlamadaActiva) {
            return;
        }

        zadarmaLlamadaActiva = false;
        mostrarIndicadorLlamada(false);
        liberarComunicacionActiva();

        agregarRegistroGestion(
            '[' + obtenerFechaGestionBonita() + '] ' +
            'Se colgó la llamada telefónica (Zadarma) con la usuaria.'
        );
    }

    function monitorearEstadoLlamada() {
        detenerMonitoreoLlamada();

        let llamadaConfirmada = false;

        zadarmaEstadoInterval = setInterval(function () {
            const apiWidget = window.zdrmWPhI ? window.zdrmWPhI.apiWidget : null;
            const callState = apiWidget ? apiWidget.callState : '';

            if (callState) {
                llamadaConfirmada = true;
            }

            if (zadarmaLlamadaActiva && llamadaConfirmada && !callState) {
                zadarmaLlamadaActiva = false;
                detenerMonitoreoLlamada();
                mostrarIndicadorLlamada(false);
                liberarComunicacionActiva();

                agregarRegistroGestion(
                    '[' + obtenerFechaGestionBonita() + '] ' +
                    'Finalizó la llamada telefónica (Zadarma) con la usuaria.'
                );
            }
        }, 500);
    }

    function inicializarZadarmaWidget() {
        if (zadarmaWidgetListo) {
            return Promise.resolve();
        }

        if (zadarmaInicializando) {
            return zadarmaInicializando;
        }

        zadarmaInicializando = fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: 'action=obtener_credenciales_llamada'
        })
            .then(parseJsonResponse)
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible obtener las credenciales de llamada.');
                }

                if (typeof zadarmaWidgetFn !== 'function') {
                    throw new Error('No se pudo cargar el softphone de Zadarma.');
                }

                zadarmaWidgetFn(json.webrtc_key, json.sip_login, 'square', 'es', true, "{right:'10px',bottom:'5px'}");

                return new Promise(function (resolve, reject) {
                    let intentos = 0;

                    const espera = setInterval(function () {
                        intentos++;

                        const listo = window.zdrmWPhI
                            && window.zdrmWPhI.apiWidget
                            && typeof window.zdrmWPhI.apiWidget.call === 'function';

                        if (listo) {
                            clearInterval(espera);
                            zadarmaWidgetListo = true;
                            resolve();
                        } else if (intentos > 20) {
                            clearInterval(espera);
                            reject(new Error('No fue posible inicializar el softphone de Zadarma.'));
                        }
                    }, 300);
                });
            })
            .finally(function () {
                zadarmaInicializando = null;
            });

        return zadarmaInicializando;
    }

    function manejarLlamar() {
        const boton = document.getElementById('btnLlamarUsuaria');

        if (
            isIniciandoLlamada
            || zadarmaLlamadaActiva
            || (boton && (boton.disabled || boton.classList.contains('is-disabled')))
        ) {
            return;
        }

        const alertaId = document.getElementById('alerta_id')?.value || '';
        const telefonoRaw = document.getElementById('form_celular_usuaria')?.value || '';
        const telefonoInternacional = obtenerTelefonoInternacional(limpiarTelefono(telefonoRaw));

        if (!alertaId || !telefonoInternacional) {
            return;
        }

        isIniciandoLlamada = true;
        setBotonVideoCargando(boton, true, 'fas fa-phone', 'Llamar', 'Conectando...');

        const body = new URLSearchParams();
        body.append('action', 'iniciar_comunicacion');
        body.append('tipo', 'llamada');
        body.append('alerta_id', alertaId);

        fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
            .then(parseJsonResponse)
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible iniciar la llamada.');
                }

                comunicacionActivaId = json.comunicacion_id;

                return inicializarZadarmaWidget();
            })
            .then(function () {
                zadarmaLlamadaActiva = true;
                window.zdrmWPhI.apiWidget.call(telefonoInternacional);
                mostrarIndicadorLlamada(true);
                monitorearEstadoLlamada();

                agregarRegistroGestion(
                    '[' + obtenerFechaGestionBonita() + '] ' +
                    'Se inició una llamada telefónica real (Zadarma) a la usuaria.'
                );
            })
            .catch(function (error) {
                console.error('Error al iniciar llamada:', error);
                liberarComunicacionActiva();

                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', error.message || 'No fue posible iniciar la llamada.', 'error');
                } else {
                    window.alert(error.message || 'No fue posible iniciar la llamada.');
                }
            })
            .finally(function () {
                isIniciandoLlamada = false;
                setBotonVideoCargando(boton, false, 'fas fa-phone', 'Llamar', 'Conectando...');
            });
    }

    function configurarBotonContacto(button, href, enabled) {
        if (!button) {
            return;
        }

        button.href = href;
        button.classList.toggle('is-disabled', !enabled);
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        button.tabIndex = enabled ? 0 : -1;
    }

    function limpiarTelefono(telefono) {
        return String(telefono || '').replace(/\D/g, '');
    }

    function obtenerTelefonoInternacional(telefono) {
        if (!telefono) {
            return '';
        }

        if (telefono.startsWith('57')) {
            return telefono;
        }

        return '57' + telefono;
    }

    function renderSingleMarker(lat, lng, nombre, direccion, estado, precision) {
        if (!visualMap) {
            return;
        }

        clearMapSelection();

        const customIcon = L.divIcon({
            className: 'custom-map-icon-wrapper',
            html: '<div class="custom-alert-marker"></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        currentMarker = L.marker([lat, lng], {
            icon: customIcon
        }).addTo(visualMap);

        const precisionNumber = parseFloat(precision);
        const circleRadius = !Number.isNaN(precisionNumber) && precisionNumber > 0
            ? Math.max(precisionNumber, 30)
            : 120;

        currentCircle = L.circle([lat, lng], {
            color: '#ff2d75',
            fillColor: '#ff2d75',
            fillOpacity: 0.10,
            radius: circleRadius
        }).addTo(visualMap);

        currentMarker.bindPopup(
            '<div style="max-width:260px;font-family:Inter,Arial,sans-serif;">' +
                '<div style="font-weight:800;font-size:15px;color:#4b1e75;margin-bottom:6px;">' +
                    escapeHtml(nombre) +
                '</div>' +
                '<div style="font-size:13px;color:#374151;line-height:1.45;">' +
                    '<strong>Dirección:</strong> ' + escapeHtml(direccion) +
                '</div>' +
                '<div style="font-size:13px;color:#374151;line-height:1.45;margin-top:6px;">' +
                    '<strong>Estado:</strong> ' +
                    (estado === 'en_atencion' ? 'En atención' : 'No atendida') +
                '</div>' +
            '</div>'
        ).openPopup();

        visualMap.setView([lat, lng], 16);

        setTimeout(function () {
            visualMap.invalidateSize();
        }, 250);
    }

    function clearMapSelection() {
        if (!visualMap) {
            return;
        }

        if (currentMarker) {
            visualMap.removeLayer(currentMarker);
            currentMarker = null;
        }

        if (currentCircle) {
            visualMap.removeLayer(currentCircle);
            currentCircle = null;
        }
    }

    function openAttentionPanel() {
        const panel = document.getElementById('attentionPanel');
        const backdrop = document.getElementById('attentionBackdrop');
        const body = document.getElementById('attentionPanelBody');

        if (!panel || !backdrop) {
            return;
        }

        previouslyFocusedElement = document.activeElement;

        panel.classList.add('show');
        panel.setAttribute('aria-hidden', 'false');
        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open-emergency');

        if (body) {
            body.scrollTop = 0;
        }

        setTimeout(function () {
            const reporte = document.getElementById('reporte');
            if (reporte) {
                reporte.focus({ preventScroll: true });
            }
        }, 180);
    }

    function requestCloseAttentionPanel() {
        if (isSaving) {
            return;
        }

        if (!hasUnsavedChanges) {
            closeAttentionPanel();
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: '¿Cerrar sin guardar?',
                text: 'Los datos que has escrito se perderán.',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Seguir editando',
                confirmButtonColor: '#ff3d68',
                cancelButtonColor: '#5d496d'
            }).then(function (result) {
                if (result.isConfirmed) {
                    closeAttentionPanel();
                }
            });
            return;
        }

        if (window.confirm('Los datos que has escrito se perderán. ¿Deseas cerrar?')) {
            closeAttentionPanel();
        }
    }

    function closeAttentionPanel() {
        const panel = document.getElementById('attentionPanel');
        const backdrop = document.getElementById('attentionBackdrop');

        if (panel) {
            panel.classList.remove('show');
            panel.setAttribute('aria-hidden', 'true');
        }

        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.setAttribute('aria-hidden', 'true');
        }

        document.body.classList.remove('modal-open-emergency');
        hasUnsavedChanges = false;
        ocultarPanelVideollamada();
        currentVideollamada = null;

        finalizarLlamadaEnCurso();

        if (previouslyFocusedElement && typeof previouslyFocusedElement.focus === 'function') {
            setTimeout(function () {
                previouslyFocusedElement.focus();
            }, 100);
        }
    }

    function marcarEnAtencion(alertaId, item) {
        if (!alertaId || !item) {
            return;
        }

        fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: 'action=marcar_en_atencion&alerta_id=' + encodeURIComponent(alertaId)
        })
            .then(parseJsonResponse)
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible marcar la emergencia en atención.');
                }

                item.dataset.estado = 'en_atencion';

                const badge = item.querySelector('.estado-chip');

                if (badge) {
                    badge.className = 'estado-chip estado-atencion';
                    badge.textContent = 'En atención';
                }

                setText('infoEstado', 'En atención');
            })
            .catch(function (error) {
                console.error('Error al marcar en atención:', error);
            });
    }

    function guardarAtencion() {
        const form = document.getElementById('formAtencion');

        if (!form || isSaving) {
            return;
        }

        if (!form.checkValidity()) {
            form.reportValidity();

            const firstInvalidField = form.querySelector(':invalid');

            if (firstInvalidField) {
                firstInvalidField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                setTimeout(function () {
                    firstInvalidField.focus({ preventScroll: true });
                }, 300);
            }

            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'guardar_atencion');

        setSavingState(true);

        fetch(AJAX_URL_VISUALIZADOR, {
            method: 'POST',
            body: formData
        })
            .then(parseJsonResponse)
            .then(function (json) {
                if (!json.ok) {
                    throw new Error(json.message || 'No fue posible guardar la atención.');
                }

                hasUnsavedChanges = false;
                closeAttentionPanel();
                removeSelectedAlertFromList();
                clearMapSelection();

                setText('infoNombre', 'Sin selección');
                setText('infoDireccion', 'Selecciona una emergencia para ver su ubicación.');
                setText('infoEstado', 'Atendida');

                selectedAlertId = null;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Atención guardada',
                        text: 'La emergencia fue marcada como atendida.',
                        confirmButtonColor: '#ff3d68'
                    });
                } else {
                    window.alert('Atención guardada correctamente.');
                }
            })
            .catch(function (error) {
                console.error('Error real al guardar:', error);

                if (typeof Swal !== 'undefined') {
                    Swal.fire(
                        'Error',
                        'Ocurrió un error al guardar la atención: ' + error.message,
                        'error'
                    );
                } else {
                    window.alert('Ocurrió un error al guardar la atención: ' + error.message);
                }
            })
            .finally(function () {
                setSavingState(false);
            });
    }

    async function parseJsonResponse(response) {
        const text = await response.text();

        if (!response.ok) {
            throw new Error(text || 'HTTP ' + response.status);
        }

        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error(text || 'La respuesta del servidor no es JSON válido.');
        }
    }

    function setSavingState(saving) {
        isSaving = saving;

        const button = document.getElementById('btnGuardarAtencion');
        const saveIcon = button ? button.querySelector('.save-icon') : null;
        const saveLabel = button ? button.querySelector('.save-label') : null;

        if (!button) {
            return;
        }

        button.disabled = saving;
        button.classList.toggle('is-loading', saving);

        if (saveIcon) {
            saveIcon.style.display = saving ? 'none' : '';
        }

        if (saveLabel) {
            saveLabel.textContent = saving ? 'Guardando...' : 'Guardar atención';
        }
    }

    function removeSelectedAlertFromList() {
        if (!selectedAlertId) {
            return;
        }

        const item = document.querySelector(
            '.alert-item[data-id="' + cssEscape(selectedAlertId) + '"]'
        );

        if (item) {
            item.remove();
        }

        updateActiveCount();

        if (!document.querySelector('.alert-item')) {
            const list = document.getElementById('alertList');

            if (list) {
                list.innerHTML = '<div class="empty-box">No hay emergencias pendientes por visualizar.</div>';
            }
        }
    }

    function updateActiveCount() {
        const countElement = document.getElementById('activeCount');

        if (countElement) {
            countElement.textContent = String(document.querySelectorAll('.alert-item').length);
        }
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(String(value));
        }

        return String(value).replace(/["\\]/g, '\\$&');
    }

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value;
        }
    }

    function setValue(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.value = value;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }
</script>
</body>
</html>
