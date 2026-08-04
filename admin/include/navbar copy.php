<?php
$userType = SessionData::getUserType();
$isAlcaldeOAuxiliar = ($userType === Util::Alcalde() || $userType === Util::Auxiliar_Alcalde());
$isAdmin = ($userType === Util::Administrador() || $userType === Util::SuperAdministrador());
$isSecretario = ($userType === Util::Secretario_Despacho() || $userType === Util::Auxiliar());
$isAlcalde = ($userType === Util::Alcalde() || $userType === Util::Auxiliar_Alcalde());
$municipioUsuarioLogueado = SessionData::getCodigoMunicipio();


//$isSecretarioDespacho = ($userType === Util::Secretario_Despacho());
//$isSecretarioDespachoOAdmin = $isSecretarioDespacho || $isAdmin;

// Informacion de notificaciones
/* $notificaciones = Util::consultarNotificaciones();
$isvalid = $notificaciones['output']['response'];
$cantidadNotificaciones = $notificaciones['output']['cantidad']; */
/* echo Util::getIdentificadorPilarPrincipal();
exit(); */
?>
<nav class="pcoded-navbar">
    <div class="navbar-wrapper">
        <div class="navbar-content scroll-div">




            <ul class="nav pcoded-inner-navbar">
                <!-- Boton chat ia -->

                <!-- Perfil de Usuario estilo MaterialPro -->
                <div class="user-profile d-flex flex-column align-items-center py-3">
                    <button onclick="toggleMenu()" id="menuToggleBtn" class="menu-toggle-btn" title="Minimizar menú">
                        <i class="feather icon-chevrons-left"></i>
                    </button>
                    <div class="profile-img mb-2">
                        <?php
                        $img = !empty(SessionData::getFotoUsuario())
                            ? "assets/img/admin/" . htmlspecialchars(SessionData::getFotoUsuario())
                            : 'assets/img/santander.png';
                        ?>
                        <img src="<?= $img ?>" alt="user" class="rounded-circle" width="60">
                    </div>
                    <h6 class="text-white fw-bold mb-0">
                        <?php echo SessionData::getNombreUsuario(); ?>
                    </h6>
                    <span class="text-muted" style="font-size: 12px;color: #ffffff !important;"><?php echo SessionData::getUserType(); ?></span>
                </div>
                <!-- Menú completo para administradores -->
                <li class="nav-item pcoded-menu-caption">
                    <label>Navegación</label>
                </li>

                <li class="nav-item">
                    <?php if ($isAdmin): ?>
                        <a href="dashboard.php" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                            <span class="pcoded-mtext">Dashboard</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($isAlcalde): ?>
                        <a href="departamentos.php?pilar=<?php echo Util::getIdentificadorPilarPrincipal(); ?>" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                            <span class="pcoded-mtext">Dashboard</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($isSecretario): ?>
                        <a href="dash_secretarias.php" class="nav-link">
                            <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                            <span class="pcoded-mtext">Dashboard</span>
                        </a>
                    <?php endif; ?>
                </li>
                <?php if ($isAdmin): ?>
                    <li class="nav-item pcoded-menu-caption">
                        <label>Configuración General</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-settings"></i></span><span class="pcoded-mtext">Configuración
                                General</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="configuracion.php">Configuración</a></li>
                            <li><a href="usuarios.php">Usuarios</a></li>
                            <li><a href="linea.php">Lineas Gestión social</a></li>
                            <li><a href="estrategia.php">Estrategias</a></li>
                            <li><a href="acciong.php">Acciones Gestión social</a></li>
                            <li><a href="usuarios_session.php">Sesión Usuarios</a></li>
                            <li><a href="secretarias.php">Secretarias y Entidades</a></li>
                            <li><a href="ministerios.php">Ministerios y Entidades</a></li>
                            <li><a href="conf_puntajes.php">Config puntajes</a></li>
                            <li><a href="conf_puntajes_secretarias.php">Config puntajes secretaría</a></li>
                        </ul>
                    </li>

                <?php endif; ?>
                <?php if ($isAdmin || $isSecretario): ?>

                    <li class="nav-item pcoded-menu-caption">
                        <label>Registro Visitas Gobernador</label>
                    </li>


                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-map"></i></span><span class="pcoded-mtext">Mapa
                                Visitas</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="mapa_visitas_departamento.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Departamento</a></li>
                        </ul>
                    </li>

                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-users"></i></span><span class="pcoded-mtext">Registro
                                Visitas</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="informacion_visitas.php">Ingreso Visitas</a></li>
                            <li><a href="cuadro-control-visitas.php">Cuadro Control Visitas</a></li>
                            <li><a href="cuadro-control-compromisos.php">Cuadro Control compromisos</a></li>
                            <li><a href="cuadro-control-compromisos-aprobacion.php">Aprobación compromisos</a></li>
                            <li><a href="gestion-cumplimiento.php">Gestión cumplimiento</a></li>
                            <li><a href="visor_gestion_compromisos.php">Visor Gestión compromisos</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                    <li class="nav-item pcoded-menu-caption">
                        <label>Gestión social</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-file-text"></i></span><span class="pcoded-mtext">Gestión social</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="gestora_social.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Actividades</a></li>
                            <li><a href="visitasgestora.php">Registro Actividades</a></li>
                            <li><a href="cuadro_control_visitasg.php">Cuadro Control Actividades</a></li>
                        </ul>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-file-text"></i></span><span class="pcoded-mtext">Gestion Social2</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="aspasactividades.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Actividades</a></li>
                            <li><a href="visitasaspas.php">Registro Actividades</a></li>
                            <li><a href="cuadro_control_visitasaspas.php">Cuadro Control Actividades</a></li>
                        </ul>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-file-text"></i></span><span class="pcoded-mtext">Mapa Comparativo</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="mapa_comparativo.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Mapa Comparativo</a></li>
                        </ul>
                    </li>

                    <li class="nav-item pcoded-menu-caption">
                        <label>Plan Desarrollo</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-layers"></i></span><span class="pcoded-mtext">Plan de
                                Desarrollo</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="plan_desarrollo.php">Metas</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isSecretario): ?>
                    <li class="nav-item pcoded-menu-caption">
                        <label>Secretarias</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-folder"></i></span><span class="pcoded-mtext">Información
                                Secretarias</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="secretaria.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>&secretaria=<?php echo Util::getSecretariaPrincipal(); ?>">Resumen Secretarias</a></li>
                            <li><a href="ingreso_pae.php">Información Pae</a></li>
                            <li><a href="hacienda.php">Información Hacienda</a></li>
                            <li><a href="bienes.php">Información Administrativa</a></li>
                            <li><a href="pae_dash.php?mun=<?php echo 'todos'; ?>">Dashboard Pae</a></li>
                            <li><a href="tic.php">Información Tic</a></li>
                            <li><a href="tic_dash.php?mun=<?php echo Util::getCodigoMunicipioPrincipal(); ?>&opcion=<?php echo Util::getOpcionPrincipalTIC(); ?>">Dashboard Tic</a></li>
                            <li><a href="proyectos_secretarias.php">Ingreso Proyectos Secretarias</a></li>
                            <li><a href="proyectos_seguimiento_secretarias.php">Seguimiento Proyectos Secretarias</a></li>
                            <li><a href="dash_secretarias.php">Dashboard Secretarias</a></li>

                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isAlcalde || $isSecretario): ?>
                    <li class="nav-item pcoded-menu-caption">
                        <label>Alcaldías</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-clipboard"></i></span><span class="pcoded-mtext">Proyectos
                                Alcaldías</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="resumenalcaldias.php?<?php
                                                                if ($isAlcalde) {
                                                                    echo 'mun=' . urlencode($municipioUsuarioLogueado);
                                                                } else {
                                                                    echo 'secretaria=' . urlencode(Util::getSecretariaPrincipal());
                                                                }
                                                                ?>">Resumen alcaldías</a></li>
                        </ul>
                        <ul class="pcoded-submenu">
                            <li><a href="proyectos_alcaldias.php">Ingreso Proyectos</a></li>
                        </ul>
                        <ul class="pcoded-submenu">
                            <li><a href="proyectos_seguimiento_alcaldias.php">Seguimiento Proyectos</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isSecretario || $isAlcalde): ?>

                    <li class="nav-item pcoded-menu-caption">
                        <label>Acción Unificada</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-globe"></i></span><span class="pcoded-mtext">Acción
                                Unificada</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="departamentos.php?pilar=<?php echo Util::getIdentificadorPilarPrincipal(); ?>">Estado
                                    Departamento</a></li>
                            <li><a
                                    href="municipios.php?mun=<?php echo Util::getCodigoMunicipioPrincipal(); ?>&dep=<?php echo Util::getDepartamentoPrincipal(); ?>&pilar=<?php echo Util::getIdentificadorPilarPrincipal(); ?>">Estado
                                    Municipios</a></li>
                            <li><a href="veredas_criticas.php">Veredas Criticas</a></li>
                            <li><a href="avances.php">Avances</a></li>
                            <li><a href="imagenes.php">Imágenes</a></li>
                            <li><a href="accionunificada.php">Acción unificada</a></li>
                            <li><a href="informes.php">Informes</a></li>
                        </ul>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-refresh-ccw"></i></span><span class="pcoded-mtext">Configuración
                                Acción Unificada</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="areas.php">Ingreso Áreas</a></li>
                            <li><a href="ingreso_factores.php">Ingreso Factores</a></li>
                            <li><a href="actores.php">Ingreso Actores</a></li>
                            <li><a href="ingreso_informacion.php">Ingreso Información</a></li>
                            <li><a href="ingreso_informacion_listado.php">Listado Ingreso Información</a></li>
                            <li><a href="actualizacion_informacion.php">Actualización Información</a></li>

                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isSecretario): ?>

                    <li class="nav-item pcoded-menu-caption">
                        <label>Información Policía</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-globe"></i></span><span class="pcoded-mtext">Información</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="informacion-policia.php">Informes Policía</a></li>
                            <li><a href="graficos-policia.php">Gráficos Policía</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isSecretario): ?>
                    <li class="nav-item pcoded-menu-caption">
                        <label>Proyectos Estratégicos</label>
                    </li>
                    <li class="nav-item pcoded-hasmenu">
                        <a href="#!" class="nav-link "><span class="pcoded-micon"><i
                                    class="feather icon-crosshair"></i></span><span class="pcoded-mtext">Proyectos Estratégicos</span></a>
                        <ul class="pcoded-submenu">
                            <li><a href="secretaria_estrategicos.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Departamento</a></li>
                            <li><a href="ingreso_estrategicos.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Ingreso Información</a></li>
                            <li><a href="actualizacion_estrategicos.php?depto_id=<?php echo Util::getIdentificadorDepartamentoPrincipal(); ?>">Actualización Información</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<!-- <div class="boton-ia-container">
    <button type="button" class="btn btn-icon btn-info boton-ia" onclick="togglebot()">
        <img src="assets/images/iaicono.gif" alt="IA Icono" class="ia-gif-boton">
    </button>
