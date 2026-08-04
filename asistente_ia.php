<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;

if (!isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Archivo no recibido']);
    exit;
}

move_uploaded_file($_FILES['audio']['tmp_name'], 'grabacion.wav');

$speech = new SpeechClient();

$config = new RecognitionConfig([
    'encoding' => RecognitionConfig\AudioEncoding::LINEAR16,
    'language_code' => 'es-ES',
]);

$audio = (new RecognitionAudio())->setContent(file_get_contents('grabacion.wav'));
$response = $speech->recognize($config, $audio);

$textoReconocido = '';
foreach ($response->getResults() as $result) {
    $textoReconocido .= $result->getAlternatives()[0]->getTranscript();
}

$speech->close();

$nombre = '';
$nombres = ['juan', 'ana', 'carlos', 'maria'];

foreach ($nombres as $n) {
    if (stripos($textoReconocido, $n) !== false) {
        $nombre = ucfirst($n);
        break;
    }
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=santandernew;charset=utf8", "root", "");
    $stmt = $pdo->query("SELECT tbl_plandesarrollo.*, tbl_secretarias.secretaria, tbl_secretarias.secretario FROM tbl_plandesarrollo INNER JOIN tbl_secretarias ON tbl_plandesarrollo.tbl_secretaria_id = tbl_secretarias.id");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($data);
    $productos = array_column($data, 'producto_servicio_pdd');
    $resumen = "Hay $total productos registrados. Algunos son: " . implode(", ", array_slice($productos, 0, 3)) . ".";

    $respuesta = $nombre
        ? "Claro que sí, $nombre. $resumen"
        : "¿Me puedes decir tu nombre por favor?";

} catch (Exception $e) {
    $respuesta = "Hubo un error accediendo a la base de datos.";
}

$ttsClient = new TextToSpeechClient();
$inputText = (new SynthesisInput())->setText($respuesta);
$voice = (new VoiceSelectionParams())->setLanguageCode('es-ES')->setSsmlGender(1);
$audioConfig = (new AudioConfig())->setAudioEncoding(1);

$ttsResponse = $ttsClient->synthesizeSpeech($inputText, $voice, $audioConfig);
$ttsClient->close();

file_put_contents('respuesta.mp3', $ttsResponse->getAudioContent());

$output = ob_get_clean();
if (!empty($output)) {
    echo json_encode(['error' => 'Salida inesperada del servidor', 'detalle' => $output]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['audio' => 'respuesta.mp3']);
