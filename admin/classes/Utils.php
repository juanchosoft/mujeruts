<?php
require_once 'SessionData.php';
require_once 'DbConection.php';
require_once 'Util.php';
/**
 * Clase que contiene todas las operaciones utilizadas sobre la base de datos
 * @author SPIDERSOFTWARE
 */
class Utils
{
    public function __construct() {}

    public static function ciudades($data)
    {
        try {
            $db = new DbConection();
            $pdo = $db->openConect();
            $q = "SELECT codigo_muncipio, nombre_mapa, municipio FROM " . $db->getTable('tbl_ciudades') . " WHERE codigo_departamento = :codigo_muncipio";
            $stmtData = $pdo->prepare($q);
            $stmtData->bindValue(':codigo_muncipio', $data, PDO::PARAM_INT);
            $stmtData->execute();
            $result = $stmtData->fetchAll(PDO::FETCH_ASSOC);
            return [
                'state' => true,
                'data' => $result
            ];
        } catch (PDOException $th) {
            return [
                'state' => false,
                'error' => $th->getMessage()
            ];
        }
    }

    public static function secretaria()
    {
        try {
            $db = new DbConection();
            $pdo = $db->openConect();
            $query = "SELECT
                            * 
                        FROM
                            " . $db->getTable('tbl_secretarias') . "
                        WHERE
                            mostrar = 'si' 
                        ORDER BY
                            secretaria ASC";

            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'state' => true,
                'data' => $result
            ];
        } catch (PDOException $th) {
            return [
                'state' => false,
                'error' => $th->getMessage()
            ];
        }
    }
}
