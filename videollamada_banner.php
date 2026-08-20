<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioIdBanner = (int) ($_SESSION['id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? $_SESSION['session_user']['id'] ?? 0);

if ($usuarioIdBanner <= 0) {
    return;
}
?>
<div id="videollamadaBanner" class="videollamada-banner" hidden role="status" aria-live="polite">
    <div class="videollamada-banner-texto">
        <i class="fas fa-video" aria-hidden="true"></i>
        <span>Tienes una videollamada de acompañamiento disponible</span>
    </div>
    <div class="videollamada-banner-acciones">
        <button type="button" id="btnUnirmeVideollamada" class="videollamada-banner-btn-join">
            Unirme a la videollamada
        </button>
        <button type="button" id="btnCerrarVideollamadaBanner" class="videollamada-banner-btn-close" aria-label="Cerrar aviso">
            &times;
        </button>
    </div>
</div>

<style>
    .videollamada-banner {
        position: fixed;
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 14px;
        max-width: calc(100vw - 24px);
        padding: 14px 18px;
        border-radius: 16px;
        background: linear-gradient(135deg, #FF4FB8 0%, #D81B9C 48%, #7B1FA2 100%);
        color: #fff;
        box-shadow: 0 14px 34px rgba(123, 31, 162, 0.35);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        animation: videollamada-banner-in 0.25s ease;
    }

    .videollamada-banner[hidden] {
        display: none !important;
    }

    .videollamada-banner-texto {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
    }

    .videollamada-banner-acciones {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .videollamada-banner-btn-join {
        padding: 10px 16px;
        border: 0;
        border-radius: 10px;
        background: #fff;
        color: #7B1FA2;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
        cursor: pointer;
    }

    .videollamada-banner-btn-join:hover {
        filter: brightness(0.96);
    }

    .videollamada-banner-btn-close {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        background: transparent;
        color: #fff;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
    }

    .videollamada-banner-btn-close:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    @keyframes videollamada-banner-in {
        from {
            opacity: 0;
            transform: translate(-50%, 12px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }

    @media (max-width: 480px) {
        .videollamada-banner {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            bottom: 10px;
        }

        .videollamada-banner-acciones {
            justify-content: center;
        }
    }
</style>

<script>
(function () {
    'use strict';

    var ENDPOINT = 'videollamada_estado.php';
    var POLL_MS = 8000;

    var banner = document.getElementById('videollamadaBanner');
    var btnUnirme = document.getElementById('btnUnirmeVideollamada');
    var btnCerrar = document.getElementById('btnCerrarVideollamadaBanner');
    var invitacionActual = null;

    if (!banner) {
        return;
    }

    function marcarEstado(accion) {
        if (!invitacionActual || !invitacionActual.id) {
            return;
        }

        var body = new URLSearchParams();
        body.append('action', accion);
        body.append('id', invitacionActual.id);

        fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin'
        }).catch(function () {});
    }

    function consultar() {
        fetch(ENDPOINT + '?action=consultar', { credentials: 'same-origin' })
            .then(function (respuesta) {
                return respuesta.json();
            })
            .then(function (json) {
                if (json && json.ok && json.pendiente) {
                    invitacionActual = json;
                    banner.hidden = false;
                }
            })
            .catch(function () {});
    }

    if (btnUnirme) {
        btnUnirme.addEventListener('click', function () {
            if (!invitacionActual) {
                return;
            }

            window.location.href = 'videollamada.php?id=' + encodeURIComponent(invitacionActual.id);
        });
    }

    if (btnCerrar) {
        btnCerrar.addEventListener('click', function () {
            marcarEstado('descartar');
            banner.hidden = true;
            invitacionActual = null;
        });
    }

    consultar();
    setInterval(consultar, POLL_MS);
})();
</script>
