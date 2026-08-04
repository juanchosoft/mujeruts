<?php
// Configuración de la API de OpenAI
define('OPENAI_API_KEY', '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'); // Sustituye por tu clave API

header('Content-Type: application/json');

// Obtener el mensaje del usuario
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

if (!$userMessage) {
    echo json_encode(['response' => 'No se recibió un mensaje válido.']);
    exit;
}

// Preparar solicitud a la API de OpenAI
$apiUrl = "https://api.openai.com/v1/chat/completions";

$requestData = [
    'model' => 'gpt-4o', // Modelo de ChatGPT
    'messages' => [
        ['role' => 'system', 'content' => 'Eres un asistente amigable y útil.'],
        ['role' => 'user', 'content' => $userMessage]
    ]
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n" .
                    "Authorization: Bearer " . OPENAI_API_KEY . "\r\n",
        'content' => json_encode($requestData),
        'ignore_errors' => true
    ]
];

// Hacer la solicitud
$context = stream_context_create($options);
$response = file_get_contents($apiUrl, false, $context);

// Decodificar la respuesta de OpenAI
if ($response === false) {
    echo json_encode(['response' => 'Hubo un error al conectar con ChatGPT.']);
    exit;
}

$responseData = json_decode($response, true);
$chatGptResponse = $responseData['choices'][0]['message']['content'] ?? 'No se obtuvo respuesta de ChatGPT.';

// Devolver la respuesta en formato JSON
echo json_encode(['response' => $chatGptResponse]);
