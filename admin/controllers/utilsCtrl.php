<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['method'])) {
    switch ($data['method']) {
        case 'ciudades':
            require_once '../classes/Utils.php';
            $user = new Utils();
            echo json_encode($user->ciudades($data['data']));
            break;
        case 'secretaria':
            require_once '../classes/Utils.php';
            $user = new Utils();
            echo json_encode($user->secretaria());
            break;
        default:
            echo 'ninguna opción valida.';
            break;
    }
} else {
    echo 'ninguna opción valida.';
}
