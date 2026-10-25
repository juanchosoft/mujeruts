<?php

/*
 * Retomar la conversación anterior de la usuaria con un asistente.
 *   POST {accion: 'ultima', asistente}                  -> solo metadatos (fecha, cantidad), sin contenido.
 *   Solo conversaciones con actividad en los últimos DIAS_MAXIMOS_RETOMAR días.
 *   POST {accion: 'cargar', asistente, conversacion_id} -> mensajes, solo si la conversación es de la usuaria y del asistente.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../admin/classes/DbConection.php';
require_once __DIR__ . '/../admin/classes/AsistentesRegistro.php';

const MAX_MENSAJES_RETOMAR = 60;
const DIAS_MAXIMOS_RETOMAR = 15;

function responderJson($codigo, array $datos)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(405, ['error' => 'Método no permitido.']);
}

$csrfRecibido = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
$csrfSesion = trim((string) ($_SESSION['elevenlabs_csrf'] ?? ''));

if ($csrfRecibido === '' || $csrfSesion === '' || !hash_equals($csrfSesion, $csrfRecibido)) {
    responderJson(403, ['error' => 'Solicitud no autorizada.']);
}

$usuarioId = (int) (
    $_SESSION['id']
    ?? $_SESSION['usuario_id']
    ?? $_SESSION['user_id']
    ?? $_SESSION['session_user']['id']
    ?? 0
);

if ($usuarioId <= 0) {
    responderJson(401, ['error' => 'Debe iniciar sesión.']);
}

session_write_close();

$cuerpo = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($cuerpo)) {
    responderJson(400, ['error' => 'El cuerpo de la solicitud no es válido.']);
}

$accion = $cuerpo['accion'] ?? '';
$asistente = $cuerpo['asistente'] ?? '';

if (!is_string($accion) || !in_array($accion, ['ultima', 'cargar'], true)) {
    responderJson(400, ['error' => 'Acción no válida.']);
}

if (!is_string($asistente) || !AsistentesRegistro::existe($asistente)) {
    responderJson(400, ['error' => 'Asistente no válido.']);
}

try {
    $pdo = (new DbConection())->openConect();

    if ($accion === 'ultima') {
        $stmt = $pdo->prepare(
            "SELECT c.id, c.fecha_ultima_actividad,
                    (SELECT COUNT(*) FROM tbl_chat_mensajes m
                      WHERE m.conversacion_id = c.id AND m.rol IN ('usuaria', 'asistente')) AS mensajes
             FROM tbl_chat_conversaciones c
             WHERE c.usuario_id = :usuario_id AND c.asistente = :asistente
               AND c.fecha_ultima_actividad >= DATE_SUB(NOW(), INTERVAL " . DIAS_MAXIMOS_RETOMAR . " DAY)
               AND EXISTS (SELECT 1 FROM tbl_chat_mensajes u WHERE u.conversacion_id = c.id AND u.rol = 'usuaria')
             ORDER BY c.fecha_ultima_actividad DESC, c.id DESC
             LIMIT 1"
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':asistente', $asistente, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            responderJson(200, ['existe' => false]);
        }

        responderJson(200, [
            'existe' => true,
            'conversacion_id' => (int) $fila['id'],
            'fecha' => (string) $fila['fecha_ultima_actividad'],
            'mensajes' => (int) $fila['mensajes']
        ]);
    }

    $conversacionId = $cuerpo['conversacion_id'] ?? null;

    if (filter_var($conversacionId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        responderJson(400, ['error' => 'Conversación no válida.']);
    }

    $stmt = $pdo->prepare(
        'SELECT id FROM tbl_chat_conversaciones
         WHERE id = :id AND usuario_id = :usuario_id AND asistente = :asistente
           AND fecha_ultima_actividad >= DATE_SUB(NOW(), INTERVAL ' . DIAS_MAXIMOS_RETOMAR . ' DAY)
         LIMIT 1'
    );
    $stmt->bindValue(':id', (int) $conversacionId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':asistente', $asistente, PDO::PARAM_STR);
    $stmt->execute();

    if (!$stmt->fetchColumn()) {
        responderJson(404, ['error' => 'No se encontró la conversación.']);
    }

    $stmt = $pdo->prepare(
        "SELECT rol, contenido, fuentes_json, documento_json FROM (
             SELECT id, rol, contenido, fuentes_json, documento_json FROM tbl_chat_mensajes
             WHERE conversacion_id = :conversacion_id AND rol IN ('usuaria', 'asistente')
             ORDER BY id DESC LIMIT " . MAX_MENSAJES_RETOMAR . "
         ) ultimos ORDER BY id ASC"
    );
    $stmt->bindValue(':conversacion_id', (int) $conversacionId, PDO::PARAM_INT);
    $stmt->execute();

    $mensajes = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $documento = $fila['documento_json'] ? json_decode($fila['documento_json'], true) : null;
        $fuentes = $fila['fuentes_json'] ? json_decode($fila['fuentes_json'], true) : [];

        $mensajes[] = [
            'rol' => $fila['rol'],
            'contenido' => (string) $fila['contenido'],
            'documento' => is_array($documento) ? $documento : null,
            'fuentes' => is_array($fuentes) ? $fuentes : []
        ];
    }

    responderJson(200, [
        'conversacion_id' => (int) $conversacionId,
        'mensajes' => $mensajes
    ]);
} catch (Throwable $e) {
    error_log('api/chat_historial.php: ' . $e->getMessage());
    responderJson(500, ['error' => 'No fue posible cargar la conversación en este momento.']);
}
