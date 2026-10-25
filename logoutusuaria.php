<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/admin/classes/AnaliticaService.php';
    AnaliticaService::alCerrarSesionUsuaria();
} catch (Throwable $e) {
    error_log('logoutusuaria.php analítica: ' . $e->getMessage());
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

session_destroy();

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
header('Location: ' . $basePath . '/login.php');
exit;