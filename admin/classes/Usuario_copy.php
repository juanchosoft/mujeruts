<?php

/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Usuario
{

    public function __construct() {}

    public static function getAllInicioSesion($rqst)
    {

        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT tbl_historial_session.*, tbl_usuarios.nickname, tbl_usuarios.nombre, tbl_usuarios.apellido  FROM " . $db->getTable('tbl_historial_session') . " 
        INNER JOIN " . $db->getTable('tbl_usuarios') . " 
        ON tbl_historial_session.tec_usuario_id = tbl_usuarios.id ";
        $result = $pdo->query($q);
        $arr = array();
        if ($result) {
            foreach ($result as $valor) {
                $arr[] = $valor;
            }
            $arrjson = array('output' => array('valid' => true, 'response' => $arr));
        } else {
            $arrjson = Util::error_no_result();
        }
        $db->closeConect();
        return $arrjson;
    }
    public static function getAll($rqst)
    {

        $limit = 15;  //vista de datos
        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
        $tipo = isset($rqst['tipo']) ? ($rqst['tipo']) : '';

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " LIMIT " . $limit;
        if ($id > 0) {
            $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE id = " . $id;
        }

        if ($tipo != "") {
            $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE tipo = '$tipo' AND habilitado = 'si'";
        }
        $result = $pdo->query($q);
        $arr = array();
        if ($result) {
            foreach ($result as $valor) {
                $arr[] = $valor;
            }
            $arrjson = array('output' => array('valid' => true, 'response' => $arr));
        } else {
            $arrjson = Util::error_no_result();
        }
        $db->closeConect();
        return $arrjson;
    }

    public static function getPaginated($limit, $offset)
    {
        $db = Database::getConnection(); // Conexión a la base de datos (PDO)
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM tbl_usuarios LIMIT :offset, :limit";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $db->query("SELECT FOUND_ROWS()")->fetchColumn();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public static function available($rqst)
    {
        $nickname = isset($rqst['nickname']) ? ($rqst['nickname']) : '';
        $id = isset($rqst['id']) ? ($rqst['id']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE nickname = :nickname";

        if ($id > 0) {
            $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE nickname = :nickname AND id != :id";
            $result = $pdo->prepare($q);
            $arr = array();
            $arrparam = array(":nickname" => $nickname, ":id" => $id);
        } else {
            $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE nickname = :nickname";
            $result = $pdo->prepare($q);
            $arr = array();
            $arrparam = array(":nickname" => $nickname);
        }

        if ($result->execute($arrparam)) {
            foreach ($result as $valor) {
                $arr[] = $valor;
            }
            if (count($arr) > 0) {
                $arrjson = Util::error_general('El email de usuario ya existe');
            } else {
                $arrjson = array('output' => array('valid' => true, 'response' => 'available'));
            }
        } else {
            $arrjson = Util::error_general('');
        }
        $db->closeConect();
        return $arrjson;
    }

    public static function login($rqst)
    {
        // Obtención de parámetros de entrada
        $nickname = isset($rqst['nickname']) ? $rqst['nickname'] : '';
        $hashpass = isset($rqst['hashpass']) ? $rqst['hashpass'] : '';

        $db = new DbConection();
        $pdo = $db->openConect();

        // Si la contraseña tiene más de 2 caracteres, se realiza el hash
        if (strlen($hashpass) > 2) {
            $hashpass = Util::make_hash_pass($hashpass);
        }

        // Consulta para verificar usuario y contraseña
        $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " WHERE nickname = :nickname AND hashpass = :hashpass AND habilitado='si'";
        $arrparam = [":nickname" => $nickname, ":hashpass" => $hashpass];

        $result = $pdo->prepare($q);
        if ($result->execute($arrparam)) {
            $arr = $result->fetchAll(PDO::FETCH_ASSOC);

            if (count($arr) > 0) {
                $user = $arr[0]; // Obtener el primer usuario encontrado
                $user['application'][] = Util::get_app_id();

                // Consultar perfiles asignados
                $q1 = "SELECT tbl_permiso_id FROM " . $db->getTable('tbl_usuarios_has_tbl_permisos') . " WHERE tbl_usuarios_id = :id ORDER BY tbl_permiso_id ASC";
                $result1 = $pdo->prepare($q1);
                $result1->execute([":id" => $user['id']]);
                $arrassigned = $result1->fetchAll(PDO::FETCH_COLUMN);

                $user['permisos'] = $arrassigned;
                $arrjson = [
                    'output' => [
                        'valid' => true,
                        'response' => [$user],
                        'permisos' => $arrassigned
                    ]
                ];

                // Guardar información en la sesión
                Util::trace_session_user(['usuarioId' => $user['id']]);
            } else {
                $arrjson = Util::error_wrong_data_login();
            }
        } else {
            $arrjson = Util::error_wrong_data_login();
        }

        $db->closeConect();
        return $arrjson;
    }

    public static function save($rqst)
    {
        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
        $tbl_secretarias_id = isset($rqst['tbl_secretarias_id']) ? intval($rqst['tbl_secretarias_id']) : 0;
        $nickname = isset($rqst['nickname']) ? ($rqst['nickname']) : '';
        $hashpass = isset($rqst['hashpass']) ? ($rqst['hashpass']) : '';
        $nombre = isset($rqst['nombre']) ? ($rqst['nombre']) : '';
        $apellido = isset($rqst['apellido']) ? ($rqst['apellido']) : '';
        $tipo = isset($rqst['tipo']) ? ($rqst['tipo']) : '';
        $habilitado = isset($rqst['habilitado']) ? ($rqst['habilitado']) : '';
        $img = isset($_SESSION['file']['nombrearchivo']) ? ($_SESSION['file']['nombrearchivo']) : '';
        $tbl_departamento_id = isset($rqst['departamentoId']) ? intval($rqst['departamentoId']) : Util::getDepartamentoPrincipal();
        $tbl_municipio_id = isset($rqst['tbl_municipio_id']) ? intval($rqst['tbl_municipio_id']) : 0;

        if ($tipo == "Alcalde") {
            $arrchk = range(5, 79); // 5 al 79
        }
        if ($tipo == "Administrador" || $tipo == "SuperAdministrador") {
            $arrchk = range(1, 79); // 1 al 79 todos
        }
        if ($tipo == "Auxiliar_Alcalde") {
            $arrchk = [65, 66, 64, 51, 68, 69, 67, 53, 54, 52, 59, 60, 58, 62, 63, 61,
                        56, 57, 55, 47, 48, 39, 42, 40, 41, 38, 36, 44, 43, 45, 46, 49,
                        50, 37, 23, 25, 24, 71, 72, 70, 9, 10, 8, 12, 13, 11, 28, 27,
                        26, 18, 19, 17, 6, 7, 5, 21, 22, 20];
        }

        $db = new DbConection();
        $pdo = $db->openConect();

        if (strlen($hashpass) > 2) {
            $hashpass = Util::make_hash_pass($hashpass);
        }
        if ($tbl_departamento_id == 0) {
            return  Util::error_general(' Identificador del departamento no está presente.');
        }
        if ($id > 0) {
            //actualiza la informacion
            $q0 = "SELECT id, img FROM " . $db->getTable('tbl_usuarios') . " WHERE id = " . $id;
            $result0 = $pdo->query($q0);
            if ($result0) {
                $table = $db->getTable('tbl_usuarios');
                $arrfieldscomma = array(
                    'nickname' => $nickname,
                    'hashpass' => $hashpass,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'tipo' => $tipo,
                    'img' => $img,
                    'tbl_secretarias_id' => $tbl_secretarias_id,
                    'habilitado' => $habilitado,
                    'tbl_departamento_id' => $tbl_departamento_id,
                    'tbl_municipio_id' => $tbl_municipio_id
                );
                $arrfieldsnocomma = array('dtcreate' => Util::date_now_server());
                $q = Util::make_query_update($table, "id = '$id'", $arrfieldscomma, $arrfieldsnocomma);
                $result = $pdo->query($q);

                // Obtemos el valor de la imagen del producto
                $file = "";
                foreach ($result0 as $valor0) {
                    $file = $valor0['img'];
                }

                if (!$result) {
                    $arrjson = Util::error_general('Actualizando los datos del usuario');
                } else {
                    $arrjson = array('output' => array('valid' => true, 'id' => $id, 'img' => $file));
                    // Eliminamos el archivo anterior siempre y cuando se halla actualizado la imagen
                    if ($file != "" && file_exists("../../assets/img/admin/usuarios/" . $file)) {
                        unlink("../../assets/img/admin/usuarios/" . $file);
                    }

                    if (in_array($tipo, ["Alcalde", "Administrador", "SuperAdministrador", "Auxiliar_Alcalde"])) {

                        $table = $db->getTable('tbl_usuarios_has_tbl_permisos');
                        $dtcreate = Util::date_now_server();

                        //Se Elimina los perfiles asignados que tenia
                        $q = "DELETE FROM " . $db->getTable('tbl_usuarios_has_tbl_permisos') . " WHERE tbl_usuarios_id = '" . $id . "'";
                        $result = $pdo->query($q);

                        foreach ($arrchk as $prf_id) {
                            if ($prf_id > 0) {
                                $q1 = "INSERT INTO $table (dtcreate, tbl_usuarios_id, tbl_permiso_id) VALUES ($dtcreate, $id, $prf_id)";
                                $result1 = $pdo->query($q1);
                                if (!$result1) {
                                    return Util::error_general('Registrando permisos del usuario');
                                }
                            }
                        }
                    }

                }
            } else {
                $arrjson = Util::error_general();
            }
        } else {
            if ($nombre != "" && $apellido != "" && $tbl_secretarias_id > 0 && $tipo != "" && $tbl_departamento_id > 0 && $tbl_municipio_id > 0) {
                $q = "INSERT INTO " . $db->getTable('tbl_usuarios') . " (dtcreate, nickname, hashpass, nombre, apellido,   tipo, img, tbl_secretarias_id,  habilitado, tbl_departamento_id, tbl_municipio_id ) 
                VALUES ( " . Util::date_now_server() . ", :nickname, :hashpass, :nombre, :apellido, :tipo,  :img, :tbl_secretarias_id,  :habilitado, :tbl_departamento_id, :tbl_municipio_id)";
                $result = $pdo->prepare($q);
                $arrparam = array(
                    ':nickname' => $nickname,
                    ':hashpass' => $hashpass,
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':tipo' => $tipo,
                    ':img' => $img,
                    ':tbl_secretarias_id' => $tbl_secretarias_id,
                    ':habilitado' => $habilitado,
                    ':tbl_departamento_id' => $tbl_departamento_id,
                    ':tbl_municipio_id' => $tbl_municipio_id
                );
                if ($result->execute($arrparam)) {

                    $lastInsertId = $pdo->lastInsertId();
                    $arrjson = array('output' => array('valid' => true, 'response' => $lastInsertId));


                    if (in_array($tipo, ["Alcalde", "Administrador", "SuperAdministrador", "Auxiliar_Alcalde"])) {

                        $table = $db->getTable('tbl_usuarios_has_tbl_permisos');
                        $dtcreate = Util::date_now_server();

                        //Se Elimina los perfiles asignados que tenia
                        $q = "DELETE FROM " . $db->getTable('tbl_usuarios_has_tbl_permisos') . " WHERE tbl_usuarios_id = '" . $lastInsertId . "'";
                        $result = $pdo->query($q);

                        foreach ($arrchk as $prf_id) {
                            if ($prf_id > 0) {
                                $q1 = "INSERT INTO $table (dtcreate, tbl_usuarios_id, tbl_permiso_id) VALUES ($dtcreate, $lastInsertId, $prf_id)";
                                $result1 = $pdo->query($q1);
                                if (!$result1) {
                                    return Util::error_general('Registrando permisos del usuario');
                                }
                            }
                        }
                    }
                    $arrjson = ['output' => ['valid' => true, 'response' => $pdo->lastInsertId()]];
                } else {
                    $arrjson = Util::error_general('Ingresando los datos del usurario');
                }
            } else {
                $arrjson = Util::error_missing_data();
            }
        }
        $db->closeConect();
        return $arrjson;
    }

    public static function acttualizarPerfil($rqst)
    {
        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
        $nickname = isset($rqst['nickname']) ? ($rqst['nickname']) : '';
        $hashpass = isset($rqst['hashpass']) ? ($rqst['hashpass']) : '';
        $nombre = isset($rqst['nombre']) ? ($rqst['nombre']) : '';
        $apellido = isset($rqst['apellido']) ? ($rqst['apellido']) : '';
        $img = isset($_SESSION['file']['nombrearchivo']) ? ($_SESSION['file']['nombrearchivo']) : '';

        $db = new DbConection();
        $pdo = $db->openConect();

        if (strlen($hashpass) > 2) {
            $hashpass = Util::make_hash_pass($hashpass);
        }
        if ($id > 0) {
            //actualiza la informacion
            $q0 = "SELECT id, img FROM " . $db->getTable('tbl_usuarios') . " WHERE id = " . $id;
            $result0 = $pdo->query($q0);
            if ($result0) {
                $table = $db->getTable('tbl_usuarios');
                $arrfieldscomma = array(
                    'nickname' => $nickname,
                    'hashpass' => $hashpass,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'img' => $img,
                );
                $arrfieldsnocomma = array('dtcreate' => Util::date_now_server());
                $q = Util::make_query_update($table, "id = '$id'", $arrfieldscomma, $arrfieldsnocomma);
                $result = $pdo->query($q);

                // Obtemos el valor de la imagen del producto
                $file = "";
                foreach ($result0 as $valor0) {
                    $file = $valor0['img'];
                }

                if (!$result) {
                    $arrjson = Util::error_general('Actualizando los datos del usuario');
                } else {
                    $arrjson = array('output' => array('valid' => true, 'id' => $id, 'img' => $file));
                    // Eliminamos el archivo anterior siempre y cuando se halla actualizado la imagen
                    if ($file != "" && file_exists("../../assets/img/admin/usuarios/" . $file)) {
                        unlink("../../assets/img/admin/usuarios/" . $file);
                    }
                }
            } else {
                $arrjson = Util::error_general();
            }
        }
        $db->closeConect();
        return $arrjson;
    }

    public static function search($rqst){

        $search = isset($rqst['search']) ? ($rqst['search']) : '';

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT * FROM " . $db->getTable('tbl_usuarios') . " 
        WHERE nombre  LIKE '%$search%'  OR
            apellido  LIKE '%$search%' OR
            tipo  LIKE '%$search%' OR
            nickname  LIKE '%$search%' LIMIT 200 ";
        $result = $pdo->query($q);

        $arr = array();

        if ($result) {
            foreach ($result as $valor) {
                $arr[] = $valor;
            }
            $arrjson = array('output' => array('valid' => true, 'response' => $arr));
        } else {
            $arrjson = Util::error_no_result();
        }
        $db->closeConect();

        return $arrjson;

    }
}
