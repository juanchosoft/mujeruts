<?php
// Configura tu token de verificación secreto
$token_verificacion = "tu_token_secreto_123_de_metatwilio";

// 1. VERIFICACIÓN DEL WEBHOOK (Método GET que envía Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hub_mode = $_GET['hub_mode'] ?? '';
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';

    if ($hub_mode === 'subscribe' && $hub_verify_token === $token_verificacion) {
        // Responde con el challenge que envía Meta para validar
        echo $hub_challenge;
        http_response_code(200);
        file_put_contents('webhook_log.txt', print_r($_GET, true), FILE_APPEND);
        exit;
    } else {
        http_response_code(403);
        exit;
    }
}

// 2. RECEPCIÓN DE MENSAJES (Método POST cuando te escriben al WhatsApp)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el cuerpo de la petición en formato JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Aquí puedes guardar el JSON en un archivo de texto para depurar y ver cómo llega
    file_put_contents('webhook_log.txt', print_r($data, true), FILE_APPEND);

    // Responder siempre a Meta con código 200 para confirmar recepción
    http_response_code(200);
    echo json_encode(["status" => "success"]);
    exit;
}
?>