<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

date_default_timezone_set('America/Bogota');

$nombrePlataforma = 'Aliada UTS';
$fechaActualizacion = '2 de agosto de 2026';
$paginaEliminarCuenta = 'delete.php';
$paginaInicio = 'index.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#A41273">
    <meta name="description" content="Política de tratamiento y protección de datos personales de Aliada UTS.">

    <title>Privacidad y tratamiento de datos | <?= htmlspecialchars($nombrePlataforma, ENT_QUOTES, 'UTF-8'); ?></title>

    <style>
        :root {
            color-scheme: light;
            --primary: #A41273;
            --primary-dark: #75084F;
            --primary-soft: #FBEAF5;
            --accent: #D81B9C;
            --text: #202534;
            --muted: #606778;
            --border: #E8DDE5;
            --surface: #FFFFFF;
            --background: #F8F5F7;
            --success: #116A4B;
            --success-soft: #EAF8F2;
            --warning: #7A4A00;
            --warning-soft: #FFF6DF;
            --shadow: 0 18px 50px rgba(84, 17, 63, 0.11);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            min-width: 280px;
            background:
                radial-gradient(circle at top left, rgba(216, 27, 156, 0.09), transparent 31rem),
                var(--background);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.65;
        }

        a {
            color: var(--primary);
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid rgba(216, 27, 156, 0.3);
            outline-offset: 3px;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid rgba(232, 221, 229, 0.88);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
        }

        .topbar-inner {
            width: min(1120px, calc(100% - 32px));
            min-height: 66px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            color: var(--primary-dark);
            font-size: 1rem;
            font-weight: 800;
            text-decoration: none;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--primary-dark));
            color: #FFFFFF;
            box-shadow: 0 8px 20px rgba(164, 18, 115, 0.22);
        }

        .back-link {
            min-height: 42px;
            padding: 9px 15px;
            border: 1px solid var(--border);
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            background: #FFFFFF;
            font-size: 0.92rem;
            font-weight: 750;
            text-decoration: none;
        }

        .page {
            width: min(960px, calc(100% - 32px));
            margin: 42px auto;
        }

        .hero {
            padding: clamp(25px, 5vw, 48px);
            border: 1px solid var(--border);
            border-radius: 28px;
            background: linear-gradient(145deg, #FFFFFF 0%, #FFF8FC 100%);
            box-shadow: var(--shadow);
        }

        .eyebrow {
            margin: 0 0 10px;
            color: var(--primary);
            font-size: 0.78rem;
            font-weight: 850;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 720px;
            margin: 0;
            color: var(--primary-dark);
            font-size: clamp(2rem, 7vw, 3.55rem);
            line-height: 1.08;
            letter-spacing: -0.045em;
        }

        .lead {
            max-width: 760px;
            margin: 20px 0 0;
            color: var(--muted);
            font-size: clamp(1rem, 2.2vw, 1.15rem);
        }

        .updated {
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .summary {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .summary-card {
            min-height: 126px;
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--surface);
        }

        .summary-icon {
            width: 36px;
            height: 36px;
            margin-bottom: 12px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 900;
        }

        .summary-card strong {
            display: block;
            margin-bottom: 4px;
            color: var(--primary-dark);
        }

        .summary-card span {
            display: block;
            color: var(--muted);
            font-size: 0.91rem;
            line-height: 1.45;
        }

        .content {
            margin-top: 24px;
            padding: clamp(22px, 5vw, 45px);
            border: 1px solid var(--border);
            border-radius: 28px;
            background: var(--surface);
            box-shadow: 0 12px 35px rgba(84, 17, 63, 0.07);
        }

        section + section {
            margin-top: 38px;
            padding-top: 34px;
            border-top: 1px solid var(--border);
        }

        h2 {
            margin: 0 0 13px;
            color: var(--primary-dark);
            font-size: clamp(1.25rem, 3vw, 1.65rem);
            line-height: 1.25;
        }

        h3 {
            margin: 22px 0 8px;
            color: var(--text);
            font-size: 1.02rem;
        }

        p {
            margin: 0 0 14px;
        }

        ul {
            margin: 12px 0 0;
            padding-left: 22px;
        }

        li + li {
            margin-top: 8px;
        }

        .notice {
            margin-top: 20px;
            padding: 18px 20px;
            border-left: 4px solid var(--success);
            border-radius: 14px;
            background: var(--success-soft);
            color: #164C3A;
        }

        .notice.warning {
            border-left-color: #D18A00;
            background: var(--warning-soft);
            color: var(--warning);
        }

        .delete-box {
            padding: clamp(22px, 4vw, 32px);
            border: 1px solid #EFC8DF;
            border-radius: 22px;
            background: linear-gradient(145deg, #FFF8FC, #FFFFFF);
            text-align: center;
        }

        .delete-box p {
            max-width: 650px;
            margin: 0 auto 20px;
            color: var(--muted);
        }

        .delete-button {
            width: fit-content;
            min-height: 48px;
            padding: 12px 21px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            background: linear-gradient(135deg, var(--accent), var(--primary-dark));
            color: #FFFFFF;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(164, 18, 115, 0.24);
        }

        .footer {
            padding: 28px 16px calc(28px + env(safe-area-inset-bottom));
            color: var(--muted);
            font-size: 0.88rem;
            text-align: center;
        }

        @media (max-width: 760px) {
            .page {
                margin-top: 24px;
            }

            .summary {
                grid-template-columns: 1fr;
            }

            .summary-card {
                min-height: auto;
            }
        }

        @media (max-width: 520px) {
            body {
                font-size: 15.5px;
            }

            .topbar-inner,
            .page {
                width: min(100% - 22px, 960px);
            }

            .brand-text {
                display: none;
            }

            .back-link {
                padding-inline: 13px;
            }

            .hero,
            .content {
                border-radius: 21px;
            }

            .delete-button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
        }

        .login-button {
    min-height: 46px;
    padding: 11px 20px;
    border: 0;
    border-radius: 999px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;

    color: #ffffff;
    background: linear-gradient(135deg, #d81b9c, #75084f);

    font-size: 0.95rem;
    font-weight: 800;
    text-decoration: none;

    box-shadow: 0 10px 24px rgba(164, 18, 115, 0.28);
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.login-button:hover {
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(164, 18, 115, 0.36);
}

.login-button:active {
    transform: scale(0.98);
}

.login-button:focus-visible {
    outline: 3px solid rgba(216, 27, 156, 0.3);
    outline-offset: 3px;
}

@media (max-width: 520px) {
    .login-button {
        min-height: 44px;
        padding: 10px 15px;
        font-size: 0.88rem;
    }
}
    </style>
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="<?= htmlspecialchars($paginaInicio, ENT_QUOTES, 'UTF-8'); ?>">
                <span class="brand-mark" aria-hidden="true">A</span>
                <span class="brand-text"><?= htmlspecialchars($nombrePlataforma, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>

           <a
                class="login-button"
                href="login.php"
                aria-label="Ir al inicio de sesión"
            >
                <span aria-hidden="true">🔐</span>
                Registrarme
            </a>
        </div>
    </header>

    <main class="page">
        <article>
            <header class="hero">
                <p class="eyebrow">Privacidad y protección</p>
                <h1>Tratamiento de datos personales</h1>
                <p class="lead">
                    En <?= htmlspecialchars($nombrePlataforma, ENT_QUOTES, 'UTF-8'); ?> tratamos tu información con respeto, seguridad y finalidades limitadas. No vendemos tus datos ni los utilizamos para publicidad.
                </p>
                <p class="updated">Última actualización: <?= htmlspecialchars($fechaActualizacion, ENT_QUOTES, 'UTF-8'); ?></p>
            </header>

            <div class="summary" aria-label="Resumen de privacidad">
                <div class="summary-card">
                    <div class="summary-icon" aria-hidden="true">✓</div>
                    <strong>No vendemos tus datos</strong>
                    <span>No se comercializan ni se utilizan para campañas publicitarias.</span>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" aria-hidden="true">⚿</div>
                    <strong>Acceso restringido</strong>
                    <span>La información se protege mediante controles razonables de seguridad.</span>
                </div>

                <div class="summary-card">
                    <div class="summary-icon" aria-hidden="true">×</div>
                    <strong>Tú tienes el control</strong>
                    <span>Puedes solicitar la eliminación de tu cuenta y tus datos.</span>
                </div>
            </div>

            <div class="content">
                <section aria-labelledby="responsable">
                    <h2 id="responsable">1. Responsable del tratamiento</h2>
                    <p>
                        <?= htmlspecialchars($nombrePlataforma, ENT_QUOTES, 'UTF-8'); ?> es responsable del tratamiento de los datos personales recolectados mediante esta plataforma, sin perjuicio de los encargados tecnológicos indispensables para su funcionamiento.
                    </p>
                    <p>
                        Esta política se aplica conforme al artículo 15 de la Constitución Política, la Ley 1581 de 2012 y sus normas reglamentarias compiladas en el Decreto 1074 de 2015.
                    </p>
                </section>

                <section aria-labelledby="datos">
                    <h2 id="datos">2. Información que podemos almacenar</h2>
                    <p>Según las funciones utilizadas, la plataforma puede almacenar:</p>
                    <ul>
                        <li>Datos de registro y contacto suministrados voluntariamente.</li>
                        <li>Información necesaria para autenticar y administrar la cuenta.</li>
                        <li>Consultas, mensajes y respuestas generadas dentro de la plataforma.</li>
                        <li>Datos técnicos y registros de seguridad, como fecha de acceso, navegador o dirección IP.</li>
                        <li>Información que la persona decida comunicar durante una solicitud de orientación.</li>
                    </ul>

                    <div class="notice warning">
                        Algunas conversaciones pueden incluir datos sensibles, como información de salud, situaciones de violencia, orientación sexual o identidad de género. Comparte solamente la información necesaria y evita incluir contraseñas, datos bancarios o documentos completos.
                    </div>
                </section>

                <section aria-labelledby="finalidades">
                    <h2 id="finalidades">3. Para qué utilizamos los datos</h2>
                    <p>Los datos se utilizan exclusivamente para:</p>
                    <ul>
                        <li>Crear, autenticar, proteger y administrar la cuenta.</li>
                        <li>Prestar las funciones de orientación y acompañamiento solicitadas.</li>
                        <li>Conservar el historial cuando esta función esté habilitada.</li>
                        <li>Prevenir fraudes, accesos no autorizados y fallas de seguridad.</li>
                        <li>Atender consultas, reclamos y solicitudes sobre datos personales.</li>
                        <li>Actuar ante una emergencia que represente un riesgo grave para la vida o integridad de la persona o de terceros, dentro de los límites permitidos por la ley.</li>
                        <li>Cumplir obligaciones legales u órdenes válidas de una autoridad competente.</li>
                    </ul>

                    <div class="notice">
                        Los datos no se venden, alquilan ni intercambian. Tampoco se utilizan para publicidad, mercadeo, perfilamiento comercial o fines distintos a los informados en esta política.
                    </div>
                </section>

                <section aria-labelledby="divulgacion">
                    <h2 id="divulgacion">4. Cuándo podría comunicarse información</h2>
                    <p>
                        La información no se comparte con terceros ajenos a la prestación del servicio. Solo podrá comunicarse de forma limitada cuando:
                    </p>
                    <ul>
                        <li>La persona lo autorice de manera previa, expresa e informada.</li>
                        <li>Sea indispensable para atender una emergencia grave y proteger la vida o integridad de la persona o de terceros.</li>
                        <li>Exista una obligación legal o una orden válida de autoridad competente.</li>
                        <li>Intervengan proveedores tecnológicos indispensables que actúen como encargados del tratamiento, sujetos a deberes de seguridad, confidencialidad y uso limitado.</li>
                    </ul>
                    <p>
                        En cualquier caso, se procurará comunicar únicamente la información estrictamente necesaria para la finalidad correspondiente.
                    </p>
                </section>

                <section aria-labelledby="ia">
                    <h2 id="ia">5. Uso de inteligencia artificial</h2>
                    <p>
                        La plataforma utiliza inteligencia artificial para generar orientación inicial. Las respuestas pueden contener errores y no sustituyen la atención profesional, médica, psicológica, jurídica ni de las autoridades.
                    </p>
                    <p>
                        No ingreses información que no sea necesaria para recibir orientación. En una emergencia, comunícate directamente con el 123 o con la autoridad competente.
                    </p>
                </section>

                <section aria-labelledby="seguridad">
                    <h2 id="seguridad">6. Almacenamiento y seguridad</h2>
                    <p>
                        Los datos pueden conservarse mientras la cuenta permanezca activa o durante el tiempo necesario para cumplir las finalidades informadas y las obligaciones legales aplicables.
                    </p>
                    <p>
                        Se aplican medidas administrativas, humanas y técnicas razonables para prevenir pérdida, alteración, consulta, uso o acceso no autorizado. Sin embargo, ningún sistema conectado a Internet puede garantizar seguridad absoluta.
                    </p>
                </section>

                <section aria-labelledby="derechos">
                    <h2 id="derechos">7. Tus derechos</h2>
                    <p>Como titular de los datos personales puedes:</p>
                    <ul>
                        <li>Conocer, consultar, actualizar y rectificar tus datos.</li>
                        <li>Solicitar prueba de la autorización, cuando corresponda.</li>
                        <li>Conocer el uso dado a tu información.</li>
                        <li>Revocar la autorización o solicitar la supresión, cuando legalmente proceda.</li>
                        <li>Presentar consultas o reclamos mediante los canales oficiales de <?= htmlspecialchars($nombrePlataforma, ENT_QUOTES, 'UTF-8'); ?>.</li>
                        <li>Presentar una queja ante la Superintendencia de Industria y Comercio después de agotar el trámite correspondiente ante el responsable.</li>
                    </ul>
                    <p>
                        La eliminación puede estar limitada cuando exista un deber legal o contractual de conservar determinada información.
                    </p>
                </section>

                <section aria-labelledby="menores">
                    <h2 id="menores">8. Niñas, niños y adolescentes</h2>
                    <p>
                        El tratamiento de datos de menores exige protección reforzada y respeto por sus derechos prevalentes. Cuando corresponda, deberá intervenir su representante legal y considerarse la opinión de la persona menor de edad según su madurez y capacidad de comprensión.
                    </p>
                </section>

                <section aria-labelledby="eliminacion">
                    <div class="delete-box">
                        <h2 id="eliminacion">Eliminar mi cuenta</h2>
                        <p>
                            Si deseas dejar de utilizar la plataforma, puedes ingresar al módulo de eliminación. Allí podrás confirmar la solicitud antes de borrar tu cuenta.
                        </p>
                        <a class="delete-button" href="<?= htmlspecialchars($paginaEliminarCuenta, ENT_QUOTES, 'UTF-8'); ?>">
                            Eliminar mi cuenta
                        </a>
                    </div>
                </section>

                <section aria-labelledby="cambios">
                    <h2 id="cambios">9. Cambios en esta política</h2>
                    <p>
                        Esta política podrá actualizarse por cambios legales, técnicos o funcionales. La versión vigente indicará su fecha de actualización y, cuando el cambio sea sustancial, se informará mediante la plataforma.
                    </p>
                </section>
            </div>
        </article>
    </main>

    <footer class="footer">
        © <?= date('Y'); ?> <?= htmlspecialchars($nombrePlataforma, ENT_QUOTES, 'UTF-8'); ?> · Protección de datos personales
    </footer>
</body>
</html>
