<?php
/*
 * Rastreador de uso para las páginas de la usuaria (partial; incluir antes de </body>).
 * Envía a api/analitica_latido.php la carga de página, un latido mientras la pestaña esté visible
 * y el cierre del tramo cuando se oculta. La página y el asistente los fija el servidor
 * (nombre del script o $analiticaPagina) contra una lista blanca; el navegador no los decide.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$analiticaUsuarioId = (int) ($_SESSION['id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? $_SESSION['session_user']['id'] ?? 0);

if ($analiticaUsuarioId <= 0) {
    return;
}

try {
    require_once __DIR__ . '/admin/classes/AnaliticaService.php';

    $analiticaPaginaActual = isset($analiticaPagina)
        ? (string) $analiticaPagina
        : basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');

    if (!array_key_exists($analiticaPaginaActual, AnaliticaService::PAGINAS)) {
        return;
    }

    $analiticaConfig = (new ConfigLoader())->getConfigAnalitica();

    if (empty($_SESSION['analitica_csrf'])) {
        $_SESSION['analitica_csrf'] = bin2hex(random_bytes(32));
    }

    $analiticaParametros = [
        'url' => 'api/analitica_latido.php',
        'csrf' => $_SESSION['analitica_csrf'],
        'pagina' => $analiticaPaginaActual,
        'intervaloMs' => (int) $analiticaConfig['heartbeat_segundos'] * 1000
    ];
} catch (Throwable $e) {
    error_log('analitica_tracker.php: ' . $e->getMessage());
    return;
}
?>
<script>
(function () {
    'use strict';

    var p = <?= json_encode($analiticaParametros, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function enviar(accion, extra, usarBeacon) {
        var cuerpo = JSON.stringify(Object.assign({ accion: accion, pagina: p.pagina, csrf: p.csrf }, extra || {}));

        if (usarBeacon && navigator.sendBeacon) {
            try {
                if (navigator.sendBeacon(p.url, new Blob([cuerpo], { type: 'application/json' }))) {
                    return;
                }
            } catch (error) {
                // Se usa fetch como respaldo.
            }
        }

        try {
            fetch(p.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': p.csrf },
                body: cuerpo,
                keepalive: true,
                credentials: 'same-origin'
            }).catch(function () {});
        } catch (error) {
            // El rastreo nunca debe afectar a la página.
        }
    }

    enviar('pagina');

    setInterval(function () {
        if (document.visibilityState === 'visible') {
            enviar('latido');
        }
    }, p.intervaloMs);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            enviar('cierre', null, true);
        } else {
            enviar('reanudar');
        }
    });

    window.addEventListener('pagehide', function () {
        enviar('cierre', null, true);
    });

    window.addEventListener('pageshow', function (evento) {
        if (evento.persisted) {
            enviar('reanudar');
        }
    });

    window.AliadaAnalitica = {
        evento: function (tipo, detalle) {
            enviar('evento', { tipo: tipo, detalle: detalle || null });
        }
    };
})();
</script>
