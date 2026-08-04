<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Atiende las peticiones AJAX activas.
 */
$rqst = $_REQUEST;
$op = isset($rqst['op']) ? $rqst['op'] : '';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=15, must-revalidate');
header('Access-Control-Allow-Origin: *');

require_once '../classes/DbConection.php';
require_once '../classes/Util.php';
require_once '../classes/SessionData.php';

$handlers = [
  'getFactoresPorMunicipiosByPilarIdPorColores' => function ($rqst) {
    require_once '../classes/Colombia.php';
    return Colombia::calcularColorDelDepartamentoByPilarId($rqst);
  },
  'graficasPorFatoresVeredasPorColor' => function ($rqst) {
    require_once '../classes/Colombia.php';
    return Colombia::calcularColorPorVeredasGeneralByPilarId($rqst);
  },
  'pms_getconf' => function ($rqst) {
    require_once '../classes/Configuracion.php';
    return Configuracion::getAll($rqst);
  },
  'pms_confsave' => function ($rqst) {
    require_once '../classes/Configuracion.php';
    return Configuracion::save($rqst);
  },
  'pms_usrlogin' => function ($rqst) {
    require_once '../classes/Usuario.php';
    return Usuario::login($rqst);
  },
  'pms_usrget' => function ($rqst) {
    require_once '../classes/Usuario.php';
    return Usuario::getAll($rqst);
  },
  'pms_usravailable' => function ($rqst) {
    require_once '../classes/Usuario.php';
    return Usuario::available($rqst);
  },
  'acttualizarPerfil' => function ($rqst) {
    require_once '../classes/Usuario.php';
    return Usuario::acttualizarPerfil($rqst);
  },
  'ciudadget' => function ($rqst) {
    require_once '../classes/Ciudad.php';
    return Ciudad::getAll($rqst);
  },
  'pms_desarrollo_get' => function ($rqst) {
    require_once '../classes/Desarrollo.php';
    return Desarrollo::getAll($rqst);
  },
  'pms_desarrollo_save' => function ($rqst) {
    require_once '../classes/Desarrollo.php';
    return Desarrollo::save($rqst);
  },
  'pms_desarrollo_filtar_secretaria' => function ($rqst) {
    require_once '../classes/Desarrollo.php';
    return Desarrollo::setFiltroSecretariaById($rqst);
  },
];

if (isset($handlers[$op])) {
  echo json_encode($handlers[$op]($rqst));
  exit;
}

echo json_encode([
  'output' => [
    'valid' => false,
    'response' => [
      'content' => 'OPERACION NO DISPONIBLE'
    ]
  ]
]);
