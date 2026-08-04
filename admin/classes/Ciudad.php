<?php

/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Ciudad
{

  public function __construct() {}

  public static function getInformacionCiudad($rqst)
  {
    $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
    $codigo_departamento = isset($rqst['codigo_departamento']) ? $rqst['codigo_departamento'] : 0;
    $codigo_muncipio = isset($rqst['codigo_muncipio']) ? $rqst['codigo_muncipio'] : 0;

    $db = new DbConection();
    $pdo = $db->openConect();

    // Consulta base
    $q = "SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada');
    $conditions = [];
    $params = [];

    // Construcción dinámica de condiciones
    if ($id > 0) {
      $conditions[] = "id = :id";
      $params[':id'] = $id;
    }
    if ($codigo_departamento > 0) {
      $conditions[] = "codigo_departamento = :codigo_departamento";
      $params[':codigo_departamento'] = $codigo_departamento;
    }
    if ($codigo_muncipio > 0) {
      $conditions[] = "codigo_muncipio = :codigo_muncipio";
      $params[':codigo_muncipio'] = $codigo_muncipio;
    }

    // Si hay condiciones, agrégalas
    if (!empty($conditions)) {
      $q .= " WHERE " . implode(" AND ", $conditions);
    }

    // Agregar ordenamiento
    $q .= " ORDER BY municipio ASC";

    // Preparar y ejecutar la consulta
    $stmt = $pdo->prepare($q);
    $stmt->execute($params);

    // Recoger resultados
    $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($arr)) {
      $arrjson = ['output' => ['valid' => true, 'response' => $arr]];
    } else {
      $arrjson = Util::error_no_result();
    }

    $db->closeConect();
    return $arrjson;
  }



  /**
   * Metodo para obtener la informacion de los apotos totales por departamento, y poblacion habilitada para votar
   * @param string $
   */
  public static function getApoyoByCodigoDepartamento($rqst)
  {
    $codigo_departamento = isset($rqst['codigo_departamento']) ? ($rqst['codigo_departamento']) : 0;

    $arrApoyoLideres = array();
    $arrApoyoDepartamento = array();
    $arrPoblacionHabilitadaDepartamento = array();

    // Apoyo de lideres y apoyo del departamento 
    if ($codigo_departamento > 0) {

      $db = new DbConection();
      $pdo = $db->openConect();

      $q = "SELECT * FROM " . $db->getTable('tbl_departamentos') . " WHERE codigo_departamento = '$codigo_departamento' ";
      $result = $pdo->query($q);
      $tbl_departamento_id = 0;
      if ($result) {
        foreach ($result as $valor) {
          $tbl_departamento_id = $valor['id'];
        }
      }


      // Apoyo de Lideres por departamento
      $qApoyoLideres = "SELECT tbl_lideres.tbl_departamento_id, Count(tbl_lideres.id) AS cantidad
        FROM " . $db->getTable('tbl_lideres') . "
        WHERE tbl_lideres.tbl_departamento_id =  $codigo_departamento
        GROUP BY tbl_lideres.tbl_departamento_id";





      $resultApoyoLideres = $pdo->query($qApoyoLideres);
      if ($resultApoyoLideres) {
        foreach ($resultApoyoLideres as $valorLider) {
          $arrApoyoLideres[] = $valorLider;
        }
      }

      // Apoyo por departamento
      $qApoyoDepartamento = "SELECT Count(comentarios.id) AS cantidad, comentarios.departamento_id
        FROM " . $db->getTable('comentarios') . " 
        WHERE comentarios.departamento_id = $tbl_departamento_id
        GROUP BY comentarios.departamento_id";


      $resultApoyoDep = $pdo->query($qApoyoDepartamento);
      if ($resultApoyoDep) {
        foreach ($resultApoyoDep as $valorApoyoDep) {
          $arrApoyoDepartamento[] = $valorApoyoDep;
        }
      }

      // Poblacion Habilitada para votar
      $qPoblacionHabilitada = " SELECT tbl_ciudades.codigo_departamento, 
        Sum(tbl_ciudades.hombres) AS hombres, 
        Sum(tbl_ciudades.mujeres) AS mujeres, 
        Sum(tbl_ciudades.total) AS total
        FROM " . $db->getTable('tbl_ciudades') . "  
        WHERE tbl_ciudades.codigo_departamento = '$codigo_departamento'
        GROUP BY tbl_ciudades.codigo_departamento";
      $resultPoblacionHabilitada = $pdo->query($qPoblacionHabilitada);
      if ($resultPoblacionHabilitada) {

        foreach ($resultPoblacionHabilitada as $valorPobHabilitada) {
          $arrPoblacionHabilitadaDepartamento[] = $valorPobHabilitada;
        }
      }

      $arrjson = array(
        'output' => array(
          'valid' => true,
          'apoyoDeLideres' => $arrApoyoLideres,
          'apoyoDep' => $arrApoyoDepartamento,
          'poblacionDepHabilitada' => $arrPoblacionHabilitadaDepartamento
        )
      );

      $db->closeConect();
      return $arrjson;
    } else {
      return Util::error_missing_data();
    }
  }

  public static function getAll($rqst)
  {
    $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
    $codigo_departamento = isset($rqst['codigo_departamento']) ? ($rqst['codigo_departamento']) : 0;
    $codigo_muncipio = isset($rqst['codigo_muncipio']) ? ($rqst['codigo_muncipio']) : 0;

    $db = new DbConection();
    $pdo = $db->openConect();

    $q = "SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " ORDER BY municipio ASC";

    if ($id > 0) {
      $q = "SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " WHERE id = " . $id;
    }

    if ($codigo_departamento > 0) {
      $q = "SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " WHERE codigo_departamento = " . $codigo_departamento;
    }
    if ($codigo_muncipio > 0) {
      $q = "SELECT * FROM " . $db->getTable('tbl_ciudades_accion_unificada') . " WHERE codigo_muncipio = " . $codigo_muncipio;
    }
    $result = $pdo->query($q);
    $arr = array();

    if ($result) {
      foreach ($result as $valor) {
        $codeCity = $valor['codigo_muncipio'];
        $colorCalculadoDelMunicipio = "";

        $arrTemp = array();
        $arrTemp['id'] = $valor['id'];
        $arrTemp['codigo_departamento'] = $valor['codigo_departamento'];
        $arrTemp['codigo_muncipio'] = $valor['codigo_muncipio'];
        $arrTemp['subregion'] = $valor['subregion'];
        $arrTemp['porcentaje_participacion'] = $valor['porcentaje_participacion'];
        $arrTemp['aap'] = $valor['aap'];
        $arrTemp['pdet'] = $valor['pdet'];
        $arrTemp['zf'] = $valor['zf'];
        $arrTemp['visible'] = $valor['visible'];
        $arrTemp['tbl_batallon_id'] = $valor['tbl_batallon_id'];
        $arrTemp['municipio'] = $valor['municipio'];
        $arrTemp['puntaje'] = $valor['puntaje'];
        $arrTemp['color'] = $valor['color'];
        $arrTemp['carpeta_mapa'] = $valor['carpeta_mapa'];
        $arrTemp['carpeta_svg'] = $valor['carpeta_svg'];
        $arrTemp['nombre_mapa'] = $valor['nombre_mapa'];
        $arrTemp['latitud'] = $valor['latitud'];
        $arrTemp['longitud'] = $valor['longitud'];
        $arrTemp['color_calculado_de_municipio'] = $colorCalculadoDelMunicipio;
        $arr[] = $arrTemp;
      }
      $arrjson = array('output' => array('valid' => true, 'response' => $arr));
    } else {
      $arrjson = Util::error_no_result();
    }
    $db->closeConect();
    return $arrjson;
  }




}