</div> -->
<style>
    .pcoded-navbar::-webkit-scrollbar {
        width: 6px;
    }

    .pcoded-navbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .pcoded-navbar::-webkit-scrollbar-thumb {
        background-color: #4ca8f0;
        border-radius: 10px;
        height: 10px !important;
    }

    .pcoded-navbar::-webkit-scrollbar-thumb:hover {
        background-color: #3490dc;
    }

    /* Firefox */
    .pcoded-navbar {
        scrollbar-width: thin;
        scrollbar-color: rgb(123, 159, 198) transparent;
    }

    .user-profile {
        background: #234162;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;

        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        padding: 20px 10px;
        color: white;
    }


    /* .user-profile .profile-img img {
    border: 2px solid white;
    box-shadow: 0 0 5px rgba(0,0,0,0.3);
} */

    /* Estilo cuando el menú está minimizado */
    body.menu-minimized .pcoded-navbar {
        width: 80px !important;
        overflow-x: hidden;
        transition: width 0.3s ease;
    }

    body.menu-minimized .pcoded-navbar .pcoded-mtext,
    body.menu-minimized .pcoded-navbar .pcoded-menu-caption,
    body.menu-minimized .pcoded-navbar .pcoded-submenu {
        display: none !important;
    }

    body.menu-minimized .pcoded-navbar .nav-link {
        text-align: center;
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }

    body.menu-minimized .pcoded-navbar .pcoded-micon {
        margin: 0 auto !important;
        display: block !important;
        font-size: 1.3rem;
    }

    .menu-toggle-wrapper {
        position: sticky;
        top: 0;
        background-color: #35434e;
        z-index: 100;
        text-align: right;
        padding: 8px 10px;
    }

    .menu-toggle-btn {
        background: none;
        border: none;
        color: #ffffff;
        font-size: 1.4rem;
        cursor: pointer;
    }

    .menu-toggle-btn:hover {
        color: #4ca8a9;
    }

    body.menu-minimized .menu-toggle-btn i {
        transform: rotate(180deg);
    }

    .menu-toggle-btn i {
        font-size: 1.2rem;
        transition: transform 0.2s ease;
    }

    .menu-toggle-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: transparent;
        border: none;
        padding: 0;
        margin: 0;
    }

    #menuToggleBtn {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 10px auto;
        width: 20px;
        height: 20px;
        background-color: rgba(62, 92, 3, 1);
        border-radius: 50%;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    #menuToggleBtn:hover {
        background-color: #d01a1aff;
    }

    /* Ajuste de CSS DIRECTO DE MENU/SUBMENU */

    /* Scroll vertical */
    .navbar-content {
        position: relative;

        overflow-y: auto;
        scrollbar-width: thin;
        transition: bottom 0.3s ease;
    }

    .pcoded-inner-navbar .nav-item,
    .pcoded-inner-navbar .pcoded-menu-caption,
    .pcoded-inner-navbar .pcoded-submenu li {
        margin-top: -5px !important;
        margin-bottom: 0 !important;
    }

    .pcoded-inner-navbar .pcoded-menu-caption {
        margin-top: -8px !important;
        margin-bottom: 5px !important;
        font-size: 14px !important;
        font-weight: bold !important;
    }


    .pcoded-inner-navbar .pcoded-submenu {
        margin: 0 !important;
        padding: 5px !important;
        width: auto !important;
        max-width: 400px !important;
        /* background-color: rgb(20 37 53); */
        border-radius: 5px;
        overflow: hidden !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
    }

    /* Cambia el color del texto*/
    .pcoded-inner-navbar .nav-item .nav-link:hover {
        color: #4ca8a9 !important;
        text-decoration: none !important;
    }

    .pcoded-inner-navbar .pcoded-submenu {
        margin-top: -3px !important;
        padding: 0 !important;
    }

    .pcoded-inner-navbar .pcoded-submenu .nav-link {
        display: block !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
        max-width: 100% !important;
    }

    /* Efecto de "salto hacia adelante" */
    /*    .pcoded-inner-navbar .nav-item .nav-link:hover {
        transform: scale(1.1);
    } */

    /* Ajustes para pantallas pequeñas */
    @media (max-width: 768px) {
        .pcoded-inner-navbar .pcoded-submenu {
            max-width: 300px !important;
            font-size: 12px !important;
            padding: 3px !important;
        }

        .pcoded-inner-navbar .pcoded-submenu .nav-link {
            font-size: 15px !important;
            padding: 4px 8px !important;
        }

        .pcoded-inner-navbar .pcoded-menu-caption {
            font-size: 12px !important;
            margin-top: -5px !important;
            margin-bottom: 5px !important;
        }


    }

    /* Ajusta el padding del contenido principal para que no se sobreponga con el navbar inferior */
    @media (max-width: 991px) {
        .content {
            padding-bottom: 70px;
            /* Ajusta segun la altura del navbar movil */
        }
    }

    /* Estilo para el navbar móvil fijo inferior */
    .mobile-navbar {
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1030;
        border-radius: 30px;
        margin: 10px auto;
        width: 95%;
        max-width: 500px;
        padding: 5px 0;
        background-color: #fff;
    }

    .mobile-nav-link {
        color: #777;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        padding: 5px;
    }

    .mobile-nav-link.active {
        color: #31374a;
        font-weight: bold;
    }

    .mobile-nav-link i {
        display: block;
        font-size: 1.3rem;
        margin-bottom: 2px;
    }

    /*Oculta el menu mobile predetermianda de la plantilla*/
    .pcoded-header .mobile-menu {
        display: none !important;
    }

    @media (max-width: 990px) {
        .boton-ia-container {
            width: 400px;
            right: -425px;
            bottom: 115px;
        }

        .boton-ia img,
        .ia-gif-boton {
            width: 400px;
        }

        .bot-popup {
            bottom: 70px;
            right: 10px;
            width: 90%;
            z-index: 900;
        }
    }

    @media (min-width: 1221px) {
        .mobile-navbar {
            display: none !important;
        }
    }

    @media (max-width: 1220px) {
        .pcoded-navbar {
            display: none !important;
        }
    }
