<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../classes/DbConection.php';

$documento = preg_replace('/[^0-9]/', '', (string)($_REQUEST['documento'] ?? $_REQUEST['identificacion'] ?? ''));

if ($documento === '') {
    http_response_code(400);
    echo json_encode([
        'valid' => false,
        'message' => 'El número de documento es obligatorio.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new DbConection();
    $pdo = $db->openConect();

    $stmt = $pdo->prepare(
        'SELECT id FROM tbl_personal_uts WHERE identificacion = :identificacion LIMIT 1'
    );
    $stmt->bindValue(':identificacion', $documento, PDO::PARAM_STR);
    $stmt->execute();
    $existe = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        echo json_encode([
            'valid' => true,
            'message' => 'Documento asociado a UTS.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(404);
    echo json_encode([
        'valid' => false,
        'message' => 'Solo se pueden registrar personas asociadas a UTS.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'No fue posible validar el documento.'
    ], JSON_UNESCAPED_UNICODE);
}
