<?php

/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Desarrollo
{


  /**
   * Metodo para setear la variable de session y realizar filtros.
   */
  public static function setFiltroSecretariaById($rqst)
  {
      $tbl_secretarias_id = isset($rqst['secretaria_id']) ? intval($rqst['secretaria_id']) : ''; 

      if($tbl_secretarias_id > 0){
        $_SESSION['session_user']['secretaria'] = $tbl_secretarias_id;
      }

      return array('output' => array('valid' => true));
  }


    public static function getAll($rqst)
    {
  
      $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
      $secretaria_id = isset($rqst['secretaria_id']) ? intval($rqst['secretaria_id']) : 0;
      $tipo = isset($rqst['tipo']) ? ($rqst['tipo']) : '';
  
      $db = new DbConection();
      $pdo = $db->openConect();
  
       
    $q="SELECT tbl_plandesarrollo.*, tbl_secretarias.secretaria
        FROM " . $db->getTable('tbl_plandesarrollo') . " 
        INNER JOIN " . $db->getTable('tbl_secretarias') . "  ON tbl_plandesarrollo.tbl_secretaria_id = tbl_secretarias.id";  
 
    
    
      if ($id > 0) {

        $q="SELECT tbl_plandesarrollo.*, tbl_secretarias.secretaria
        FROM " . $db->getTable('tbl_plandesarrollo') . " 
        INNER JOIN " . $db->getTable('tbl_secretarias') . "  ON tbl_plandesarrollo.tbl_secretaria_id = tbl_secretarias.id WHERE tbl_plandesarrollo.id = " . $id;  
   
      }
      if ($secretaria_id > 0) {

        $q="SELECT tbl_plandesarrollo.*, tbl_secretarias.secretaria
        FROM " . $db->getTable('tbl_plandesarrollo') . " 
        INNER JOIN " . $db->getTable('tbl_secretarias') . "  ON tbl_plandesarrollo.tbl_secretaria_id = tbl_secretarias.id 
        WHERE tbl_secretarias.id = " . $secretaria_id;  
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

  public static function save($rqst)
  {
    $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
    $tbl_secretaria_id = isset($rqst['tbl_secretaria_id']) ? intval($rqst['tbl_secretaria_id']) : 0;
    $eje_estrategico =  isset($rqst['eje_estrategico']) ? ($rqst['eje_estrategico']) : '';
    $sector_pdd =  isset($rqst['sector_pdd']) ? ($rqst['sector_pdd']) : '';
    $sector_cat_prod =  isset($rqst['sector_cat_prod']) ? ($rqst['sector_cat_prod']) : '';
    $producto_servicio_pdd = isset($rqst['producto_servicio_pdd']) ? intval($rqst['producto_servicio_pdd']) : 0;
    $direccion_resp = isset($rqst['direccion_resp']) ? ($rqst['direccion_resp']) : [];
    $ps2024 = isset($rqst['ps2024']) ? floatval($rqst['ps2024']) : 0;
    $ps2025 = isset($rqst['ps2025']) && $rqst['ps2025'] !="" ? ($rqst['ps2025']) : null;
    $ps2026 = isset($rqst['ps2026']) && $rqst['ps2026'] !="" ? ($rqst['ps2026']) : null;
    $ps2027 = isset($rqst['ps2027']) && $rqst['ps2027'] !=""  ? ($rqst['ps2027']) : null;
    $user_id =  intval($_SESSION['session_user']['id']);


   
    $db = new DbConection();
    $pdo = $db->openConect();

    if ($id > 0) {
      $q = "SELECT * FROM " . $db->getTable('tbl_plandesarrollo') . " WHERE id = " . $id;
      $result = $pdo->query($q);
      if ($result) {
        $table = $db->getTable('tbl_plandesarrollo');
        $arrfieldscomma = array(
          'eje_estrategico' => $eje_estrategico,
          'sector_pdd' => $sector_pdd,
          'sector_cat_prod' => $sector_cat_prod,
          'tbl_secretaria_id' => $tbl_secretaria_id,
          'direccion_resp' => $direccion_resp,
          'ps2024' => $ps2024,
          'ps2025' => $ps2025,
          'ps2026' => $ps2026,
          'ps2027' => $ps2027,
          'user_id' => $user_id       
        );
        $arrfieldsnocomma = array('dtcreate' => Util::date_now_server());
                $q = Util::make_query_update($table, "id = '$id'", $arrfieldscomma, $arrfieldsnocomma);
                $result = $pdo->query($q);
                if(!$result){
                    $arrjson = Util::error_general('Actualizando los datos de brigada');
                }else{
                    $arrjson = array('output' => array('valid' => true, 'id' => $id));
                }
            } else {
                $arrjson = Util::error_general();
            }
        } else {
            if ($eje_estrategico != "") {
                $q = "INSERT INTO " . $db->getTable('tbl_plandesarrollo') . " (dtcreate, eje_estrategico, sector_pdd, sector_cat_prod, tbl_secretaria_id, direccion_resp, ps2024, ps2025, ps2026, ps2027, user_id)
                VALUES ( " . Util::date_now_server() . ", :eje_estrategico, :sector_pdd, :sector_cat_prod, :tbl_secretaria_id, :direccion_resp, :ps2024, :ps2025, :ps2026, :ps2027, :user_id)";
                $result = $pdo->prepare($q);
                $arrparam = array(
                    ':eje_estrategico' => $eje_estrategico,
                    ':sector_pdd' => $sector_pdd,
                    ':sector_cat_prod' => $sector_cat_prod,
                    ':tbl_secretaria_id' => $tbl_secretaria_id,
                    ':direccion_resp' => $direccion_resp,
                    ':ps2024' => $ps2024,
                    ':ps2025' => $ps2025,
                    ':ps2026' => $ps2026,
                    ':ps2027' => $ps2027,
                    ':user_id' => $user_id);
                if ($result->execute($arrparam)) {
                    $arrjson = array('output' => array('valid' => true, 'response' => $pdo->lastInsertId()));
                } else {
                    $arrjson = Util::error_general(' Al guardar los datos de brigada');
                }
            } else {
                $arrjson = Util::error_missing_data();
            }
        }
        $db->closeConect();
        return $arrjson;
    }

    public static function updateStatus($id, $status) {
      $db = new DbConection();
      $pdo = $db->openConect();

      $q = "UPDATE " . $db->getTable('avance_2024') . " SET id = :status WHERE id = :id";
      $stmt = $pdo->prepare($q);
      $stmt->bindParam(':status', $status, PDO::PARAM_STR);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $result = $stmt->execute();
      $db->closeConect();

      return $result;
  }

    
}