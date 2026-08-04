<?php

/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Configuracion
{

    public function __construct() {}


    public static function getAll($rqst)
    {
        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;

        $db = new DbConection();
        $pdo = $db->openConect();

        $q = "SELECT * FROM " . $db->getTable('tbl_configuracion') . " ORDER BY id DESC LIMIT 1";

        if ($id > 0) {
            $q = "SELECT * FROM " . $db->getTable('tbl_configuracion') . " WHERE id = " . $id . " LIMIT 1";
        }
        $result = Util::sb_db_get($q);
        $arr = array();
        if (!empty($result)) {
            foreach ($result as $valor) {
                $arr[] = $valor;
            }
            $arrjson = array('output' => array('valid' => true, 'response' => [$result]));
        } else {
            $arrjson = Util::error_no_result();
        }
        $db->closeConect();

        return $arrjson;
    }

    public static function save($rqst)
    {
        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
        $tipo_configuracion_colores = isset($rqst['tipo_configuracion_colores']) ? ($rqst['tipo_configuracion_colores']) : '';
        $comentarios = isset($rqst['comentarios']) ? ($rqst['comentarios']) : '';

        if($tipo_configuracion_colores == ""){
            $arrjson = Util::error_general('El campo Tipo Configuración Colores es obligatorio');
            return $arrjson;
        }

        $db = new DbConection();
        $pdo = $db->openConect();

        if ($id > 0) {
            $q = "SELECT id  FROM " . $db->getTable('tbl_configuracion') . " WHERE id = " . $id;
            $result = $pdo->query($q);
            if ($result) {
                $table = $db->getTable('tbl_configuracion');
                $arrfieldscomma = array(
                    'tipo_configuracion_colores' => $tipo_configuracion_colores,
                    'comentarios' => $comentarios,
                );
                $arrfieldsnocomma = array('dtcreate' => Util::date_now_server());
                $q = Util::make_query_update($table, "id = '$id'", $arrfieldscomma, $arrfieldsnocomma);

                $result = $pdo->query($q);
                if (!$pdo->query($q)) {
                    $arrjson = Util::error_general('Actualizando las Configuraciones del sistema');
                } else {
                    $arrjson = array('output' => array('valid' => true, 'id' => $id));
                }
            }
            $db->closeConect();
            return $arrjson;
        }else{
            return  Util::error_missing_data();
        }

    }
}
