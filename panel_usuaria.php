<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

date_default_timezone_set('America/Bogota');

$pageTitle       = 'Aliada UTS | Acompañamiento y Protección';
$pageDescription = 'Plataforma Aliada UTS para orientar, acompañar y proteger a las mujeres de la comunidad universitaria.';
$currentYear     = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#D81B9C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <meta name="robots" content="index,follow">

    <link rel="icon" type="image/png" href="assets/img/icono.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/icono.png">
    <link rel="apple-touch-icon" href="assets/img/icono.png">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/panel_usuaria.css">
</head>

<body>
    <div class="loader-bg" id="pageLoader">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <main class="app-mobile">
        <div class="mobile-shell">

            <header class="hero-card">
                <div class="hero-inner">
                    <div class="hero-top">
                        <div class="logo-box">
                            <img src="assets/img/utslog.png" alt="Aliada UTS">
                        </div>

                        <div class="hero-copy">
                            <div class="eyebrow">Universitaria Tecnológica de Santander</div>
                            <div class="eyebrow">Aliada UTS</div>
                            <h1 class="hero-title">
                                ¿Cómo podemos <span>ayudarte?</span>
                            </h1>
                        </div>
                    </div>

                    <p class="hero-text">
                        Elige una opción para recibir orientación, acompañamiento o activar una ruta de apoyo.
                    </p>

                    <div class="quick-actions">
                        <a href="emergencia.php" class="quick-btn white">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Emergencia
                        </a>

                        <button type="button" class="quick-btn" onclick="window.history.back();">
                            <i class="fa-solid fa-arrow-left"></i>
                            Volver
                        </button>
                    </div>
                </div>
            </header>

            <section class="intro-panel">
                <h2 class="intro-title">Atención clara, rápida y segura</h2>
                <p class="intro-text">
                    Esta plataforma está diseñada para que encuentres ayuda sin complicaciones.
                    Toca el módulo que necesites y continúa con la orientación.
                </p>
            </section>

            <div class="section-title">
                <h2>Módulos de apoyo con IA</h2>
                <span>Toca una opción</span>
            </div>

            <section class="services-grid">

                <a href="asistencia_legal.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </div>
                            <h3 class="service-title">Orientación legal con IA</h3>
                            <span class="service-desc">Asistente inteligente para brindar guía jurídica y rutas de protección.</span>
                        </div>
                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

                <a href="asistencia_psicologica_v2.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <h3 class="service-title">Apoyo psicológico con IA</h3>
                            <span class="service-desc">Asistente inteligente para escuchar, orientar y acompañar de forma segura.</span>
                        </div>
                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

                <a href="asistencia_trabajo_social.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <i class="fa-solid fa-hands-holding-circle"></i>
                            </div>
                            <h3 class="service-title">IA Trabajo social</h3>
                            <span class="service-desc">Orientación rápida, humana y segura.</span>
                        </div>
                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

                <a href="asistente_legal.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <i class="fa-solid fa-rainbow"></i>
                            </div>
                            <h3 class="service-title">IA de orientación LGBTIQ+</h3>
                            <span class="service-desc">Asistente inteligente para escuchar, orientar y acompañar con respeto.</span>
                        </div>
                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

                <a href="maltrato.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <i class="fa-solid fa-hand"></i>
                            </div>
                            <h3 class="service-title">Reporte de maltrato</h3>
                            <span class="service-desc">Graba o toma Evidencias de Maltrato</span>
                        </div>
                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

                <a href="violencia_intrafamiliar.php" class="service-link">
                    <article class="service-card">
                        <div>
                            <div class="service-icon">
                                <i class="fa-solid fa-house-circle-exclamation"></i>
                            </div>
                            <h3 class="service-title">Violencia intrafamiliar</h3>
                            <span class="service-desc">Graba o toma Evidencias</span>
                        </div>
                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

                <a href="emergencia.php" class="service-link emergency-link">
                    <article class="service-card emergency">
                        <div class="service-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div class="emergency-copy">
                            <h3 class="service-title">Emergencia institucional</h3>
                            <span class="service-desc">
                                Usa esta opción si necesitas activar una alerta inmediata.
                            </span>
                        </div>

                        <div class="service-arrow">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </article>
                </a>

            </section>

            <section class="help-panel">
                <h3>¿No sabes cuál elegir?</h3>
                <p>
                    Empieza por apoyo psicológico o trabajo social. Desde allí podrán orientarte hacia la ruta adecuada.
                </p>
            </section>

    <footer class="footer-mobile">
    <p>
        © <?php echo $currentYear; ?> <strong>Aliada UTS</strong><br>
        Acompañamiento, orientación y protección para la comunidad universitaria.<br>
        Desarrollado por <strong>Helios Electronics</strong><br>

        <a
            href="https://helios.com.co"
            target="_blank"
            rel="noopener noreferrer"
        >
            helios.com.co
        </a>
    </p>
</footer>

        </div>
    </main>

    <nav class="bottom-nav">
        <button type="button" class="bottom-btn" onclick="window.history.back();">
            <i class="fa-solid fa-arrow-left"></i>
            Volver
        </button>

        <a href="emergencia.php" class="bottom-btn primary">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Emergencia
        </a>

        <a href="panel_usuaria.php" class="bottom-btn">
            <i class="fa-solid fa-house"></i>
            Inicio
        </a>
    </nav>

    <script>
        window.addEventListener('load', function () {
            const loader = document.getElementById('pageLoader');

            if (loader) {
                setTimeout(function () {
                    loader.classList.add('hide');
                }, 180);
            }
        });
    </script>
    <?php include __DIR__ . '/videollamada_banner.php'; ?>
</body>
</html>
