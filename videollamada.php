<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/admin/classes/DbConection.php';

$usuarioId = (int) ($_SESSION['id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? $_SESSION['session_user']['id'] ?? 0);
$idParam = trim((string) ($_GET['id'] ?? ''));

$invitacion = null;
$errorCarga = '';

/**
 * Distingue UUID v4/estilo de un id numerico interno.
 */
function esTokenUuid($valor)
{
    return (bool) preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
        (string) $valor
    );
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

if ($idParam === '') {
    $errorCarga = 'El enlace de la videollamada no es válido.';
} elseif ($usuarioId <= 0) {
    // Sin sesion: redirigir a login y volver aqui tras autenticarse.
    $returnPath = 'videollamada.php?id=' . rawurlencode($idParam);
    header('Location: login.php?return=' . rawurlencode($returnPath));
    exit;
} else {
    try {
        $db = new DbConection();
        $pdo = $db->openConect();

        if (esTokenUuid($idParam)) {
            $stmt = $pdo->prepare(
                "SELECT id, alerta_id, room_name, guest_url, estado, usuario_id
                 FROM tbl_videollamada_invitacion
                 WHERE token = :token
                 LIMIT 1"
            );
            $stmt->bindValue(':token', $idParam, PDO::PARAM_STR);
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, alerta_id, room_name, guest_url, estado, usuario_id
                 FROM tbl_videollamada_invitacion
                 WHERE id = :id
                 LIMIT 1"
            );
            $stmt->bindValue(':id', (int) $idParam, PDO::PARAM_INT);
        }

        $stmt->execute();
        $invitacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitacion) {
            $errorCarga = 'Esta videollamada ya no está disponible.';
            $invitacion = null;
        } elseif ((int) $invitacion['usuario_id'] !== $usuarioId) {
            // El enlace es valido pero pertenece a otra usuaria.
            $errorCarga = 'Este enlace de videollamada no corresponde a tu cuenta.';
            $invitacion = null;
        } elseif ($invitacion['estado'] === 'cerrada') {
            $errorCarga = 'Esta videollamada ya fue cerrada.';
            $invitacion = null;
        } else {
            $stmtUpdate = $pdo->prepare(
                "UPDATE tbl_videollamada_invitacion
                 SET estado = 'unida', unido_en = NOW()
                 WHERE id = :id"
            );
            $stmtUpdate->bindValue(':id', (int) $invitacion['id'], PDO::PARAM_INT);
            $stmtUpdate->execute();
        }
    } catch (Throwable $e) {
        $errorCarga = 'No fue posible cargar la videollamada.';
        $invitacion = null;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Videollamada | Aliada UTS</title>

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --aliada-violeta: #7B1FA2;
            --aliada-fucsia: #D81B9C;
            --aliada-rosa: #FF4FB8;
            --aliada-bg: #FFF5FB;
            --aliada-text: #2B1237;
            --aliada-muted: #80658D;
            --aliada-gradient: linear-gradient(135deg, #FF4FB8 0%, #D81B9C 48%, #7B1FA2 100%);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--aliada-bg);
            color: var(--aliada-text);
        }

        .shell {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            max-width: 920px;
            margin: 0 auto;
        }

        .topbar {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            padding-top: calc(14px + env(safe-area-inset-top));
            background: var(--aliada-gradient);
            color: #fff;
        }

        .topbar h1 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.95;
        }

        .content {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        #videollamadaFrame {
            flex: 1 1 auto;
            width: 100%;
            border: 0;
            min-height: 70dvh;
            background: #1a0a24;
        }

        .error-box {
            margin: 32px 20px;
            padding: 22px 18px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid rgba(123, 31, 162, 0.15);
            box-shadow: 0 10px 30px rgba(43, 18, 55, 0.08);
            text-align: center;
        }

        .error-box i {
            font-size: 2rem;
            color: var(--aliada-fucsia);
            margin-bottom: 10px;
        }

        .error-box p {
            margin: 0 0 16px;
            color: var(--aliada-muted);
            line-height: 1.45;
        }

        .error-box a.btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            background: var(--aliada-gradient);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <h1>Videollamada Aliada UTS</h1>
            <a href="panel_usuaria.php"><i class="fa-solid fa-arrow-left"></i> Volver</a>
        </header>

        <div class="content">
            <?php if ($errorCarga !== '' || !$invitacion): ?>
                <div class="error-box">
                    <i class="fa-solid fa-video-slash" aria-hidden="true"></i>
                    <p><?php echo e($errorCarga !== '' ? $errorCarga : 'No fue posible abrir la videollamada.'); ?></p>
                    <a class="btn" href="panel_usuaria.php">Ir al panel</a>
                </div>
            <?php else: ?>
                <iframe
                    id="videollamadaFrame"
                    src="<?php echo e($invitacion['guest_url']); ?>"
                    allow="camera *; microphone *; display-capture *; autoplay"
                    allowfullscreen
                    title="Sala de videollamada"
                ></iframe>
            <?php endif; ?>
        </div>
    </div>
<?php include __DIR__ . '/analitica_tracker.php'; ?>
</body>
</html>
