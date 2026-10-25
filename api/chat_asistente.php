<?php

/*
 * Chat con Claude para los asistentes Trabajo Social, Jurídica y LGBTQ+.
 * Violeta IA (psicológico) tiene su propio endpoint: api/chat_psicologico.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../admin/classes/DbConection.php';
require_once __DIR__ . '/../admin/classes/ConfigLoader.php';
require_once __DIR__ . '/../admin/classes/AnthropicClient.php';
require_once __DIR__ . '/../admin/classes/ChatRiesgoService.php';
require_once __DIR__ . '/../admin/classes/AsistentesRegistro.php';
require_once __DIR__ . '/../admin/classes/Util.php';

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

// La sesión no se usa más en este request; liberarla evita bloquear otras
// peticiones de la misma usuaria mientras Claude responde (puede tardar).
session_write_close();

$cuerpo = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($cuerpo)) {
    responderJson(400, ['error' => 'El cuerpo de la solicitud no es válido.']);
}

$asistente = $cuerpo['asistente'] ?? '';

if (!is_string($asistente) || !in_array($asistente, AsistentesRegistro::CHAT_GENERICO, true)) {
    responderJson(400, ['error' => 'Asistente no válido.']);
}

$conversacionIdRecibido = $cuerpo['conversacion_id'] ?? null;

if ($conversacionIdRecibido !== null && filter_var($conversacionIdRecibido, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    responderJson(400, ['error' => 'Conversación no válida.']);
}

$conversacionId = (int) $conversacionIdRecibido;

if (!is_string($cuerpo['mensaje'] ?? '')) {
    responderJson(422, ['error' => 'El mensaje no es válido.']);
}

$mensaje = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($cuerpo['mensaje'] ?? '')) ?? '');
$origen = ($cuerpo['origen'] ?? 'texto') === 'voz' ? 'voz' : 'texto';

if ($mensaje === '') {
    responderJson(422, ['error' => 'El mensaje no puede estar vacío.']);
}

if (mb_strlen($mensaje, 'UTF-8') > 4000) {
    responderJson(422, ['error' => 'El mensaje es demasiado largo.']);
}

try {

$config = new ConfigLoader();
$configAsistente = $config->getConfigAsistente($asistente);
$definicion = AsistentesRegistro::obtener($asistente);

$reintentosApi = 2;
set_time_limit($configAsistente['timeout_segundos'] * ($reintentosApi + 1) + 30);

$db = new DbConection();
$pdo = $db->openConect();

$conversacion = null;

if ($conversacionId > 0) {
    $stmt = $pdo->prepare(
        'SELECT * FROM tbl_chat_conversaciones WHERE id = :id AND usuario_id = :usuario_id AND asistente = :asistente LIMIT 1'
    );
    $stmt->bindValue(':id', $conversacionId, PDO::PARAM_INT);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':asistente', $asistente, PDO::PARAM_STR);
    $stmt->execute();
    $conversacion = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$conversacion) {
    $stmt = $pdo->prepare(
        'INSERT INTO tbl_chat_conversaciones (usuario_id, asistente, session_token) VALUES (:usuario_id, :asistente, :session_token)'
    );
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':asistente', $asistente, PDO::PARAM_STR);
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

$stmtHistorial = $pdo->prepare(
    'SELECT rol, contenido FROM tbl_chat_mensajes
     WHERE conversacion_id = :conversacion_id AND rol IN (\'usuaria\', \'asistente\')
     ORDER BY id DESC LIMIT :limite'
);
$stmtHistorial->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
$stmtHistorial->bindValue(':limite', $config->getVentanaHistorialMensajes(), PDO::PARAM_INT);
$stmtHistorial->execute();

$historial = array_reverse($stmtHistorial->fetchAll(PDO::FETCH_ASSOC));
array_pop($historial);

$stmtTotal = $pdo->prepare(
    'SELECT COUNT(*) FROM tbl_chat_mensajes WHERE conversacion_id = :conversacion_id AND rol IN (\'usuaria\', \'asistente\')'
);
$stmtTotal->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
$stmtTotal->execute();

// Con la ventana recortada, el modelo no debe afirmar que la usuaria "no dijo" algo que quedó fuera.
if ((int) $stmtTotal->fetchColumn() - 1 > count($historial)) {
    array_unshift($historial, [
        'rol' => 'usuaria',
        'contenido' => '[Aviso del sistema, no escrito por la usuaria: esta conversación es más larga y los mensajes más antiguos no están disponibles para ti. Si la usuaria menciona algo que no ves, no digas que no lo contó; pídele con amabilidad que te lo recuerde.]'
    ]);
}
$stmtTotal = null;

$opciones = [
    'max_tokens' => $configAsistente['max_tokens'],
    'timeout' => $configAsistente['timeout_segundos'],
    'riesgo_extendido' => true,
    'tool_documento' => $configAsistente['documentos_habilitado'],
    'cachear_system' => true,
    'continuar_si_sin_texto' => true,
    'reintentos' => $reintentosApi
];

if ($configAsistente['web_search_habilitado']) {
    $opciones['web_search'] = ['max_uses' => $configAsistente['web_search_max_usos']];
}

// La llamada a Claude puede tardar decenas de segundos: la conexión a MySQL se
// libera durante la espera para no agotar max_connections con chats concurrentes.
$stmtInsertUsuaria = $stmtHistorial = $stmt = null;
$pdo = null;
$db->closeConect();

$anthropic = new AnthropicClient($config);
$respuestaClaude = $anthropic->enviarMensaje(AsistentesRegistro::leerPrompt($asistente), $historial, $mensaje, $opciones);

$pdo = $db->openConect();

if (!$respuestaClaude['ok']) {
    error_log('api/chat_asistente.php [' . $asistente . ']: ' . $respuestaClaude['error_mensaje']);
    $esLimite = mb_stripos($respuestaClaude['error_mensaje'], 'límite') !== false;
    responderJson(502, ['error' => $esLimite
        ? 'Hay muchas personas usando el servicio en este momento. Intenta de nuevo en unos segundos.'
        : 'No fue posible obtener una respuesta en este momento. Tu mensaje quedó guardado; intenta de nuevo en unos segundos.']);
}

$uso = $respuestaClaude['uso'];

/*
 * Salvaguarda de auditoría: un teléfono en la respuesta sin ninguna búsqueda
 * web en el turno sugiere un dato dado de memoria (el prompt lo prohíbe).
 */
