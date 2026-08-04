<?php
require_once __DIR__ . '/../include/generic_classes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Bogota');

$usuarioPanelId     = $_SESSION['panel_user']['id'] ?? 0;
$usuarioPanelNombre = trim($_SESSION['panel_user']['nombre_completo'] ?? '');
$usuarioPanelUser   = trim($_SESSION['panel_user']['usuario'] ?? '');
$usuarioPanelCorreo = trim($_SESSION['panel_user']['correo'] ?? '');
$usuarioPanelRol    = trim($_SESSION['panel_user']['rol'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $db = new DbConection();
    $pdo = $db->openConect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'marcar_en_atencion') {
        $alertaId = (int)($_POST['alerta_id'] ?? 0);

        if ($alertaId <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'ID de alerta inválido'
            ]);
            exit;
        }

        $sql = "UPDATE tbl_alerta_policia
                SET estado_atencion_visualizador = 'en_atencion',
                    fecha_apertura_modal = NOW(),
                    visualizador_usuario_id = :usuario_id
                WHERE id = :id
                  AND COALESCE(estado_atencion_visualizador, 'no_atendida') IN ('no_atendida','en_atencion')";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioPanelId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $alertaId, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'ok' => true,
            'message' => 'Emergencia marcada en atención'
        ]);
        exit;
    }

    if ($action === 'guardar_atencion') {
        $alertaId = (int)($_POST['alerta_id'] ?? 0);

        $nombreUsuaria = trim($_POST['nombre_usuaria'] ?? '');
        $celularUsuaria = trim($_POST['celular_usuaria'] ?? '');
        $contactoEmergencia = trim($_POST['contacto_emergencia'] ?? '');
        $nombreContactoEmergencia = trim($_POST['nombre_contacto_emergencia'] ?? '');
        $direccionDetectada = trim($_POST['direccion_detectada'] ?? '');
        $latitud = trim($_POST['latitud'] ?? '');
        $longitud = trim($_POST['longitud'] ?? '');
        $precisionMetros = trim($_POST['precision_metros'] ?? '');

        $reporte = trim($_POST['reporte'] ?? '');
        $tareaRealizada = trim($_POST['tarea_realizada'] ?? '');
        $cuadranteEnviado = trim($_POST['cuadrante_enviado'] ?? '');
        $gestionRealizada = trim($_POST['gestion_realizada'] ?? '');

        if (
            $alertaId <= 0 ||
            $reporte === '' ||
            $tareaRealizada === '' ||
            $cuadranteEnviado === '' ||
            $gestionRealizada === ''
        ) {
            echo json_encode([
                'ok' => false,
                'message' => 'Todos los campos de gestión son obligatorios'
            ]);
            exit;
        }

        $pdo->beginTransaction();

        $sqlInsert = "INSERT INTO tbl_alerta_policia_gestion (
                        alerta_id,
                        usuario_panel_id,
                        usuario_panel_nombre,
                        usuario_panel_usuario,
                        usuario_panel_correo,
                        usuario_panel_rol,
                        nombre_usuaria,
                        celular_usuaria,
                        contacto_emergencia,
                        nombre_contacto_emergencia,
                        direccion_detectada,
                        latitud,
                        longitud,
                        precision_metros,
                        reporte,
                        tarea_realizada,
                        cuadrante_enviado,
                        gestion_realizada,
                        fecha_gestion
                      ) VALUES (
                        :alerta_id,
                        :usuario_panel_id,
                        :usuario_panel_nombre,
                        :usuario_panel_usuario,
                        :usuario_panel_correo,
                        :usuario_panel_rol,
                        :nombre_usuaria,
                        :celular_usuaria,
                        :contacto_emergencia,
                        :nombre_contacto_emergencia,
                        :direccion_detectada,
                        :latitud,
                        :longitud,
                        :precision_metros,
                        :reporte,
                        :tarea_realizada,
                        :cuadrante_enviado,
                        :gestion_realizada,
                        NOW()
                      )";

        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':alerta_id' => $alertaId,
            ':usuario_panel_id' => $usuarioPanelId > 0 ? $usuarioPanelId : null,
            ':usuario_panel_nombre' => $usuarioPanelNombre,
            ':usuario_panel_usuario' => $usuarioPanelUser,
            ':usuario_panel_correo' => $usuarioPanelCorreo,
            ':usuario_panel_rol' => $usuarioPanelRol,
            ':nombre_usuaria' => $nombreUsuaria,
            ':celular_usuaria' => $celularUsuaria,
            ':contacto_emergencia' => $contactoEmergencia,
            ':nombre_contacto_emergencia' => $nombreContactoEmergencia,
            ':direccion_detectada' => $direccionDetectada,
            ':latitud' => $latitud,
            ':longitud' => $longitud,
            ':precision_metros' => $precisionMetros,
            ':reporte' => $reporte,
            ':tarea_realizada' => $tareaRealizada,
            ':cuadrante_enviado' => $cuadranteEnviado,
            ':gestion_realizada' => $gestionRealizada
        ]);

        $sqlUpdate = "UPDATE tbl_alerta_policia
                      SET estado_atencion_visualizador = 'atendida',
                          fecha_atencion = NOW(),
                          visualizador_usuario_id = :usuario_id
                      WHERE id = :id";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':usuario_id', $usuarioPanelId, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':id', $alertaId, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $pdo->commit();

        echo json_encode([
            'ok' => true,
            'message' => 'Atención guardada correctamente'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => false,
        'message' => 'Acción no válida'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'ok' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
    exit;
}