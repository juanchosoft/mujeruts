<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
}

const LOGIN_URL = 'login.php';
const USER_TABLE = 'tbl_usuarios';
const USER_PRIMARY_KEY = 'id';
const CONFIRMATION_TEXT = 'ELIMINAR';

/**
 * Obtiene el identificador desde las estructuras de sesión utilizadas
 * habitualmente por el proyecto. No acepta valores que no sean enteros.
 */
function obtenerIdUsuarioSesion(): ?int
{
    $candidatos = [
        $_SESSION['session_user']['id'] ?? null,
        $_SESSION['session_user']['usuario_id'] ?? null,
        $_SESSION['session_user']['id_usuario'] ?? null,
        $_SESSION['usuario_id'] ?? null,
        $_SESSION['id_usuario'] ?? null,
        $_SESSION['id'] ?? null,
    ];

    foreach ($candidatos as $candidato) {
        if (filter_var($candidato, FILTER_VALIDATE_INT) !== false && (int) $candidato > 0) {
            return (int) $candidato;
        }
    }

    return null;
}

function limpiarSesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $parametros['path'],
                'domain' => $parametros['domain'],
                'secure' => (bool) $parametros['secure'],
                'httponly' => (bool) $parametros['httponly'],
                'samesite' => 'Lax',
            ]
        );
    }

    session_destroy();
}

function nombreSqlSeguro(string $nombre): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $nombre)) {
        throw new RuntimeException('Se encontró un identificador SQL no válido.');
    }

    return '`' . $nombre . '`';
}

/**
 * Elimina registros de tablas que tengan una clave foránea directa hacia
 * tbl_usuarios.id. Las tablas y columnas provienen de information_schema,
 * y aun así se validan antes de incorporarlas a la consulta.
 */
function eliminarRegistrosRelacionados(PDO $pdo, string $baseDatos, int $usuarioId): void
{
    $consulta = $pdo->prepare(
        'SELECT TABLE_NAME, COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE REFERENCED_TABLE_SCHEMA = :schema
           AND REFERENCED_TABLE_NAME = :tabla
           AND REFERENCED_COLUMN_NAME = :columna
           AND TABLE_SCHEMA = :schema_actual'
    );

    $consulta->execute([
        ':schema' => $baseDatos,
        ':tabla' => USER_TABLE,
        ':columna' => USER_PRIMARY_KEY,
        ':schema_actual' => $baseDatos,
    ]);

    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $relacion) {
        $tabla = (string) ($relacion['TABLE_NAME'] ?? '');
        $columna = (string) ($relacion['COLUMN_NAME'] ?? '');

        if ($tabla === '' || $columna === '' || $tabla === USER_TABLE) {
            continue;
        }

        $sql = sprintf(
            'DELETE FROM %s.%s WHERE %s = :usuario_id',
            nombreSqlSeguro($baseDatos),
            nombreSqlSeguro($tabla),
            nombreSqlSeguro($columna)
        );

        $eliminar = $pdo->prepare($sql);
        $eliminar->execute([':usuario_id' => $usuarioId]);
    }
}

$usuarioId = obtenerIdUsuarioSesion();

if ($usuarioId === null) {
    header('Location: ' . LOGIN_URL);
    exit;
}

if (empty($_SESSION['delete_account_csrf'])) {
    $_SESSION['delete_account_csrf'] = bin2hex(random_bytes(32));
}

