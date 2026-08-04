<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

$nombrePanel = $_SESSION['panel_user']['nombre_completo'] ?? 'Administradora';
$rolPanel    = $_SESSION['panel_user']['rol'] ?? 'SuperAdministradora';

$esVisorHeader = strtolower(trim((string)$rolPanel)) === 'visor';

$tituloHeader = isset($modulo) && trim((string)$modulo) !== ''
    ? trim((string)$modulo)
    : 'Dashboard';

$logoAliada  = 'assets/img/utslog.png';
$iconoAliada = 'assets/img/icono.png';
?>

<style>
    :root {
        --au-sidebar-width: 310px;
        --au-header-height: 92px;

        --au-violet: #7B1FA2;
        --au-violet-dark: #4A0E63;
        --au-violet-soft: #F3E8FF;

        --au-fuchsia: #D81B9C;
        --au-fuchsia-dark: #A50F78;
        --au-fuchsia-soft: #FCE7F3;

        --au-pink: #FF4FB8;
        --au-pink-soft: #FFE4F3;

        --au-white: #FFFFFF;
        --au-text: #2B1237;
        --au-muted: #8B6A94;
        --au-border: rgba(216, 27, 156, .16);

        --au-gradient-main: linear-gradient(135deg, #FF4FB8 0%, #D81B9C 46%, #7B1FA2 100%);
        --au-gradient-soft: linear-gradient(135deg, rgba(255, 79, 184, .14), rgba(123, 31, 162, .10));
        --au-shadow: 0 18px 46px rgba(123, 31, 162, .20);
        --au-shadow-soft: 0 10px 28px rgba(216, 27, 156, .14);
    }

    .pcoded-header.aliada-header {
        position: fixed !important;
        top: 0;
        right: 0;
        left: var(--au-sidebar-width);
        height: var(--au-header-height);
        z-index: 1030;
        background:
            radial-gradient(circle at 8% 0%, rgba(255, 79, 184, .26), transparent 28%),
            radial-gradient(circle at 98% 30%, rgba(123, 31, 162, .20), transparent 32%),
            rgba(255, 255, 255, .88) !important;
        border-bottom: 1px solid rgba(216, 27, 156, .13);
        box-shadow: var(--au-shadow-soft);
        backdrop-filter: blur(18px);
    }

    .aliada-header .navbar-collapse {
        height: 100%;
    }

    .aliada-header .header-wrapper {
        height: 100%;
        padding: 0 26px !important;
        display: flex;
        align-items: center;
    }

    .aliada-topbar {
        width: 100%;
        min-height: var(--au-header-height);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .aliada-topbar-left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .aliada-menu-btn {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border: none;
        border-radius: 17px;
        background: var(--au-gradient-main);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        cursor: pointer;
        transition: .25s ease;
        box-shadow: 0 14px 30px rgba(216, 27, 156, .28);
    }

    .aliada-menu-btn i {
        color: #fff;
    }

    .aliada-menu-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 38px rgba(123, 31, 162, .34);
        filter: brightness(1.04);
    }

    .aliada-brand-chip {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
        padding: 10px 18px 10px 12px;
        border-radius: 22px;
        background: rgba(255, 255, 255, .78);
        border: 1px solid rgba(216, 27, 156, .16);
        box-shadow: 0 12px 28px rgba(123, 31, 162, .10);
    }

    .aliada-brand-logo {
        width: 96px;
        height: 46px;
        min-width: 96px;
        border-radius: 16px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 7px 10px;
        border: 1px solid rgba(216, 27, 156, .10);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.80);
    }

    .aliada-brand-logo img {
        display: block;
        max-width: 100%;
        max-height: 34px;
        object-fit: contain;
    }

    .aliada-title-box {
        min-width: 0;
    }

    .aliada-title-box .module-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 4px;
        color: var(--au-fuchsia-dark);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .aliada-title-box .module-label::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--au-pink);
        box-shadow: 0 0 0 5px rgba(255, 79, 184, .15);
        flex-shrink: 0;
    }

    .aliada-title-box .module-title {
        margin: 0;
        color: var(--au-text);
        font-size: 18px;
        font-weight: 950;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 360px;
    }

    .aliada-topbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: auto;
        flex-wrap: nowrap;
    }

    .aliada-pill {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        min-height: 46px;
        padding: 8px 15px 8px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .78);
        border: 1px solid rgba(216, 27, 156, .14);
        color: var(--au-text);
        box-shadow: 0 10px 24px rgba(123, 31, 162, .08);
        white-space: nowrap;
    }

    .aliada-pill-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 999px;
        background: var(--au-gradient-main);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 22px rgba(216, 27, 156, .24);
    }

    .aliada-pill-copy {
        display: flex;
        flex-direction: column;
        line-height: 1.05;
    }

    .aliada-pill .label {
        font-size: 9.5px;
        color: var(--au-muted);
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .aliada-pill .value {
        font-size: 12.5px;
        color: var(--au-text);
        font-weight: 900;
        max-width: 185px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .aliada-round-btn {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 17px;
        background: rgba(255, 255, 255, .82);
        border: 1px solid rgba(216, 27, 156, .15);
        color: var(--au-fuchsia);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        text-decoration: none;
        transition: .25s ease;
        box-shadow: 0 10px 24px rgba(123, 31, 162, .08);
    }

    .aliada-round-btn:hover {
        transform: translateY(-2px);
        background: var(--au-gradient-main);
        color: #fff;
        box-shadow: 0 16px 34px rgba(216, 27, 156, .28);
    }

    .aliada-round-btn.logout {
        color: #EF4444;
    }

    .aliada-round-btn.logout:hover {
        background: linear-gradient(135deg, #FF4FB8, #EF4444);
        color: #fff;
    }

    body.nav-collapse .pcoded-header.aliada-header {
        left: 80px;
    }

    .pcoded-main-container {
        padding-top: var(--au-header-height);
    }

    @media (max-width: 1199.98px) {
        .aliada-pill.role-pill {
            display: none;
        }

        .aliada-title-box .module-title {
            max-width: 280px;
        }
    }

    @media (max-width: 991.98px) {
        .pcoded-header.aliada-header {
            left: 0 !important;
            height: auto;
            min-height: 82px;
        }

        .aliada-header .header-wrapper {
            padding: 12px 14px !important;
            height: auto;
        }

        .aliada-topbar {
            min-height: auto;
            flex-wrap: wrap;
            gap: 12px;
        }

        .aliada-topbar-left {
            width: 100%;
        }

        .aliada-brand-chip {
            flex: 1;
        }

        .aliada-title-box .module-title {
            max-width: 100%;
            font-size: 16px;
        }

        .aliada-topbar-right {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .aliada-pill {
            flex: 1;
            min-width: 220px;
        }

        .pcoded-main-container {
            padding-top: 150px;
        }
    }

    @media (max-width: 640px) {
        .aliada-header .header-wrapper {
            padding: 10px !important;
        }

        .aliada-topbar {
            gap: 10px;
        }

        .aliada-menu-btn {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 15px;
        }

        .aliada-brand-chip {
            padding: 8px 10px;
            border-radius: 18px;
            gap: 10px;
        }

        .aliada-brand-logo {
            width: 76px;
            height: 40px;
            min-width: 76px;
            border-radius: 14px;
        }

        .aliada-brand-logo img {
            max-height: 29px;
        }

        .aliada-title-box .module-label {
            font-size: 8.5px;
            margin-bottom: 3px;
        }

        .aliada-title-box .module-title {
            font-size: 14.5px;
        }

        .aliada-pill {
            width: 100%;
            min-width: 100%;
            justify-content: flex-start;
        }

        .aliada-pill.role-pill {
            display: inline-flex;
        }

        .aliada-round-btn {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 15px;
        }

        .pcoded-main-container {
            padding-top: 205px;
        }
    }

    @media (max-width: 420px) {
        .aliada-pill.role-pill {
            display: none;
        }

        .pcoded-main-container {
            padding-top: 158px;
        }
    }
</style>

<header class="navbar pcoded-header navbar-expand-lg navbar-light headerpos-fixed aliada-header">
    <div class="collapse navbar-collapse show">
        <div class="header-wrapper w-100">
            <div class="aliada-topbar">

                <div class="aliada-topbar-left">
                    <button class="aliada-menu-btn" type="button" onclick="toggleSidebarAliada()" aria-label="Abrir menú">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="aliada-brand-chip">
                        <div class="aliada-brand-logo">
                            <img src="<?php echo htmlspecialchars($logoAliada, ENT_QUOTES, 'UTF-8'); ?>" alt="Aliada UTS">
                        </div>

                        <div class="aliada-title-box">
                            <div class="module-label">Aliada UTS</div>
                            <h1 class="module-title">
                                <?php echo htmlspecialchars($tituloHeader, ENT_QUOTES, 'UTF-8'); ?>
                            </h1>
                        </div>
                    </div>
                </div>

                <div class="aliada-topbar-right">
                    <div class="aliada-pill user-pill">
                        <span class="aliada-pill-icon">
                            <i class="fas fa-user-shield"></i>
                        </span>
                        <span class="aliada-pill-copy">
                            <span class="label">Usuario</span>
                            <span class="value"><?php echo htmlspecialchars($nombrePanel, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </div>

                    <div class="aliada-pill role-pill">
                        <span class="aliada-pill-icon">
                            <i class="fas fa-id-badge"></i>
                        </span>
                        <span class="aliada-pill-copy">
                            <span class="label">Rol</span>
                            <span class="value"><?php echo htmlspecialchars($rolPanel, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </div>

                    <a href="<?php echo $esVisorHeader ? 'visualizador_emergencias.php' : 'ver_emergencias.php'; ?>" class="aliada-round-btn" title="Emergencias">
                        <i class="fas fa-heart-pulse"></i>
                    </a>

                    <a href="dashboard.php" class="aliada-round-btn" title="Inicio">
                        <i class="fas fa-house"></i>
                    </a>

                    <a href="logout.php" class="aliada-round-btn logout" title="Cerrar sesión">
                        <i class="fas fa-right-from-bracket"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</header>

<script>
    function toggleSidebarAliada() {
        const body = document.body;

        body.classList.toggle('nav-collapse');
        body.classList.toggle('pcoded-navbar-collapsed');

        const sidebar =
            document.querySelector('.pcoded-navbar') ||
            document.querySelector('.navbar-collapsed') ||
            document.querySelector('.pcoded-sidebar');

        if (sidebar) {
            sidebar.classList.toggle('navbar-collapsed');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('active');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const favicon = document.querySelector("link[rel='icon']") || document.createElement('link');
        favicon.rel = 'icon';
        favicon.type = 'image/png';
        favicon.href = '<?php echo htmlspecialchars($iconoAliada, ENT_QUOTES, 'UTF-8'); ?>';

        if (!favicon.parentNode) {
            document.head.appendChild(favicon);
        }

        const shortcut = document.querySelector("link[rel='shortcut icon']") || document.createElement('link');
        shortcut.rel = 'shortcut icon';
        shortcut.type = 'image/png';
        shortcut.href = '<?php echo htmlspecialchars($iconoAliada, ENT_QUOTES, 'UTF-8'); ?>';

        if (!shortcut.parentNode) {
            document.head.appendChild(shortcut);
        }
    });
</script>