<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/classes/ConfigLoader.php';

function responderErrorStt(int $codigo, string $mensaje): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['error' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderErrorStt(405, 'Método no permitido.');
}

$csrfRecibido = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
$csrfSesion = trim((string) ($_SESSION['elevenlabs_csrf'] ?? ''));

if ($csrfRecibido === '' || $csrfSesion === '' || !hash_equals($csrfSesion, $csrfRecibido)) {
    responderErrorStt(403, 'Solicitud no autorizada.');
}

if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    responderErrorStt(422, 'No se recibió el audio grabado.');
}

$archivoTemporal = $_FILES['audio']['tmp_name'];

if (filesize($archivoTemporal) < 500) {
    responderErrorStt(422, 'La grabación es demasiado corta.');
}

// Safari/iOS graba audio/mp4 (.m4a); el resto de navegadores, audio/webm.
$extensionAudio = strtolower(pathinfo((string) ($_FILES['audio']['name'] ?? ''), PATHINFO_EXTENSION));
$nombreAudio = in_array($extensionAudio, ['webm', 'm4a', 'mp4', 'ogg'], true) ? 'grabacion.' . $extensionAudio : 'grabacion.webm';

$config = new ConfigLoader();

try {
    $apiKey = $config->getElevenLabsApiKey();
    $modelo = $config->getElevenLabsSttModel();
} catch (Exception $e) {
    responderErrorStt(500, 'Falta configurar la API Key de ElevenLabs para voz a texto.');
}

$curl = curl_init('https://api.elevenlabs.io/v1/speech-to-text');

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 12,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'xi-api-key: ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => [
        'model_id' => $modelo,
        'language_code' => 'es',
        'file' => new CURLFile($archivoTemporal, $_FILES['audio']['type'] ?: 'audio/webm', $nombreAudio)
    ]
]);

$respuesta = curl_exec($curl);
$errorCurl = curl_error($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($respuesta === false) {
    responderErrorStt(502, 'No fue posible conectar con ElevenLabs. Detalle: ' . $errorCurl);
}

$json = json_decode((string) $respuesta, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $detalle = is_array($json) ? ($json['detail'] ?? null) : null;
    error_log('elevenlabs_stt.php: HTTP ' . $httpCode . ' ' . json_encode($detalle, JSON_UNESCAPED_UNICODE));

    // Cuenta/cuota/servicio (401, 402, 429, 5xx): no es un problema del audio de la usuaria.
    if (in_array($httpCode, [401, 402, 429], true) || $httpCode >= 500) {
        responderErrorStt(503, 'La transcripción de voz no está disponible en este momento. Puedes escribir tu mensaje.');
    }

    responderErrorStt(502, is_string($detalle) ? $detalle : 'No fue posible transcribir el audio. Intenta grabar de nuevo o escribe tu mensaje.');
}

$texto = is_array($json) ? trim((string) ($json['text'] ?? '')) : '';

if ($texto === '') {
    responderErrorStt(422, 'No se detectó voz en la grabación.');
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
echo json_encode(['texto' => $texto], JSON_UNESCAPED_UNICODE);
