<?php

/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class DetalleVotaciones {
    public static function save($rqst)
    {
        $id = isset($rqst['id']) ? intval($rqst['id']) : 0;
        $oficiales = isset($rqst['oficiales']) ? ($rqst['oficiales']) : 0;
        $suboficiales =  isset($rqst['suboficiales']) ? ($rqst['suboficiales']) : 0;
        $soldados = isset($rqst['soldados']) ? ($rqst['soldados']) : 0;
        $total = isset($rqst['total']) ? ($rqst['total']) : 0;
        $indicativo = isset($rqst['indicativo']) ? ($rqst['indicativo']) :0;
        $comandante = isset($rqst['comandante']) ? ($rqst['comandante']) :0;
        $helicoportado = isset($rqst['helicoportado']) ? ($rqst['helicoportado']) :0;
        $observaciones = isset($rqst['observaciones']) ? ($rqst['observaciones']) :0;
        $horas_vuelo = isset($rqst['horas_vuelo']) ? ($rqst['horas_vuelo']) :0;
        $telefono = isset($rqst['telefono']) ? ($rqst['telefono']) : '';
        $desplazamiento = isset($rqst['desplazamiento']) ? ($rqst['desplazamiento']) : '';
        $compania = isset($rqst['compania']) ? ($rqst['compania']) :'';
        $peloton = isset($rqst['peloton']) ? ($rqst['peloton']) :'';
        $seccion = isset($rqst['seccion']) ? ($rqst['seccion']) :'';
        $escuadra = isset($rqst['escuadra']) ? ($rqst['escuadra']) :'';
        $soldados18 = isset($rqst['soldados18']) ? ($rqst['soldados18']) : 0;
        $reserva = isset($rqst['reserva']) ? ($rqst['reserva']) : '';
        $grado = isset($rqst['grado']) ? ($rqst['grado']) : '';

        $latitudu = isset($rqst['latitudu']) ? ($rqst['latitudu']) :'';
        $c1u = isset($rqst['c1u']) ? ($rqst['c1u']) :'';
        $c2u = isset($rqst['c2u']) ? ($rqst['c2u']) :'';
        $c3u = isset($rqst['c3u']) ? ($rqst['c3u']) :'';
        $longitud = isset($rqst['longitud']) ? ($rqst['longitud']) : 0;
        $c4u = isset($rqst['c4u']) ? ($rqst['c4u']) : '';
        $c5u = isset($rqst['c5u']) ? ($rqst['c5u']) : '';
        $c6u = isset($rqst['c6u']) ? ($rqst['c6u']) : '';
        $tbl_usuario_id =  $_SESSION['session_user']['id'];

        $db = new DbConection();
        $pdo = $db->openConect();

        if ($id > 0) {
            //actualiza la informacion
            $q = "SELECT id FROM " . $db->getTable('tbl_votaciones') . " WHERE id = " . $id;
            $result = $pdo->query($q);
            if ($result) {
                $table = $db->getTable('tbl_votaciones');
                $arrfieldscomma = array(
                    'oficiales' => $oficiales,
                    'suboficiales' => $suboficiales,
                    'soldados' => $soldados,
                    'total' => $total,
                    'indicativo' => $indicativo,
                    'comandante' => $comandante,
                    'telefono' => $telefono,
                    'helicoportado' => $helicoportado,
                    'horas_vuelo' => $horas_vuelo,
                    'observaciones' => $observaciones,
                    'telefono' => $telefono,
                    'desplazamiento' => $desplazamiento,
                    'compania' => $compania,
                    'peloton' => $peloton,
                    'seccion' => $seccion,
                    'escuadra' => $escuadra,
                    'soldados18' => $soldados18,
                    'reserva' => $reserva,
                    'grado' => $grado,
                    'longitudu' => $longitudu,
                    'c1u' => $c1u,
                    'c2u' => $c2u,
                    'c3u' => $c3u,
                    'latitudu' => $latitudu,
                    'c4u' => $c4u,
                    'c5u' => $c5u,
                    'c6u' => $c6u,
                    'tbl_usuario_id' => $tbl_usuario_id);
                $arrfieldsnocomma = array('dtcreate' => Util::date_now_server());
                $q = Util::make_query_update($table, "id = '$id'", $arrfieldscomma, $arrfieldsnocomma);
                $result = $pdo->query($q);
                if(!$result){
                    $arrjson = Util::error_general('Actualizando los datos de Detalle de Votaciones');
                }else{
                    $arrjson = array('output' => array('valid' => true, 'id' => $id));
                }
            } else {
                $arrjson = Util::error_general();
            }
        } else {
            if ($soldados != "") {
                $q = "UPDATE" . $db->getTable('tbl_votaciones') . " (dtcreate_at, oficiales, suboficiales, soldados, helicoportado, observaciones, horas_vuelo, total, indicativo, comandante, desplazamiento, telefono, compania, peloton, seccion, escuadra, soldados18, reserva, grado, latitud, c1u, c2u, c3u, longitud, c4u, c5u, c6u, tbl_usuario_id)
                VALUES ( " . Util::date_now_server() . ", :comandante, :suboficiales, :soldados, :helicoportado, :observaciones, :horas_vuelo, :total, :indicativo, :comandante, :desplazamiento, :telefono, :compania, :peloton, :seccion, :escuadra, :soldados18, :reserva, :grado, :latitud, :c1u, :c2u, :c3u, :longitud, :c4u, :c5u, :c6u,:tbl_usuario_id)";
                $result = $pdo->prepare($q);
                $arrparam = array(
                    ':oficiales' => $oficiales,
                    ':suboficiales' => $suboficiales,
                    ':soldados' => $soldados,
                    ':comandante' => $comandante,
                    ':telefono' => $telefono,
                    ':total' => $total,
                    ':helicoportado' => $helicoportado,
                    ':horas_vuelo' => $horas_vuelo,
                    ':observaciones' => $observaciones,
                    ':indicativo' => $indicativo,
                    ':desplazamiento' => $desplazamiento,
                    'compania' => $compania,
                    'peloton' => $peloton,
                    'seccion' => $seccion,
                    'escuadra' => $escuadra,
                    'soldados18' => $soldados18,
                    'reserva' => $reserva,
                    'grado' => $grado,
                    'latitudu' => $latitudu,
                    'c1u' => $c1u,
                    'c2u' => $c2u,
                    'c3u' => $c3u,
                    'longitudu' => $longitudu,
                    'c4u' => $c4u,
                    'c5u' => $c5u,
                    'c6u' => $c6u,
                    ':tbl_usuario_id' => $tbl_usuario_id);
                if ($result->execute($arrparam)) {
                    $arrjson = array('output' => array('valid' => true, 'response' => $pdo->lastInsertId()));
                } else {
                    $arrjson = Util::error_general(' Al guardar los datos de Detalle Votaciones');
                }
            } else {
                $arrjson = Util::error_missing_data();
            }
        }
        $db->closeConect();
        return $arrjson;
    }
}

