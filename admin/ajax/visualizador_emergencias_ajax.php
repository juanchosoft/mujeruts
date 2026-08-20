<?php
require_once __DIR__ . '/../include/generic_classes.php';
require_once __DIR__ . '/../../enviar_whatsapp_emergencia.php';
require_once __DIR__ . '/../classes/HmsVideoService.php';
require_once __DIR__ . '/../classes/ZadarmaService.php';

function purgarComunicacionesObsoletas($pdo)
{
    $pdo->exec("DELETE FROM tbl_comunicacion_activa WHERE iniciado_en < (NOW() - INTERVAL 2 HOUR)");
}

/**
 * Candado de concurrencia entre llamadas (Zadarma) y videollamadas (100ms):
 * mientras haya una fila activa en tbl_comunicacion_activa no se puede
 * iniciar otra comunicación. Con una sola línea el candado es global; si se
 * agregan más líneas, filtrar por linea_id en vez de bloquear todo.
 */
function adquirirCandadoComunicacion($pdo, $tipo, $alertaId, $usuarioPanelId)
{
    purgarComunicacionesObsoletas($pdo);

    $stmtCheck = $pdo->query("SELECT tipo FROM tbl_comunicacion_activa ORDER BY id DESC LIMIT 1");
    $activa = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($activa) {
        $tipoTexto = $activa['tipo'] === 'llamada' ? 'una llamada' : 'una videollamada';

        return [
            'ok' => false,
            'message' => 'Ya hay ' . $tipoTexto . ' en curso. Espera a que termine antes de iniciar otra.'
        ];
    }

    $stmtLinea = $pdo->query("SELECT id FROM tbl_zadarma_lineas WHERE activa = 1 ORDER BY id ASC LIMIT 1");
    $linea = $stmtLinea->fetch(PDO::FETCH_ASSOC);

    $stmtInsert = $pdo->prepare(
        "INSERT INTO tbl_comunicacion_activa (tipo, alerta_id, usuario_panel_id, linea_id, iniciado_en)
         VALUES (:tipo, :alerta_id, :usuario_panel_id, :linea_id, NOW())"
    );
    $stmtInsert->execute([
        ':tipo' => $tipo,
        ':alerta_id' => $alertaId,
        ':usuario_panel_id' => $usuarioPanelId > 0 ? $usuarioPanelId : null,
        ':linea_id' => $linea['id'] ?? null
    ]);

    return [
        'ok' => true,
        'comunicacion_id' => (int) $pdo->lastInsertId()
    ];
}

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

