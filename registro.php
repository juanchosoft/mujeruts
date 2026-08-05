<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

include './admin/classes/DbConection.php';
require_once './enviar_whatsapp_emergencia.php';

$_SESSION['registro_errors'] = [];
$_SESSION['registro_old'] = $_POST;

function redirigirRegistro()
{
    header('Location: login.php?panel=registro');
    exit;
}

function limpiar($valor)
{
    return trim((string)($valor ?? ''));
}

function soloNumeros($valor)
{
    return preg_replace('/[^0-9]/', '', (string)$valor);
}

function esSoloTextoEspacios($valor)
{
    return (bool) preg_match('/^[A-Za-zÁÉÍÓÚÜáéíóúüÑñ ]+$/u', $valor);
}

function esPasswordSegura($password)
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$nombres              = limpiar($_POST['nombres'] ?? '');
$apellidos            = limpiar($_POST['apellidos'] ?? '');
$tipoDocumento        = limpiar($_POST['tipo_documento'] ?? '');
$documento            = soloNumeros($_POST['documento'] ?? '');
$correo               = limpiar($_POST['correo'] ?? '');
$celular              = soloNumeros($_POST['celular'] ?? '');
$direccion            = limpiar($_POST['direccion'] ?? '');
$usuario              = limpiar($_POST['usuario'] ?? '');
$password             = (string)($_POST['password'] ?? '');
$passwordConfirmacion = (string)($_POST['password_confirmacion'] ?? '');
$tblDepartamentoId    = (int)($_POST['tbl_departamento_id'] ?? 68);
$tblMunicipioId       = (int)($_POST['tbl_municipio_id'] ?? 0);

$contactoEmergencia   = soloNumeros($_POST['contacto_emergencia'] ?? '');
$nombreContacto       = limpiar($_POST['nombre_contacto'] ?? '');

$errors = [];

if ($nombres === '') {
    $errors['nombres'] = 'Los nombres son obligatorios.';
} elseif (!esSoloTextoEspacios($nombres)) {
    $errors['nombres'] = 'Los nombres solo pueden contener letras y espacios.';
}

if ($apellidos === '') {
    $errors['apellidos'] = 'Los apellidos son obligatorios.';
} elseif (!esSoloTextoEspacios($apellidos)) {
    $errors['apellidos'] = 'Los apellidos solo pueden contener letras y espacios.';
}

if ($tipoDocumento === '') {
    $errors['tipo_documento'] = 'Debes seleccionar el tipo de documento.';
}

if ($documento === '') {
    $errors['documento'] = 'El número de documento es obligatorio.';
} elseif (!preg_match('/^[0-9]+$/', $documento)) {
    $errors['documento'] = 'El número de documento solo puede contener números.';
}

if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errors['correo'] = 'Debes ingresar un correo electrónico válido.';
}

if ($celular === '') {
    $errors['celular'] = 'El celular es obligatorio.';
} elseif (!preg_match('/^[0-9]{10}$/', $celular)) {
    $errors['celular'] = 'El celular debe tener exactamente 10 dígitos.';
}

if ($direccion === '') {
    $errors['direccion'] = 'La dirección es obligatoria.';
}

if ($usuario === '') {
    $errors['usuario'] = 'El usuario es obligatorio.';
} elseif (!preg_match('/^[A-Za-z0-9]+$/', $usuario)) {
    $errors['usuario'] = 'El usuario solo puede contener letras y números.';
}

if ($contactoEmergencia === '') {
    $errors['contacto_emergencia'] = 'El contacto de emergencia es obligatorio.';
} elseif (!preg_match('/^[0-9]{10}$/', $contactoEmergencia)) {
    $errors['contacto_emergencia'] = 'El celular de emergencia debe tener exactamente 10 dígitos.';
}

if ($nombreContacto === '') {
    $errors['nombre_contacto'] = 'El nombre del contacto de emergencia es obligatorio.';
} elseif (!esSoloTextoEspacios($nombreContacto)) {
    $errors['nombre_contacto'] = 'El nombre del contacto solo puede contener letras y espacios.';
}

if ($tblMunicipioId <= 0) {
    $errors['tbl_municipio_id'] = 'Debes seleccionar un municipio.';
}

if ($password === '') {
    $errors['password'] = 'La contraseña es obligatoria.';
} elseif (!esPasswordSegura($password)) {
    $errors['password'] = 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.';
}

if ($passwordConfirmacion === '') {
    $errors['password_confirmacion'] = 'Debes confirmar la contraseña.';
} elseif ($password !== $passwordConfirmacion) {
    $errors['password_confirmacion'] = 'Las contraseñas no coinciden.';
}

if (!empty($errors)) {
    $_SESSION['registro_errors'] = $errors;
    redirigirRegistro();
}