if ($uso['busquedas_web'] === 0 && preg_match('/(\+?57[\s\-]?)?\(?\d{1,3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}/', $respuestaClaude['texto_respuesta'])) {
    error_log('api/chat_asistente.php [' . $asistente . ']: respuesta con posible teléfono sin búsqueda web (conversación ' . $conversacionId . ').');
    $respuestaClaude['texto_respuesta'] .= ' Ten en cuenta que este dato no se verificó en línea en este momento; confírmalo antes de acudir.';
}

// La sugerencia del modelo se guarda siempre; el botón solo se envía si la bandera
// está activa y el riesgo es menor a 3.
$derivacion = AsistentesRegistro::derivacion($respuestaClaude['derivar_a'], $asistente);
$derivacionVisible = (Util::MOSTRAR_BOTON_DERIVACION && $respuestaClaude['nivel_riesgo'] < 3) ? $derivacion : null;
$mostrarBotones = $configAsistente['botones_emergencia'] && $respuestaClaude['requiere_botones_emergencia'];

$stmtInsertAsistente = $pdo->prepare(
    'INSERT INTO tbl_chat_mensajes (
        conversacion_id, rol, contenido, origen_mensaje, nivel_riesgo, senales_riesgo, accion_sugerida,
        tokens_entrada, tokens_salida, busquedas_web, latencia_ms, derivar_a, fuentes_json, documento_json
     ) VALUES (
        :conversacion_id, :rol, :contenido, :origen_mensaje, :nivel_riesgo, :senales_riesgo, :accion_sugerida,
        :tokens_entrada, :tokens_salida, :busquedas_web, :latencia_ms, :derivar_a, :fuentes_json, :documento_json
     )'
);
$stmtInsertAsistente->bindValue(':conversacion_id', $conversacionId, PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':rol', 'asistente', PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':contenido', $respuestaClaude['texto_respuesta'], PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':origen_mensaje', 'texto', PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':nivel_riesgo', $respuestaClaude['nivel_riesgo'], PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':senales_riesgo', json_encode($respuestaClaude['senales'], JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':accion_sugerida', $mostrarBotones ? 'mostrar_botones_emergencia' : null, PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':tokens_entrada', $uso['tokens_entrada'], PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':tokens_salida', $uso['tokens_salida'], PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':busquedas_web', $uso['busquedas_web'], PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':latencia_ms', $respuestaClaude['latencia_ms'], PDO::PARAM_INT);
$stmtInsertAsistente->bindValue(':derivar_a', $derivacion['slug'] ?? null, PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':fuentes_json', $respuestaClaude['fuentes'] ? json_encode($respuestaClaude['fuentes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null, PDO::PARAM_STR);
$stmtInsertAsistente->bindValue(':documento_json', $respuestaClaude['documento'] ? json_encode($respuestaClaude['documento'], JSON_UNESCAPED_UNICODE) : null, PDO::PARAM_STR);
$stmtInsertAsistente->execute();

$mensajeAsistenteId = (int) $pdo->lastInsertId();

$stmtActualizarConversacion = $pdo->prepare(
    'UPDATE tbl_chat_conversaciones SET nivel_riesgo_maximo = :nivel_riesgo_maximo, fecha_ultima_actividad = NOW() WHERE id = :id'
);
$stmtActualizarConversacion->bindValue(':nivel_riesgo_maximo', max($nivelRiesgoMaximoPrevio, $respuestaClaude['nivel_riesgo']), PDO::PARAM_INT);
$stmtActualizarConversacion->bindValue(':id', $conversacionId, PDO::PARAM_INT);
$stmtActualizarConversacion->execute();

if ($configAsistente['escalamiento_alerta']) {
    $riesgoService = new ChatRiesgoService($pdo, $config);
    $riesgoService->procesarNivelRiesgo(
        $conversacionId,
        $usuarioId,
        $mensajeAsistenteId,
        $respuestaClaude['nivel_riesgo'],
        $respuestaClaude['resumen_auditoria'],
        $definicion['prefijo_alerta'],
        $configAsistente['escalamiento_whatsapp']
    );
}

$accionesSugeridas = [];

if ($configAsistente['botones_emergencia'] && $respuestaClaude['nivel_riesgo'] >= 3) {
    $accionesSugeridas = ['llamar_policia', 'avisar_contacto', 'compartir_ubicacion'];
}

responderJson(200, [
    'conversacion_id' => $conversacionId,
    'mensaje_id' => $mensajeAsistenteId,
    'respuesta_texto' => $respuestaClaude['texto_respuesta'],
    'nivel_riesgo' => $respuestaClaude['nivel_riesgo'],
    'requiere_botones_emergencia' => $mostrarBotones,
    'acciones_sugeridas' => $accionesSugeridas,
    'documento' => $respuestaClaude['documento'],
    'fuentes' => $respuestaClaude['fuentes'],
    'derivar_a' => $derivacionVisible
]);

} catch (Throwable $e) {
    error_log('api/chat_asistente.php: ' . $e->getMessage());
    responderJson(500, ['error' => 'No fue posible procesar tu mensaje en este momento. Por favor intenta de nuevo.']);
}
