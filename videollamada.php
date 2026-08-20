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
$invitacionId = (int) ($_GET['id'] ?? 0);

$invitacion = null;
$errorCarga = '';

if ($usuarioId <= 0) {
    $errorCarga = 'Debes iniciar sesión para unirte a la videollamada.';
} elseif ($invitacionId <= 0) {
    $errorCarga = 'El enlace de la videollamada no es válido.';
} else {
    try {
        $db = new DbConection();
        $pdo = $db->openConect();

        $stmt = $pdo->prepare(
            "SELECT id, alerta_id, room_name, guest_url, estado
             FROM tbl_videollamada_invitacion
             WHERE id = :id AND usuario_id = :usuario_id
             LIMIT 1"
        );
        $stmt->bindValue(':id', $invitacionId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $invitacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invitacion) {
            $errorCarga = 'Esta videollamada ya no está disponible o no te pertenece.';
        } elseif ($invitacion['estado'] === 'cerrada') {
            $errorCarga = 'Esta videollamada ya fue cerrada.';
        } else {
            $stmtUpdate = $pdo->prepare(
                "UPDATE tbl_videollamada_invitacion
                 SET estado = 'unida', unido_en = NOW()
                 WHERE id = :id"
            );
            $stmtUpdate->bindValue(':id', $invitacionId, PDO::PARAM_INT);
            $stmtUpdate->execute();
        }
    } catch (Throwable $e) {
        $errorCarga = 'No fue posible cargar la videollamada.';
    }
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
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

        * {
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: var(--aliada-bg);
            color: var(--aliada-text);
        }

        .call-shell {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            width: 100%;
        }

        .call-header {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: calc(12px + env(safe-area-inset-top)) 16px 12px;
            background: var(--aliada-gradient);
            color: #fff;
        }

        .call-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            text-decoration: none;
            flex: 0 0 auto;
        }

        .call-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.3;
        }

        .call-subtitle {
            margin: 2px 0 0;
            font-size: 12px;
            font-weight: 600;
            opacity: 0.9;
        }

        .call-frame-wrap {
            flex: 1 1 auto;
            min-height: 0;
            background: #000;
        }

        #videollamadaFrame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }

        .call-error {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 32px 24px calc(24px + env(safe-area-inset-bottom));
            text-align: center;
        }

        .call-error i {
            font-size: 40px;
            color: var(--aliada-fucsia);
        }

        .call-error p {
            margin: 0;
            color: var(--aliada-muted);
            font-size: 14px;
            line-height: 1.5;
            max-width: 320px;
        }

        .call-error a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 14px;
            background: var(--aliada-gradient);
            color: #fff;
            font-weight: 800;
            font-size: 14px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="call-shell">
        <header class="call-header">
            <a href="panel_usuaria.php" class="call-back" aria-label="Volver">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </a>
            <div>
                <p class="call-title">Videollamada de acompañamiento</p>
                <p class="call-subtitle">Aliada UTS</p>
            </div>
        </header>

        <?php if ($errorCarga !== ''): ?>
            <div class="call-error">
                <i class="fas fa-video-slash" aria-hidden="true"></i>
                <p><?php echo e($errorCarga); ?></p>
                <a href="panel_usuaria.php">
                    <i class="fas fa-house" aria-hidden="true"></i>
                    Volver al inicio
                </a>
            </div>
        <?php else: ?>
            <div class="call-frame-wrap">
                <iframe
                    id="videollamadaFrame"
                    title="Videollamada Aliada UTS"
                    src="<?php echo e($invitacion['guest_url']); ?>"
                    allow="camera *; microphone *; display-capture *; autoplay; fullscreen"
                    allowfullscreen
                ></iframe>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
