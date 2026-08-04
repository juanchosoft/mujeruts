<?php
/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE SESIÓN
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

date_default_timezone_set('America/Bogota');

$mensaje = '';
$loginInput = '';
$debugError = '';

require_once __DIR__ . '/admin/classes/DbConection.php';

/*
|--------------------------------------------------------------------------
| SI YA HAY SESIÓN ACTIVA, REDIRIGIR SEGÚN EL ROL
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['panel_user']) && !empty($_SESSION['panel_user']['id'])) {
    $rolSesion = strtolower(trim((string)($_SESSION['panel_user']['rol'] ?? '')));

    if ($rolSesion === 'visor') {
        header('Location: visualizador_emergencias.php');
        exit;
    }

    header('Location: dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF PARA EL FORMULARIO
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['login_csrf'])) {
    try {
        $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['login_csrf'] = hash('sha256', session_id() . microtime(true));
    }
}

/*
|--------------------------------------------------------------------------
| FUNCIÓN PARA VALIDAR PASSWORD
|--------------------------------------------------------------------------
*/
function validarPasswordLogin(string $passwordIngresado, ?string $hashGuardado): bool
{
    $hashGuardado = (string)$hashGuardado;

    if ($hashGuardado === '') {
        return false;
    }

    if (password_verify($passwordIngresado, $hashGuardado)) {
        return true;
    }

    /* Compatibilidad temporal con contraseñas antiguas almacenadas en MD5. */
    if (strlen($hashGuardado) === 32 && ctype_xdigit($hashGuardado)) {
        return hash_equals(strtolower($hashGuardado), md5($passwordIngresado));
    }

    return false;
}

