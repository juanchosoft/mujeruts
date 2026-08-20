<?php
require_once __DIR__ . '/admin/classes/DbConection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Bogota');

$usuarioId = (int) ($_SESSION['id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? $_SESSION['session_user']['id'] ?? 0);

if ($usuarioId <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'Sesión no válida'
    ]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = new DbConection();
    $pdo = $db->openConect();

    if ($action === 'consultar') {
        $stmt = $pdo->prepare(
            "SELECT id, alerta_id, room_name, guest_url
             FROM tbl_videollamada_invitacion
             WHERE usuario_id = :usuario_id
               AND estado = 'pendiente'
               AND creado_en >= (NOW() - INTERVAL 30 MINUTE)
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $invitacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitacion) {
            echo json_encode([
                'ok' => true,
                'pendiente' => false
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'pendiente' => true,
            'id' => (int) $invitacion['id'],
            'alerta_id' => (int) $invitacion['alerta_id'],
            'room_name' => $invitacion['room_name'],
            'guest_url' => $invitacion['guest_url']
        ]);
        exit;
    }

    if ($action === 'marcar_unida' || $action === 'descartar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                'ok' => false,
                'message' => 'ID inválido'
            ]);
            exit;
        }

        $nuevoEstado = $action === 'marcar_unida' ? 'unida' : 'cerrada';

        $stmt = $pdo->prepare(
            "UPDATE tbl_videollamada_invitacion
             SET estado = :estado, unido_en = NOW()
             WHERE id = :id AND usuario_id = :usuario_id"
        );
        $stmt->bindValue(':estado', $nuevoEstado);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'ok' => true
        ]);
        exit;
    }

    echo json_encode([
        'ok' => false,
        'message' => 'Acción no válida'
    ]);
    exit;
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
    exit;
}
