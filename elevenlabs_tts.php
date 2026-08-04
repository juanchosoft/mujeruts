<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE ELEVENLABS
|--------------------------------------------------------------------------
| Sustituye únicamente estos dos valores.
|
| IMPORTANTE:
| Este archivo se ejecuta en el servidor. La API Key nunca debe ponerse
| dentro del JavaScript de la página principal.
*/
$elevenLabsApiKey = getenv('ELEVENLABS_API_KEY') ?: 'sk_91d917234502033224764b1f96f9c50800277c81b8200739';
$elevenLabsVoiceId = getenv('ELEVENLABS_VOICE_ID') ?: 'jm2RgXtln3Fsh9DxVFYo';

/*
|--------------------------------------------------------------------------
| RESPUESTAS JSON DE ERROR
|--------------------------------------------------------------------------
*/
function responderError(int $codigo, string $mensaje, array $detalle = []): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');

    echo json_encode(
        array_merge(
            ['error' => $mensaje],
            $detalle
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDACIONES BÁSICAS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderError(
        405,
        'Método no permitido.'
    );
}

$csrfRecibido =
    trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$csrfSesion =
    trim((string) ($_SESSION['elevenlabs_csrf'] ?? ''));

if (
    $csrfRecibido === '' ||
    $csrfSesion === '' ||
    !hash_equals($csrfSesion, $csrfRecibido)
) {
    responderError(
        403,
        'Solicitud no autorizada.'
    );
}

if (
    $elevenLabsApiKey === '' ||
    $elevenLabsApiKey === 'PEGA_AQUI_TU_API_KEY'
) {
    responderError(
        500,
        'Falta configurar la API Key de ElevenLabs.'
    );
}

if (
    $elevenLabsVoiceId === '' ||
    $elevenLabsVoiceId === 'PEGA_AQUI_TU_VOICE_ID'
) {
    responderError(
        500,
        'Falta configurar el Voice ID de ElevenLabs.'
    );
}

if (!function_exists('curl_init')) {
    responderError(
        500,
        'La extensión cURL de PHP no está instalada.'
    );
}

/*
|--------------------------------------------------------------------------
| LEER TEXTO
|--------------------------------------------------------------------------
*/
$contenido =
    file_get_contents('php://input');

$datos =
    json_decode(
        $contenido ?: '',
        true
    );

if (!is_array($datos)) {
    responderError(
        400,
        'El cuerpo de la solicitud no es válido.'
    );
}

$texto =
    trim((string) ($datos['text'] ?? ''));

$texto =
    preg_replace(
        '/\s+/u',
        ' ',
        $texto
    ) ?? '';

if ($texto === '') {
    responderError(
        422,
        'No se recibió texto para convertir en audio.'
    );
}

/*
 * Limita el tamaño de cada fragmento para controlar consumo y latencia.
 */
if (mb_strlen($texto, 'UTF-8') > 1200) {
    responderError(
        422,
        'El fragmento de texto es demasiado largo.'
    );
}

/*
|--------------------------------------------------------------------------
| LLAMAR A ELEVENLABS
|--------------------------------------------------------------------------
| Flash v2.5 está pensado para conversaciones de baja latencia.
*/
$url =
    'https://api.elevenlabs.io/v1/text-to-speech/' .
    rawurlencode($elevenLabsVoiceId) .
    '/stream?output_format=mp3_44100_128';

$cuerpo = [
    'text' => $texto,
    'model_id' => 'eleven_flash_v2_5',
    'language_code' => 'es',

    'voice_settings' => [
        'stability' => 0.52,
        'similarity_boost' => 0.82,
        'style' => 0.05,
        'use_speaker_boost' => true,
        'speed' => 1.0
    ]
];

$curl =
    curl_init($url);

curl_setopt_array(
    $curl,
    [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 45,

        CURLOPT_HTTPHEADER => [
            'Accept: audio/mpeg',
            'Content-Type: application/json',
            'xi-api-key: ' . $elevenLabsApiKey
        ],

        CURLOPT_POSTFIELDS => json_encode(
            $cuerpo,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    ]
);

$respuesta =
    curl_exec($curl);

$errorCurl =
    curl_error($curl);

$codigoHttp =
    (int) curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

$tipoContenido =
    (string) curl_getinfo(
        $curl,
        CURLINFO_CONTENT_TYPE
    );

curl_close($curl);

if ($respuesta === false) {
    responderError(
        502,
        'No fue posible conectar con ElevenLabs.',
        [
            'detail' => $errorCurl
        ]
    );
}

if ($codigoHttp < 200 || $codigoHttp >= 300) {
    $mensajeElevenLabs =
        'ElevenLabs rechazó la solicitud.';

    $respuestaJson =
        json_decode(
            (string) $respuesta,
            true
        );

    if (is_array($respuestaJson)) {
        $detalle =
            $respuestaJson['detail'] ?? null;

        if (is_string($detalle) && $detalle !== '') {
            $mensajeElevenLabs = $detalle;
        } elseif (
            is_array($detalle) &&
            !empty($detalle['message'])
        ) {
            $mensajeElevenLabs =
                (string) $detalle['message'];
        }
    }

    responderError(
        502,
        $mensajeElevenLabs,
        [
            'elevenlabs_status' => $codigoHttp
        ]
    );
}

/*
|--------------------------------------------------------------------------
| DEVOLVER EL AUDIO
|--------------------------------------------------------------------------
*/
header(
    'Content-Type: ' .
    (
        str_contains(
            strtolower($tipoContenido),
            'audio'
        )
            ? $tipoContenido
            : 'audio/mpeg'
    )
);

header('Content-Length: ' . strlen((string) $respuesta));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

echo $respuesta;