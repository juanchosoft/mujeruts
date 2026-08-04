<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

/*
|--------------------------------------------------------------------------
| RUTA BASE PARA ASSETS
|--------------------------------------------------------------------------
| Si este archivo se incluye desde vistas en la raíz del proyecto:
| assets/img/icono.png funciona correctamente.
|--------------------------------------------------------------------------
*/
$faviconPath = 'assets/img/favicon.ico';
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<title>Aliada UTS</title>

<meta name="description" content="Aliada UTS, plataforma institucional de orientación, acompañamiento, bienestar y rutas de atención para mujeres de la comunidad UTS.">
<meta name="keywords" content="Aliada UTS, UTS mujer, bienestar, orientación, acompañamiento, atención, mujeres, rutas de apoyo, comunidad universitaria">
<meta name="author" content="SpiderSoftware">
<meta name="robots" content="index,follow">

<meta name="theme-color" content="#D81B9C">
<meta name="msapplication-TileColor" content="#D81B9C">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Aliada UTS">

<link rel="icon" type="image/png" href="<?php echo $faviconPath; ?>">
<link rel="shortcut icon" type="image/png" href="<?php echo $faviconPath; ?>">
<link rel="apple-touch-icon" href="<?php echo $faviconPath; ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.css">

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    :root {
        --au-violet: #7B1FA2;
        --au-violet-dark: #4A0E63;
        --au-violet-soft: #F3E8FF;

        --au-fuchsia: #D81B9C;
        --au-fuchsia-dark: #A50F78;
        --au-fuchsia-soft: #FCE7F3;

        --au-pink: #FF4FB8;
        --au-pink-soft: #FFE4F3;

        --au-white: #FFFFFF;
        --au-bg: #FFF5FB;
        --au-bg-2: #FDF2FF;

        --au-card: rgba(255, 255, 255, .92);
        --au-card-2: rgba(255, 255, 255, .82);
        --au-line: rgba(216, 27, 156, .14);

        --au-text: #2B1237;
        --au-text-soft: #6F4D7A;
        --au-text-muted: #8B6A94;

        --au-success: #22C55E;
        --au-warning: #F59E0B;
        --au-danger: #EF4444;
        --au-info: #38BDF8;

        --au-radius-xl: 26px;
        --au-radius-lg: 20px;
        --au-radius-md: 16px;
        --au-radius-sm: 12px;

        --au-shadow: 0 20px 60px rgba(123, 31, 162, .18);
        --au-shadow-soft: 0 10px 28px rgba(123, 31, 162, .12);

        --au-gradient-main: linear-gradient(135deg, #FF4FB8 0%, #D81B9C 48%, #7B1FA2 100%);
        --au-gradient-soft: linear-gradient(135deg, rgba(255, 79, 184, .15), rgba(123, 31, 162, .10));
        --au-gradient-bg: linear-gradient(135deg, #FFF5FB 0%, #FDF2FF 45%, #FFFFFF 100%);
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif !important;
        min-height: 100%;
    }

    body {
        background:
            radial-gradient(circle at top left, rgba(255, 79, 184, .20), transparent 26%),
            radial-gradient(circle at bottom right, rgba(123, 31, 162, .18), transparent 30%),
            radial-gradient(circle at center, rgba(216, 27, 156, .06), transparent 42%),
            var(--au-gradient-bg);
        color: var(--au-text);
    }

    a {
        text-decoration: none;
    }

    .pcoded-main-container,
    .pcoded-content {
        background: transparent !important;
    }

    .page-header {
        margin-bottom: 18px;
    }

    .page-header .breadcrumb {
        background: transparent;
        margin-bottom: 0;
    }

    .page-header .breadcrumb-item,
    .page-header .breadcrumb-item a,
    .page-header h5 {
        color: var(--au-text) !important;
    }

    .page-header h5 {
        font-weight: 900;
        letter-spacing: -.3px;
    }

    .breadcrumb-item.active {
        color: var(--au-text-muted) !important;
    }

    .card,
    .dashboard-card,
    .panel-card,
    .widget-card {
        background: var(--au-card);
        border: 1px solid var(--au-line);
        border-radius: var(--au-radius-lg);
        box-shadow: var(--au-shadow-soft);
        color: var(--au-text);
        overflow: hidden;
    }

    .card .card-header,
    .panel-head {
        background: linear-gradient(135deg, rgba(255, 79, 184, .08), rgba(123, 31, 162, .06));
        border-bottom: 1px solid var(--au-line);
        color: var(--au-text);
    }

    .card-title,
    .card-header h5,
    .panel-head h5,
    .panel-head h4 {
        color: var(--au-text);
        font-weight: 900;
    }

    .table {
        color: var(--au-text);
        margin-bottom: 0;
    }

    .table thead th {
        border-bottom: 1px solid var(--au-line);
        color: #542264;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .6px;
        font-weight: 900;
        background: rgba(252, 231, 243, .75);
    }

    .table td,
    .table th {
        border-color: rgba(216, 27, 156, .10) !important;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: all .18s ease;
    }

    .table tbody tr:hover {
        background: rgba(255, 79, 184, .055);
    }

    .btn {
        border-radius: 14px;
        font-weight: 800;
        transition: all .2s ease;
    }

    .btn-primary {
        background: var(--au-gradient-main);
        border: none;
        box-shadow: 0 14px 30px rgba(216, 27, 156, .24);
    }

    .btn-primary:hover,
    .btn-primary:focus {
        background: var(--au-gradient-main);
        filter: brightness(1.04);
        transform: translateY(-1px);
        border: none;
        box-shadow: 0 18px 38px rgba(123, 31, 162, .28);
    }

    .btn-outline-primary {
        color: var(--au-fuchsia-dark);
        border: 1px solid rgba(216, 27, 156, .35);
        background: rgba(252, 231, 243, .58);
    }

    .btn-outline-primary:hover {
        color: #fff;
        background: var(--au-gradient-main);
        border-color: transparent;
        box-shadow: 0 14px 30px rgba(216, 27, 156, .22);
    }

    .btn-light {
        background: rgba(255, 255, 255, .86);
        border: 1px solid rgba(216, 27, 156, .12);
        color: var(--au-text);
    }

    .btn-light:hover {
        background: #fff;
        color: var(--au-fuchsia-dark);
        box-shadow: var(--au-shadow-soft);
    }

    .form-control,
    .form-select,
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        background: rgba(255, 255, 255, .92) !important;
        border: 1px solid rgba(216, 27, 156, .16) !important;
        color: var(--au-text) !important;
        border-radius: 14px !important;
        min-height: 46px;
        box-shadow: none !important;
    }

    .form-control::placeholder {
        color: #B895C2 !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: rgba(216, 27, 156, .70) !important;
        box-shadow: 0 0 0 4px rgba(216, 27, 156, .11) !important;
        background: #fff !important;
        color: var(--au-text) !important;
    }

    .form-label {
        color: #3B164D;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered,
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        color: var(--au-text) !important;
        line-height: 44px !important;
        padding-left: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
    }

    .select2-dropdown {
        background: #FFFFFF !important;
        border: 1px solid rgba(216, 27, 156, .18) !important;
        color: var(--au-text) !important;
        border-radius: 14px !important;
        overflow: hidden;
        box-shadow: var(--au-shadow-soft);
    }

    .select2-results__option {
        color: var(--au-text) !important;
    }

    .select2-results__option--highlighted {
        background: var(--au-fuchsia) !important;
        color: #FFFFFF !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: var(--au-fuchsia-soft) !important;
        border: 1px solid rgba(216, 27, 156, .22) !important;
        color: var(--au-fuchsia-dark) !important;
        border-radius: 999px !important;
        font-weight: 700;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        color: var(--au-text-soft) !important;
        margin-top: 8px;
    }

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background: rgba(255, 255, 255, .92) !important;
        border: 1px solid rgba(216, 27, 156, .16) !important;
        color: var(--au-text) !important;
        border-radius: 12px !important;
        padding: 6px 10px !important;
        outline: none !important;
    }

    .page-link {
        background: rgba(255, 255, 255, .86) !important;
        border: 1px solid rgba(216, 27, 156, .14) !important;
        color: var(--au-fuchsia-dark) !important;
        font-weight: 700;
    }

    .page-link:hover {
        background: var(--au-fuchsia-soft) !important;
        color: var(--au-violet-dark) !important;
    }

    .page-item.active .page-link {
        background: var(--au-gradient-main) !important;
        border-color: transparent !important;
        color: #fff !important;
        box-shadow: 0 10px 24px rgba(216, 27, 156, .18);
    }

    .badge {
        border-radius: 999px;
        padding: .5rem .8rem;
        font-weight: 800;
    }

    .bg-success {
        background-color: var(--au-success) !important;
    }

    .bg-warning {
        background-color: var(--au-warning) !important;
        color: #fff !important;
    }

    .bg-danger {
        background-color: var(--au-danger) !important;
    }

    .bg-info {
        background-color: var(--au-info) !important;
        color: #fff !important;
    }

    .loader-bg {
        position: fixed;
        z-index: 9999;
        inset: 0;
        background:
            radial-gradient(circle at top left, rgba(255, 79, 184, .24), transparent 34%),
            linear-gradient(135deg, rgba(74, 14, 99, .92), rgba(216, 27, 156, .82));
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .loader-track {
        width: 180px;
        height: 8px;
        background: rgba(255, 255, 255, .22);
        border-radius: 999px;
        overflow: hidden;
        box-shadow: 0 12px 35px rgba(0, 0, 0, .20);
    }

    .loader-fill {
        width: 45%;
        height: 100%;
        background: linear-gradient(135deg, #FFFFFF, #FFD6EF, #FF4FB8);
        border-radius: 999px;
        animation: aliada-loading 1.2s infinite ease-in-out;
    }

    @keyframes aliada-loading {
        0% {
            transform: translateX(-120%);
        }

        100% {
            transform: translateX(320%);
        }
    }

    .swal2-popup {
        border-radius: 22px !important;
        border: 1px solid rgba(255, 255, 255, .12) !important;
    }

    .leaflet-container {
        border-radius: 20px;
        border: 1px solid rgba(216, 27, 156, .14);
        box-shadow: var(--au-shadow-soft);
    }

    .text-muted {
        color: var(--au-text-muted) !important;
    }

    .text-primary {
        color: var(--au-fuchsia) !important;
    }

    .bg-light {
        background: rgba(252, 231, 243, .70) !important;
        color: var(--au-text) !important;
    }

    .border {
        border-color: var(--au-line) !important;
    }

    .shadow-sm,
    .shadow {
        box-shadow: var(--au-shadow-soft) !important;
    }

    hr {
        border-color: rgba(216, 27, 156, .14);
        opacity: 1;
    }

    ::selection {
        background: rgba(216, 27, 156, .24);
        color: var(--au-text);
    }

    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: #FDF2FF;
    }

    ::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #FF4FB8, #D81B9C, #7B1FA2);
        border-radius: 999px;
        border: 2px solid #FDF2FF;
    }

    @media (max-width: 768px) {
        .page-header .row {
            row-gap: 12px;
        }

        .card,
        .dashboard-card,
        .panel-card,
        .widget-card {
            border-radius: 18px;
        }

        .form-control,
        .form-select {
            min-height: 44px;
        }

        .table thead th {
            font-size: 11px;
        }
    }
</style>