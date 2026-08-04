<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Bogota');

$response = [
    'ok'      => false,
    'message' => 'Solicitud no válida.'
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    if (!class_exists('DbConection')) {
        require_once './admin/classes/DbConection.php';
    }

    $usuarioId = 0;

    if (!empty($_SESSION['id'])) {
        $usuarioId = (int) $_SESSION['id'];
    } elseif (!empty($_SESSION['usuario_id'])) {
        $usuarioId = (int) $_SESSION['usuario_id'];
    } elseif (!empty($_SESSION['user_id'])) {
        $usuarioId = (int) $_SESSION['user_id'];
    } elseif (!empty($_SESSION['session_user']['id'])) {
        $usuarioId = (int) $_SESSION['session_user']['id'];
    }

    if ($usuarioId <= 0) {
        throw new Exception('No se encontró la sesión de la usuaria.');
    }

    $latitud             = trim((string)($_POST['latitud'] ?? ''));
    $longitud            = trim((string)($_POST['longitud'] ?? ''));
    $direccion           = trim((string)($_POST['direccion'] ?? ''));
    $precision           = trim((string)($_POST['precision'] ?? ''));
    $ubicacionConfirmada = trim((string)($_POST['ubicacion_confirmada'] ?? 'si'));

    if ($ubicacionConfirmada === '') {
        $ubicacionConfirmada = 'si';
    }

    $db  = new DbConection();
    $pdo = $db->openConect();
    $pdo->beginTransaction();

    $sqlUsuario = "SELECT
                        id,
                        nombre,
                        apellido,
                        celular,
                        contacto_emergencia,
                        nombre_contacto
                   FROM tbl_usuarios
                   WHERE id = :id
                   LIMIT 1";

    $stmtUsuario = $pdo->prepare($sqlUsuario);
    $stmtUsuario->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $stmtUsuario->execute();

    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception('No se encontró la información de la usuaria.');
    }

    $nombreUsuaria = trim(
        (string)($usuario['nombre'] ?? '') . ' ' . (string)($usuario['apellido'] ?? '')
    );

    if ($nombreUsuaria === '') {
        $nombreUsuaria = 'Usuaria';
    }

    $celularUsuaria           = trim((string)($usuario['celular'] ?? ''));
    $contactoEmergencia       = trim((string)($usuario['contacto_emergencia'] ?? ''));
    $nombreContactoEmergencia = trim((string)($usuario['nombre_contacto'] ?? ''));

    // Si no llegó ubicación completa, no se bloquea el proceso.
    if ($latitud === '') {
        $latitud = null;
    }

    if ($longitud === '') {
        $longitud = null;
    }

    if ($direccion === '') {
        $direccion = 'Ubicación no disponible al momento de generar la alerta';
    }

    if ($precision === '') {
        $precision = null;
    }

    $sqlInsertAlerta = "INSERT INTO tbl_alerta_policia (
                            usuario_id,
                            nombre_usuaria,
                            celular_usuaria,
                            contacto_emergencia,
                            nombre_contacto_emergencia,
                            direccion_detectada,
                            latitud,
                            longitud,
                            precision_metros,
                            ubicacion_confirmada,
                            fecha_registro
                        ) VALUES (
                            :usuario_id,
                            :nombre_usuaria,
                            :celular_usuaria,
                            :contacto_emergencia,
                            :nombre_contacto_emergencia,
                            :direccion_detectada,
                            :latitud,
                            :longitud,
                            :precision_metros,
                            :ubicacion_confirmada,
                            NOW()
                        )";

    $stmtAlerta = $pdo->prepare($sqlInsertAlerta);
    $stmtAlerta->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmtAlerta->bindValue(':nombre_usuaria', $nombreUsuaria, PDO::PARAM_STR);
    $stmtAlerta->bindValue(':celular_usuaria', $celularUsuaria, PDO::PARAM_STR);
    $stmtAlerta->bindValue(':contacto_emergencia', $contactoEmergencia, PDO::PARAM_STR);
    $stmtAlerta->bindValue(':nombre_contacto_emergencia', $nombreContactoEmergencia, PDO::PARAM_STR);
    $stmtAlerta->bindValue(':direccion_detectada', $direccion, PDO::PARAM_STR);

    if ($latitud === null) {
        $stmtAlerta->bindValue(':latitud', null, PDO::PARAM_NULL);
    } else {
        $stmtAlerta->bindValue(':latitud', $latitud, PDO::PARAM_STR);
    }

    if ($longitud === null) {
        $stmtAlerta->bindValue(':longitud', null, PDO::PARAM_NULL);
    } else {
        $stmtAlerta->bindValue(':longitud', $longitud, PDO::PARAM_STR);
    }

    if ($precision === null) {
        $stmtAlerta->bindValue(':precision_metros', null, PDO::PARAM_NULL);
    } else {
        $stmtAlerta->bindValue(':precision_metros', $precision, PDO::PARAM_STR);
    }

    $stmtAlerta->bindValue(':ubicacion_confirmada', $ubicacionConfirmada, PDO::PARAM_STR);
    $stmtAlerta->execute();

    $alertaId = (int) $pdo->lastInsertId();

    // Centro de monitoreo: si existe la tabla, también registra.
    try {
        $sqlInsertMonitoreo = "INSERT INTO tbl_centro_monitoreo_alertas (
                                    alerta_id,
                                    usuario_id,
                                    tipo_alerta,
                                    estado,
                                    prioridad,
                                    nombre_usuaria,
                                    celular_usuaria,
                                    contacto_emergencia,
                                    nombre_contacto_emergencia,
                                    direccion_detectada,
                                    latitud,
                                    longitud,
                                    precision_metros,
                                    ubicacion_confirmada,
                                    observacion,
                                    fecha_registro
                               ) VALUES (
                                    :alerta_id,
                                    :usuario_id,
                                    'POLICIA',
                                    'PENDIENTE',
                                    'ALTA',
                                    :nombre_usuaria,
                                    :celular_usuaria,
                                    :contacto_emergencia,
                                    :nombre_contacto_emergencia,
                                    :direccion_detectada,
                                    :latitud,
                                    :longitud,
                                    :precision_metros,
                                    :ubicacion_confirmada,
                                    :observacion,
                                    NOW()
                               )";

        $stmtMonitoreo = $pdo->prepare($sqlInsertMonitoreo);
        $stmtMonitoreo->bindValue(':alerta_id', $alertaId, PDO::PARAM_INT);
        $stmtMonitoreo->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmtMonitoreo->bindValue(':nombre_usuaria', $nombreUsuaria, PDO::PARAM_STR);
        $stmtMonitoreo->bindValue(':celular_usuaria', $celularUsuaria, PDO::PARAM_STR);
        $stmtMonitoreo->bindValue(':contacto_emergencia', $contactoEmergencia, PDO::PARAM_STR);
        $stmtMonitoreo->bindValue(':nombre_contacto_emergencia', $nombreContactoEmergencia, PDO::PARAM_STR);
        $stmtMonitoreo->bindValue(':direccion_detectada', $direccion, PDO::PARAM_STR);

        if ($latitud === null) {
            $stmtMonitoreo->bindValue(':latitud', null, PDO::PARAM_NULL);
        } else {
            $stmtMonitoreo->bindValue(':latitud', $latitud, PDO::PARAM_STR);
        }

        if ($longitud === null) {
            $stmtMonitoreo->bindValue(':longitud', null, PDO::PARAM_NULL);
        } else {
            $stmtMonitoreo->bindValue(':longitud', $longitud, PDO::PARAM_STR);
        }

        if ($precision === null) {
            $stmtMonitoreo->bindValue(':precision_metros', null, PDO::PARAM_NULL);
        } else {
            $stmtMonitoreo->bindValue(':precision_metros', $precision, PDO::PARAM_STR);
        }

        $stmtMonitoreo->bindValue(':ubicacion_confirmada', $ubicacionConfirmada, PDO::PARAM_STR);
        $stmtMonitoreo->bindValue(':observacion', 'Alerta generada desde botón Policía', PDO::PARAM_STR);
        $stmtMonitoreo->execute();
    } catch (Throwable $e) {
        // No bloquear el flujo si esta tabla no existe o falla.
    }

    $pdo->commit();

    $response['ok'] = true;
    $response['message'] = 'Alerta registrada correctamente.';
    $response['alerta_id'] = $alertaId;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $response['ok'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;