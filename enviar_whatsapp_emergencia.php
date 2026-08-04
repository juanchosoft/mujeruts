<?php
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/config_whatsapp.php';

function enviarWhatsAppTexto($numeroDestino, $mensaje)
{
    $numeroDestino = preg_replace('/[^0-9]/', '', (string)$numeroDestino);

    if ($numeroDestino === '') {
        return [
            'ok' => false,
            'message' => 'Número de destino vacío.'
        ];
    }

    $url = 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'   => $numeroDestino,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $mensaje
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WHATSAPP_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return [
            'ok' => false,
            'message' => 'Error CURL: ' . $curlErr
        ];
    }

    $json = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'ok' => true,
            'message' => 'WhatsApp enviado correctamente.',
            'response' => $json
        ];
    }

    return [
        'ok' => false,
        'message' => 'Error al enviar WhatsApp.',
        'http_code' => $httpCode,
        'response' => $json
    ];
}