<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../admin/classes/DbConection.php';
require_once __DIR__ . '/../admin/classes/ConfigLoader.php';
require_once __DIR__ . '/../admin/classes/AnthropicClient.php';
require_once __DIR__ . '/../admin/classes/ChatRiesgoService.php';

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
    responderJson(401, ['error' => 'Debe iniciar sesión para usar este chat.']);
}

$cuerpo = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($cuerpo)) {
    responderJson(400, ['error' => 'El cuerpo de la solicitud no es válido.']);
}

$conversacionId = isset($cuerpo['conversacion_id']) ? (int) $cuerpo['conversacion_id'] : 0;
$mensaje = trim((string) ($cuerpo['mensaje'] ?? ''));
$origen = ($cuerpo['origen'] ?? 'texto') === 'voz' ? 'voz' : 'texto';

if ($mensaje === '') {
    responderJson(422, ['error' => 'El mensaje no puede estar vacío.']);
}

if (mb_strlen($mensaje, 'UTF-8') > 4000) {
    responderJson(422, ['error' => 'El mensaje es demasiado largo.']);
}

try {

$config = new ConfigLoader();
$db = new DbConection();
$pdo = $db->openConect();

$conversacion = null;

if ($conversacionId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM tbl_chat_conversaciones WHERE id = :id AND usuario_id = :usuario_id LIMIT 1');
    $stmt->bindValue(':id', $conversacionId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $conversacion = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$conversacion) {
    $stmt = $pdo->prepare(
        'INSERT INTO tbl_chat_conversaciones (usuario_id, asistente, session_token) VALUES (:usuario_id, :asistente, :session_token)'
    );
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':asistente', 'psicologico', PDO::PARAM_STR);
    $stmt->bindValue(':session_token', bin2hex(random_bytes(16)), PDO::PARAM_STR);
    $stmt->execute();

    $conversacionId = (int) $pdo->lastInsertId();
    $nivelRiesgoMaximoPrevio = 1;
} else {
    $conversacionId = (int) $conversacion['id'];
    $nivelRiesgoMaximoPrevio = (int) $conversacion['nivel_riesgo_maximo'];
}

$stmtInsertUsuaria = $pdo->prepare(
    'INSERT INTO tbl_chat_mensajes (conversacion_id, rol, contenido, origen_mensaje) VALUES (:conversacion_id, :rol, :contenido, :origen_mensaje)'
);
$stmtInsertUsuaria->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
$stmtInsertUsuaria->bindValue(':rol', 'usuaria', PDO::PARAM_STR);
$stmtInsertUsuaria->bindValue(':contenido', $mensaje, PDO::PARAM_STR);
$stmtInsertUsuaria->bindValue(':origen_mensaje', $origen, PDO::PARAM_STR);
$stmtInsertUsuaria->execute();

$ventana = $config->getVentanaHistorialMensajes();

$stmtHistorial = $pdo->prepare(
    'SELECT rol, contenido FROM tbl_chat_mensajes
     WHERE conversacion_id = :conversacion_id AND rol IN (\'usuaria\', \'asistente\')
     ORDER BY id DESC LIMIT :limite'
);
$stmtHistorial->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
$stmtHistorial->bindValue(':limite', $ventana, PDO::PARAM_INT);
$stmtHistorial->execute();

$historial = array_reverse($stmtHistorial->fetchAll(PDO::FETCH_ASSOC));
array_pop($historial);

$systemPrompt = file_get_contents(__DIR__ . '/../prompts/violeta_system_prompt.txt');

$anthropic = new AnthropicClient($config);
$respuestaClaude = $anthropic->enviarMensaje($systemPrompt, $historial, $mensaje);

if (!$respuestaClaude['ok']) {
    responderJson(502, ['error' => $respuestaClaude['error_mensaje']]);
}

$stmtInsertAsistente = $pdo->prepare(
    'INSERT INTO tbl_chat_mensajes (conversacion_id, rol, contenido, origen_mensaje, nivel_riesgo, senales_riesgo, accion_sugerida)
     VALUES (:conversacion_id, :rol, :contenido, :origen_mensaje, :nivel_riesgo, :senales_riesgo, :accion_sugerida)'
);
$stmtInsertAsistente->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':rol', 'asistente', PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':contenido', $respuestaClaude['texto_respuesta'], PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':origen_mensaje', 'texto', PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':nivel_riesgo', $respuestaClaude['nivel_riesgo'], PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':senales_riesgo', json_encode($respuestaClaude['senales'], JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':accion_sugerida', $respuestaClaude['requiere_botones_emergencia'] ? 'mostrar_botones_emergencia' : null, PDO::PARAM_STR);
$stmtInsertAsistente->execute();

$mensajeAsistenteId = (int) $pdo->lastInsertId();

$nivelRiesgoMaximoNuevo = max($nivelRiesgoMaximoPrevio, $respuestaClaude['nivel_riesgo']);

$stmtActualizarConversacion = $pdo->prepare(
    'UPDATE tbl_chat_conversaciones SET nivel_riesgo_maximo = :nivel_riesgo_maximo, fecha_ultima_actividad = NOW() WHERE id = :id'
);
$stmtActualizarConversacion->bindValue(':nivel_riesgo_maximo', $nivelRiesgoMaximoNuevo, PDO::PARAM_INT);
$stmtActualizarConversacion->bindValue(':id', $conversacionId, PDO::PARAM_INT);
$stmtActualizarConversacion->execute();

$riesgoService = new ChatRiesgoService($pdo, $config);
$riesgoService->procesarNivelRiesgo(
    $conversacionId,
    $usuarioId,
    $mensajeAsistenteId,
    $respuestaClaude['nivel_riesgo'],
    $respuestaClaude['resumen_auditoria']
);

$accionesSugeridas = [];

if ($respuestaClaude['nivel_riesgo'] >= 3) {
    $accionesSugeridas = ['llamar_policia', 'avisar_contacto', 'compartir_ubicacion'];
}

responderJson(200, [
    'conversacion_id' => $conversacionId,
    'mensaje_id' => $mensajeAsistenteId,
    'respuesta_texto' => $respuestaClaude['texto_respuesta'],
    'nivel_riesgo' => $respuestaClaude['nivel_riesgo'],
    'requiere_botones_emergencia' => $respuestaClaude['requiere_botones_emergencia'],
    'acciones_sugeridas' => $accionesSugeridas
]);

} catch (Throwable $e) {
    /*
     * Cualquier falla no prevista (conexión a BD caída, excepción de PDO, etc.)
     * debe devolver JSON limpio en vez de un error fatal de PHP - sobre todo
     * porque esto puede ocurrir justo después de que Claude ya clasificó un
     * riesgo alto y generó su respuesta.
     */
    error_log('api/chat_psicologico.php: ' . $e->getMessage());
    responderJson(500, ['error' => 'No fue posible procesar tu mensaje en este momento. Por favor intenta de nuevo.']);
}