$csrf = (string) $_SESSION['delete_account_csrf'];
$error = '';
$nombreUsuario = trim((string) (
    $_SESSION['nombre_completo']
    ?? $_SESSION['session_user']['nombre']
    ?? $_SESSION['nombre']
    ?? 'tu cuenta'
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfRecibido = (string) ($_POST['csrf_token'] ?? '');
    $confirmacion = strtoupper(trim((string) ($_POST['confirmacion'] ?? '')));
    $aceptaBorrado = isset($_POST['acepta_borrado']) && $_POST['acepta_borrado'] === '1';

    if (!hash_equals($csrf, $csrfRecibido)) {
        $error = 'La solicitud venció o no es válida. Recarga la página e inténtalo nuevamente.';
    } elseif (!$aceptaBorrado || $confirmacion !== CONFIRMATION_TEXT) {
        $error = 'Marca la confirmación y escribe ELIMINAR para continuar.';
    } else {
        $archivoConexion = __DIR__ . '/admin/classes/DbConection.php';

        if (!is_file($archivoConexion)) {
            $error = 'No se encontró el archivo de conexión a la base de datos.';
        } else {
            try {
                require_once $archivoConexion;

                if (!class_exists('DbConection')) {
                    throw new RuntimeException('La clase DbConection no está disponible.');
                }

                $db = new DbConection();
                $pdo = $db->openConect();

                if (!$pdo instanceof PDO) {
                    throw new RuntimeException('No fue posible abrir una conexión válida.');
                }

                $pdo->beginTransaction();

                $verificar = $pdo->prepare(
                    'SELECT id FROM tbl_usuarios WHERE id = :usuario_id LIMIT 1 FOR UPDATE'
                );
                $verificar->execute([':usuario_id' => $usuarioId]);

                if (!$verificar->fetchColumn()) {
                    throw new RuntimeException('La cuenta ya no existe o no pudo ser localizada.');
                }

                eliminarRegistrosRelacionados($pdo, $db->getDbName(), $usuarioId);

                $eliminarCuenta = $pdo->prepare(
                    'DELETE FROM tbl_usuarios WHERE id = :usuario_id'
                );
                $eliminarCuenta->execute([':usuario_id' => $usuarioId]);

                if ($eliminarCuenta->rowCount() !== 1) {
                    throw new RuntimeException('No fue posible eliminar la cuenta solicitada.');
                }

                $pdo->commit();
                $db->closeConect();

                limpiarSesion();

                header('Location: ' . LOGIN_URL . '?cuenta_eliminada=1');
                exit;
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Error al eliminar cuenta: ' . $e->getMessage());
                $error = 'No fue posible eliminar la cuenta. No se realizó ningún cambio. Inténtalo nuevamente o contacta al administrador.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#A41273">
    <meta name="robots" content="noindex,nofollow">
    <title>Eliminar cuenta | Aliada UTS</title>

    <style>
        :root {
            color-scheme: light;
            --primary: #A41273;
            --primary-dark: #75084F;
            --accent: #D81B9C;
            --surface: #FFFFFF;
            --background: #F8F5F7;
            --text: #222432;
            --muted: #626778;
            --border: #E8DDE5;
            --danger: #B4233C;
            --danger-dark: #84172B;
            --danger-soft: #FFF0F2;
            --shadow: 0 22px 60px rgba(84, 17, 63, 0.14);
        }

        * { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            margin: 0;
            min-width: 280px;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            background:
                radial-gradient(circle at top left, rgba(216, 27, 156, .11), transparent 32rem),
                var(--background);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.55;
        }

        .topbar {
            border-bottom: 1px solid rgba(232, 221, 229, .9);
            background: rgba(255, 255, 255, .93);
        }

        .topbar-inner {
            width: min(100% - 30px, 980px);
            min-height: 68px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            font-weight: 850;
            text-decoration: none;
        }

        .brand-mark {
            width: 39px;
            height: 39px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--primary-dark));
            box-shadow: 0 8px 20px rgba(164, 18, 115, .22);
        }

        .login-link {
            min-height: 44px;
            padding: 10px 17px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--primary-dark));
            font-size: .91rem;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 9px 22px rgba(164, 18, 115, .23);
        }

        main {
            width: min(100% - 30px, 720px);
            margin: clamp(24px, 5vw, 52px) auto;
        }

        .card {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 27px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .card-head {
            padding: clamp(25px, 6vw, 45px);
            background: linear-gradient(145deg, #FFFFFF, #FFF8FC);
            text-align: center;
        }

        .danger-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 17px;
            border-radius: 22px;
            display: grid;
            place-items: center;
            color: var(--danger);
            background: var(--danger-soft);
            font-size: 2rem;
            font-weight: 900;
        }

        h1 {
            margin: 0;
            color: var(--primary-dark);
            font-size: clamp(1.75rem, 6vw, 2.7rem);
            line-height: 1.1;
            letter-spacing: -.035em;
        }

        .intro {
            max-width: 560px;
            margin: 16px auto 0;
            color: var(--muted);
        }

        .card-body {
            padding: clamp(22px, 6vw, 42px);
            border-top: 1px solid var(--border);
        }

        .warning {
            margin-bottom: 25px;
            padding: 18px 19px;
            border: 1px solid #F1C5CD;
            border-left: 5px solid var(--danger);
            border-radius: 15px;
            background: var(--danger-soft);
            color: #6C1727;
        }

        .warning strong { display: block; margin-bottom: 5px; }

        .delete-list {
            margin: 0 0 26px;
            padding-left: 22px;
            color: var(--muted);
        }

        .delete-list li + li { margin-top: 8px; }

        .error {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid #F0BBC5;
            border-radius: 13px;
            background: var(--danger-soft);
            color: var(--danger-dark);
            font-weight: 700;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 780;
        }

        .confirmation-input {
            width: 100%;
            min-height: 51px;
            padding: 12px 15px;
            border: 1px solid #CFC3CB;
            border-radius: 13px;
            color: var(--text);
            background: #FFFFFF;
            font: inherit;
            font-weight: 700;
            text-transform: uppercase;
            outline: none;
        }

        .confirmation-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(216, 27, 156, .13);
        }

        .check-wrap {
            margin: 18px 0 25px;
            display: flex;
            align-items: flex-start;
            gap: 11px;
            color: var(--muted);
            font-weight: 550;
            cursor: pointer;
        }

        .check-wrap input {
            width: 19px;
            height: 19px;
            margin: 3px 0 0;
            flex: 0 0 auto;
            accent-color: var(--danger);
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .button {
            min-height: 50px;
            padding: 12px 18px;
            border: 0;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font: inherit;
            font-weight: 850;
            text-decoration: none;
            cursor: pointer;
        }

        .button-cancel {
            border: 1px solid var(--border);
            color: var(--primary-dark);
            background: #FFFFFF;
        }

        .button-delete {
            color: #FFFFFF;
            background: linear-gradient(135deg, #D63850, var(--danger-dark));
            box-shadow: 0 10px 25px rgba(180, 35, 60, .25);
        }

        .button:hover { transform: translateY(-1px); }

        .button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: 3px solid rgba(216, 27, 156, .25);
            outline-offset: 3px;
        }

        footer {
            padding: 24px 15px calc(24px + env(safe-area-inset-bottom));
            color: var(--muted);
            font-size: .86rem;
            text-align: center;
        }

        @media (max-width: 560px) {
            .brand-text { display: none; }
            .card { border-radius: 21px; }
            .actions { grid-template-columns: 1fr; }
            .button-delete { order: 1; }
            .button-cancel { order: 2; }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="login.php">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span class="brand-text">Aliada UTS</span>
            </a>

            <a class="login-link" href="login.php">Volver al inicio</a>
        </div>
    </header>

    <main>
        <section class="card" aria-labelledby="titulo-eliminar">
            <div class="card-head">
                <div class="danger-icon" aria-hidden="true">!</div>
                <h1 id="titulo-eliminar">Eliminar mi cuenta</h1>
                <p class="intro">
                    <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>, esta acción es permanente y no puede deshacerse.
                </p>
            </div>

            <div class="card-body">
                <div class="warning" role="alert">
                    <strong>Se borrará toda tu información.</strong>
                    Al confirmar, se eliminarán tu cuenta y los registros asociados mediante relaciones de la base de datos.
                </div>

                <ul class="delete-list">
                    <li>Datos personales y de contacto.</li>
                    <li>Información de acceso y perfil.</li>
                    <li>Registros asociados a tu usuario mediante claves foráneas.</li>
                    <li>Tu sesión actual se cerrará automáticamente.</li>
                </ul>

                <?php if ($error !== ''): ?>
                    <div class="error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="delete.php" autocomplete="off">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <label for="confirmacion">
                        Para continuar, escribe <strong>ELIMINAR</strong>:
                    </label>
                    <input
                        class="confirmation-input"
                        type="text"
                        id="confirmacion"
                        name="confirmacion"
                        inputmode="text"
                        autocapitalize="characters"
                        spellcheck="false"
                        maxlength="8"
                        required
                    >

                    <label class="check-wrap" for="acepta_borrado">
                        <input
                            type="checkbox"
                            id="acepta_borrado"
                            name="acepta_borrado"
                            value="1"
                            required
                        >
                        <span>Entiendo que la eliminación es definitiva y que no podré recuperar la cuenta ni su información.</span>
                    </label>

                    <div class="actions">
                        <a class="button button-cancel" href="login.php">Conservar mi cuenta</a>
                        <button class="button button-delete" type="submit">Eliminar definitivamente</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer>
        © <?= date('Y'); ?> Aliada UTS · Protección de datos personales
    </footer>
</body>
</html>
