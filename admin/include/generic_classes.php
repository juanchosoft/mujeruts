<?php
error_reporting(E_ERROR | E_PARSE);

$baseDir = dirname(__DIR__); // .../admin

require_once $baseDir . '/classes/Util.php';
require_once $baseDir . '/classes/DbConection.php';
require_once __DIR__ . '/generic_validate_session.php';

/*
|--------------------------------------------------------------------------
| SessionData
|--------------------------------------------------------------------------
| Cargar solo si el archivo existe. Si no existe, crear una clase mínima
| para evitar errores fatales en vistas que no usan sus métodos.
|--------------------------------------------------------------------------
*/
$sessionDataFile = $baseDir . '/classes/SessionData.php';

if (file_exists($sessionDataFile)) {
    require_once $sessionDataFile;
} else {
    if (!class_exists('SessionData')) {
        class SessionData
        {
            public static function getPermission($id = null) { return true; }
            public static function getUserType() { return $_SESSION['panel_user']['rol'] ?? null; }
            public static function getSecretaria() { return null; }
            public static function getCodigoMunicipio() { return null; }
            public static function getUserFullName() { return $_SESSION['panel_user']['nombre_completo'] ?? null; }
        }
    }
}

function base_url() {
    $port = $_SERVER['SERVER_PORT'] ?? '80';

    $nameServer = ($port !== '80' && $port !== '443')
        ? (($_SERVER['SERVER_NAME'] ?? 'localhost') . ':' . $port)
        : ($_SERVER['SERVER_NAME'] ?? 'localhost');

    $url = sprintf(
        '%s://%s%s',
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $nameServer,
        $_SERVER['REQUEST_URI'] ?? '/'
    );

    $scriptName = basename($_SERVER['SCRIPT_FILENAME'] ?? '', '.php') . '.php';
    $url = str_replace($scriptName, '', $url);

    return $url;
}