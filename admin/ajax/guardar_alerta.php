<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

date_default_timezone_set('America/Bogota');

const ALIADA_GUARDAR_ALERTA_VERSION =
    'COLOMBIA_DIAGNOSTICO_20260803_1836';

if (!headers_sent()) {
    header(
        'X-Aliada-Guardar-Alerta: ' .
        ALIADA_GUARDAR_ALERTA_VERSION
    );
}

/*
|--------------------------------------------------------------------------
| DEPENDENCIAS
|--------------------------------------------------------------------------
| Este archivo está ubicado en:
| admin/ajax/guardar_alerta.php
|
| La conexión está ubicada en:
| admin/classes/DbConection.php
*/
require_once __DIR__ . '/../classes/DbConection.php';

/*
|--------------------------------------------------------------------------
| RESPUESTA JSON
|--------------------------------------------------------------------------
*/
function responderJson(
    bool $valido,
    string $mensaje,
    array $datos = [],
    int $codigoHttp = 200
): void {
    http_response_code($codigoHttp);

    echo json_encode(
        array_merge(
            [
                'valid' => $valido,
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
    responderJson(
        false,
        'Método no permitido.',
        [],
        405
    );
}

/*
|--------------------------------------------------------------------------
| FECHA OFICIAL DE COLOMBIA
|--------------------------------------------------------------------------
| Esta fecha no depende de la zona horaria del servidor de Hostinger.
*/
$zonaColombia =
    new DateTimeZone(
        'America/Bogota'
    );

$ahoraColombia =
    new DateTimeImmutable(
        'now',
        $zonaColombia
    );

$fechaRegistro =
    $ahoraColombia->format(
        'Y-m-d H:i:s'
    );

$fechaArchivo =
    $ahoraColombia->format(
        'Ymd_His'
    );

$archivoRuta = null;
$rutaFisicaGuardada = null;
$pdo = null;

try {
    /*
    |--------------------------------------------------------------------------
    | DATOS RECIBIDOS
    |--------------------------------------------------------------------------
    */
    $tipo =
        trim(
            (string) (
                $_POST['tipo']
                ?? ''
            )
        );

    $usuarioId =
        isset($_POST['usuario_id'])
            ? (int) $_POST['usuario_id']
            : 0;

    /*
     * Si no llega usuario_id, intenta tomarlo desde sesión.
     */
    if ($usuarioId <= 0) {
        $usuarioId =
            (int) (
                $_SESSION['id']
                ?? $_SESSION['usuario_id']
                ?? $_SESSION['user_id']
                ?? $_SESSION['session_user']['id']
                ?? 0
            );
    }

    $nombreUsuario =
        trim(
            (string) (
                $_POST['nombre_usuario']
                ?? $_SESSION['nombre_completo']
                ?? $_SESSION['session_user_name']
                ?? ''
            )
        );

    $latitud =
        trim(
            (string) (
                $_POST['latitud']
                ?? ''
            )
        );

    $longitud =
        trim(
            (string) (
                $_POST['longitud']
                ?? ''
            )
        );

    $precisionGps =
        trim(
            (string) (
                $_POST['precision_gps']
                ?? ''
            )
        );

    $descripcion =
        trim(
            (string) (
                $_POST['descripcion']
                ?? ''
            )
        );

    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES
    |--------------------------------------------------------------------------
    */
    if ($tipo === '') {
        responderJson(
            false,
            'No se recibió el tipo de alerta.',
            [],
            422
        );
    }

    if ($usuarioId <= 0) {
        responderJson(
            false,
            'No fue posible identificar a la usuaria.',
            [],
            401
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR ARCHIVO DE EVIDENCIA
    |--------------------------------------------------------------------------
    */
    if (
        isset($_FILES['archivo']) &&
        is_array($_FILES['archivo']) &&
        (
            (int) (
                $_FILES['archivo']['error']
                ?? UPLOAD_ERR_NO_FILE
            )
        ) !== UPLOAD_ERR_NO_FILE
    ) {
        $archivo =
            $_FILES['archivo'];

        $errorArchivo =
            (int) (
                $archivo['error']
                ?? UPLOAD_ERR_NO_FILE
            );

        $mensajesErrorUpload = [
            UPLOAD_ERR_INI_SIZE =>
                'El archivo supera el tamaño permitido por el servidor.',

            UPLOAD_ERR_FORM_SIZE =>
                'El archivo supera el tamaño permitido por el formulario.',

            UPLOAD_ERR_PARTIAL =>
                'El archivo se recibió de forma incompleta.',

            UPLOAD_ERR_NO_FILE =>
                'No se recibió ningún archivo.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'El servidor no tiene una carpeta temporal disponible.',

            UPLOAD_ERR_CANT_WRITE =>
                'El servidor no pudo escribir el archivo.',

            UPLOAD_ERR_EXTENSION =>
                'Una extensión de PHP detuvo la carga del archivo.'
        ];

        if ($errorArchivo !== UPLOAD_ERR_OK) {
            responderJson(
                false,
                $mensajesErrorUpload[$errorArchivo]
                    ?? 'Ocurrió un error al subir el archivo.',
                [
                    'upload_error' =>
                        $errorArchivo
                ],
                400
            );
        }

        $archivoTemporal =
            (string) (
                $archivo['tmp_name']
                ?? ''
            );

        if (
            $archivoTemporal === '' ||
            !is_uploaded_file($archivoTemporal)
        ) {
            responderJson(
                false,
                'El archivo temporal no es válido.',
                [],
                400
            );
        }

        /*
         * Máximo permitido: 100 MB.
         */
        $tamanoMaximo =
            100 * 1024 * 1024;

        $tamanoArchivo =
            (int) (
                $archivo['size']
                ?? 0
            );

        if (
            $tamanoArchivo <= 0 ||
            $tamanoArchivo > $tamanoMaximo
        ) {
            responderJson(
                false,
                'El archivo está vacío o supera el máximo permitido de 100 MB.',
                [
                    'size_bytes' =>
                        $tamanoArchivo
                ],
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CARPETA DE EVIDENCIAS
        |--------------------------------------------------------------------------
        */
        $uploadDir =
            __DIR__ .
            '/../../uploads/evidencias/';

        if (!is_dir($uploadDir)) {
            if (
                !mkdir(
                    $uploadDir,
                    0755,
                    true
                ) &&
                !is_dir($uploadDir)
            ) {
                responderJson(
                    false,
                    'No fue posible crear la carpeta de evidencias.',
                    [],
                    500
                );
            }
        }

        if (!is_writable($uploadDir)) {
            responderJson(
                false,
                'La carpeta de evidencias no tiene permisos de escritura.',
                [],
                500
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR TIPO REAL DEL ARCHIVO
        |--------------------------------------------------------------------------
        */
        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

        $mimeType =
            (string) $finfo->file(
                $archivoTemporal
            );

        $tiposPermitidos = [
            'audio/webm' => [
                'extension' => 'webm',
                'prefijo' => 'audio'
            ],

            'audio/ogg' => [
                'extension' => 'ogg',
                'prefijo' => 'audio'
            ],

            'audio/mpeg' => [
                'extension' => 'mp3',
                'prefijo' => 'audio'
            ],

            'audio/wav' => [
                'extension' => 'wav',
                'prefijo' => 'audio'
            ],

            'audio/x-wav' => [
                'extension' => 'wav',
                'prefijo' => 'audio'
            ],

            'audio/mp4' => [
                'extension' => 'm4a',
                'prefijo' => 'audio'
            ],

            'video/webm' => [
                'extension' => 'webm',
                'prefijo' => 'video'
            ],

            'video/mp4' => [
                'extension' => 'mp4',
                'prefijo' => 'video'
            ],

            'video/quicktime' => [
                'extension' => 'mov',
                'prefijo' => 'video'
            ],

            'image/jpeg' => [
                'extension' => 'jpg',
                'prefijo' => 'foto'
            ],

            'image/png' => [
                'extension' => 'png',
                'prefijo' => 'foto'
            ],

            'image/webp' => [
                'extension' => 'webp',
                'prefijo' => 'foto'
            ]
        ];

        if (!isset($tiposPermitidos[$mimeType])) {
            responderJson(
                false,
                'El tipo real del archivo no está permitido.',
                [
                    'mime_type' =>
                        $mimeType
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
        | NOMBRE ÚNICO CON FECHA DE COLOMBIA
        |--------------------------------------------------------------------------
        */
        try {
            $codigoUnico =
                bin2hex(
                    random_bytes(4)
                );
        } catch (Throwable $e) {
            $codigoUnico =
                substr(
                    sha1(
                        uniqid(
                            '',
                            true
                        )
                    ),
                    0,
                    8
                );
        }

        $archivoNombre =
            $prefijo .
            '_' .
            $fechaArchivo .
            '_' .
            $codigoUnico .
            '.' .
            $extension;

        $rutaFinal =
            $uploadDir .
            $archivoNombre;

        if (
            !move_uploaded_file(
                $archivoTemporal,
                $rutaFinal
            )
        ) {
            responderJson(
                false,
                'No se pudo guardar el archivo de evidencia.',
                [],
                500
            );
        }

        @chmod(
            $rutaFinal,
            0644
        );

        $rutaFisicaGuardada =
            $rutaFinal;

        $archivoRuta =
            'uploads/evidencias/' .
            $archivoNombre;
    }

    /*
    |--------------------------------------------------------------------------
    | CONEXIÓN A BASE DE DATOS
    |--------------------------------------------------------------------------
    */
    $db =
        new DbConection();

    $pdo =
        $db->openConect();

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    /*
     * Ajusta la sesión de MySQL a Colombia.
     * Se usa -05:00 porque Colombia no maneja cambio de horario.
     */
    $pdo->exec(
        "SET SESSION time_zone = '-05:00'"
    );

    /*
    |--------------------------------------------------------------------------
    | DIAGNÓSTICO DE LA CONEXIÓN REAL
    |--------------------------------------------------------------------------
    */
    $diagnosticoMysql =
        $pdo->query(
            "
            SELECT
                DATABASE() AS base_datos,
                NOW() AS mysql_now,
                UTC_TIMESTAMP() AS mysql_utc,
                @@session.time_zone AS zona_sesion,
                @@global.time_zone AS zona_global
            "
        )->fetch(
            PDO::FETCH_ASSOC
        );

    /*
    |--------------------------------------------------------------------------
    | DATOS DE CONTACTO DESDE EL PERFIL DE LA USUARIA
    |--------------------------------------------------------------------------
    | Algunas alertas antiguas quedaron sin contacto en tbl_alerta_policia
    | porque este endpoint no lo guardaba. Se completa aquí para nuevas alertas.
    */
    $celularUsuaria = '';
    $contactoEmergencia = '';
    $nombreContactoEmergencia = '';

    $stmtUsuario = $pdo->prepare(
        'SELECT celular, contacto_emergencia, nombre_contacto, nombre, apellido
         FROM tbl_usuarios
         WHERE id = :id
         LIMIT 1'
    );
    $stmtUsuario->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $stmtUsuario->execute();
    $usuarioDb = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if ($usuarioDb) {
        $celularUsuaria = trim((string) ($usuarioDb['celular'] ?? ''));
        $contactoEmergencia = trim((string) ($usuarioDb['contacto_emergencia'] ?? ''));
        $nombreContactoEmergencia = trim((string) ($usuarioDb['nombre_contacto'] ?? ''));

        if ($nombreUsuario === '') {
            $nombreUsuario = trim(
                trim((string) ($usuarioDb['nombre'] ?? '')) . ' ' .
                trim((string) ($usuarioDb['apellido'] ?? ''))
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR ALERTA
    |--------------------------------------------------------------------------
    | La fecha se envía como parámetro desde PHP.
    | No se utiliza NOW() ni CURRENT_TIMESTAMP.
    */
    $sql = "
        INSERT INTO tbl_alerta_policia
        (
            usuario_id,
            nombre_usuaria,
            celular_usuaria,
            contacto_emergencia,
            nombre_contacto_emergencia,
            tipo_alerta,
            descripcion,
            latitud,
            longitud,
            precision_gps,
            archivo,
            estado,
            fecha_registro
        )
        VALUES
        (
            :usuario_id,
            :nombre_usuaria,
            :celular_usuaria,
            :contacto_emergencia,
            :nombre_contacto_emergencia,
            :tipo_alerta,
            :descripcion,
            :latitud,
            :longitud,
            :precision_gps,
            :archivo,
            'pendiente',
            :fecha_registro
        )
    ";

    $stmt =
        $pdo->prepare(
            $sql
        );

    $stmt->bindValue(
        ':usuario_id',
        $usuarioId,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':nombre_usuaria',
        $nombreUsuario,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':celular_usuaria',
        $celularUsuaria !== '' ? $celularUsuaria : null,
        $celularUsuaria !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ':contacto_emergencia',
        $contactoEmergencia !== '' ? $contactoEmergencia : null,
        $contactoEmergencia !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ':nombre_contacto_emergencia',
        $nombreContactoEmergencia !== '' ? $nombreContactoEmergencia : null,
        $nombreContactoEmergencia !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ':tipo_alerta',
        $tipo,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':descripcion',
        $descripcion,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':latitud',
        $latitud,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':longitud',
        $longitud,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':precision_gps',
        $precisionGps,
        PDO::PARAM_STR
    );

    if ($archivoRuta === null) {
        $stmt->bindValue(
            ':archivo',
            null,
            PDO::PARAM_NULL
        );
    } else {
        $stmt->bindValue(
            ':archivo',
            $archivoRuta,
            PDO::PARAM_STR
        );
    }

    $stmt->bindValue(
        ':fecha_registro',
        $fechaRegistro,
        PDO::PARAM_STR
    );

    $stmt->execute();

    $alertaId =
        (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | VOLVER A LEER LA FECHA GUARDADA
    |--------------------------------------------------------------------------
    | Así comprobamos el valor real almacenado, sin depender de otra pantalla.
    */
    $stmtVerificacion =
        $pdo->prepare(
            "
            SELECT
                id,
                fecha_registro,
                tipo_alerta,
                archivo
            FROM tbl_alerta_policia
            WHERE id = :id
            LIMIT 1
            "
        );

    $stmtVerificacion->bindValue(
        ':id',
        $alertaId,
        PDO::PARAM_INT
    );

    $stmtVerificacion->execute();

    $registroGuardado =
        $stmtVerificacion->fetch(
            PDO::FETCH_ASSOC
        ) ?: [];

    /*
    |--------------------------------------------------------------------------
    | REGISTRO LOCAL DE DIAGNÓSTICO
    |--------------------------------------------------------------------------
    */
    $lineaDiagnostico =
        json_encode(
            [
                'version' =>
                    ALIADA_GUARDAR_ALERTA_VERSION,

                'archivo_php' =>
                    __FILE__,

                'fecha_php' =>
                    $fechaRegistro,

                'timezone_php' =>
                    date_default_timezone_get(),

                'registro_bd' =>
                    $registroGuardado,

                'mysql' =>
                    $diagnosticoMysql
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ) .
        PHP_EOL;

    @file_put_contents(
        __DIR__ .
        '/guardar_alerta_diagnostico.log',
        $lineaDiagnostico,
        FILE_APPEND |
        LOCK_EX
    );

    /*
    |--------------------------------------------------------------------------
    | RESPUESTA EXITOSA
    |--------------------------------------------------------------------------
    */
    responderJson(
        true,
        'Alerta guardada correctamente con fecha y hora de Colombia.',
        [
            'id' =>
                $alertaId,

            'archivo' =>
                $archivoRuta,

            'fecha_registro' =>
                $fechaRegistro,

            'fecha_iso' =>
                $ahoraColombia->format(
                    DATE_ATOM
                ),

            'zona_horaria' =>
                'America/Bogota',

            'offset' =>
                '-05:00',

            'version_endpoint' =>
                ALIADA_GUARDAR_ALERTA_VERSION,

            'archivo_php_ejecutado' =>
                __FILE__,

            'timezone_php' =>
                date_default_timezone_get(),

            'fecha_enviada_por_php' =>
                $fechaRegistro,

            'registro_leido_desde_bd' =>
                $registroGuardado,

            'diagnostico_mysql' =>
                $diagnosticoMysql
        ]
    );

} catch (Throwable $e) {
    /*
     * Si el archivo se guardó pero falló el registro en BD,
     * se elimina para no dejar archivos huérfanos.
     */
    if (
        $rutaFisicaGuardada !== null &&
        is_file($rutaFisicaGuardada)
    ) {
        @unlink(
            $rutaFisicaGuardada
        );
    }

    responderJson(
        false,
        'Error al guardar la alerta: ' .
        $e->getMessage(),
        [],
        500
    );
}
