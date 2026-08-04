<?php

// CONFIGURACIÓN DE SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cache_expire', 200000);
    ini_set('session.cache_limiter', 'none');
    ini_set('session.cookie_lifetime', 2000000);
    ini_set('session.gc_maxlifetime', 200000);

    session_start();
}

// VALIDAR SESIÓN DEL PANEL
if (
    !isset($_SESSION['panel_user']) &&
    !isset($_REQUEST['route_map'])
) {
    header('Location: logout.php');
    exit;
}

// VALIDACIÓN EXTRA DE ESTRUCTURA
if (
    isset($_SESSION['panel_user']) &&
    empty($_SESSION['panel_user']['id']) &&
    !isset($_REQUEST['route_map'])
) {
    header('Location: logout.php');
    exit;
}