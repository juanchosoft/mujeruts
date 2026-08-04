<?php
error_reporting(E_ALL);

require_once 'SessionData.php';
require_once 'DbConection.php';
require_once 'Util.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Clase que contiene operaciones sobre la base de datos para usuarios
 * @author SPIDERSOFTWARE
 */
class Usuario
{
    public function __construct() {}

    public static function getAllInicioSession($data)
    {
        try {
            $db = new DbConection();
            $pdo = $db->openConect();

            $draw = $data['draw'] ?? 1;
            $start = $data['start'] ?? 0;
            $length = $data['length'] ?? 10;
            $searchValue = $data['search']['value'] ?? '';
            $orderColumnIndex = $data['order'][0]['column'] ?? 0;
            $orderDirection = strtolower($data['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

            $columns = ['a.id', 'a.navegador', 'a.dtcreate', 'b.nickname', 'b.nombre', 'b.apellido', 'a.ip'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'a.dtcreate';

            $where = '';
            $params = [];

            if ($searchValue !== '') {
                $where = "WHERE a.navegador LIKE :search OR b.nickname LIKE :search OR b.nombre LIKE :search OR b.apellido LIKE :search OR b.id LIKE :search OR a.ip LIKE :search";
                $params[':search'] = '%' . $searchValue . '%';
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . $db->getTable('tbl_historial_session'));
            $stmt->execute();
            $recordsTotal = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM " . $db->getTable('tbl_historial_session') . " a
                INNER JOIN " . $db->getTable('tbl_usuarios') . " b ON a.tec_usuario_id = b.id
                $where
            ");
            $stmt->execute($params);
            $recordsFiltered = (int)$stmt->fetchColumn();

            $sql = "
                SELECT
                    b.id,
                    a.ip,
                    a.navegador,
                    a.dtcreate,
                    b.nickname,
                    b.nombre,
                    b.apellido,
                    CONCAT(b.nombre, ' ', b.apellido) AS nombre_completo
                FROM " . $db->getTable('tbl_historial_session') . " a
                INNER JOIN " . $db->getTable('tbl_usuarios') . " b ON a.tec_usuario_id = b.id
                $where
                ORDER BY $orderColumn $orderDirection
                LIMIT :start, :length
            ";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $db->closeConect();

            return [
                'draw' => (int)$draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows
            ];
        } catch (PDOException $th) {
            return [
                'state' => false,
                'message' => $th->getMessage()
            ];
        }
    }

    public static function getAll($rqst)
    {
        $data = $rqst ?? [];

        $draw = $data['draw'] ?? 1;
        $start = $data['start'] ?? 0;
        $length = $data['length'] ?? 10;
        $searchValue = trim($data['search']['value'] ?? '');
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        try {
            $db = new DbConection();
            $pdo = $db->openConect();

            $municipioUsuarioLogueado = SessionData::getCodigoMunicipio();
            $userType = SessionData::getUserType();
            $isAdmin = ($userType === Util::Administrador() || $userType === Util::SuperAdministrador());

            $table = $db->getTable('tbl_usuarios');
            $tableSecretaria = $db->getTable('tbl_secretarias');

            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = :id");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $db->closeConect();

                return [
                    'valid' => true,
                    'data' => $result
                ];
            }

            $where = [];
            $params = [];

            if (!$isAdmin && !empty($municipioUsuarioLogueado)) {
                $where[] = "u.tbl_municipio_id = :municipio";
                $params[':municipio'] = $municipioUsuarioLogueado;
            }

            if ($searchValue !== '') {
                $where[] = "(u.nombre LIKE :search OR u.apellido LIKE :search OR u.tipo LIKE :search OR s.secretaria LIKE :search OR u.nickname LIKE :search OR u.email LIKE :search)";
                $params[':search'] = '%' . $searchValue . '%';
            }

            $whereSql = '';
            if (!empty($where)) {
                $whereSql = ' WHERE ' . implode(' AND ', $where);
            }

            $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmtTotal->execute();
            $recordsTotal = (int)$stmtTotal->fetchColumn();

            $stmtFiltered = $pdo->prepare("
                SELECT COUNT(*)
                FROM $table u
                LEFT JOIN $tableSecretaria s ON u.tbl_secretarias_id = s.id
                $whereSql
            ");
            $stmtFiltered->execute($params);
            $recordsFiltered = (int)$stmtFiltered->fetchColumn();

            $sql = "
                SELECT
                    u.id,
                    u.nombre,
                    u.apellido,
                    u.tipo,
                    s.secretaria,
                    u.nickname,
                    u.habilitado,
                    u.img,
                    u.email
                FROM $table u
                LEFT JOIN $tableSecretaria s ON u.tbl_secretarias_id = s.id
                $whereSql
                ORDER BY u.id DESC
                LIMIT :start, :length
            ";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rows = [];
            foreach ($result as $row) {
                $rows[] = [
                    'id' => $row['id'],
                    'nombre' => $row['nombre'],
                    'apellido' => $row['apellido'],
                    'tipo' => $row['tipo'],
                    'secretaria' => $row['secretaria'] ?? '',
                    'email' => $row['email'],
                    'nickname' => $row['nickname'],
                    'habilitado' => in_array((string)$row['habilitado'], ['1', 'si', 'SI', 'true', 'TRUE'], true) ? 'Sí' : 'No',
                    'foto' => !empty($row['img'])
                        ? '<img src="assets/img/admin/' . htmlspecialchars($row['img'], ENT_QUOTES, 'UTF-8') . '" width="40" height="40" class="rounded-circle" />'
                        : '',
                ];
            }

            $db->closeConect();

            return [
                'draw' => (int)$draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows
            ];
        } catch (PDOException $e) {
            return [
                'error' => true,
                'message' => 'Error al obtener los datos',
                'details' => $e->getMessage()
            ];
        }
    }

    public static function editUser($data)
    {
        try {
            $db = new DbConection();
            $pdo = $db->openConect();
            $id = (int)$data;

            $stmt = $pdo->prepare("SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $db->closeConect();

            return [
                'valid' => true,
                'data' => $result
            ];
        } catch (PDOException $e) {
            return [
                'valid' => false,
                'message' => 'Error al obtener los datos',
                'details' => $e->getMessage()
            ];
        }
    }

    public static function editUserSave($data, $files)
    {
        try {
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            $secretaria = isset($data['tbl_secretarias_id']) && is_numeric($data['tbl_secretarias_id']) ? (int)$data['tbl_secretarias_id'] : 30;
            $nickname = trim($data['nickname'] ?? '');
            $hashpass = trim($data['hashpass'] ?? '');
            $passConfirm = trim($data['hashpass1'] ?? '');
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $tipo = trim($data['tipo'] ?? '');
            $habilitado = trim($data['habilitado'] ?? '');
            $email = trim($data['email'] ?? '');
            $departamento = isset($data['tbl_departamento_id']) ? (int)$data['tbl_departamento_id'] : Util::getDepartamentoPrincipal();
            $tbl_municipio_id = isset($data['tbl_municipio_id']) && is_numeric($data['tbl_municipio_id']) ? (int)$data['tbl_municipio_id'] : null;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['state' => false, 'message' => 'El correo electrónico no es válido.'];
            }

            foreach (['id', 'nombre', 'apellido', 'nickname', 'tipo', 'habilitado'] as $field) {
                if (empty($data[$field])) {
                    return ['error' => true, 'message' => "El campo '$field' es obligatorio."];
                }
            }

            if ($tipo === 'Seleccione') {
                return ['error' => true, 'message' => "Debe seleccionar un tipo válido."];
            }

            $imgPath = null;
            if (!empty($files['imagen']['name'])) {
                $uploadDir = '../../assets/img/admin/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($files['imagen']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExts, true)) {
                    return ['state' => false, 'message' => 'Formato de imagen no permitido.'];
                }

                if (getimagesize($files['imagen']['tmp_name']) === false) {
                    return ['state' => false, 'message' => 'El archivo no es una imagen válida.'];
                }

                if ($files['imagen']['size'] > (5 * 1024 * 1024)) {
                    return ['state' => false, 'message' => 'La imagen supera el tamaño máximo permitido.'];
                }

                $imgName = uniqid('usr_', true) . '.' . $ext;
                $imgTarget = $uploadDir . $imgName;

                if (!move_uploaded_file($files['imagen']['tmp_name'], $imgTarget)) {
                    return ['state' => false, 'message' => 'Error al guardar la imagen.'];
                }

                $imgPath = $imgName;
            }

            $cambioPass = false;
            $pass = null;

            if ($hashpass !== '' || $passConfirm !== '') {
                if ($hashpass === '' || $passConfirm === '') {
                    return ['error' => true, 'message' => 'Debes completar ambos campos de contraseña.'];
                }
                if ($hashpass !== $passConfirm) {
                    return ['error' => true, 'message' => 'Las contraseñas no coinciden.'];
                }
                $cambioPass = true;
                $pass = Util::make_hash_pass($hashpass);
            }

            $db = new DbConection();
            $pdo = $db->openConect();

            $q = "UPDATE " . $db->getTable('tbl_usuarios') . " SET
                    nombre = :nombre,
                    apellido = :apellido,
                    nickname = :nickname,
                    tipo = :tipo,
                    tbl_secretarias_id = :secretaria,
                    tbl_departamento_id = :departamento,
                    tbl_municipio_id = :municipio,
                    habilitado = :habilitado,
                    email = :email";

            if ($imgPath !== null) {
                $q .= ", img = :img";
            }

            if ($cambioPass) {
                $q .= ", hashpass = :hashpass";
            }

            $q .= " WHERE id = :id";

            $stmt = $pdo->prepare($q);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':apellido', $apellido);
            $stmt->bindValue(':nickname', $nickname);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->bindValue(':secretaria', $secretaria, PDO::PARAM_INT);
            $stmt->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $stmt->bindValue(':municipio', $tbl_municipio_id, PDO::PARAM_INT);
            $stmt->bindValue(':habilitado', $habilitado);
            $stmt->bindValue(':email', $email);

            if ($imgPath !== null) {
                $stmt->bindValue(':img', $imgPath);
            }

            if ($cambioPass) {
                $stmt->bindValue(':hashpass', $pass);
            }

            $stmt->execute();
            $db->closeConect();

            return [
                'state' => true,
                'message' => 'Usuario actualizado correctamente.'
            ];
        } catch (PDOException $e) {
            return [
                'state' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public static function RestablecerContrasena($data)
    {
        try {
            $email = trim($data ?? '');

            if ($email === '') {
                return ['error' => true, 'message' => 'El campo email es obligatorio.'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['error' => true, 'message' => 'El correo electrónico no es válido.'];
            }

            $db = new DbConection();
            $pdo = $db->openConect();

            $stmt = $pdo->prepare("SELECT id, nombre FROM " . $db->getTable('tbl_usuarios') . " WHERE email = :email");
            $stmt->bindValue(':email', $email);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $db->closeConect();
                return ['error' => true, 'message' => 'No existe un usuario registrado con ese correo electrónico.'];
            }

            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("INSERT INTO " . $db->getTable('tbl_password_resets') . " (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
            $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':expires_at', $expira);
            $stmt->execute();

            require '../../vendor/autoload.php';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'email@spidersoftware.co';
            $mail->Password = 'Marti3933++$$++';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('email@spidersoftware.co', 'Restablecer Contraseña');
            $mail->addAddress($email, $user['nombre']);

            $resetLink = "http://{$_SERVER['HTTP_HOST']}/gobernacion/actualiza-contrasena.html?token=$token";
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = "Restablecimiento de contraseña - Gobernación de Santander";

            $mail->Body = '
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <style>
                        .container { max-width: 600px; margin: auto; font-family: "Segoe UI", sans-serif; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; background-color: #ffffff; color: #333; }
                        .header { background-color: #234162; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
                        .header h1 { margin: 0; font-size: 20px; }
                        .content { padding: 20px 10px; }
                        .content p { line-height: 1.6; }
                        .btn { display: inline-block; margin-top: 20px; padding: 12px 20px; background-color: #00796b; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
                        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #888; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>Gobernación de Santander</h1>
                        </div>
                        <div class="content">
                            <p>Hola <strong>' . htmlspecialchars($user['nombre']) . '</strong>,</p>
                            <p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el botón a continuación para establecer una nueva:</p>
                            <p style="text-align: center;">
                                <a class="btn" href="' . $resetLink . '">Restablecer contraseña</a>
                            </p>
                            <p>Este enlace expirará en 1 hora. Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                            <p>Cordialmente,<br>Equipo de soporte - Accion Unificada,<br>Gobernación de Santander</p>
                        </div>
                        <div class="footer">
                            Este mensaje fue enviado automáticamente. Por favor, no respondas a este correo.
                        </div>
                    </div>
                </body>
                </html>';

            $sent = $mail->send();

            $db->closeConect();

            if (!$sent) {
                return [
                    'state' => false,
                    'message' => 'Error al enviar el correo: ' . $mail->ErrorInfo
                ];
            }

            return [
                'state' => true,
                'message' => 'Se ha enviado un correo con instrucciones para restablecer la contraseña.'
            ];
        } catch (PDOException $e) {
            return [
                'state' => false,
                'message' => 'Error al restablecer la contraseña ' . $e->getMessage()
            ];
        }
    }

    public static function actualizaContrasena($data)
    {
        try {
            $token = trim($data['token'] ?? '');
            $password = trim($data['password'] ?? '');
            $confirm = trim($data['confirm'] ?? '');

            if ($token === '') {
                return ['state' => false, 'message' => 'Token inválido.'];
            }

            if ($password === '' || $confirm === '') {
                return ['state' => false, 'message' => 'Los campos de contraseña no pueden estar vacíos.'];
            }

            if (strlen($password) < 4) {
                return ['state' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres.'];
            }

            if ($password !== $confirm) {
                return ['state' => false, 'message' => 'Las contraseñas no coinciden.'];
            }

            $db = new DbConection();
            $pdo = $db->openConect();

            $stmt = $pdo->prepare("SELECT user_id, expires_at FROM " . $db->getTable('tbl_password_resets') . " WHERE token = ?");
            $stmt->execute([$token]);
            $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetData || strtotime($resetData['expires_at']) < time()) {
                return ['state' => false, 'message' => 'El enlace ha expirado o no es válido.'];
            }

            $id = (int)$resetData['user_id'];
            $hashpass = Util::make_hash_pass($password);
            $fecha = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("UPDATE " . $db->getTable('tbl_usuarios') . " SET hashpass = :hashpass, dtcreate = :dtcreate WHERE id = :id");
            $stmt->bindValue(':hashpass', $hashpass);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':dtcreate', $fecha);
            $stmt->execute();

            $db->closeConect();

            if ($stmt->rowCount() > 0) {
                return ['state' => true, 'message' => 'Contraseña actualizada correctamente.'];
            }

            return ['state' => false, 'message' => 'No se pudo actualizar la contraseña. Por favor, inténtalo de nuevo.'];
        } catch (PDOException $e) {
            return [
                'state' => false,
                'message' => 'Error al actualizar la contraseña: ' . $e->getMessage()
            ];
        }
    }

    public static function getPaginated($limit, $offset)
    {
        $db = new DbConection();
        $pdo = $db->openConect();

        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM " . $db->getTable('tbl_usuarios') . " LIMIT :offset, :limit";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

        $db->closeConect();

        return [
            'data' => $data,
            'total' => (int)$total,
        ];
    }

    public static function available($rqst)
    {
        $nickname = trim($rqst['nickname'] ?? '');
        $id = isset($rqst['id']) ? (int)$rqst['id'] : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        if ($id > 0) {
            $q = "SELECT id FROM " . $db->getTable('tbl_usuarios') . " WHERE nickname = :nickname AND id != :id";
            $stmt = $pdo->prepare($q);
            $params = [':nickname' => $nickname, ':id' => $id];
        } else {
            $q = "SELECT id FROM " . $db->getTable('tbl_usuarios') . " WHERE nickname = :nickname";
            $stmt = $pdo->prepare($q);
            $params = [':nickname' => $nickname];
        }

        if ($stmt->execute($params)) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                $arrjson = Util::error_general('El usuario ya existe');
            } else {
                $arrjson = ['output' => ['valid' => true, 'response' => 'available']];
            }
        } else {
            $arrjson = Util::error_general('');
        }

        $db->closeConect();
        return $arrjson;
    }

    public static function login($rqst)
    {
        $nickname = trim($rqst['nickname'] ?? '');
        $hashpass = trim($rqst['hashpass'] ?? '');

        if ($nickname === '' || $hashpass === '') {
            return Util::error_wrong_data_login();
        }

        try {
            $db = new DbConection();
            $pdo = $db->openConect();

            // IMPORTANTE:
            // login.php ya envía la contraseña hasheada en md5.
            // Aquí NO se debe volver a hashear.
            $q = "SELECT *
                  FROM " . $db->getTable('tbl_usuarios') . "
                  WHERE nickname = :nickname
                    AND hashpass = :hashpass
                    AND (habilitado = 'si' OR habilitado = '1' OR habilitado = 1 OR habilitado = 'true')";

            $stmt = $pdo->prepare($q);
            $stmt->execute([
                ':nickname' => $nickname,
                ':hashpass' => $hashpass
            ]);

            $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($arr) > 0) {
                $user = $arr[0];
                $user['application'][] = Util::get_app_id();

                $q1 = "SELECT tbl_permiso_id
                       FROM " . $db->getTable('tbl_usuarios_has_tbl_permisos') . "
                       WHERE tbl_usuarios_id = :id
                       ORDER BY tbl_permiso_id ASC";

                $stmt1 = $pdo->prepare($q1);
                $stmt1->execute([':id' => $user['id']]);
                $arrassigned = $stmt1->fetchAll(PDO::FETCH_COLUMN);

                $user['permisos'] = $arrassigned;

                Util::trace_session_user(['usuarioId' => $user['id']]);

                $db->closeConect();

                return [
                    'output' => [
                        'valid' => true,
                        'response' => [$user],
                        'permisos' => $arrassigned
                    ]
                ];
            }

            $db->closeConect();
            return Util::error_wrong_data_login();
        } catch (PDOException $e) {
            return [
                'output' => [
                    'valid' => false,
                    'response' => [
                        'content' => 'Usuario o contraseña incorrectos.'
                    ]
                ]
            ];
        }
    }

    public static function acttualizarPerfil($rqst)
    {
        $id = isset($rqst['id']) ? (int)$rqst['id'] : 0;
        $nickname = trim($rqst['nickname'] ?? '');
        $hashpass = trim($rqst['hashpass'] ?? '');
        $nombre = trim($rqst['nombre'] ?? '');
        $apellido = trim($rqst['apellido'] ?? '');
        $img = $_SESSION['file']['nombrearchivo'] ?? '';

        $db = new DbConection();
        $pdo = $db->openConect();

        if (strlen($hashpass) > 2) {
            $hashpass = Util::make_hash_pass($hashpass);
        }

        if ($id > 0) {
            $q0 = "SELECT id, img FROM " . $db->getTable('tbl_usuarios') . " WHERE id = :id";
            $stmt0 = $pdo->prepare($q0);
            $stmt0->execute([':id' => $id]);
            $old = $stmt0->fetch(PDO::FETCH_ASSOC);

            if (!$old) {
                $db->closeConect();
                return Util::error_general('No se encontró el usuario');
            }

            $fields = [
                'nickname' => $nickname,
                'hashpass' => $hashpass,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'img' => $img,
            ];

            $sql = "UPDATE " . $db->getTable('tbl_usuarios') . "
                    SET nickname = :nickname,
                        hashpass = :hashpass,
                        nombre = :nombre,
                        apellido = :apellido,
                        img = :img,
                        dtcreate = NOW()
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                ':nickname' => $nickname,
                ':hashpass' => $hashpass,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':img' => $img,
                ':id' => $id
            ]);

            if (!$ok) {
                $db->closeConect();
                return Util::error_general('Actualizando los datos del usuario');
            }

            if (!empty($old['img']) && file_exists("../../assets/img/admin/usuarios/" . $old['img'])) {
                @unlink("../../assets/img/admin/usuarios/" . $old['img']);
            }

            $db->closeConect();

            return [
                'output' => [
                    'valid' => true,
                    'id' => $id,
                    'img' => $old['img']
                ]
            ];
        }

        $db->closeConect();
        return Util::error_general('ID de usuario inválido');
    }

    public static function search($rqst)
    {
        $search = trim($rqst['search'] ?? '');

        $db = new DbConection();
        $pdo = $db->openConect();

        $sql = "SELECT *
                FROM " . $db->getTable('tbl_usuarios') . "
                WHERE nombre LIKE :search
                   OR apellido LIKE :search
                   OR tipo LIKE :search
                   OR nickname LIKE :search
                LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':search' => '%' . $search . '%'
        ]);

        $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $db->closeConect();

        if (!empty($arr)) {
            return ['output' => ['valid' => true, 'response' => $arr]];
        }

        return Util::error_no_result();
    }
}