</style>
<script>
    function toggleMenu() {
        const body = document.body;
        const btn = document.getElementById("menuToggleBtn");
        body.classList.toggle("menu-minimized");

        // Cambiar ícono según el estado
        if (body.classList.contains("menu-minimized")) {
            btn.innerHTML = '<i class="feather icon-chevrons-right" title="Maximizar menú"></i>';
        } else {
            btn.innerHTML = '<i class="feather icon-chevrons-left" title="Minimizar menú"></i>';
        }
    }
</script>

<!-- INICIO MENU DE CELULAR -->
<nav class="mobile-navbar fixed-bottom">
    <div class="container">
        <div class="row py-2-movil">
            <div class="col text-center">
                <a href="dashboard.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="feather icon-home"></i>
                    <span class="d-block small">Inicio</span>
                </a>
            </div>
            <div class="col text-center">
                <a href="menu_mobile.php" class="mobile-nav-link" id="toggleMobileMenu">
                    <i class="feather icon-menu"></i>
                    <span class="d-block small">Menú</span>
                </a>
            </div>
            <div class="col text-center">
                <a href="logout.php" class="mobile-nav-link">
                    <i class="feather icon-log-out"></i>
                    <span class="d-block small">Cerrar sesión</span>
                </a>
            </div>
        </div>
    </div>
</nav>