/*
|--------------------------------------------------------------------------
| PROCESO DE LOGIN
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = isset($_POST['login']) ? trim((string)$_POST['login']) : '';
    $password   = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $csrfToken  = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';

    if (!hash_equals((string)($_SESSION['login_csrf'] ?? ''), $csrfToken)) {
        $mensaje = 'La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.';
    } elseif ($loginInput === '' || $password === '') {
        $mensaje = 'Por favor, completa el usuario y la contraseña.';
    } else {
        try {
            $db = new DbConection();
            $pdo = $db->openConect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT
                        id,
                        nombre_completo,
                        usuario,
                        correo,
                        password_hash,
                        rol,
                        estado
                    FROM usuarios_panel
                    WHERE (usuario = :login OR correo = :login)
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':login', $loginInput, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $mensaje = 'Usuario o contraseña incorrectos.';
            } else {
                $estado = strtolower(trim((string)($usuario['estado'] ?? '')));

                if ($estado !== 'activo') {
                    $mensaje = 'Tu acceso se encuentra inactivo. Comunícate con la administración del sistema.';
                } elseif (!validarPasswordLogin($password, $usuario['password_hash'] ?? '')) {
                    $mensaje = 'Usuario o contraseña incorrectos.';
                } else {
                    session_regenerate_id(true);
                    unset($_SESSION['login_csrf']);

                    $_SESSION['panel_user'] = [
                        'id'              => (int)$usuario['id'],
                        'nombre_completo' => $usuario['nombre_completo'] ?? '',
                        'usuario'         => $usuario['usuario'] ?? '',
                        'correo'          => $usuario['correo'] ?? '',
                        'rol'             => $usuario['rol'] ?? '',
                        'estado'          => $usuario['estado'] ?? '',
                        'login_time'      => date('Y-m-d H:i:s'),
                        'ip'              => $_SERVER['REMOTE_ADDR'] ?? '',
                        'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ];

                    $sqlUpdate = "UPDATE usuarios_panel
                                  SET ultimo_login = NOW()
                                  WHERE id = :id";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->bindValue(':id', (int)$usuario['id'], PDO::PARAM_INT);
                    $stmtUpdate->execute();

                    $rolUsuario = strtolower(trim((string)($usuario['rol'] ?? '')));

                    if ($rolUsuario === 'visor') {
                        header('Location: visualizador_emergencias.php');
                        exit;
                    }

                    header('Location: dashboard.php');
                    exit;
                }
            }
        } catch (Throwable $e) {
            $mensaje = 'No fue posible iniciar sesión en este momento. Inténtalo nuevamente.';
            $debugError = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include './admin/include/generic_head.php'; ?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#48145f">
    <title>Aliada UTS | Ingreso seguro</title>

    <style>
        :root {
            --uts-purple-950: #24102f;
            --uts-purple-900: #351044;
            --uts-purple-800: #4b1762;
            --uts-purple-700: #672281;
            --uts-purple-600: #7d2a98;
            --uts-pink-600: #c22684;
            --uts-pink-500: #e13d9f;
            --uts-pink-400: #f05cb2;
            --uts-pink-100: #fde8f5;
            --uts-lilac-100: #f4eafa;
            --uts-cream: #fffafc;
            --uts-white: #ffffff;
            --uts-text: #2d1736;
            --uts-muted: #715d79;
            --uts-border: rgba(97, 36, 121, .14);
            --uts-shadow: 0 28px 80px rgba(44, 13, 57, .22);
            --uts-radius-xl: 30px;
            --uts-radius-lg: 20px;
            --uts-radius-md: 15px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            min-height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            font-family: Inter, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--uts-text);
            background: #f9f3fb;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        button,
        input {
            font: inherit;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        .auth-page {
            position: relative;
            isolation: isolate;
            width: 100%;
            height: 100vh;
            height: 100svh;
            height: 100dvh;
            display: grid;
            place-items: center;
            padding:
                max(12px, env(safe-area-inset-top))
                max(12px, env(safe-area-inset-right))
                max(12px, env(safe-area-inset-bottom))
                max(12px, env(safe-area-inset-left));
            overflow: hidden;
            background:
                radial-gradient(circle at 10% 6%, rgba(240, 92, 178, .24), transparent 28%),
                radial-gradient(circle at 92% 92%, rgba(103, 34, 129, .23), transparent 30%),
                linear-gradient(135deg, #fff9fc 0%, #f8eefb 48%, #fdfbff 100%);
        }

        .auth-page::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -3;
            opacity: .7;
            background-image:
                linear-gradient(rgba(103, 34, 129, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(103, 34, 129, .045) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(circle at center, #000 20%, transparent 78%);
        }

        .auth-page::after {
            content: "";
            position: absolute;
            z-index: -2;
            width: min(38vw, 520px);
            aspect-ratio: 1;
            left: -13vw;
            bottom: -18vw;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(225, 61, 159, .28), rgba(103, 34, 129, .20));
            filter: blur(8px);
        }

        .auth-shell {
            width: min(1160px, 100%);
            height: min(720px, calc(100dvh - 24px));
            max-height: calc(100dvh - 24px);
            min-height: 0;
        }

        .auth-card {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 0;
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(380px, .92fr);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .82);
            border-radius: var(--uts-radius-xl);
            background: rgba(255, 255, 255, .94);
            box-shadow: var(--uts-shadow);
            backdrop-filter: blur(18px);
        }

        .auth-visual,
        .auth-form-panel {
            position: relative;
            min-width: 0;
            min-height: 0;
        }

        /* ---------------------------------------------------------------
           PANEL INSTITUCIONAL
        ---------------------------------------------------------------- */
        .auth-visual {
            display: flex;
            align-items: center;
            padding: clamp(30px, 4vw, 58px);
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 18% 13%, rgba(240, 92, 178, .55), transparent 29%),
                radial-gradient(circle at 88% 83%, rgba(255, 255, 255, .13), transparent 27%),
                linear-gradient(148deg, #421252 0%, #672281 48%, #b5257e 100%);
        }

        .auth-visual::before,
        .auth-visual::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .auth-visual::before {
            width: 340px;
            height: 340px;
            top: -170px;
            right: -120px;
            border: 1px solid rgba(255, 255, 255, .18);
            box-shadow:
                0 0 0 44px rgba(255, 255, 255, .035),
                0 0 0 88px rgba(255, 255, 255, .025);
        }

        .auth-visual::after {
            width: 220px;
            height: 220px;
            left: -110px;
            bottom: -105px;
            background: rgba(255, 255, 255, .07);
        }

        .visual-content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 640px;
        }

        .institution-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: clamp(24px, 4vh, 38px);
        }

        .institution-mark {
            flex: 0 0 auto;
            width: 72px;
            height: 72px;
            display: grid;
            place-items: center;
            padding: 9px;
            border-radius: 21px;
            background: #fff;
            border: 1px solid rgba(255, 255, 255, .54);
            box-shadow: 0 16px 34px rgba(31, 7, 41, .23);
        }

        .institution-mark img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .institution-copy strong,
        .institution-copy span {
            display: block;
        }

        .institution-copy strong {
            margin-bottom: 4px;
            font-size: 18px;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -.25px;
        }

        .institution-copy span {
            color: rgba(255, 255, 255, .75);
            font-size: 12px;
            line-height: 1.35;
        }

        .visual-kicker {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            width: fit-content;
            margin-bottom: 16px;
            padding: 8px 13px;
            border: 1px solid rgba(255, 255, 255, .20);
            border-radius: 999px;
            color: #fff;
            background: rgba(255, 255, 255, .10);
            font-size: 11px;
            font-weight: 850;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .visual-kicker i {
            color: #ffd8ee;
        }

        .visual-title {
            max-width: 590px;
            margin: 0 0 16px;
            color: #fff;
            font-size: clamp(34px, 4vw, 56px);
            line-height: .99;
            letter-spacing: -2px;
            font-weight: 950;
        }

        .visual-title span {
            color: #ffd8ee;
        }

        .visual-text {
            max-width: 585px;
            margin: 0;
            color: rgba(255, 255, 255, .82);
            font-size: 15px;
            line-height: 1.58;
        }

        .community-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 24px;
        }

        .community-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 7px 11px;
            border-radius: 12px;
            color: rgba(255, 255, 255, .94);
            background: rgba(255, 255, 255, .105);
            border: 1px solid rgba(255, 255, 255, .14);
            font-size: 11.5px;
            font-weight: 750;
        }

        .community-tag i {
            color: #ffd8ee;
        }

        .support-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 28px;
        }

        .support-item {
            min-width: 0;
            padding: 14px;
            border-radius: 17px;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .14);
        }

        .support-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            margin-bottom: 10px;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, rgba(240, 92, 178, .95), rgba(255, 255, 255, .16));
            box-shadow: 0 10px 24px rgba(40, 8, 53, .16);
        }

        .support-item strong,
        .support-item span {
            display: block;
        }

        .support-item strong {
            margin-bottom: 4px;
            color: #fff;
            font-size: 12px;
            line-height: 1.25;
            font-weight: 850;
        }

        .support-item span {
            color: rgba(255, 255, 255, .68);
            font-size: 10.7px;
            line-height: 1.35;
        }

        /* ---------------------------------------------------------------
           PANEL DEL FORMULARIO
        ---------------------------------------------------------------- */
        .auth-form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(28px, 4vw, 54px);
            overflow: hidden;
            background:
                radial-gradient(circle at 100% 0%, rgba(225, 61, 159, .08), transparent 28%),
                #fff;
        }

        .form-box {
            width: min(100%, 430px);
            min-width: 0;
        }

        .mobile-brand {
            display: none;
        }

        .form-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--uts-pink-600);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .form-eyebrow::before {
            content: "";
            width: 22px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--uts-pink-500), var(--uts-purple-600));
        }

        .form-title {
            margin: 0;
            color: var(--uts-purple-950);
            font-size: clamp(30px, 3vw, 42px);
            line-height: 1.04;
            letter-spacing: -1.35px;
            font-weight: 950;
        }

        .form-subtitle {
            margin: 12px 0 24px;
            color: var(--uts-muted);
            font-size: 13.5px;
            line-height: 1.52;
        }

        .login-form {
            width: 100%;
        }

        .field-group {
            margin-bottom: 15px;
        }

        .field-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 7px;
            color: #3c2145;
            font-size: 12.5px;
            font-weight: 850;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            z-index: 1;
            transform: translateY(-50%);
            color: #a17daa;
            font-size: 15px;
            pointer-events: none;
            transition: color .2s ease;
        }

        .input-control {
            width: 100%;
            height: 54px;
            padding: 0 50px 0 45px;
            border: 1px solid var(--uts-border);
            border-radius: var(--uts-radius-md);
            outline: none;
            color: var(--uts-text);
            background: #fff;
            box-shadow: 0 8px 24px rgba(63, 19, 79, .045);
            font-size: 14px;
            font-weight: 600;
            caret-color: var(--uts-pink-600);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .input-control::placeholder {
            color: #aa96b0;
            font-weight: 500;
        }

        .input-control:hover {
            border-color: rgba(194, 38, 132, .28);
        }

        .input-control:focus {
            border-color: var(--uts-pink-500);
            box-shadow: 0 0 0 4px rgba(225, 61, 159, .11), 0 12px 28px rgba(76, 23, 98, .08);
            transform: translateY(-1px);
        }

        .input-control:focus + .input-state,
        .input-wrap:focus-within .input-icon {
            color: var(--uts-pink-600);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            z-index: 2;
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 12px;
            color: var(--uts-purple-700);
            background: var(--uts-lilac-100);
            cursor: pointer;
            transition: color .2s ease, background .2s ease, transform .2s ease;
        }

        .toggle-password:hover {
            color: #fff;
            background: linear-gradient(135deg, var(--uts-pink-500), var(--uts-purple-600));
        }

        .toggle-password:active {
            transform: translateY(-50%) scale(.96);
        }

        .btn-login {
            position: relative;
            width: 100%;
            height: 55px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 4px;
            overflow: hidden;
            border: 0;
            border-radius: 16px;
            color: #fff;
            background: linear-gradient(135deg, var(--uts-pink-500) 0%, var(--uts-pink-600) 42%, var(--uts-purple-700) 100%);
            box-shadow: 0 16px 35px rgba(160, 30, 117, .29);
            cursor: pointer;
            font-size: 14.5px;
            font-weight: 900;
            letter-spacing: .01em;
            transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
        }

        .btn-login::before {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-120%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .26), transparent);
            transition: transform .65s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 42px rgba(103, 34, 129, .32);
        }

        .btn-login:hover::before {
            transform: translateX(120%);
        }

        .btn-login:focus-visible,
        .toggle-password:focus-visible,
        .input-control:focus-visible {
            outline: 3px solid rgba(225, 61, 159, .20);
            outline-offset: 2px;
        }

        .btn-login.is-loading {
            pointer-events: none;
            opacity: .82;
        }

        .btn-spinner {
            display: none;
            width: 17px;
            height: 17px;
            border: 2px solid rgba(255, 255, 255, .38);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .75s linear infinite;
        }

        .btn-login.is-loading .btn-spinner {
            display: inline-block;
        }

        .btn-login.is-loading .btn-arrow {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .privacy-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 13px;
            border: 1px solid rgba(103, 34, 129, .10);
            border-radius: 15px;
            color: #705d77;
            background: linear-gradient(135deg, rgba(253, 232, 245, .70), rgba(244, 234, 250, .64));
            font-size: 11.6px;
            line-height: 1.42;
        }

        .privacy-note i {
            flex: 0 0 auto;
            margin-top: 2px;
            color: var(--uts-pink-600);
        }

        .message-box {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 13px;
            padding: 11px 13px;
            border: 1px solid rgba(207, 51, 51, .18);
            border-radius: 14px;
            color: #a92525;
            background: rgba(248, 72, 72, .08);
            font-size: 12.2px;
            line-height: 1.4;
            font-weight: 750;
        }

        .message-box i {
            flex: 0 0 auto;
            margin-top: 2px;
        }

        .debug-box {
            margin-top: 8px;
            padding: 9px 11px;
            border: 1px solid rgba(207, 51, 51, .18);
            border-radius: 12px;
            color: #982121;
            background: rgba(248, 72, 72, .07);
            font-size: 10.5px;
            line-height: 1.35;
            word-break: break-word;
        }

        .form-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 16px;
            color: #927e99;
            font-size: 10.8px;
            text-align: center;
        }

        .form-footer i {
            color: var(--uts-pink-500);
        }

        /* ---------------------------------------------------------------
           PORTÁTILES Y TABLETAS
        ---------------------------------------------------------------- */
        @media (max-width: 980px) {
            .auth-shell {
                width: min(780px, 100%);
                height: calc(100dvh - 24px);
            }

            .auth-card {
                grid-template-columns: 1fr;
                grid-template-rows: 205px minmax(0, 1fr);
            }

            .auth-visual {
                padding: 22px 30px;
            }

            .visual-content {
                display: grid;
                grid-template-columns: auto 1fr;
                column-gap: 18px;
                align-items: center;
                max-width: none;
            }

            .institution-row {
                grid-row: 1 / span 3;
                align-self: center;
                margin: 0;
            }

            .institution-copy {
                display: none;
            }

            .institution-mark {
                width: 92px;
                height: 92px;
                border-radius: 25px;
            }

            .visual-kicker {
                margin-bottom: 8px;
                padding: 6px 10px;
                font-size: 9px;
            }

            .visual-title {
                margin-bottom: 7px;
                font-size: clamp(27px, 5vw, 38px);
                letter-spacing: -1.2px;
            }

            .visual-text {
                display: -webkit-box;
                max-width: 610px;
                overflow: hidden;
                font-size: 12.3px;
                line-height: 1.38;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .community-tags,
            .support-grid {
                display: none;
            }

            .auth-form-panel {
                padding: 26px 38px;
            }

            .form-box {
                width: min(100%, 500px);
            }

            .form-title {
                font-size: 34px;
            }

            .form-subtitle {
                margin-bottom: 19px;
            }
        }

        /* ---------------------------------------------------------------
           CELULARES
        ---------------------------------------------------------------- */
        @media (max-width: 640px) {
            .auth-page {
                padding:
                    max(8px, env(safe-area-inset-top))
                    max(8px, env(safe-area-inset-right))
                    max(8px, env(safe-area-inset-bottom))
                    max(8px, env(safe-area-inset-left));
            }

            .auth-shell {
                width: 100%;
                height: calc(100dvh - 16px);
                max-height: calc(100dvh - 16px);
            }

            .auth-card {
                grid-template-rows: 148px minmax(0, 1fr);
                border-radius: 23px;
            }

            .auth-visual {
                padding: 14px 18px;
            }

            .visual-content {
                grid-template-columns: 66px 1fr;
                column-gap: 13px;
            }

            .institution-mark {
                width: 66px;
                height: 66px;
                padding: 7px;
                border-radius: 18px;
            }

            .visual-kicker {
                margin-bottom: 5px;
                padding: 5px 8px;
                font-size: 7.8px;
                letter-spacing: .04em;
            }

            .visual-title {
                margin-bottom: 5px;
                font-size: clamp(21px, 6.1vw, 27px);
                line-height: 1.02;
                letter-spacing: -.85px;
            }

            .visual-text {
                font-size: 10.7px;
                line-height: 1.28;
                -webkit-line-clamp: 2;
            }

            .auth-form-panel {
                align-items: center;
                padding: 18px 19px 16px;
            }

            .form-box {
                width: 100%;
            }

            .form-eyebrow {
                margin-bottom: 7px;
                font-size: 9px;
            }

            .form-title {
                font-size: clamp(26px, 8vw, 32px);
                letter-spacing: -1px;
            }

            .form-subtitle {
                margin: 7px 0 15px;
                font-size: 11.5px;
                line-height: 1.38;
            }

            .field-group {
                margin-bottom: 11px;
            }

            .field-label {
                margin-bottom: 5px;
                font-size: 11.4px;
            }

            .input-control {
                height: 47px;
                padding-left: 42px;
                border-radius: 14px;
                font-size: 13px;
            }

            .input-icon {
                left: 15px;
                font-size: 13px;
            }

            .toggle-password {
                width: 34px;
                height: 34px;
                right: 7px;
                border-radius: 11px;
            }

            .btn-login {
                height: 48px;
                border-radius: 14px;
                font-size: 13.2px;
            }

            .privacy-note {
                margin-top: 11px;
                padding: 9px 10px;
                border-radius: 13px;
                font-size: 10.5px;
                line-height: 1.32;
            }

            .message-box {
                margin-top: 9px;
                padding: 9px 10px;
                border-radius: 13px;
                font-size: 11px;
            }

            .form-footer {
                margin-top: 10px;
                font-size: 9.8px;
            }
        }

        /* ---------------------------------------------------------------
           PANTALLAS CON POCA ALTURA
        ---------------------------------------------------------------- */
        @media (min-width: 981px) and (max-height: 760px) {
            .auth-shell {
                height: calc(100dvh - 20px);
                max-height: calc(100dvh - 20px);
            }

            .auth-visual {
                padding: 28px 40px;
            }

            .institution-row {
                margin-bottom: 22px;
            }

            .institution-mark {
                width: 62px;
                height: 62px;
            }

            .visual-title {
                font-size: 42px;
            }

            .visual-text {
                font-size: 13px;
            }

            .community-tags {
                margin-top: 17px;
            }

            .support-grid {
                margin-top: 18px;
            }

            .support-item {
                padding: 11px;
            }

            .support-item span {
                display: none;
            }

            .support-icon {
                width: 34px;
                height: 34px;
                margin-bottom: 8px;
            }

            .auth-form-panel {
                padding: 28px 42px;
            }

            .form-title {
                font-size: 35px;
            }

            .form-subtitle {
                margin: 8px 0 16px;
            }

            .field-group {
                margin-bottom: 11px;
            }

            .input-control {
                height: 49px;
            }

            .btn-login {
                height: 50px;
            }

            .privacy-note {
                margin-top: 11px;
                padding: 9px 11px;
            }

            .form-footer {
                margin-top: 10px;
            }
        }

        @media (max-width: 640px) and (max-height: 700px) {
            .auth-card {
                grid-template-rows: 112px minmax(0, 1fr);
            }

            .auth-visual {
                padding: 10px 16px;
            }

            .visual-content {
                grid-template-columns: 54px 1fr;
                column-gap: 11px;
            }

            .institution-mark {
                width: 54px;
                height: 54px;
                border-radius: 15px;
            }

            .visual-kicker {
                display: none;
            }

            .visual-title {
                margin: 0 0 4px;
                font-size: 20px;
            }

            .visual-text {
                font-size: 9.8px;
                -webkit-line-clamp: 2;
            }

            .auth-form-panel {
                padding: 12px 16px;
            }

            .form-eyebrow {
                display: none;
            }

            .form-title {
                font-size: 25px;
            }

            .form-subtitle {
                margin: 5px 0 10px;
                font-size: 10.5px;
                -webkit-line-clamp: 1;
                display: -webkit-box;
                overflow: hidden;
                -webkit-box-orient: vertical;
            }

            .field-group {
                margin-bottom: 8px;
            }

            .field-label {
                margin-bottom: 4px;
                font-size: 10.7px;
            }

            .input-control {
                height: 42px;
            }

            .toggle-password {
                width: 31px;
                height: 31px;
            }

            .btn-login {
                height: 43px;
            }

            .privacy-note {
                display: none;
            }

            .form-footer {
                display: none;
            }

            .message-box {
                margin-top: 7px;
                padding: 7px 9px;
                font-size: 10.3px;
            }
        }

        /* Teléfonos en orientación horizontal. */
        @media (max-height: 500px) and (orientation: landscape) {
            .auth-shell {
                width: min(980px, 100%);
                height: calc(100dvh - 12px);
                max-height: calc(100dvh - 12px);
            }

            .auth-card {
                grid-template-columns: .82fr 1.18fr;
                grid-template-rows: 1fr;
                border-radius: 20px;
            }

            .auth-visual {
                padding: 18px;
            }

            .visual-content {
                display: block;
            }

            .institution-row {
                margin-bottom: 10px;
            }

            .institution-mark {
                width: 54px;
                height: 54px;
                border-radius: 15px;
            }

            .institution-copy strong {
                font-size: 14px;
            }

            .institution-copy span,
            .visual-kicker,
            .visual-text,
            .community-tags,
            .support-grid {
                display: none;
            }

            .visual-title {
                margin: 0;
                font-size: clamp(25px, 4vw, 36px);
                letter-spacing: -1px;
            }

            .auth-form-panel {
                padding: 12px 24px;
            }

            .form-eyebrow,
            .privacy-note,
            .form-footer {
                display: none;
            }

            .form-title {
                font-size: 26px;
            }

            .form-subtitle {
                margin: 4px 0 8px;
                font-size: 10.5px;
            }

            .field-group {
                margin-bottom: 7px;
            }

            .field-label {
                margin-bottom: 3px;
                font-size: 10.5px;
            }

            .input-control {
                height: 40px;
            }

            .toggle-password {
                width: 30px;
                height: 30px;
            }

            .btn-login {
                height: 41px;
            }

            .message-box {
                margin-top: 6px;
                padding: 6px 8px;
                font-size: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
<main class="auth-page">
    <div class="auth-shell">
        <section class="auth-card" aria-label="Ingreso a Aliada UTS">

            <aside class="auth-visual" aria-label="Información de Aliada UTS">
                <div class="visual-content">
                    <div class="institution-row">
                        <div class="institution-mark">
                            <img src="assets/img/utslog.png" alt="Logo de Aliada UTS">
                        </div>
                        <div class="institution-copy">
                            <strong>Aliada UTS</strong>
                            <span>Universitaria Tecnológica de Santander</span>                        </div>
                    </div>

                    <div class="visual-kicker">
                        <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                        Bienestar, orientación y acompañamiento
                    </div>

                    <h1 class="visual-title">
                        Un espacio seguro para la <span>comunidad UTS</span>
                    </h1>

                    <p class="visual-text">
                        Plataforma institucional dirigida a estudiantes y personal de la UTS, con atención prioritaria
                        para mujeres y personas de los sectores sociales LGBTIQ+, en un entorno cercano, respetuoso y confidencial.
                    </p>

                    <div class="community-tags" aria-label="Población atendida">
                        <span class="community-tag">
                            <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                            Estudiantes
                        </span>
                        <span class="community-tag">
                            <i class="fa-solid fa-id-badge" aria-hidden="true"></i>
                            Docentes y personal
                        </span>
                        <span class="community-tag">
                            <i class="fa-solid fa-venus" aria-hidden="true"></i>
                            Mujeres
                        </span>
                        <span class="community-tag">
                            <i class="fa-solid fa-rainbow" aria-hidden="true"></i>
                            Comunidad LGBTIQ+
                        </span>
                    </div>

                    <div class="support-grid" aria-label="Características de la plataforma">
                        <div class="support-item">
                            <div class="support-icon">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            </div>
                            <strong>Acceso protegido</strong>
                            <span>Información reservada para personal autorizado.</span>
                        </div>

                        <div class="support-item">
                            <div class="support-icon">
                                <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
                            </div>
                            <strong>Atención humana</strong>
                            <span>Acompañamiento respetuoso, cercano e incluyente.</span>
                        </div>

                        <div class="support-item">
                            <div class="support-icon">
                                <i class="fa-solid fa-route" aria-hidden="true"></i>
                            </div>
                            <strong>Rutas claras</strong>
                            <span>Orientación organizada para acceder al apoyo institucional.</span>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="auth-form-panel">
                <div class="form-box">
                    <div class="form-eyebrow">Ingreso institucional</div>

                    <h2 class="form-title">Bienvenida a Aliada UTS</h2>

                    <p class="form-subtitle">
                        Ingresa con tu usuario o correo institucional para acceder al panel de atención y acompañamiento.
                    </p>

                    <form
                        action=""
                        method="POST"
                        class="login-form"
                        id="loginForm"
                        autocomplete="on"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars((string)($_SESSION['login_csrf'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >

                        <div class="field-group">
                            <label class="field-label" for="login">
                                Usuario o correo electrónico
                            </label>

                            <div class="input-wrap">
                                <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                                <input
                                    type="text"
                                    name="login"
                                    id="login"
                                    class="input-control"
                                    placeholder="Escribe tu usuario o correo"
                                    value="<?php echo htmlspecialchars($loginInput, ENT_QUOTES, 'UTF-8'); ?>"
                                    autocomplete="username"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    maxlength="150"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label" for="passwordInput">
                                Contraseña
                            </label>

                            <div class="input-wrap">
                                <i class="fa-solid fa-key input-icon" aria-hidden="true"></i>
                                <input
                                    type="password"
                                    name="password"
                                    id="passwordInput"
                                    class="input-control"
                                    placeholder="Ingresa tu contraseña"
                                    autocomplete="current-password"
                                    maxlength="255"
                                    required
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    id="togglePasswordBtn"
                                    aria-label="Mostrar contraseña"
                                    aria-pressed="false"
                                >
                                    <i class="fa-regular fa-eye" id="eyeIcon" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-login" id="btnLogin">
                            <span class="btn-spinner" aria-hidden="true"></span>
                            <span class="btn-text">Ingresar a Aliada UTS</span>
                            <i class="fa-solid fa-arrow-right btn-arrow" aria-hidden="true"></i>
                        </button>

                        <div class="privacy-note">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            <span>
                                Tu acceso y la información registrada en la plataforma son tratados de forma reservada
                                conforme a los protocolos institucionales.
                            </span>
                        </div>

                        <?php if ($mensaje !== ''): ?>
                            <div class="message-box" role="alert" aria-live="assertive">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php
                        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);
                        if ($debugError !== '' && $isLocal):
                        ?>
                            <div class="debug-box">
                                <?php echo htmlspecialchars($debugError, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-footer">
                            <i class="fa-solid fa-heart" aria-hidden="true"></i>
                            <span>Una plataforma de bienestar para la comunidad UTS</span>
                        </div>
                    </form>
                </div>
            </section>

        </section>
    </div>
</main>

<?php include './admin/include/gerenic_script.php'; ?>

<script>
    (function () {
        'use strict';

        const form = document.getElementById('loginForm');
        const loginInput = document.getElementById('login');
        const passwordInput = document.getElementById('passwordInput');
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const eyeIcon = document.getElementById('eyeIcon');
        const btnLogin = document.getElementById('btnLogin');
        const btnText = btnLogin ? btnLogin.querySelector('.btn-text') : null;

        if (togglePasswordBtn && passwordInput && eyeIcon) {
            togglePasswordBtn.addEventListener('click', function () {
                const willShow = passwordInput.type === 'password';

                passwordInput.type = willShow ? 'text' : 'password';
                togglePasswordBtn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
                togglePasswordBtn.setAttribute(
                    'aria-label',
                    willShow ? 'Ocultar contraseña' : 'Mostrar contraseña'
                );

                eyeIcon.classList.toggle('fa-eye', !willShow);
                eyeIcon.classList.toggle('fa-eye-slash', willShow);

                passwordInput.focus({ preventScroll: true });
            });
        }

        if (form && btnLogin && btnText) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                btnLogin.classList.add('is-loading');
                btnLogin.setAttribute('aria-busy', 'true');
                btnLogin.disabled = true;
                btnText.textContent = 'Validando acceso...';
            });
        }

        /* Evita que el teclado móvil desplace la tarjeta y deje contenido oculto. */
        if (window.visualViewport) {
            const authPage = document.querySelector('.auth-page');

            const updateViewport = function () {
                if (!authPage) return;
                authPage.style.height = Math.round(window.visualViewport.height) + 'px';
            };

            window.visualViewport.addEventListener('resize', updateViewport);
            window.visualViewport.addEventListener('scroll', updateViewport);
        }

        /* En un reintento de acceso, conserva el foco en el campo más útil. */
        if (loginInput && loginInput.value.trim() !== '' && passwordInput) {
            passwordInput.focus({ preventScroll: true });
        }
    })();
</script>
</body>
</html>
