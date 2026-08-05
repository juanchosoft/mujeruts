<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../classes/DbConection.php';

$usuario = trim((string)($_REQUEST['usuario'] ?? $_REQUEST['nickname'] ?? ''));

if ($usuario === '') {
    http_response_code(400);
    echo json_encode([
        'valid' => false,
        'message' => 'El nombre de usuario es obligatorio.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[A-Za-z0-9]+$/', $usuario)) {
    http_response_code(400);
    echo json_encode([
        'valid' => false,
        'message' => 'El usuario solo puede contener letras y números.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new DbConection();
    $pdo = $db->openConect();

    $stmt = $pdo->prepare(
        'SELECT id FROM tbl_usuarios WHERE nickname = :nickname LIMIT 1'
    );
    $stmt->bindValue(':nickname', $usuario, PDO::PARAM_STR);
    $stmt->execute();
    $existe = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        http_response_code(409);
        echo json_encode([
            'valid' => false,
            'message' => 'Este nombre de usuario ya está en uso.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'valid' => true,
        'message' => 'Usuario disponible.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'No fue posible validar el usuario.'
    ], JSON_UNESCAPED_UNICODE);
}
