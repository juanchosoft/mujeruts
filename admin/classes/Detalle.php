<?php

/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Detalle
{  

  public function __construct()
  {
  }

  public static function getAll($rqst)
  {

    $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
    $tipo = isset($rqst['tipo']) ? ($rqst['tipo']) : '';

    $db = new DbConection();
    $pdo = $db->openConect();


    $q= "SELECT tbl_visitas.provincia, tbl_ciudades.municipio 
    FROM " . $db->getTable('tbl_visitas') . "
    INNER JOIN " . $db->getTable('tbl_ciudades') . " ON tbl_visitas.tbl_municipio_id = tbl_ciudades.codigo_muncipio GROUP BY municipio ORDER BY tbl_visitas.provincia ASC"; 

 
    if ($id > 0) {
      $q = "SELECT * FROM " . $db->getTable('tbl_visitas') . " WHERE id = " . $id;
    }

    if ($tipo != "") {
      $q = "SELECT * FROM " . $db->getTable('tbl_visitas') . " WHERE tipo = '$tipo' AND habilitado = 'si'";
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
}