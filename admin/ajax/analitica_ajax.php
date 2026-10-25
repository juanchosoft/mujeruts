<?php

/*
 * Acciones del dashboard de analítica. Solo rol Administrador del panel.
 * Lecturas: GET ?accion=kpis|en_linea|series|uso_asistentes|metricas|sesiones|uso_detalle
 * Escrituras auditadas: POST accion=revelar_identidad|revelar_ip|registrar_exportacion con cabecera X-CSRF-Token.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../classes/DbConection.php';
require_once __DIR__ . '/../classes/ConfigLoader.php';
require_once __DIR__ . '/../classes/AnaliticaService.php';
require_once __DIR__ . '/../classes/AnaliticaConsultas.php';

function responder($codigo, array $datos)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

$usuarioPanelId = (int) ($_SESSION['panel_user']['id'] ?? 0);
$rol = strtolower(trim((string) ($_SESSION['panel_user']['rol'] ?? '')));

if ($usuarioPanelId <= 0 || $rol !== 'administrador') {
    responder(403, ['ok' => false, 'error' => 'Acceso restringido a administradores del panel.']);
}

$csrfSesion = (string) ($_SESSION['analitica_panel_csrf'] ?? '');

// La sesión no se necesita más; liberarla evita bloquear otras pestañas del panel.
session_write_close();

$metodo = $_SERVER['REQUEST_METHOD'];
$cuerpoJson = null;

if ($metodo === 'POST') {
    $cuerpoJson = json_decode((string) file_get_contents('php://input'), true);
    $entrada = is_array($cuerpoJson) ? $cuerpoJson : $_POST;
} else {
    $entrada = $_GET;
}

$accion = $entrada['accion'] ?? '';

if (!is_string($accion) || $accion === '') {
    responder(400, ['ok' => false, 'error' => 'Acción no indicada.']);
}

$accionesLectura = ['kpis', 'en_linea', 'series', 'uso_asistentes', 'metricas', 'sesiones', 'uso_detalle'];
$accionesEscritura = ['revelar_identidad', 'revelar_ip', 'registrar_exportacion'];

if (in_array($accion, $accionesEscritura, true)) {
    if ($metodo !== 'POST') {
        responder(405, ['ok' => false, 'error' => 'Método no permitido.']);
    }

    $csrfRecibido = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if ($csrfSesion === '' || !hash_equals($csrfSesion, $csrfRecibido)) {
        responder(403, ['ok' => false, 'error' => 'Solicitud no autorizada. Recarga la página.']);
    }
} elseif (!in_array($accion, $accionesLectura, true) || $metodo !== 'GET') {
    responder(400, ['ok' => false, 'error' => 'Acción no válida.']);
}

try {
    $db = new DbConection();
    $pdo = $db->openConect();
    $consultas = new AnaliticaConsultas($pdo);

    try {
        $f = $consultas->validarFiltros($entrada);
    } catch (InvalidArgumentException $e) {
        responder(422, ['ok' => false, 'error' => $e->getMessage()]);
    }

    if (in_array($accion, ['kpis', 'en_linea'], true)) {
        $servicio = new AnaliticaService($pdo, (new ConfigLoader())->getConfigAnalitica());
        $servicio->cerrarInactivas();

        if ($accion === 'kpis' && AnaliticaService::mantenimientoPendiente()) {
            $servicio->ejecutarMantenimiento();
        }
    }

    switch ($accion) {
        case 'kpis':
            responder(200, ['ok' => true, 'filtros' => $f, 'datos' => $consultas->kpis($f)]);
            break;

        case 'en_linea':
            responder(200, ['ok' => true, 'datos' => $consultas->enLinea($f)]);
            break;

        case 'series':
            responder(200, ['ok' => true, 'datos' => $consultas->series($f)]);
            break;

        case 'uso_asistentes':
            responder(200, ['ok' => true, 'datos' => $consultas->usoAsistentes($f)]);
            break;

        case 'metricas':
            responder(200, ['ok' => true, 'datos' => $consultas->metricas($f)]);
            break;

        case 'sesiones':
            responder(200, ['ok' => true, 'datos' => $consultas->sesiones($f)]);
            break;

        case 'uso_detalle':
            responder(200, ['ok' => true, 'datos' => $consultas->usoDetalle($f)]);
            break;
    }

    $ipPanel = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    if ($accion === 'revelar_identidad' || $accion === 'revelar_ip') {
        try {
            $motivo = $consultas->validarMotivo($entrada['motivo'] ?? null);
        } catch (InvalidArgumentException $e) {
            responder(422, ['ok' => false, 'error' => $e->getMessage()]);
        }

        if ($accion === 'revelar_identidad') {
            $uid = filter_var($entrada['usuario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($uid === false) {
                responder(422, ['ok' => false, 'error' => 'Usuaria no válida.']);
            }

            $nombre = $consultas->revelarIdentidad($uid);

            if ($nombre === null) {
                responder(404, ['ok' => false, 'error' => 'La usuaria ya no existe.']);
            }

            $consultas->registrarAuditoria($usuarioPanelId, 'revelar_identidad', $uid, 'Motivo: ' . $motivo, $ipPanel);
            responder(200, ['ok' => true, 'nombre' => $nombre !== '' ? $nombre : '(sin nombre registrado)', 'expira_seg' => 60]);
        }

        $sid = filter_var($entrada['sesion_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($sid === false) {
            responder(422, ['ok' => false, 'error' => 'Sesión no válida.']);
        }

        $datosIp = $consultas->revelarIp($sid);

        if ($datosIp === null) {
            responder(404, ['ok' => false, 'error' => 'La sesión ya no existe.']);
        }

        $consultas->registrarAuditoria($usuarioPanelId, 'revelar_ip', $datosIp['usuario_id'], 'Sesión ' . $sid . '. Motivo: ' . $motivo, $ipPanel);
        responder(200, [
            'ok' => true,
            'ip' => $datosIp['ip'] !== '' ? $datosIp['ip'] : '(sin IP registrada)',
            'anonimizada' => $datosIp['anonimizada'],
            'expira_seg' => 60
        ]);
    }

    if ($accion === 'registrar_exportacion') {
        $tabla = $entrada['tabla'] ?? '';
        $formato = $entrada['formato'] ?? '';

        if (!is_string($tabla) || !in_array($tabla, ['sesiones', 'uso_detalle', 'en_linea', 'resumen'], true)
            || !is_string($formato) || !in_array($formato, ['excel', 'pdf'], true)) {
            responder(422, ['ok' => false, 'error' => 'Exportación no válida.']);
        }

        $detalle = 'tabla=' . $tabla . '; rango=' . $f['desde'] . '..' . $f['hasta']
            . '; asistente=' . ($f['asistente'] ?? 'todos')
            . '; dispositivo=' . ($f['dispositivo'] ?? 'todos')
            . '; municipio=' . ($f['municipio'] ?? 'todos');

        $consultas->registrarAuditoria($usuarioPanelId, $formato === 'excel' ? 'exportar_excel' : 'exportar_pdf', null, $detalle, $ipPanel);
        responder(200, ['ok' => true]);
    }

    responder(400, ['ok' => false, 'error' => 'Acción no válida.']);
} catch (Throwable $e) {
    error_log('analitica_ajax.php [' . $accion . ']: ' . $e->getMessage());
    responder(500, ['ok' => false, 'error' => 'No fue posible procesar la solicitud.']);
}
