<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Bogota');

require_once './admin/include/generic_classes.php';

$response = [
    'ok' => false,
    'message' => 'Solicitud inválida.'
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $usuarioId = 0;
    $nombreUsuaria = 'Usuaria';

    if (!empty($_SESSION['id'])) {
        $usuarioId = (int)$_SESSION['id'];
    } elseif (!empty($_SESSION['usuario_id'])) {
        $usuarioId = (int)$_SESSION['usuario_id'];
    } elseif (!empty($_SESSION['user_id'])) {
        $usuarioId = (int)$_SESSION['user_id'];
    } elseif (!empty($_SESSION['session_user']['id'])) {
        $usuarioId = (int)$_SESSION['session_user']['id'];
    }

    if (!empty($_SESSION['nombre_completo'])) {
        $nombreUsuaria = trim((string)$_SESSION['nombre_completo']);
    } elseif (!empty($_SESSION['nombre'])) {
        $nombreUsuaria = trim((string)$_SESSION['nombre']);
    } elseif (!empty($_SESSION['session_user']['nombre'])) {
        $nombre = trim((string)($_SESSION['session_user']['nombre'] ?? ''));
        $apellido = trim((string)($_SESSION['session_user']['ape'] ?? ''));
        $nombreUsuaria = trim($nombre . ' ' . $apellido);
    }

    if ($usuarioId <= 0) {
        throw new Exception('No se encontró la sesión de la usuaria.');
    }

    $tipoEvidencia = trim((string)($_POST['tipo_evidencia'] ?? ''));

    if ($tipoEvidencia === '') {
        throw new Exception('El tipo de evidencia es obligatorio.');
    }

    if (!isset($_FILES['archivo']) || empty($_FILES['archivo']['name'])) {
        throw new Exception('No se recibió ningún archivo.');
    }

    $archivo = $_FILES['archivo'];

    if ((int)$archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al cargar el archivo.');
    }

    $mimePermitidos = [
        'audio' => ['audio/webm', 'audio/mp3', 'audio/mpeg', 'audio/wav', 'audio/ogg'],
        'foto'  => ['image/jpeg', 'image/png', 'image/webp'],
        'video' => ['video/webm', 'video/mp4', 'video/ogg', 'video/quicktime']
    ];

    if (!isset($mimePermitidos[$tipoEvidencia])) {
        throw new Exception('Tipo de evidencia no válido.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeReal, $mimePermitidos[$tipoEvidencia], true)) {
        throw new Exception('Formato de archivo no permitido para ' . $tipoEvidencia . '.');
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension === '') {
        $mapExt = [
            'audio/webm' => 'webm',
            'audio/mp3' => 'mp3',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/webm' => 'webm',
            'video/mp4' => 'mp4',
            'video/ogg' => 'ogv',
            'video/quicktime' => 'mov'
        ];
        $extension = $mapExt[$mimeReal] ?? 'bin';
    }

    $directorioBase = __DIR__ . '/uploads/evidencias/';
    if (!is_dir($directorioBase)) {
        if (!mkdir($directorioBase, 0777, true)) {
            throw new Exception('No fue posible crear el directorio de evidencias.');
        }
    }

    $nombreArchivo = 'evidencia_' . $usuarioId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $rutaFisica = $directorioBase . $nombreArchivo;
    $rutaRelativa = 'uploads/evidencias/' . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
        throw new Exception('No fue posible guardar el archivo en el servidor.');
    }

    $db = new DbConection();
    $pdo = $db->openConect();

    $sql = "INSERT INTO tbl_evidencias_violencia (
                usuario_id,
                nombre_usuaria,
                tipo_evidencia,
                nombre_archivo,
                ruta_archivo,
                extension,
                tamano_bytes,
                fecha_registro
            ) VALUES (
                :usuario_id,
                :nombre_usuaria,
                :tipo_evidencia,
                :nombre_archivo,
                :ruta_archivo,
                :extension,
                :tamano_bytes,
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(':nombre_usuaria', $nombreUsuaria, PDO::PARAM_STR);
    $stmt->bindValue(':tipo_evidencia', $tipoEvidencia, PDO::PARAM_STR);
    $stmt->bindValue(':nombre_archivo', $nombreArchivo, PDO::PARAM_STR);
    $stmt->bindValue(':ruta_archivo', $rutaRelativa, PDO::PARAM_STR);
    $stmt->bindValue(':extension', $extension, PDO::PARAM_STR);
    $stmt->bindValue(':tamano_bytes', (int)$archivo['size'], PDO::PARAM_INT);
    $stmt->execute();

    $response['ok'] = true;
    $response['message'] = 'Evidencia guardada correctamente.';
    $response['ruta'] = $rutaRelativa;
    $response['tipo'] = $tipoEvidencia;
} catch (Throwable $e) {
    $response['ok'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;