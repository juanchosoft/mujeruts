<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = '';

include './admin/classes/SessionData.php';
include './admin/classes/DbConection.php';
include './admin/classes/Util.php';
include './admin/classes/Usuario.php';
include './admin/classes/Departamento.php';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Limpia texto para sesión
 */
function limpiarTextoSesion($valor)
{
    return trim((string)($valor ?? ''));
}

/**
 * Guarda en sesión los datos principales del usuario
 */
function guardarSesionUsuario(array $usuario, array $permisos = [])
{
    $nombre   = limpiarTextoSesion($usuario['nombre'] ?? '');
    $apellido = limpiarTextoSesion($usuario['ape'] ?? '');
    $nickname = limpiarTextoSesion($usuario['nickname'] ?? '');
    $correo   = limpiarTextoSesion($usuario['correo'] ?? '');

    if ($correo === '' && filter_var($nickname, FILTER_VALIDATE_EMAIL)) {
        $correo = $nickname;
    }

    $nombreCompleto = trim($nombre . ' ' . $apellido);

    $_SESSION['session_user'] = $usuario;
    $_SESSION['session_user']['permisos'] = $permisos;

    $_SESSION['id']                = (int)($usuario['id'] ?? 0);
    $_SESSION['nombre']            = $nombre;
    $_SESSION['apellido']          = $apellido;
    $_SESSION['nombre_completo']   = $nombreCompleto !== '' ? $nombreCompleto : ($nombre !== '' ? $nombre : $nickname);
    $_SESSION['nickname']          = $nickname;
    $_SESSION['correo']            = $correo;
    $_SESSION['session_user_name'] = $_SESSION['nombre_completo'];

    $_SESSION['usuario_nombre'] = $_SESSION['nombre_completo'];
    $_SESSION['usuario']        = $nickname;
}

$rqst = $_REQUEST;
$op = $rqst['op'] ?? '';

if ($op === 'pms_usrlogin') {
    $nickname = trim($rqst['nickname'] ?? '');
    $hashpass = $rqst['hashpass'] ?? '';

    if ($nickname !== '' && $hashpass !== '') {
        $hashpass = md5($hashpass);

        $arr = [
            'nickname' => $nickname,
            'hashpass' => $hashpass
        ];

        $res = Usuario::login($arr);
        $isvalid = $res['output']['valid'] ?? false;

        if ($isvalid) {
            $usuarioData = $res['output']['response'][0] ?? [];
            $permisos    = $res['output']['permisos'] ?? [];

            guardarSesionUsuario($usuarioData, $permisos);

            $userType = SessionData::getUserType();
            $isSecretario = ($userType === Util::Secretario_Despacho() || $userType === Util::Auxiliar());
            $isAlcalde    = ($userType === Util::Alcalde() || $userType === Util::Auxiliar_Alcalde());

            if ($isAlcalde) {
                header('Location: departamentos.php?pilar=' . Util::getIdentificadorPilarPrincipal());
            } elseif ($isSecretario) {
                header('Location: panel_usuaria.php');
            } else {
                header('Location: panel_usuaria.php');
            }

            exit;
        } else {
            $mensaje = $res['output']['response']['content'] ?? 'Credenciales inválidas.';
        }
    } else {
        $mensaje = 'Todos los campos son obligatorios.';
    }
}

$registroErrors  = $_SESSION['registro_errors'] ?? [];
$registroOld     = $_SESSION['registro_old'] ?? [];
$registroSuccess = $_SESSION['registro_success'] ?? '';

unset($_SESSION['registro_errors'], $_SESSION['registro_old'], $_SESSION['registro_success']);

