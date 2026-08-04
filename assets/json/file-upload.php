<?php
declare(strict_types=1);

if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/
$carpetaUploads = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

/*
 * Tamaño máximo permitido: 100 MB.
 * También debe coincidir con upload_max_filesize y post_max_size de PHP.
 */
$tamanoMaximo = 100 * 1024 * 1024;

/*
|--------------------------------------------------------------------------
| RESPUESTA JSON
|--------------------------------------------------------------------------
*/
function responderUpload(
    bool $valido,
    string $mensaje,
    array $datos = [],
    int $codigoHttp = 200
): void {
    http_response_code($codigoHttp);

    echo json_encode(
        array_merge(
            [
                'valid'   => $valido,
                'message' => $mensaje
            ],
            $datos
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR MÉTODO
|--------------------------------------------------------------------------
*/
if (
    !isset($_SERVER['REQUEST_METHOD']) ||
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    responderUpload(
        false,
        'Método no permitido.',
        [],
        405
    );
}

/*
|--------------------------------------------------------------------------
| CREAR CARPETA SI NO EXISTE
|--------------------------------------------------------------------------
*/
if (!is_dir($carpetaUploads)) {
    if (
        !mkdir(
            $carpetaUploads,
            0755,
            true
        ) &&
        !is_dir($carpetaUploads)
    ) {
        responderUpload(
            false,
            'No fue posible crear la carpeta de evidencias.',
            [],
            500
        );
    }
}

if (!is_writable($carpetaUploads)) {
    responderUpload(
        false,
        'La carpeta uploads no tiene permisos de escritura.',
        [],
        500
    );
}

/*
|--------------------------------------------------------------------------
| LOCALIZAR ARCHIVO
|--------------------------------------------------------------------------
| Acepta tanto:
| $_FILES['file']
| $_FILES['archivo']
*/
$archivo = null;

if (
    isset($_FILES['file']) &&
    is_array($_FILES['file'])
) {
    $archivo = $_FILES['file'];

} elseif (
    isset($_FILES['archivo']) &&
    is_array($_FILES['archivo'])
) {
    $archivo = $_FILES['archivo'];
}

if ($archivo === null) {
    responderUpload(
        false,
        'No se recibió ningún archivo.',
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| VALIDAR ERROR DE SUBIDA
|--------------------------------------------------------------------------
*/
$errorUpload =
    (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

$mensajesError = [
    UPLOAD_ERR_INI_SIZE =>
        'El archivo supera el límite configurado en el servidor.',

    UPLOAD_ERR_FORM_SIZE =>
        'El archivo supera el tamaño permitido por el formulario.',

    UPLOAD_ERR_PARTIAL =>
        'El archivo se subió parcialmente.',

    UPLOAD_ERR_NO_FILE =>
        'No se seleccionó ningún archivo.',

    UPLOAD_ERR_NO_TMP_DIR =>
        'El servidor no tiene una carpeta temporal disponible.',

    UPLOAD_ERR_CANT_WRITE =>
        'El servidor no pudo escribir el archivo.',

    UPLOAD_ERR_EXTENSION =>
        'Una extensión de PHP detuvo la carga.'
];

if ($errorUpload !== UPLOAD_ERR_OK) {
    responderUpload(
        false,
        $mensajesError[$errorUpload]
            ?? 'Ocurrió un error al recibir el archivo.',
        [
            'upload_error' => $errorUpload
        ],
        400
    );
}

$archivoTemporal =
    (string) ($archivo['tmp_name'] ?? '');

$nombreOriginal =
    trim(
        (string) ($archivo['name'] ?? '')
    );

$tamanoArchivo =
    (int) ($archivo['size'] ?? 0);

if (
    $archivoTemporal === '' ||
    !is_uploaded_file($archivoTemporal)
) {
    responderUpload(
        false,
        'El archivo temporal no es válido.',
        [],
        400
    );
}

if (
    $tamanoArchivo <= 0 ||
    $tamanoArchivo > $tamanoMaximo
) {
    responderUpload(
        false,
        'El archivo está vacío o supera el máximo de 100 MB.',
        [
            'size_bytes' => $tamanoArchivo
        ],
        422
    );
}

/*
|--------------------------------------------------------------------------
| VALIDAR TIPO REAL DEL ARCHIVO
|--------------------------------------------------------------------------
*/
$finfo =
    new finfo(FILEINFO_MIME_TYPE);

$mimeType =
    (string) $finfo->file(
        $archivoTemporal
    );

/*
 * Extensiones permitidas para evidencias.
 */
$tiposPermitidos = [
    'image/jpeg' => [
        'extension' => 'jpg',
        'prefijo'   => 'foto'
    ],

    'image/png' => [
        'extension' => 'png',
        'prefijo'   => 'foto'
    ],

    'image/webp' => [
        'extension' => 'webp',
        'prefijo'   => 'foto'
    ],

    'audio/webm' => [
        'extension' => 'webm',
        'prefijo'   => 'audio'
    ],

    'audio/ogg' => [
        'extension' => 'ogg',
        'prefijo'   => 'audio'
    ],

    'audio/mpeg' => [
        'extension' => 'mp3',
        'prefijo'   => 'audio'
    ],

    'audio/mp4' => [
        'extension' => 'm4a',
        'prefijo'   => 'audio'
    ],

    'video/webm' => [
        'extension' => 'webm',
        'prefijo'   => 'video'
    ],

    'video/mp4' => [
        'extension' => 'mp4',
        'prefijo'   => 'video'
    ],

    'video/quicktime' => [
        'extension' => 'mov',
        'prefijo'   => 'video'
    ],

    'application/pdf' => [
        'extension' => 'pdf',
        'prefijo'   => 'documento'
    ]
];

if (!isset($tiposPermitidos[$mimeType])) {
    responderUpload(
        false,
        'El tipo de archivo no está permitido.',
        [
            'mime_type' => $mimeType
        ],
        422
    );
}

$extension =
    $tiposPermitidos[$mimeType]['extension'];

$prefijo =
    $tiposPermitidos[$mimeType]['prefijo'];

/*
|--------------------------------------------------------------------------
| FECHA DE COLOMBIA
|--------------------------------------------------------------------------
*/
$fechaColombia =
    date('Y-m-d H:i:s');

$fechaArchivo =
    date('Ymd_His');

/*
 * Código aleatorio para impedir archivos repetidos.
 */
try {
    $codigoUnico =
        bin2hex(
            random_bytes(4)
        );
} catch (Throwable $e) {
    $codigoUnico =
        str_replace(
            '.',
            '',
            uniqid('', true)
        );
}

/*
|--------------------------------------------------------------------------
| CREAR NOMBRE SEGURO
|--------------------------------------------------------------------------
| Ejemplos:
| audio_20260803_181530_a17b43cd.webm
| foto_20260803_181535_883ae620.jpg
| video_20260803_181542_2d59b912.mp4
*/
$nombreGuardado =
    $prefijo .
    '_' .
    $fechaArchivo .
    '_' .
    $codigoUnico .
    '.' .
    $extension;

$rutaFinal =
    $carpetaUploads .
    DIRECTORY_SEPARATOR .
    $nombreGuardado;

/*
|--------------------------------------------------------------------------
| MOVER ARCHIVO
|--------------------------------------------------------------------------
*/
if (
    !move_uploaded_file(
        $archivoTemporal,
        $rutaFinal
    )
) {
    responderUpload(
        false,
        'No fue posible guardar el archivo en el servidor.',
        [],
        500
    );
}

/*
 * Permisos de lectura para el servidor web.
 */
@chmod(
    $rutaFinal,
    0644
);

/*
|--------------------------------------------------------------------------
| RESPUESTA EXITOSA
|--------------------------------------------------------------------------
*/
responderUpload(
    true,
    'Evidencia guardada correctamente con fecha de Colombia.',
    [
        'archivo_original' => $nombreOriginal,
        'archivo_guardado' => $nombreGuardado,
        'ruta_relativa'    => 'uploads/' . $nombreGuardado,
        'mime_type'        => $mimeType,
        'size_bytes'       => $tamanoArchivo,
        'fecha_evidencia'  => $fechaColombia,
        'zona_horaria'     => 'America/Bogota'
    ]
);