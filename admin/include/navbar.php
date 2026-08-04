<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nombrePanel = $_SESSION['panel_user']['nombre_completo'] ?? 'Administradora';
$rolPanel    = $_SESSION['panel_user']['rol'] ?? 'Panel de control';
$paginaActual = basename($_SERVER['PHP_SELF']);
$esVisor = strtolower(trim((string)$rolPanel)) === 'visor';

function itemActivo($archivoActual, $archivos = []) {
    return in_array($archivoActual, $archivos, true) ? 'active' : '';
}
?>

<style>
    :root{
        --vs-sidebar-width: 310px;
        --vs-sidebar-collapsed: 80px;
    }

    .pcoded-navbar.violeta-navbar{
        position: fixed !important;
        top: 0;
        left: 0;
        bottom: 0;
        width: var(--vs-sidebar-width) !important;
        min-width: var(--vs-sidebar-width) !important;
        z-index: 1040;
        background:
            radial-gradient(circle at top left, rgba(255,60,60,.18), transparent 20%),
            linear-gradient(180deg, #090611 0%, #070b18 42%, #040812 100%) !important;
        border-right: 1px solid rgba(255,255,255,.06);
        box-shadow: 18px 0 45px rgba(0,0,0,.30);
        overflow: hidden;
        transition: width .25s ease;
    }

    .violeta-navbar .navbar-wrapper,
    .violeta-navbar .navbar-content{
        background: transparent !important;
        height: 100%;
    }

    .violeta-navbar .navbar-content{
        padding-top: 0 !important;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .violeta-sidebar-top{
        padding: 24px 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,.06);
        margin-bottom: 10px;
    }

    .violeta-brand-box{
        display:flex;
        align-items:center;
        gap:14px;
    }

    .violeta-brand-icon{
        width:54px;
        height:54px;
        border-radius:18px;
        background: linear-gradient(135deg, #ff2d55, #ff4d6d);
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:22px;
        box-shadow: 0 16px 28px rgba(255,45,85,.30);
        flex-shrink: 0;
    }

    .violeta-brand-copy .brand-title{
        display:block;
        color:#fff;
        font-size:18px;
        line-height:1.05;
        font-weight:900;
        margin:0;
        white-space: nowrap;
    }

    .violeta-brand-copy .brand-sub{
        display:block;
        color:#c0b6cf;
        font-size:13px;
        line-height:1.2;
        font-weight:500;
        margin-top:4px;
        white-space: nowrap;
    }

    .violeta-navbar .pcoded-inner-navbar{
        padding: 10px 12px 24px !important;
    }

    .violeta-navbar .pcoded-inner-navbar > li{
        margin-bottom: 10px;
    }

    .violeta-navbar .pcoded-inner-navbar > li > a{
        display:flex !important;
        align-items:center;
        gap:14px;
        padding: 14px 14px !important;
        border-radius: 18px !important;
        color:#ffffff !important;
        font-weight:700;
        background: transparent;
        border:1px solid transparent;
        transition: all .25s ease;
        min-height: 72px;
    }

    .violeta-navbar .pcoded-inner-navbar > li > a:hover{
        background: linear-gradient(135deg, rgba(255,45,85,.12), rgba(255,255,255,.02));
        border-color: rgba(255,45,85,.22);
        transform: translateX(4px);
    }

    .violeta-navbar .pcoded-inner-navbar > li.active > a{
        background: linear-gradient(135deg, rgba(255,45,85,.20), rgba(255,255,255,.03));
        border: 1px solid rgba(255,45,85,.28);
        box-shadow: 0 14px 28px rgba(0,0,0,.16);
    }

    .violeta-navbar .nav-icon-wrap{
        width:44px;
        min-width:44px;
        height:44px;
        border-radius:14px;
        background: rgba(255,255,255,.06);
        border:1px solid rgba(255,255,255,.08);
        display:flex;
        align-items:center;
        justify-content:center;
        color:#fff;
        font-size:18px;
        transition: all .25s ease;
    }

    .violeta-navbar .pcoded-inner-navbar > li.active > a .nav-icon-wrap,
    .violeta-navbar .pcoded-inner-navbar > li > a:hover .nav-icon-wrap{
        background: linear-gradient(135deg, #ff2d55, #ff5a6e);
        border-color: transparent;
        box-shadow: 0 14px 24px rgba(255,45,85,.22);
    }

    .violeta-navbar .nav-copy{
        display:flex;
        flex-direction:column;
        min-width:0;
        overflow: hidden;
    }

    .violeta-navbar .nav-copy .title{
        color:#fff;
        font-size:15px;
        font-weight:800;
        line-height:1.1;
        margin-bottom:5px;
        white-space: nowrap;
    }

    .violeta-navbar .nav-copy .desc{
        color:#9da7ba;
        font-size:12px;
        line-height:1.35;
        font-weight:600;
    }

    .violeta-logout-item{
        margin-top: 18px;
    }

    .violeta-logout-item > a{
        background: rgba(255,255,255,.03) !important;
        border:1px solid rgba(255,255,255,.08) !important;
    }

    body.nav-collapse .pcoded-navbar.violeta-navbar{
        width: var(--vs-sidebar-collapsed) !important;
        min-width: var(--vs-sidebar-collapsed) !important;
    }

    body.nav-collapse .violeta-brand-copy,
    body.nav-collapse .violeta-navbar .nav-copy{
        display: none !important;
    }

    body.nav-collapse .violeta-sidebar-top{
        padding-left: 13px;
        padding-right: 13px;
    }

    body.nav-collapse .violeta-navbar .pcoded-inner-navbar{
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    body.nav-collapse .violeta-navbar .pcoded-inner-navbar > li > a{
        justify-content: center;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    @media (max-width: 991.98px){
        .pcoded-navbar.violeta-navbar{
            transform: translateX(-100%);
            width: 290px !important;
            min-width: 290px !important;
        }

        .pcoded-navbar.violeta-navbar.mob-open{
            transform: translateX(0);
        }
    }
</style>

<nav class="pcoded-navbar navbar-expand-lg navbar-light violeta-navbar">
    <div class="navbar-wrapper">
        <div class="navbar-content scroll-div">

            <div class="violeta-sidebar-top">
                <div class="violeta-brand-box">
                   
                        <img src="./assets/img/utslog.png" alt="" width="75px">
                
                    <div class="violeta-brand-copy">
                        <div class="brand-title">Aliada</div>
             
                    </div>
                </div>
            </div>

            <ul class="nav pcoded-inner-navbar">

                <?php if (!$esVisor): ?>
                <li class="nav-item <?php echo itemActivo($paginaActual, ['dashboard.php','index.php']); ?>">
                    <a href="dashboard.php" class="nav-link">
                        <div class="nav-icon-wrap"><i class="fas fa-house"></i></div>
                        <div class="nav-copy">
                            <span class="title">Dashboard</span>
                            <span class="desc">Resumen general del sistema</span>
                        </div>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item <?php echo itemActivo($paginaActual, ['ver_emergencias.php','detalle_emergencia.php','visualizador_emergencias.php']); ?>">
                    <a href="<?php echo $esVisor ? 'visualizador_emergencias.php' : 'ver_emergencias.php'; ?>" class="nav-link">
                        <div class="nav-icon-wrap"><i class="fas fa-bell"></i></div>
                        <div class="nav-copy">
                            <span class="title">Ver emergencias</span>
                            <span class="desc">Alertas, estados y ubicaciones</span>
                        </div>
                    </a>
                </li>

                <?php if (!$esVisor): ?>
                <li class="nav-item <?php echo itemActivo($paginaActual, ['crear_usuario_panel.php','usuarios_panel.php']); ?>">
                    <a href="crear_usuario_panel.php" class="nav-link">
                        <div class="nav-icon-wrap"><i class="fas fa-user-plus"></i></div>
                        <div class="nav-copy">
                            <span class="title">Usuarios</span>
                            <span class="desc">Creación y administración</span>
                        </div>
                    </a>
                </li>

                <li class="nav-item <?php echo itemActivo($paginaActual, ['estadisticas.php']); ?>">
                    <a href="estadisticas.php" class="nav-link">
                        <div class="nav-icon-wrap"><i class="fas fa-gear"></i></div>
                        <div class="nav-copy">
                            <span class="title">Estadisticas</span>
                            <span class="desc">Estadisticas del sistema</span>
                        </div>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item violeta-logout-item">
                    <a href="logout.php" class="nav-link">
                        <div class="nav-icon-wrap"><i class="fas fa-power-off"></i></div>
                        <div class="nav-copy">
                            <span class="title">Cerrar sesión</span>
                            <span class="desc">Salir del panel de control</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>