function oldValue($key, $default = '')
{
    global $registroOld;
    return htmlspecialchars($registroOld[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function hasError($key)
{
    global $registroErrors;
    return isset($registroErrors[$key]);
}

function errorText($key)
{
    global $registroErrors;
    return htmlspecialchars($registroErrors[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Aliada UTS | Ingreso</title>

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">

    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/fonts/fontawesome/css/fontawesome-all.css">

    <style>
        :root {
            --aliada-violeta: #7B1FA2;
            --aliada-violeta-dark: #4A0E63;
            --aliada-fucsia: #D81B9C;
            --aliada-fucsia-dark: #A50F78;
            --aliada-rosa: #FF4FB8;
            --aliada-rosa-soft: #FFE4F3;
            --aliada-bg: #FFF5FB;
            --aliada-text: #2B1237;
            --aliada-muted: #80658D;
            --aliada-border: rgba(216, 27, 156, .18);
            --aliada-gradient: linear-gradient(135deg, #FF4FB8 0%, #D81B9C 48%, #7B1FA2 100%);
            --aliada-shadow: 0 20px 55px rgba(123, 31, 162, .24);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--aliada-text);
            background:
                radial-gradient(circle at 15% 0%, rgba(255, 79, 184, .30), transparent 30%),
                radial-gradient(circle at 95% 15%, rgba(123, 31, 162, .24), transparent 34%),
                linear-gradient(180deg, #FFF5FB 0%, #FDF2FF 52%, #FFFFFF 100%);
        }

        body {
            overflow-x: hidden;
        }

        .mobile-login-page {
            width: 100%;
            min-height: 100dvh;
            padding: 18px 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            isolation: isolate;
        }

        .mobile-login-page::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(216, 27, 156, .055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(123, 31, 162, .055) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(circle at top, black, transparent 70%);
            z-index: -2;
        }

        .mobile-card {
            width: 100%;
            max-width: 430px;
            min-height: auto;
            background: rgba(255, 255, 255, .92);
            border: 1px solid rgba(255, 255, 255, .80);
            border-radius: 30px;
            box-shadow: var(--aliada-shadow);
            overflow: hidden;
            backdrop-filter: blur(18px);
        }

        .mobile-hero {
            position: relative;
            padding: 26px 22px 24px;
            background:
                radial-gradient(circle at 15% 10%, rgba(255, 255, 255, .26), transparent 26%),
                radial-gradient(circle at 90% 85%, rgba(255, 79, 184, .34), transparent 32%),
                var(--aliada-gradient);
            color: #fff;
            overflow: hidden;
        }

        .mobile-hero::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            right: -70px;
            top: -70px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
        }

        .logo-box {
            width: 132px;
            height: 66px;
            padding: 10px 14px;
            border-radius: 22px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 34px rgba(74, 14, 99, .24);
            position: relative;
            z-index: 1;
            margin-bottom: 20px;
        }

        .logo-box img {
            display: block;
            max-width: 100%;
            max-height: 46px;
            object-fit: contain;
        }

        .mobile-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .17);
            border: 1px solid rgba(255, 255, 255, .22);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
            margin-bottom: 12px;
        }

        .mobile-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #FFD6EF;
            box-shadow: 0 0 0 5px rgba(255, 255, 255, .12);
        }

        .mobile-title {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: 27px;
            line-height: 1.04;
            letter-spacing: -1px;
            font-weight: 950;
        }

        .mobile-title span {
            color: #FFD6EF;
        }

        .mobile-subtitle {
            position: relative;
            z-index: 1;
            margin: 12px 0 0;
            color: rgba(255, 255, 255, .84);
            font-size: 13px;
            line-height: 1.45;
        }

        .mobile-form-area {
            padding: 22px;
        }

        .form-panel {
            display: none;
        }

        .form-panel.active {
            display: block;
        }

        .panel-head {
            margin-bottom: 18px;
        }

        .panel-head h2 {
            margin: 0;
            color: var(--aliada-text);
            font-size: 23px;
            font-weight: 950;
            letter-spacing: -.7px;
        }

        .panel-head p {
            margin: 8px 0 0;
            color: var(--aliada-muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .field {
            margin-bottom: 14px;
        }

        .label {
            display: block;
            margin-bottom: 7px;
            color: #3B164D;
            font-size: 12.5px;
            font-weight: 850;
        }

        .input,
        .select {
            width: 100%;
            height: 50px;
            border: 1px solid var(--aliada-border);
            border-radius: 17px;
            background: #fff;
            color: var(--aliada-text);
            padding: 0 15px;
            font-size: 14px;
            outline: none;
            box-shadow: 0 10px 24px rgba(123, 31, 162, .045);
            transition: all .18s ease;
        }

        .select {
            appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, #A50F78 50%),
                linear-gradient(135deg, #A50F78 50%, transparent 50%);
            background-position:
                calc(100% - 20px) 22px,
                calc(100% - 14px) 22px;
            background-size: 6px 6px;
            background-repeat: no-repeat;
        }

        .input::placeholder {
            color: #B89BC2;
        }

        .input:focus,
        .select:focus {
            border-color: var(--aliada-fucsia);
            box-shadow: 0 0 0 4px rgba(216, 27, 156, .11);
        }

        .input-error {
            border-color: #EF4444 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, .10) !important;
        }

        .error-message {
            display: block;
            margin-top: 5px;
            color: #B42318;
            font-size: 11.5px;
            font-weight: 700;
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .input {
            padding-right: 54px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            width: 38px;
            height: 38px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #FCE7F3, #F3E8FF);
            color: var(--aliada-fucsia);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 16px;
            font-size: 12.2px;
        }

        .check-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--aliada-muted);
            font-weight: 700;
        }

        .check-wrap input {
            width: 16px;
            height: 16px;
            accent-color: var(--aliada-fucsia);
        }

        .inline-link {
            color: var(--aliada-fucsia-dark);
            font-weight: 850;
            text-decoration: none;
            white-space: nowrap;
        }

        .account-management {
            margin: 14px 0 2px;
            padding-top: 14px;
            border-top: 1px solid rgba(148, 163, 184, .20);
            text-align: center;
        }

        .delete-account-link {
            min-height: 40px;
            padding: 8px 14px;
            border: 1px solid rgba(190, 24, 93, .18);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #A61B4D;
            background: rgba(255, 241, 246, .72);
            font-size: 12.5px;
            font-weight: 850;
            line-height: 1.3;
            text-decoration: none;
            transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
        }

        .delete-account-link:hover {
            color: #86133D;
            background: #FFF1F6;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(166, 27, 77, .10);
        }

        .delete-account-link:focus-visible {
            outline: 3px solid rgba(216, 27, 156, .22);
            outline-offset: 3px;
        }

        .account-management small {
            display: block;
            margin-top: 7px;
            color: var(--aliada-muted);
            font-size: 10.8px;
            line-height: 1.35;
        }

        .btn-main {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 18px;
            background: var(--aliada-gradient);
            color: #fff;
            font-size: 14.5px;
            font-weight: 950;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            box-shadow: 0 17px 38px rgba(216, 27, 156, .31);
        }

        .btn-outline-mobile {
            width: 100%;
            height: 48px;
            border-radius: 17px;
            border: 1px solid rgba(216, 27, 156, .22);
            background: rgba(252, 231, 243, .58);
            color: var(--aliada-fucsia-dark);
            font-weight: 900;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
        }

        .switch-text {
            margin: 16px 0 0;
            text-align: center;
            color: var(--aliada-muted);
            font-size: 13px;
            font-weight: 650;
        }

        .switch-text a {
            color: var(--aliada-fucsia-dark);
            font-weight: 950;
            text-decoration: none;
        }

        .note-box {
            margin-top: 15px;
            padding: 12px 13px;
            border-radius: 17px;
            background: linear-gradient(135deg, rgba(255, 79, 184, .13), rgba(123, 31, 162, .08));
            border: 1px solid rgba(216, 27, 156, .15);
            color: #745580;
            font-size: 12px;
            line-height: 1.38;
            text-align: center;
        }

        .register-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }

        .general-error-box {
            padding: 11px 13px;
            margin-bottom: 14px;
            border-radius: 15px;
            background: rgba(239, 68, 68, .10);
            border: 1px solid rgba(239, 68, 68, .20);
            color: #B42318;
            font-size: 12.5px;
            font-weight: 800;
        }

        .register-scroll {
            max-height: 62dvh;
            overflow-y: auto;
            padding-right: 2px;
        }

        .register-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .register-scroll::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #FF4FB8, #D81B9C, #7B1FA2);
            border-radius: 999px;
        }



        /* =========================================================
           FLUJO DE BIENVENIDA INICIAL - ALIADA UTS
        ========================================================= */
        .aliada-onboarding-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background:
                radial-gradient(circle at 12% 12%, rgba(255, 79, 184, .28), transparent 30%),
                radial-gradient(circle at 88% 18%, rgba(123, 31, 162, .26), transparent 34%),
                rgba(43, 18, 55, .64);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .28s ease, visibility .28s ease;
        }

        .aliada-onboarding-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .aliada-onboarding-card {
            width: 100%;
            max-width: 470px;
            height: min(920px, calc(100dvh - 28px));
            max-height: calc(100dvh - 28px);

            display: flex;
            flex-direction: column;

            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .82);
            border-radius: 30px;
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 28px 80px rgba(45, 12, 63, .38);
            transform: translateY(18px) scale(.975);
            transition: transform .32s cubic-bezier(.2, .8, .2, 1);
        }

        .aliada-onboarding-overlay.active .aliada-onboarding-card {
            transform: translateY(0) scale(1);
        }

        .aliada-onboarding-top {
            position: relative;
            flex: 0 0 auto;
            padding: 22px 22px 20px;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 12% 10%, rgba(255, 255, 255, .30), transparent 27%),
                radial-gradient(circle at 92% 90%, rgba(255, 79, 184, .38), transparent 35%),
                var(--aliada-gradient);
        }

        .aliada-onboarding-top::before,
        .aliada-onboarding-top::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            pointer-events: none;
        }

        .aliada-onboarding-top::before {
            width: 170px;
            height: 170px;
            top: -90px;
            right: -55px;
        }

        .aliada-onboarding-top::after {
            width: 85px;
            height: 85px;
            left: -38px;
            bottom: -35px;
        }

        .aliada-onboarding-top-inner {
            position: relative;
            z-index: 1;
        }

        .aliada-onboarding-brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .aliada-onboarding-logo {
            width: 132px;
            height: 64px;
            padding: 9px 13px;
            border-radius: 21px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 34px rgba(74, 14, 99, .24);
        }

        .aliada-onboarding-logo img {
            display: block;
            max-width: 100%;
            max-height: 46px;
            object-fit: contain;
        }

        .aliada-onboarding-shield {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: rgba(255, 255, 255, .17);
            border: 1px solid rgba(255, 255, 255, .25);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
            font-size: 22px;
        }

        .aliada-onboarding-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .17);
            border: 1px solid rgba(255, 255, 255, .23);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 11px;
        }

        .aliada-onboarding-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #FFD6EF;
            box-shadow: 0 0 0 5px rgba(255, 255, 255, .11);
        }

        .aliada-onboarding-title {
            margin: 0;
            max-width: 390px;
            font-size: 27px;
            line-height: 1.06;
            font-weight: 950;
            letter-spacing: -1px;
        }

        .aliada-onboarding-title span {
            color: #FFD6EF;
        }

        .aliada-onboarding-subtitle {
            margin: 10px 0 0;
            max-width: 410px;
            color: rgba(255, 255, 255, .88);
            font-size: 12.8px;
            line-height: 1.5;
        }

        .aliada-onboarding-body {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;

            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;

            padding: 18px 22px 0;
            scrollbar-width: thin;
            scrollbar-color: var(--aliada-fucsia) transparent;
        }

        .aliada-onboarding-body::-webkit-scrollbar {
            width: 5px;
        }

        .aliada-onboarding-body::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: var(--aliada-gradient);
        }

        .aliada-onboarding-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 17px;
        }

        .aliada-onboarding-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: rgba(123, 31, 162, .17);
            transition: width .24s ease, background .24s ease;
        }

        .aliada-onboarding-dot.active {
            width: 31px;
            background: var(--aliada-gradient);
        }

        .aliada-onboarding-step {
            display: none;
        }

        .aliada-onboarding-step.active {
            display: block;
            animation: aliadaStepIn .30s ease;
        }

        @keyframes aliadaStepIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .aliada-step-head {
            margin-bottom: 14px;
        }

        .aliada-step-head h3 {
            margin: 0;
            color: var(--aliada-text);
            font-size: 23px;
            line-height: 1.12;
            font-weight: 950;
            letter-spacing: -.7px;
        }

        .aliada-step-head p {
            margin: 8px 0 0;
            color: var(--aliada-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .aliada-highlight-box {
            margin-top: 14px;
            padding: 15px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 79, 184, .12), rgba(123, 31, 162, .08));
            border: 1px solid rgba(216, 27, 156, .15);
            color: #6D4C7D;
            font-size: 13px;
            line-height: 1.55;
        }

        .aliada-highlight-box strong {
            display: block;
            margin-bottom: 5px;
            color: #5B1B78;
            font-size: 13.4px;
        }

        .aliada-feature-list {
            display: grid;
            gap: 9px;
            margin-top: 14px;
        }

        .aliada-feature-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 12px;
            border: 1px solid rgba(216, 27, 156, .12);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(123, 31, 162, .05);
        }

        .aliada-feature-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--aliada-fucsia-dark);
            background: linear-gradient(135deg, #FCE7F3, #F3E8FF);
            font-size: 14px;
        }

        .aliada-feature-text {
            padding-top: 1px;
            color: var(--aliada-text);
            font-size: 12.8px;
            line-height: 1.42;
            font-weight: 700;
        }

        .aliada-disclaimer {
            margin-top: 13px;
            padding: 13px 14px;
            border-radius: 16px;
            background: rgba(255, 244, 250, .92);
            border: 1px solid rgba(216, 27, 156, .14);
            color: #765487;
            font-size: 11.8px;
            line-height: 1.5;
        }

        .aliada-risk-box {
            margin-top: 13px;
            padding: 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(123, 31, 162, .07), rgba(255, 79, 184, .08));
            border: 1px solid rgba(123, 31, 162, .12);
        }

        .aliada-risk-box > strong {
            display: block;
            margin-bottom: 10px;
            color: #4A0E63;
            font-size: 13px;
        }

        .aliada-risk-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .aliada-risk-chip {
            padding: 10px 11px;
            border-radius: 14px;
            font-size: 11.7px;
            font-weight: 800;
            line-height: 1.35;
            border: 1px solid rgba(255, 255, 255, .7);
        }

        .aliada-risk-chip.green {
            color: #166534;
            background: rgba(34, 197, 94, .11);
        }

        .aliada-risk-chip.yellow {
            color: #92400E;
            background: rgba(250, 204, 21, .16);
        }

        .aliada-risk-chip.orange {
            color: #9A3412;
            background: rgba(249, 115, 22, .13);
        }

        .aliada-risk-chip.red {
            color: #991B1B;
            background: rgba(239, 68, 68, .12);
        }

        .aliada-consent-list {
            display: grid;
            gap: 9px;
            margin-top: 14px;
        }

        .aliada-check-card {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 13px;
            border: 1px solid rgba(216, 27, 156, .12);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(123, 31, 162, .05);
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease, transform .18s ease;
        }

        .aliada-check-card:hover {
            transform: translateY(-1px);
            border-color: rgba(216, 27, 156, .28);
            background: #FFF9FD;
        }

        .aliada-check-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 2px 0 0;
            flex: 0 0 18px;
            accent-color: var(--aliada-fucsia);
        }

        .aliada-check-card span {
            color: var(--aliada-text);
            font-size: 12.5px;
            line-height: 1.45;
            font-weight: 700;
        }

        .aliada-onboarding-actions {
            position: sticky;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 20;

            display: flex;
            align-items: center;
            gap: 10px;

            margin: 18px -22px 0;
            padding:
                18px
                22px
                calc(18px + env(safe-area-inset-bottom));

            background:
                linear-gradient(
                    180deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, .96) 24%,
                    #FFFFFF 48%,
                    #FFFFFF 100%
                );

            border-bottom-left-radius: 28px;
            border-bottom-right-radius: 28px;

            box-shadow:
                0 -12px 28px rgba(74, 14, 99, .08);
        }

        .aliada-btn-primary,
        .aliada-btn-secondary {
            min-width: 0;
            min-height: 52px;
            padding: 10px 16px;
            border-radius: 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.2;
            text-align: center;
            white-space: normal;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .aliada-btn-primary {
            flex: 1 1 auto;
            color: #fff;
            background: var(--aliada-gradient);
            box-shadow: 0 16px 34px rgba(216, 27, 156, .27);
        }

        .aliada-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 19px 38px rgba(216, 27, 156, .32);
        }

        .aliada-btn-secondary {
            flex: 0 0 auto;
            min-width: 118px;
            color: var(--aliada-fucsia-dark);
            background: rgba(252, 231, 243, .64);
            border: 1px solid rgba(216, 27, 156, .17);
        }

        .aliada-onboarding-skip {
            width: 100%;
            margin: 10px 0 16px;
            padding: 7px 8px;
            border: 0;
            background: transparent;
            color: var(--aliada-muted);
            font-size: 12.5px;
            font-weight: 800;
            cursor: pointer;
        }

        .aliada-mini-note {
            margin-top: 9px;
            color: #8A6A98;
            font-size: 11.3px;
            line-height: 1.45;
            text-align: center;
        }

        @media (max-width: 430px) {
            .aliada-onboarding-overlay {
                padding: 9px;
                align-items: center;
            }

            .aliada-onboarding-card {
                max-height: calc(100dvh - 18px);
                border-radius: 24px;
            }

            .aliada-onboarding-top {
                padding: 18px 17px 17px;
            }

            .aliada-onboarding-logo {
                width: 116px;
                height: 58px;
            }

            .aliada-onboarding-shield {
                width: 46px;
                height: 46px;
                border-radius: 16px;
                font-size: 19px;
            }

            .aliada-onboarding-title {
                font-size: 23px;
            }

            .aliada-onboarding-body {
                flex: 1 1 auto;
                min-height: 0;
                max-height: none;
                padding: 16px 17px 0;
            }

            .aliada-step-head h3 {
                font-size: 21px;
            }

            .aliada-risk-grid {
                grid-template-columns: 1fr 1fr;
                gap: 7px;
            }

            .aliada-risk-chip {
                padding: 9px 8px;
                font-size: 10.8px;
            }

            .aliada-onboarding-actions {
                flex-direction: row;

                margin:
                    16px
                    -17px
                    0;

                padding:
                    17px
                    17px
                    calc(17px + env(safe-area-inset-bottom));

                border-bottom-left-radius: 22px;
                border-bottom-right-radius: 22px;
            }

            .aliada-btn-secondary {
                flex: 0 0 38%;
                min-width: 0;
            }

            .aliada-btn-primary {
                flex: 1 1 auto;
                min-width: 0;
            }
        }

        @media (max-width: 350px) {
            .aliada-onboarding-actions {
                flex-direction: column-reverse;
            }

            .aliada-btn-secondary,
            .aliada-btn-primary {
                width: 100%;
                flex: 1 1 auto;
            }
        }

        @media (min-width: 700px) {
            .mobile-login-page {
                align-items: center;
            }

            .mobile-card {
                max-width: 450px;
            }
        }

        @media (max-width: 380px) {
            .mobile-login-page {
                padding: 10px;
            }

            .mobile-card {
                border-radius: 24px;
            }

            .mobile-hero {
                padding: 22px 18px 20px;
            }

            .logo-box {
                width: 118px;
                height: 58px;
                margin-bottom: 16px;
            }

            .mobile-title {
                font-size: 24px;
            }

            .mobile-form-area {
                padding: 18px;
            }

            .input,
            .select,
            .btn-main {
                height: 48px;
            }

            .remember-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================================================
         BIENVENIDA INICIAL ALIADA UTS
    ========================================================== -->
    <div
        id="aliadaOnboarding"
        class="aliada-onboarding-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="aliadaOnboardingTitle"
        aria-hidden="true"
    >
        <section class="aliada-onboarding-card">
            <div class="aliada-onboarding-top">
                <div class="aliada-onboarding-top-inner">
                    <div class="aliada-onboarding-brand-row">
                        <div class="aliada-onboarding-logo">
                            <img src="assets/img/utslog.png" alt="Aliada UTS">
                        </div>

                        <div class="aliada-onboarding-shield" aria-hidden="true">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>

                    <div class="aliada-onboarding-badge">Bienvenida a Aliada UTS</div>

                    <h2 id="aliadaOnboardingTitle" class="aliada-onboarding-title">
                        Su espacio de <span>acompañamiento y protección</span>
                    </h2>

                    <p class="aliada-onboarding-subtitle">
                        Una herramienta institucional, segura y cercana para orientarla cuando más lo necesite.
                    </p>
                </div>
            </div>

            <div class="aliada-onboarding-body">
                <div class="aliada-onboarding-progress" aria-label="Progreso de bienvenida">
                    <span class="aliada-onboarding-dot active" data-dot="1"></span>
                    <span class="aliada-onboarding-dot" data-dot="2"></span>
                    <span class="aliada-onboarding-dot" data-dot="3"></span>
                </div>

                <!-- PANTALLA 1 -->
                <div class="aliada-onboarding-step active" data-step="1">
                    <div class="aliada-step-head">
                        <h3>Nunca está sola.</h3>

                        <p>
                            Aliada UTS fue creada para que ninguna mujer de nuestra comunidad tenga que enfrentar sola
                            una situación de violencia, riesgo o vulnerabilidad.
                        </p>
                    </div>

                    <div class="aliada-highlight-box">
                        <strong>Estamos para acompañarla.</strong>
                        Aquí encontrará orientación, herramientas y acceso a rutas de apoyo diseñadas para ayudarle
                        cuando más lo necesite.
                    </div>

                    <div class="aliada-onboarding-actions">
                        <button
                            type="button"
                            class="aliada-btn-primary"
                            onclick="cambiarPasoOnboarding(2)"
                        >
                            Comenzar
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="aliada-onboarding-skip"
                        onclick="cerrarOnboardingAliada(true)"
                    >
                        Ya tengo una cuenta
                    </button>
                </div>

                <!-- PANTALLA 2 -->
                <div class="aliada-onboarding-step" data-step="2">
                    <div class="aliada-step-head">
                        <h3>¿Cómo puede ayudarla Aliada UTS?</h3>

                        <p>
                            Tendrá herramientas de orientación, acompañamiento y protección disponibles en un solo lugar.
                        </p>
                    </div>

                    <div class="aliada-feature-list">
                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-bell"></i></div>
                            <div class="aliada-feature-text">Activar un botón de pánico.</div>
                        </div>

                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-user-shield"></i></div>
                            <div class="aliada-feature-text">Contactar rápidamente a un familiar o persona de confianza.</div>
                        </div>

                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-robot"></i></div>
                            <div class="aliada-feature-text">Acceder a orientación inicial mediante inteligencia artificial.</div>
                        </div>

                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-balance-scale"></i></div>
                            <div class="aliada-feature-text">Recibir información jurídica sobre violencia contra la mujer.</div>
                        </div>

                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-folder-open"></i></div>
                            <div class="aliada-feature-text">Registrar y almacenar evidencias de manera organizada.</div>
                        </div>

                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-map-signs"></i></div>
                            <div class="aliada-feature-text">Consultar rutas institucionales de atención.</div>
                        </div>

                        <div class="aliada-feature-item">
                            <div class="aliada-feature-icon"><i class="fas fa-phone-volume"></i></div>
                            <div class="aliada-feature-text">Facilitar la comunicación con las autoridades cuando sea necesario.</div>
                        </div>
                    </div>

                    <div class="aliada-disclaimer">
                        <strong>Importante:</strong> Aliada UTS es una herramienta de apoyo y orientación.
                        No sustituye la atención médica, psicológica, jurídica ni la intervención de los servicios
                        de emergencia o de las autoridades competentes.
                    </div>

                    <div class="aliada-risk-box">
                        <strong>Orientación según el nivel de riesgo</strong>

                        <div class="aliada-risk-grid">
                            <div class="aliada-risk-chip green">🟢 Necesita información.</div>
                            <div class="aliada-risk-chip yellow">🟡 Está viviendo violencia.</div>
                            <div class="aliada-risk-chip orange">🟠 Está en crisis emocional.</div>
                            <div class="aliada-risk-chip red">🔴 Existe riesgo alto.</div>
                        </div>
                    </div>

                    <div class="aliada-onboarding-actions">
                        <button
                            type="button"
                            class="aliada-btn-secondary"
                            onclick="cambiarPasoOnboarding(1)"
                        >
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            Volver
                        </button>

                        <button
                            type="button"
                            class="aliada-btn-primary"
                            onclick="cambiarPasoOnboarding(3)"
                        >
                            Continuar
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- PANTALLA 3 -->
                <div class="aliada-onboarding-step" data-step="3">
                    <div class="aliada-step-head">
                        <h3>Antes de continuar</h3>

                        <p>
                            Para brindarle un mejor acompañamiento necesitamos su autorización.
                        </p>
                    </div>

                    <div class="aliada-consent-list">
                        <label class="aliada-check-card">
                            <input type="checkbox" class="aliada-consent-check">
                            <span>He leído y comprendido el alcance de Aliada UTS.</span>
                        </label>

                        <label class="aliada-check-card">
                            <input type="checkbox" class="aliada-consent-check">
                            <span>Entiendo que esta aplicación es una herramienta de apoyo y no reemplaza la atención profesional.</span>
                        </label>

                        <label class="aliada-check-card">
                            <input type="checkbox" class="aliada-consent-check">
                            <span>Autorizo el tratamiento de mis datos personales conforme a la normativa vigente.</span>
                        </label>

                        <label class="aliada-check-card">
                            <input type="checkbox" class="aliada-consent-check">
                            <span>
                                Autorizo que, cuando yo lo decida o cuando active las funciones correspondientes,
                                la aplicación contacte a la persona de confianza registrada.
                            </span>
                        </label>
                    </div>

                    <div class="aliada-onboarding-actions">
                        <button
                            type="button"
                            class="aliada-btn-secondary"
                            onclick="cambiarPasoOnboarding(2)"
                        >
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            Volver
                        </button>

                        <button
                            type="button"
                            class="aliada-btn-primary"
                            onclick="finalizarOnboardingAliada()"
                        >
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            Crear mi cuenta
                        </button>
                    </div>

                    <div class="aliada-mini-note">
                        Su información será tratada con enfoque de cuidado, acompañamiento y protección.
                    </div>
                </div>
            </div>
        </section>
    </div>

    <main class="mobile-login-page">
        <section class="mobile-card">

            <div class="mobile-hero">
                <div class="logo-box">
                    <img src="assets/img/utslog.png" alt="Aliada UTS">
                </div>

                <div class="mobile-badge">Plataforma institucional</div>
                 <div class="mobile-badge">Universitario Tecnologico de Santander</div>

                <h1 class="mobile-title">
                    Bienvenida a <span>Aliada UTS</span>
                </h1>

                <p class="mobile-subtitle">
                    Ingresa de forma segura para recibir orientación, apoyo y acompañamiento.
                </p>
            </div>

            <div class="mobile-form-area">

                <div id="panelLogin" class="form-panel active">
                    <form method="POST" action="login.php" onsubmit="guardarCredenciales();">
                        <input type="hidden" name="op" value="pms_usrlogin">

                        <div class="panel-head">
                            <h2>Iniciar sesión</h2>
                            <p>Accede con tu usuario y contraseña.</p>
                        </div>

                        <div class="field">
                            <label class="label" for="nickname">Usuario</label>
                            <input
                                type="text"
                                class="input"
                                id="nickname"
                                name="nickname"
                                placeholder="Ingresa tu usuario"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div class="field">
                            <label class="label" for="hashpass">Contraseña</label>
                            <div class="password-wrap">
                                <input
                                    type="password"
                                    class="input"
                                    id="hashpass"
                                    name="hashpass"
                                    placeholder="Ingresa tu contraseña"
                                    autocomplete="current-password"
                                    required
                                >
                                <button type="button" class="toggle-password" data-target="hashpass" aria-label="Mostrar contraseña">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="remember-row">
                            <label class="check-wrap" for="customCheck1">
                                <input type="checkbox" id="customCheck1" checked>
                                Recuérdame
                            </label>

                            <a href="restablecer-contrasena.html" class="inline-link">¿Olvidaste?</a>
                        </div>

                        <button type="submit" class="btn-main">
                            <i class="fas fa-arrow-right"></i>
                            Ingresar
                        </button>

                        <p class="switch-text">
                            ¿No tienes cuenta?
                            <a href="javascript:void(0);" onclick="mostrarPanel('registro')">Regístrate</a>
                        </p>

                        <div class="account-management">
                            <a
                                class="delete-account-link"
                                href="delete.php"
                                aria-label="Ir a la opción para eliminar mi cuenta"
                            >
                                <i class="fas fa-user-times" aria-hidden="true"></i>
                                Eliminar mi cuenta
                            </a>
                            <small>Por seguridad, debes haber iniciado sesión.</small>
                        </div>

                        <div class="note-box">
                            Aliada UTS acompaña, orienta y protege a las mujeres de la comunidad universitaria.
                        </div>
                    </form>
                </div>

                <div id="panelRegistro" class="form-panel">
                    <form method="POST" action="registro.php" id="formRegistro">
                        <div class="panel-head">
                            <h2>Crear cuenta</h2>
                            <p>Completa tus datos para acceder a Aliada UTS.</p>
                        </div>

                        <?php if (!empty($registroErrors['general'])): ?>
                            <div class="general-error-box"><?= errorText('general') ?></div>
                        <?php endif; ?>

                        <div class="register-scroll">
                            <div class="field">
                                <label class="label" for="reg_nombre">Nombres</label>
                                <input type="text" class="input <?= hasError('nombres') ? 'input-error' : '' ?>" id="reg_nombre" name="nombres" placeholder="Tus nombres" value="<?= oldValue('nombres') ?>">
                                <?php if (hasError('nombres')): ?><span class="error-message"><?= errorText('nombres') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_apellido">Apellidos</label>
                                <input type="text" class="input <?= hasError('apellidos') ? 'input-error' : '' ?>" id="reg_apellido" name="apellidos" placeholder="Tus apellidos" value="<?= oldValue('apellidos') ?>">
                                <?php if (hasError('apellidos')): ?><span class="error-message"><?= errorText('apellidos') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_tipo_doc">Tipo de documento</label>
                                <select class="select <?= hasError('tipo_documento') ? 'input-error' : '' ?>" id="reg_tipo_doc" name="tipo_documento">
                                    <option value="">Selecciona</option>
                                    <option value="CC" <?= oldValue('tipo_documento') === 'CC' ? 'selected' : '' ?>>Cédula de ciudadanía</option>
                                    <option value="TI" <?= oldValue('tipo_documento') === 'TI' ? 'selected' : '' ?>>Tarjeta de identidad</option>
                                    <option value="CE" <?= oldValue('tipo_documento') === 'CE' ? 'selected' : '' ?>>Cédula de extranjería</option>
                                    <option value="PP" <?= oldValue('tipo_documento') === 'PP' ? 'selected' : '' ?>>Pasaporte</option>
                                </select>
                                <?php if (hasError('tipo_documento')): ?><span class="error-message"><?= errorText('tipo_documento') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_documento">Número de documento</label>
                                <input type="text" class="input <?= hasError('documento') ? 'input-error' : '' ?>" id="reg_documento" name="documento" placeholder="Número de documento" value="<?= oldValue('documento') ?>">
                                <?php if (hasError('documento')): ?><span class="error-message"><?= errorText('documento') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_correo">Correo electrónico</label>
                                <input type="email" class="input <?= hasError('correo') ? 'input-error' : '' ?>" id="reg_correo" name="correo" placeholder="correo@ejemplo.com" value="<?= oldValue('correo') ?>">
                                <?php if (hasError('correo')): ?><span class="error-message"><?= errorText('correo') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_celular">Celular</label>
                                <input type="text" class="input <?= hasError('celular') ? 'input-error' : '' ?>" id="reg_celular" name="celular" placeholder="Número de celular" value="<?= oldValue('celular') ?>">
                                <?php if (hasError('celular')): ?><span class="error-message"><?= errorText('celular') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_contacto_emergencia">Celular contacto de emergencia</label>
                                <input type="text" class="input <?= hasError('contacto_emergencia') ? 'input-error' : '' ?>" id="reg_contacto_emergencia" name="contacto_emergencia" placeholder="Número del contacto" value="<?= oldValue('contacto_emergencia') ?>">
                                <?php if (hasError('contacto_emergencia')): ?><span class="error-message"><?= errorText('contacto_emergencia') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_nombre_contacto">Nombre contacto de emergencia</label>
                                <input type="text" class="input <?= hasError('nombre_contacto') ? 'input-error' : '' ?>" id="reg_nombre_contacto" name="nombre_contacto" placeholder="Nombre del contacto" value="<?= oldValue('nombre_contacto') ?>">
                                <?php if (hasError('nombre_contacto')): ?><span class="error-message"><?= errorText('nombre_contacto') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label">Departamento</label>
                                <input type="text" class="input" value="Santander" readonly>
                                <input type="hidden" name="tbl_departamento_id" value="68">
                            </div>

                            <div class="field">
                                <label class="label" for="tbl_municipio_id">Municipio</label>
                                <select class="select <?= hasError('tbl_municipio_id') ? 'input-error' : '' ?>" id="tbl_municipio_id" name="tbl_municipio_id" data-selected="<?= oldValue('tbl_municipio_id') ?>">
                                    <option value="">Cargando municipios...</option>
                                </select>
                                <?php if (hasError('tbl_municipio_id')): ?><span class="error-message"><?= errorText('tbl_municipio_id') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_direccion">Dirección</label>
                                <input type="text" class="input <?= hasError('direccion') ? 'input-error' : '' ?>" id="reg_direccion" name="direccion" placeholder="Dirección" value="<?= oldValue('direccion') ?>">
                                <?php if (hasError('direccion')): ?><span class="error-message"><?= errorText('direccion') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_usuario">Nombre de usuario</label>
                                <input type="text" class="input <?= hasError('usuario') ? 'input-error' : '' ?>" id="reg_usuario" name="usuario" placeholder="Crea tu usuario" value="<?= oldValue('usuario') ?>">
                                <?php if (hasError('usuario')): ?><span class="error-message"><?= errorText('usuario') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_password">Contraseña</label>
                                <div class="password-wrap">
                                    <input type="password" class="input <?= hasError('password') ? 'input-error' : '' ?>" id="reg_password" name="password" placeholder="Crea una contraseña">
                                    <button type="button" class="toggle-password" data-target="reg_password" aria-label="Mostrar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (hasError('password')): ?><span class="error-message"><?= errorText('password') ?></span><?php endif; ?>
                            </div>

                            <div class="field">
                                <label class="label" for="reg_password2">Confirmar contraseña</label>
                                <div class="password-wrap">
                                    <input type="password" class="input <?= hasError('password_confirmacion') ? 'input-error' : '' ?>" id="reg_password2" name="password_confirmacion" placeholder="Repite la contraseña">
                                    <button type="button" class="toggle-password" data-target="reg_password2" aria-label="Mostrar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (hasError('password_confirmacion')): ?><span class="error-message"><?= errorText('password_confirmacion') ?></span><?php endif; ?>
                            </div>

                        <label
    class="check-wrap"
    for="aceptoDatos"
    style="align-items:flex-start; margin:4px 0 16px;"
>
    <input
        type="checkbox"
        id="aceptoDatos"
        name="aceptoDatos"
        value="1"
        required
        style="margin-top:2px;"
    >

    <span>
        Acepto la
        <a
            href="politica_privacidad.php"
            target="_blank"
            rel="noopener noreferrer"
        >
            política de tratamiento de datos personales
        </a>
        para recibir orientación y acompañamiento institucional mediante inteligencia artificial.
    </span>
</label>
                        </div>

                        <button type="submit" class="btn-main">
                            <i class="fas fa-user-plus"></i>
                            Crear cuenta
                        </button>

                        <div style="margin-top: 12px;">
                            <button type="button" class="btn-outline-mobile" onclick="mostrarPanel('login')">
                                <i class="fas fa-arrow-left"></i>
                                Ya tengo cuenta
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </main>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <?php if (!empty($mensaje)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    icon: 'error',
                    title: 'No fue posible ingresar',
                    text: <?= json_encode($mensaje) ?>,
                    confirmButtonColor: '#D81B9C'
                });
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($registroSuccess)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Registro exitoso',
                    text: <?= json_encode($registroSuccess) ?>,
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#D81B9C'
                });
            });
        </script>
    <?php endif; ?>

    <script>
        function mostrarPanel(tipo) {
            const panelLogin = document.getElementById('panelLogin');
            const panelRegistro = document.getElementById('panelRegistro');

            if (tipo === 'registro') {
                panelLogin.classList.remove('active');
                panelRegistro.classList.add('active');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                panelRegistro.classList.remove('active');
                panelLogin.classList.add('active');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function setCookie(name, value, days) {
            const expires = new Date(Date.now() + days * 86400000).toUTCString();
            document.cookie = `${name}=${value}; expires=${expires}; path=/`;
        }

        function getCookie(name) {
            const cookies = document.cookie.split(';');

            for (let c of cookies) {
                const parts = c.trim().split('=');
                const k = parts.shift();
                const v = parts.join('=');

                if (k === name) {
                    return decodeURIComponent(v);
                }
            }

            return '';
        }

        function guardarCredenciales() {
            const remember = document.getElementById('customCheck1');
            const usuario = document.getElementById('nickname');
            const pass = document.getElementById('hashpass');

            if (!remember || !usuario || !pass) return;

            if (remember.checked) {
                setCookie('remember_user', encodeURIComponent(usuario.value), 30);

                if (typeof CryptoJS !== 'undefined') {
                    const encryptedPass = CryptoJS.AES.encrypt(pass.value, 'clave_maestra').toString();
                    setCookie('remember_pass', encodeURIComponent(encryptedPass), 30);
                }
            } else {
                setCookie('remember_user', '', -1);
                setCookie('remember_pass', '', -1);
            }
        }

        function cargarMunicipiosSantander() {
            if (typeof $ === 'undefined') return;

            const $municipio = $('#tbl_municipio_id');

            if (!$municipio.length) return;

            const municipioSeleccionado = $municipio.attr('data-selected') || '';

            $municipio.html('<option value="">Cargando municipios...</option>');

            $.ajax({
                url: 'admin/ajax/rqst.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    op: 'ciudadget',
                    codigo_departamento: '68'
                },
                success: function (data) {
                    let html = '<option value="">Seleccione un municipio</option>';

                    if (data && data.output && data.output.valid && Array.isArray(data.output.response)) {
                        data.output.response.forEach(function (municipio) {
                            const codigo = municipio.codigo_muncipio || '';
                            const nombre = municipio.municipio || '';
                            const selected = (municipioSeleccionado !== '' && municipioSeleccionado == codigo) ? 'selected' : '';

                            html += `<option value="${codigo}" ${selected}>${nombre}</option>`;
                        });

                        $municipio.html(html);
                    } else {
                        $municipio.html('<option value="">No se pudieron cargar los municipios</option>');
                    }
                },
                error: function (xhr, status, error) {
                    $municipio.html('<option value="">Error al cargar municipios</option>');
                    console.error('Error AJAX municipios:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                }
            });
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.toggle-password');

            if (!btn) return;

            e.preventDefault();

            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';

                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                input.type = 'password';

                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const savedUser = getCookie('remember_user');
            const savedPass = getCookie('remember_pass');

            const nickname = document.getElementById('nickname');
            const hashpass = document.getElementById('hashpass');
            const remember = document.getElementById('customCheck1');

            if (savedUser && nickname) {
                nickname.value = savedUser;

                if (remember) {
                    remember.checked = true;
                }
            }

            if (savedPass && hashpass && typeof CryptoJS !== 'undefined') {
                try {
                    const decrypted = CryptoJS.AES.decrypt(savedPass, 'clave_maestra').toString(CryptoJS.enc.Utf8);
                    hashpass.value = decrypted;
                } catch (e) {
                    console.warn('No se pudo descifrar la contraseña.');
                }
            }

            const urlParams = new URLSearchParams(window.location.search);
            const panel = urlParams.get('panel');

            if (panel === 'registro') {
                mostrarPanel('registro');
            } else {
                mostrarPanel('login');
            }

            cargarMunicipiosSantander();

            const formRegistro = document.getElementById('formRegistro');

            if (formRegistro) {
                formRegistro.addEventListener('submit', function (e) {
                    const pass1 = document.getElementById('reg_password').value.trim();
                    const pass2 = document.getElementById('reg_password2').value.trim();
                    const acepta = document.getElementById('aceptoDatos').checked;
                    const municipio = document.getElementById('tbl_municipio_id').value;
                    const contactoEmergencia = document.getElementById('reg_contacto_emergencia').value.trim();
                    const nombreContacto = document.getElementById('reg_nombre_contacto').value.trim();

                    if (!acepta) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Debes aceptar el tratamiento de datos personales.',
                            confirmButtonColor: '#D81B9C'
                        });
                        return;
                    }

                    if (municipio === '') {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Debes seleccionar un municipio.',
                            confirmButtonColor: '#D81B9C'
                        });
                        return;
                    }

                    if (contactoEmergencia === '') {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Debes ingresar el celular del contacto de emergencia.',
                            confirmButtonColor: '#D81B9C'
                        });
                        return;
                    }

                    if (nombreContacto === '') {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Debes ingresar el nombre del contacto de emergencia.',
                            confirmButtonColor: '#D81B9C'
                        });
                        return;
                    }

                    if (pass1 === '' || pass2 === '') {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Debes completar la contraseña y su confirmación.',
                            confirmButtonColor: '#D81B9C'
                        });
                        return;
                    }

                    if (pass1 !== pass2) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Contraseñas no coinciden',
                            text: 'Verifica que ambas contraseñas sean iguales.',
                            confirmButtonColor: '#D81B9C'
                        });
                    }
                });
            }
        });
    </script>


    <script>
        /* =========================================================
           CONTROL DEL FLUJO DE BIENVENIDA
        ========================================================= */
        const ALIADA_ONBOARDING_KEY = 'aliada_uts_onboarding_v2';
        let pasoActualOnboarding = 1;

        function cambiarPasoOnboarding(paso) {
            pasoActualOnboarding = paso;

            document.querySelectorAll('.aliada-onboarding-step').forEach(function (item) {
                item.classList.remove('active');
            });

            document.querySelectorAll('.aliada-onboarding-dot').forEach(function (item) {
                item.classList.remove('active');
            });

            const step = document.querySelector(`.aliada-onboarding-step[data-step="${paso}"]`);
            const dot = document.querySelector(`.aliada-onboarding-dot[data-dot="${paso}"]`);
            const body = document.querySelector('.aliada-onboarding-body');

            if (step) {
                step.classList.add('active');
            }

            if (dot) {
                dot.classList.add('active');
            }

            if (body) {
                body.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function abrirOnboardingAliada() {
            const overlay = document.getElementById('aliadaOnboarding');

            if (!overlay) {
                return;
            }

            cambiarPasoOnboarding(1);
            overlay.classList.add('active');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function cerrarOnboardingAliada(marcarVisto = true) {
            const overlay = document.getElementById('aliadaOnboarding');

            if (!overlay) {
                return;
            }

            overlay.classList.remove('active');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            if (marcarVisto) {
                localStorage.setItem(ALIADA_ONBOARDING_KEY, 'ok');
            }
        }

        function finalizarOnboardingAliada() {
            const checks = Array.from(
                document.querySelectorAll('.aliada-consent-check')
            );

            const todosAceptados = checks.length > 0 && checks.every(function (item) {
                return item.checked;
            });

            if (!todosAceptados) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Antes de continuar',
                    text: 'Debe marcar todas las autorizaciones para crear su cuenta.',
                    confirmButtonColor: '#D81B9C'
                });
                return;
            }

            localStorage.setItem(ALIADA_ONBOARDING_KEY, 'ok');
            localStorage.setItem('aliada_uts_consentimiento_inicial', 'aceptado');

            const checkboxRegistro = document.getElementById('aceptoDatos');

            if (checkboxRegistro) {
                checkboxRegistro.checked = true;
            }

            cerrarOnboardingAliada(true);

            if (typeof mostrarPanel === 'function') {
                mostrarPanel('registro');
            }

            setTimeout(function () {
                const primerCampo = document.getElementById('reg_nombre');

                if (primerCampo) {
                    primerCampo.focus({ preventScroll: true });
                    primerCampo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const onboardingVisto = localStorage.getItem(ALIADA_ONBOARDING_KEY);
            const parametros = new URLSearchParams(window.location.search);
            const panelSolicitado = parametros.get('panel');

            const hayEstadoServidor = <?php echo json_encode(
                !empty($mensaje) ||
                !empty($registroErrors) ||
                !empty($registroSuccess)
            ); ?>;

            /*
             * No interrumpe errores de ingreso, validaciones del registro,
             * mensajes de éxito ni enlaces que abren directamente el registro.
             */
            if (
                !onboardingVisto &&
                !hayEstadoServidor &&
                panelSolicitado !== 'registro'
            ) {
                setTimeout(function () {
                    abrirOnboardingAliada();
                }, 380);
            }
        });
    </script>

</body>
</html>
