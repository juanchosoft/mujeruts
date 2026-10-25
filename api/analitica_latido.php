<?php

/*
 * Recibe los latidos del rastreador de uso (analitica_tracker.php).
 * POST JSON { accion: pagina|latido|reanudar|cierre|evento, pagina, csrf?, tipo?, detalle? }
 * Responde siempre sin contenido: 204 en operación normal, 403 si el CSRF no coincide.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../admin/classes/AnaliticaService.php';

function terminarSinContenido($codigo = 204)
{
    http_response_code($codigo);
    header('Cache-Control: no-store');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    terminarSinContenido(405);
}

$usuarioId = (int) (
    $_SESSION['id']
    ?? $_SESSION['usuario_id']
    ?? $_SESSION['user_id']
    ?? $_SESSION['session_user']['id']
    ?? 0
);

if ($usuarioId <= 0) {
    terminarSinContenido(204);
}

$cuerpo = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($cuerpo)) {
    terminarSinContenido(400);
}

$csrfSesion = (string) ($_SESSION['analitica_csrf'] ?? '');
$csrfRecibido = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($cuerpo['csrf'] ?? ''));

if ($csrfSesion === '' || !is_string($csrfRecibido) || !hash_equals($csrfSesion, $csrfRecibido)) {
    terminarSinContenido(403);
}

$accion = $cuerpo['accion'] ?? '';
$pagina = $cuerpo['pagina'] ?? '';

if (!is_string($accion) || !in_array($accion, ['pagina', 'latido', 'reanudar', 'cierre', 'evento'], true)
    || !is_string($pagina) || !array_key_exists($pagina, AnaliticaService::PAGINAS)) {
    terminarSinContenido(400);
}

$sesionId = (int) ($_SESSION['analitica_sesion_id'] ?? 0);
$sesionUsuarioId = (int) ($_SESSION['analitica_usuario_id'] ?? 0);

// La sesión PHP se libera ya para que latidos simultáneos de la misma usuaria no se bloqueen entre sí.
session_write_close();

try {
    $servicio = AnaliticaService::crear();
    $estado = 'inexistente';

    if ($sesionId > 0 && $sesionUsuarioId === $usuarioId) {
        $estado = $accion === 'evento' ? 'ok' : $servicio->registrarActividad($sesionId, $usuarioId, $accion, $pagina, true);
    }

    if ($estado === 'inexistente' || $estado === 'cerrada') {
        // Sesión abierta antes del despliegue, ya cerrada por inactividad o de otra usuaria: se abre una nueva.
        $sesionId = $servicio->iniciarSesion($usuarioId, 'recuperada');

        session_start();
        $_SESSION['analitica_sesion_id'] = $sesionId;
        $_SESSION['analitica_usuario_id'] = $usuarioId;
        session_write_close();

        if ($accion !== 'evento') {
            $servicio->registrarActividad($sesionId, $usuarioId, $accion === 'pagina' ? 'pagina' : 'reanudar', $pagina, true);
        }
    }

    if ($accion === 'evento') {
        $tipo = $cuerpo['tipo'] ?? '';
        $detalle = $cuerpo['detalle'] ?? null;

        if (is_string($tipo) && ($detalle === null || is_string($detalle))) {
            $detalleLimpio = $detalle !== null ? preg_replace('/[^a-z0-9_\-]/i', '', substr($detalle, 0, 40)) : null;
            $servicio->registrarEvento($sesionId, $usuarioId, $tipo, $detalleLimpio);
        }
    }

    http_response_code(204);
    header('Cache-Control: no-store');
    header('Connection: close');
    ignore_user_abort(true);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }

    $servicio->cerrarInactivas();
    $servicio->resolverGeoPendiente($sesionId);
} catch (Throwable $e) {
    error_log('api/analitica_latido.php: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(204);
    }
}
