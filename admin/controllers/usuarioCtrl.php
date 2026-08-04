<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method']) && $_POST['method'] === 'ingresaUsuario') {
    require_once '../classes/Usuario.php';
    $user = new Usuario();
    echo json_encode($user->save($_POST, $_FILES));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method']) && $_POST['method'] === 'editUserSave') {
    require_once '../classes/Usuario.php';
    $user = new Usuario();
    echo json_encode($user->editUserSave($_POST, $_FILES));
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['method'])) {
    switch ($data['method']) {
        case 'load':
            require_once '../classes/Usuario.php';
            $user = new Usuario();
            echo json_encode($user->getAll($data['data']));
            break;
        case 'editUser':
            require_once '../classes/Usuario.php';
            $user = new Usuario();
            echo json_encode($user->editUser($data['data']));
            break;
        case 'RestablecerContrasena':
            require_once '../classes/Usuario.php';
            $user = new Usuario();
            echo json_encode($user->RestablecerContrasena($data['data']));
            break;
        case 'actualizaContrasena':
            require_once '../classes/Usuario.php';
            $user = new Usuario();
            echo json_encode($user->actualizaContrasena($data['data']));
        case 'getAllInicioSession':
            require_once '../classes/Usuario.php';
            $user = new Usuario();
            echo json_encode($user->getAllInicioSession($data['data']));
            break;
        default:
            echo 'ninguna opción valida.';
            break;
    }
} else {
    echo 'ninguna opción valida.';
}
