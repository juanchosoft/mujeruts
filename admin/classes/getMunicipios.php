<?php
require_once './admin/classes/DbConection.php';

$dbConnection = new DbConection();
$db = $dbConnection->openConect();
if (isset($_GET['departamento_id']) && !empty($_GET['departamento_id'])) {
    $departamento_id = $_GET['departamento_id'];
    $stmt = $db->prepare("SELECT codigo_muncipio AS id, municipio FROM tbl_ciudades WHERE codigo_departamento = ?");
    $stmt->execute([$departamento_id]);
    $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['output' => ['valid' => true, 'response' => $municipios]]);
} else {
    echo json_encode(['output' => ['valid' => false, 'response' => [], 'error' => 'ID de departamento inválido']]);
}