if ($usuarioPanelId <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'Sesión de panel no válida'
    ]);
    exit;
}

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

    if ($action === 'alertar_contacto_emergencia') {
        $alertaId = (int) ($_POST['alerta_id'] ?? 0);

        if ($alertaId <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'ID de alerta inválido'
            ]);
            exit;
        }

        $stmtAlerta = $pdo->prepare(
            'SELECT
                a.nombre_usuaria,
                COALESCE(NULLIF(TRIM(a.contacto_emergencia), \'\'), u.contacto_emergencia) AS contacto_emergencia,
                COALESCE(NULLIF(TRIM(a.nombre_contacto_emergencia), \'\'), u.nombre_contacto) AS nombre_contacto_emergencia
             FROM tbl_alerta_policia a
             LEFT JOIN tbl_usuarios u ON u.id = a.usuario_id
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmtAlerta->bindValue(':id', $alertaId, PDO::PARAM_INT);
        $stmtAlerta->execute();
        $alerta = $stmtAlerta->fetch(PDO::FETCH_ASSOC);

        if (!$alerta) {
            echo json_encode([
                'ok' => false,
                'message' => 'No se encontró la alerta indicada'
            ]);
            exit;
        }

        $telefonoContacto = trim((string) ($alerta['contacto_emergencia'] ?? ''));
        $nombreContacto = trim((string) ($alerta['nombre_contacto_emergencia'] ?? ''));
        $nombreUsuaria = trim((string) ($alerta['nombre_usuaria'] ?? ''));

        if ($telefonoContacto === '' || normalizarTelefonoWhatsApp($telefonoContacto) === '') {
            echo json_encode([
                'ok' => false,
                'message' => 'Esta usuaria no tiene un contacto de emergencia registrado.'
            ]);
            exit;
        }

        $resultadoWhatsApp = enviarWhatsAppAlertaRiesgo(
            $telefonoContacto,
            $nombreContacto,
            $nombreUsuaria
        );

        if (empty($resultadoWhatsApp['ok'])) {
            echo json_encode([
                'ok' => false,
                'message' => $resultadoWhatsApp['message'] ?? 'No fue posible enviar la alerta por WhatsApp.'
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'message' => 'Se envió la plantilla de WhatsApp al contacto de emergencia.',
            'contacto_nombre' => $nombreContacto !== '' ? $nombreContacto : 'contacto de emergencia',
            'contacto_telefono' => $telefonoContacto
        ]);
        exit;
    }

    if ($action === 'iniciar_videollamada') {
        $alertaId = (int) ($_POST['alerta_id'] ?? 0);
        $nombreUsuaria = trim($_POST['nombre_usuaria'] ?? '');

        if ($alertaId <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'ID de alerta inválido'
            ]);
            exit;
        }

        $candado = adquirirCandadoComunicacion($pdo, 'videollamada', $alertaId, $usuarioPanelId);

        if (empty($candado['ok'])) {
            echo json_encode([
                'ok' => false,
                'message' => $candado['message']
            ]);
            exit;
        }

        $hms = new HmsVideoService();
        $resultado = $hms->iniciarVideollamada($alertaId, $nombreUsuaria, $usuarioPanelNombre);

        if (empty($resultado['ok'])) {
            $pdo->prepare('DELETE FROM tbl_comunicacion_activa WHERE id = :id')
                ->execute([':id' => $candado['comunicacion_id']]);

            echo json_encode([
                'ok' => false,
                'message' => $resultado['message'] ?? 'No fue posible iniciar la videollamada.'
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'comunicacion_id' => $candado['comunicacion_id'],
            'room_name' => $resultado['room_name'],
            'host_url' => $resultado['host_url'],
            'guest_url' => $resultado['guest_url']
        ]);
        exit;
    }

    if ($action === 'enviar_video_whatsapp' || $action === 'enviar_video_sms') {
        $alertaId = (int) ($_POST['alerta_id'] ?? 0);
        $telefono = trim($_POST['telefono'] ?? '');
        $guestUrl = trim($_POST['guest_url'] ?? '');
        $roomName = trim($_POST['room_name'] ?? '');

        if ($alertaId <= 0 || $guestUrl === '') {
            echo json_encode([
                'ok' => false,
                'message' => 'Faltan datos para enviar el enlace de videollamada.'
            ]);
            exit;
        }

        $telefonoNormalizado = normalizarTelefonoWhatsApp($telefono);

        if ($telefonoNormalizado === '') {
            echo json_encode([
                'ok' => false,
                'message' => 'No hay un número de teléfono válido para enviar el enlace.'
            ]);
            exit;
        }

        $canal = $action === 'enviar_video_whatsapp' ? 'whatsapp' : 'sms';
        $medio = $canal === 'whatsapp' ? 'WhatsApp' : 'SMS';

        // Envío simulado: no se llama a WhatsApp Cloud API ni a un proveedor de
        // SMS. Se deja la invitación pendiente en tbl_videollamada_invitacion
        // para que la usuaria la vea en su app (polling en videollamada_banner.php).
        $stmtUsuarioAlerta = $pdo->prepare('SELECT usuario_id FROM tbl_alerta_policia WHERE id = :id LIMIT 1');
        $stmtUsuarioAlerta->bindValue(':id', $alertaId, PDO::PARAM_INT);
        $stmtUsuarioAlerta->execute();
        $usuarioIdAlerta = (int) ($stmtUsuarioAlerta->fetchColumn() ?: 0);

        if ($usuarioIdAlerta <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'No fue posible ubicar la usuaria asociada a esta alerta para entregarle el enlace.'
            ]);
            exit;
        }

        $stmtInvitacion = $pdo->prepare(
            "INSERT INTO tbl_videollamada_invitacion
                (alerta_id, usuario_id, room_name, guest_url, canal, estado, creado_en)
             VALUES
                (:alerta_id, :usuario_id, :room_name, :guest_url, :canal, 'pendiente', NOW())"
        );
        $stmtInvitacion->execute([
            ':alerta_id' => $alertaId,
            ':usuario_id' => $usuarioIdAlerta,
            ':room_name' => $roomName,
            ':guest_url' => $guestUrl,
            ':canal' => $canal
        ]);

        echo json_encode([
            'ok' => true,
            'message' => 'Se simuló el envío de la plantilla de videollamada por ' . $medio . ' al número ' . $telefonoNormalizado . '. El enlace ya está disponible en la aplicación de la usuaria.',
            'telefono' => $telefonoNormalizado,
            'guest_url' => $guestUrl
        ]);
        exit;
    }

    if ($action === 'obtener_credenciales_llamada') {
        $stmtLinea = $pdo->query("SELECT sip_login FROM tbl_zadarma_lineas WHERE activa = 1 ORDER BY id ASC LIMIT 1");
        $linea = $stmtLinea->fetch(PDO::FETCH_ASSOC);

        if (!$linea) {
            echo json_encode([
                'ok' => false,
                'message' => 'No hay una línea de Zadarma configurada.'
            ]);
            exit;
        }

        $zadarma = new ZadarmaService();
        $resultado = $zadarma->obtenerWebrtcKey($linea['sip_login']);

        if (empty($resultado['ok'])) {
            echo json_encode([
                'ok' => false,
                'message' => $resultado['message'] ?? 'No fue posible obtener las credenciales de llamada.'
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'webrtc_key' => $resultado['key'],
            'sip_login' => $linea['sip_login']
        ]);
        exit;
    }

    if ($action === 'iniciar_comunicacion') {
        $tipo = $_POST['tipo'] ?? '';
        $alertaId = (int) ($_POST['alerta_id'] ?? 0);

        if (!in_array($tipo, ['llamada', 'videollamada'], true) || $alertaId <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'Datos inválidos para iniciar la comunicación.'
            ]);
            exit;
        }

        $candado = adquirirCandadoComunicacion($pdo, $tipo, $alertaId, $usuarioPanelId);

        echo json_encode($candado);
        exit;
    }

    if ($action === 'finalizar_comunicacion') {
        $comunicacionId = (int) ($_POST['comunicacion_id'] ?? 0);

        if ($comunicacionId > 0) {
            $pdo->prepare('DELETE FROM tbl_comunicacion_activa WHERE id = :id')
                ->execute([':id' => $comunicacionId]);
        } else {
            // Sin id: liberar cualquier comunicación activa de esta operadora.
            $pdo->prepare('DELETE FROM tbl_comunicacion_activa WHERE usuario_panel_id = :usuario_id')
                ->execute([':usuario_id' => $usuarioPanelId]);
        }

        echo json_encode(['ok' => true]);
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