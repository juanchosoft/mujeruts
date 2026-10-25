<?php
declare(strict_types=1);

// Configura tu token de verificación secreto
$token_verificacion = 'tu_token_secreto_123_de_metatwilio';

// 1. VERIFICACIÓN DEL WEBHOOK (Método GET que envía Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hub_mode = $_GET['hub_mode'] ?? '';
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';

    if ($hub_mode === 'subscribe' && $hub_verify_token === $token_verificacion) {
        echo $hub_challenge;
        http_response_code(200);
        file_put_contents('webhook_log.txt', print_r($_GET, true), FILE_APPEND);
        exit;
    }

    http_response_code(403);
    exit;
}

// 2. RECEPCIÓN DE MENSAJES (Método POST cuando te escriben al WhatsApp)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    file_put_contents('webhook_log.txt', print_r($data, true), FILE_APPEND);

    if (is_array($data)) {
        require_once __DIR__ . '/admin/classes/CampanaInvitacionWebhook.php';
        try {
            CampanaInvitacionWebhook::procesarPayload($data);
        } catch (Throwable $e) {
            error_log('webhook campaña: ' . $e->getMessage());
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
    exit;
}

http_response_code(405);
exit;