try {
    $db  = new DbConection();
    $pdo = $db->openConect();

    $stmtUts = $pdo->prepare(
        'SELECT id FROM tbl_personal_uts WHERE identificacion = :identificacion LIMIT 1'
    );
    $stmtUts->bindValue(':identificacion', $documento, PDO::PARAM_STR);
    $stmtUts->execute();

    if (!$stmtUts->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['registro_errors'] = [
            'documento' => 'Solo se pueden registrar personas asociadas a UTS.'
        ];
        redirigirRegistro();
    }

    $sqlExiste = "SELECT id, nickname, email, identificacion_num
                  FROM tbl_usuarios
                  WHERE nickname = :nickname
                     OR email = :email
                     OR identificacion_num = :identificacion_num
                  LIMIT 1";
    $stmtExiste = $pdo->prepare($sqlExiste);
    $stmtExiste->bindValue(':nickname', $usuario, PDO::PARAM_STR);
    $stmtExiste->bindValue(':email', $correo, PDO::PARAM_STR);
    $stmtExiste->bindValue(':identificacion_num', $documento, PDO::PARAM_STR);
    $stmtExiste->execute();
    $existente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        $errorsUnicos = [];

        if (strcasecmp((string)$existente['nickname'], $usuario) === 0) {
            $errorsUnicos['usuario'] = 'Este nombre de usuario ya está en uso.';
        }
        if (strcasecmp((string)$existente['email'], $correo) === 0) {
            $errorsUnicos['correo'] = 'Este correo electrónico ya está registrado.';
        }
        if ((string)$existente['identificacion_num'] === $documento) {
            $errorsUnicos['documento'] = 'Este número de documento ya está registrado.';
        }
        if (empty($errorsUnicos)) {
            $errorsUnicos['general'] = 'Ya existe una usuaria con ese usuario, correo o documento.';
        }

        $_SESSION['registro_errors'] = $errorsUnicos;
        redirigirRegistro();
    }

    $sql = "INSERT INTO tbl_usuarios (
                dtcreate,
                nickname,
                hashpass,
                nombre,
                apellido,
                identificacion_tipo,
                identificacion_num,
                email,
                celular,
                direccion,
                area,
                tipo,
                habilitado,
                img,
                contacto_emergencia,
                nombre_contacto,
                tbl_departamento_id,
                tbl_municipio_id
            ) VALUES (
                NOW(),
                :nickname,
                :hashpass,
                :nombre,
                :apellido,
                :identificacion_tipo,
                :identificacion_num,
                :email,
                :celular,
                :direccion,
                :area,
                :tipo,
                :habilitado,
                :img,
                :contacto_emergencia,
                :nombre_contacto,
                :tbl_departamento_id,
                :tbl_municipio_id
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nickname', $usuario, PDO::PARAM_STR);
    $stmt->bindValue(':hashpass', md5($password), PDO::PARAM_STR);
    $stmt->bindValue(':nombre', $nombres, PDO::PARAM_STR);
    $stmt->bindValue(':apellido', $apellidos, PDO::PARAM_STR);
    $stmt->bindValue(':identificacion_tipo', $tipoDocumento, PDO::PARAM_STR);
    $stmt->bindValue(':identificacion_num', $documento, PDO::PARAM_STR);
    $stmt->bindValue(':email', $correo, PDO::PARAM_STR);
    $stmt->bindValue(':celular', $celular, PDO::PARAM_STR);
    $stmt->bindValue(':direccion', $direccion, PDO::PARAM_STR);
    $stmt->bindValue(':area', 'Mujer', PDO::PARAM_STR);
    $stmt->bindValue(':tipo', 'usuaria', PDO::PARAM_STR);
    $stmt->bindValue(':habilitado', 'SI', PDO::PARAM_STR);
    $stmt->bindValue(':img', '', PDO::PARAM_STR);
    $stmt->bindValue(':contacto_emergencia', $contactoEmergencia, PDO::PARAM_STR);
    $stmt->bindValue(':nombre_contacto', $nombreContacto, PDO::PARAM_STR);
    $stmt->bindValue(':tbl_departamento_id', $tblDepartamentoId, PDO::PARAM_INT);
    $stmt->bindValue(':tbl_municipio_id', $tblMunicipioId, PDO::PARAM_INT);
    $stmt->execute();

    $nombreCompleto = trim($nombres . ' ' . $apellidos);
    $resultadoWa = enviarWhatsAppPlantillaRegistro(
        $contactoEmergencia,
        $nombreContacto,
        $nombreCompleto
    );

    unset($_SESSION['registro_errors'], $_SESSION['registro_old']);

    if (!empty($resultadoWa['ok'])) {
        $_SESSION['registro_success'] = 'Tu cuenta fue creada correctamente. Ya puedes iniciar sesión. Se notificó a tu contacto de emergencia por WhatsApp.';
    } else {
        $_SESSION['registro_success'] = 'Tu cuenta fue creada correctamente. Ya puedes iniciar sesión. No fue posible notificar al contacto de emergencia por WhatsApp en este momento.';
    }

    header('Location: login.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['registro_errors'] = [
        'general' => 'Ocurrió un error al registrar la usuaria. Inténtalo de nuevo.'
    ];
    error_log('Error registro usuaria: ' . $e->getMessage());
    redirigirRegistro();
}
