<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >

    <title>Condiciones del Servicio | UTS Mujer</title>

    <meta
        name="description"
        content="Condiciones del servicio de la plataforma UTS Mujer."
    >

    <style>

        :root {
            --verde: #C3D730;
            --verde-oscuro: #9eaf1d;
            --texto: #242424;
            --texto-secundario: #5f6368;
            --fondo: #f6f7f8;
            --blanco: #ffffff;
            --borde: #e5e7eb;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;

            background: var(--fondo);
            color: var(--texto);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        /* ==========================================
           HEADER
        ========================================== */

        header {
            background: var(--blanco);
            border-bottom: 4px solid var(--verde);
            padding:
                max(18px, env(safe-area-inset-top))
                20px
                18px;
        }

        .header-content {
            max-width: 900px;
            margin: auto;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            max-height: 65px;
            max-width: 190px;
            object-fit: contain;
        }

        .header-title {
            text-align: right;
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--texto);
        }

        .header-title p {
            font-size: 14px;
            color: var(--texto-secundario);
        }

        /* ==========================================
           CONTENIDO
        ========================================== */

        main {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 18px 50px;
        }

        .card {
            background: var(--blanco);
            border: 1px solid var(--borde);
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .intro {
            background: rgba(195, 215, 48, 0.13);
            border-left: 5px solid var(--verde);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 30px;
        }

        h2 {
            font-size: 20px;
            margin-top: 35px;
            margin-bottom: 12px;
            color: var(--texto);
        }

        h2:first-of-type {
            margin-top: 0;
        }

        h3 {
            font-size: 17px;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        p {
            margin-bottom: 14px;
        }

        ul {
            padding-left: 22px;
            margin: 12px 0 20px;
        }

        li {
            margin-bottom: 8px;
        }

        strong {
            color: #151515;
        }

        .important {
            background: #fffbea;
            border: 1px solid #f2df94;
            border-radius: 12px;
            padding: 18px;
            margin: 20px 0;
        }

        .emergency {
            background: #fff3f3;
            border: 1px solid #f1c4c4;
            border-radius: 12px;
            padding: 18px;
            margin: 20px 0;
        }

        .date {
            font-size: 14px;
            color: var(--texto-secundario);
            margin-bottom: 25px;
        }

        a {
            color: #748400;
            font-weight: 600;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* ==========================================
           FOOTER
        ========================================== */

        footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--texto-secundario);
            font-size: 13px;
        }

        footer strong {
            display: block;
            margin-bottom: 5px;
        }

        /* ==========================================
           CELULAR
        ========================================== */

        @media (max-width: 600px) {

            header {
                padding-left: 15px;
                padding-right: 15px;
            }

            .header-content {
                flex-direction: column;
                justify-content: center;
                text-align: center;
                gap: 10px;
            }

            .header-title {
                text-align: center;
            }

            .header-title h1 {
                font-size: 20px;
            }

            .logo {
                max-height: 55px;
            }

            main {
                margin-top: 18px;
                padding-left: 12px;
                padding-right: 12px;
            }

            .card {
                padding: 22px 18px;
                border-radius: 14px;
            }

            h2 {
                font-size: 18px;
            }

            p,
            li {
                font-size: 15px;
            }

        }

    </style>
</head>

<body>

<header>

    <div class="header-content">

        <!-- Logo ubicado en la misma carpeta -->
        <img
            src="logo.png"
            alt="UTS Mujer"
            class="logo"
        >

        <div class="header-title">
            <h1>Condiciones del Servicio</h1>
            <p>UTS Mujer</p>
        </div>

    </div>

</header>


<main>

    <article class="card">

        <p class="date">
            Última actualización: 11 de agosto de 2026
        </p>


        <div class="intro">

            <p>
                Bienvenida a <strong>UTS Mujer</strong>.
                Estas Condiciones del Servicio regulan el acceso y uso de
                la plataforma, sus herramientas digitales, servicios de
                orientación y funcionalidades asociadas.
            </p>

            <p>
                Al acceder, registrarse o utilizar UTS Mujer, la usuaria
                declara haber leído y comprendido estas condiciones.
            </p>

        </div>


        <!-- 1 -->

        <h2>1. ¿Qué es UTS Mujer?</h2>

        <p>
            UTS Mujer es una plataforma tecnológica orientada a brindar
            herramientas de información, prevención, orientación y
            acompañamiento a integrantes de la comunidad de las
            Unidades Tecnológicas de Santander, con especial enfoque
            en mujeres y población diversa.
        </p>

        <p>
            La plataforma puede ofrecer diferentes herramientas
            digitales destinadas a facilitar el acceso a información,
            orientación y canales de apoyo.
        </p>


        <!-- 2 -->

        <h2>2. Servicios disponibles</h2>

        <p>
            Dependiendo de la versión y de las funcionalidades habilitadas,
            UTS Mujer podrá incluir, entre otros:
        </p>

        <ul>
            <li>Orientación general sobre situaciones de riesgo o vulnerabilidad.</li>

            <li>Información relacionada con rutas institucionales de atención.</li>

            <li>Orientación jurídica de carácter informativo.</li>

            <li>Orientación psicológica inicial y contenidos de apoyo.</li>

            <li>Orientación desde trabajo social.</li>

            <li>Herramientas asistidas mediante inteligencia artificial.</li>

            <li>Registro y seguimiento de determinados reportes realizados mediante la plataforma.</li>

            <li>Configuración de contactos de emergencia.</li>

            <li>
                Envío de notificaciones o mensajes a contactos de emergencia
                cuando la usuaria active determinadas funcionalidades.
            </li>

            <li>
                Acceso a información relacionada con entidades,
                líneas de atención y mecanismos institucionales de apoyo.
            </li>
        </ul>


        <!-- 3 -->

        <h2>3. Uso de inteligencia artificial</h2>

        <p>
            Algunas funcionalidades de UTS Mujer pueden utilizar sistemas
            de inteligencia artificial para facilitar la interacción,
            proporcionar información, orientar consultas y ayudar a las
            usuarias a identificar posibles rutas de atención.
        </p>

        <div class="important">

            <strong>Importante:</strong>

            <p style="margin-top:10px; margin-bottom:0;">
                Las respuestas generadas mediante inteligencia artificial
                tienen carácter informativo y de orientación y no reemplazan
                la atención directa de profesionales, autoridades,
                organismos de emergencia o instituciones competentes.
            </p>

        </div>


        <!-- 4 -->

        <h2>4. Situaciones de emergencia</h2>

        <p>
            UTS Mujer incluye herramientas diseñadas para facilitar el acceso
            a mecanismos de apoyo; sin embargo, la disponibilidad de la
            plataforma puede depender de factores tecnológicos, de conectividad,
            disponibilidad de servicios externos y funcionamiento del dispositivo
            utilizado por la usuaria.
        </p>

        <div class="emergency">

            <strong>En caso de una emergencia inmediata:</strong>

            <p style="margin-top:10px;">
                Si existe un peligro inmediato para la vida, integridad o
                seguridad de una persona, la usuaria debe contactar directamente
                a las autoridades y servicios de emergencia disponibles.
            </p>

            <p style="margin-bottom:0;">
                En Colombia, la línea general de emergencias es
                <strong>123</strong>.
            </p>

        </div>

        <p>
            El uso de UTS Mujer no debe retrasar la solicitud de ayuda
            directa a las autoridades cuando exista una situación de
            emergencia.
        </p>


        <!-- 5 -->

        <h2>5. Contactos de emergencia</h2>

        <p>
            La plataforma puede permitir que la usuaria registre una o varias
            personas como contactos de emergencia.
        </p>

        <p>
            Al registrar un contacto, la usuaria declara que la información
            proporcionada es correcta y que cuenta con autorización o una
            relación legítima que permita utilizar ese dato para las finalidades
            asociadas al servicio.
        </p>

        <p>
            Cuando se active una funcionalidad de acompañamiento o alerta,
            UTS Mujer podrá enviar al contacto registrado un mensaje
            solicitándole que se comunique con la usuaria o le brinde
            acompañamiento.
        </p>


        <!-- 6 -->

        <h2>6. Registro e información de la usuaria</h2>

        <p>
            Para utilizar determinadas funcionalidades puede ser necesario
            proporcionar algunos datos.
        </p>

        <p>
            La usuaria se compromete a proporcionar información veraz,
            actualizada y suficiente cuando esta sea requerida para prestar
            el servicio.
        </p>

        <p>
            UTS Mujer procurará solicitar únicamente aquella información
            necesaria para la prestación de sus funcionalidades o aquella
            que la usuaria decida proporcionar voluntariamente.
        </p>


        <!-- 7 -->

        <h2>7. Privacidad y protección de datos</h2>

        <p>
            El tratamiento de información personal realizado mediante
            UTS Mujer se encuentra sujeto a su Política de Privacidad y
            a las normas colombianas aplicables sobre protección de
            datos personales.
        </p>

        <p>
            La información suministrada será utilizada para las finalidades
            informadas a la usuaria y relacionadas con la prestación,
            seguridad, funcionamiento y mejoramiento de la plataforma.
        </p>

        <p>
            Para conocer con mayor detalle cómo se recopila, utiliza,
            almacena y protege la información, consulte nuestra
            <strong>Política de Privacidad</strong>.
        </p>


        <!-- 8 -->

        <h2>8. Seguridad de la cuenta</h2>

        <p>
            La usuaria es responsable de mantener bajo su control sus
            mecanismos de acceso a la plataforma y de adoptar medidas
            razonables para impedir que terceros utilicen su cuenta sin
            autorización.
        </p>

        <p>
            Si identifica un uso no autorizado de su información o cuenta,
            deberá comunicarlo a los canales de soporte habilitados.
        </p>


        <!-- 9 -->

        <h2>9. Uso adecuado de la plataforma</h2>

        <p>
            La usuaria se compromete a utilizar UTS Mujer únicamente para
            finalidades legítimas.
        </p>

        <p>No está permitido:</p>

        <ul>

            <li>Realizar reportes deliberadamente falsos.</li>

            <li>Suplantar la identidad de otra persona.</li>

            <li>
                Intentar acceder sin autorización a información,
                sistemas o cuentas de terceros.
            </li>

            <li>
                Utilizar la plataforma para amenazar, acosar,
                discriminar o perjudicar a otras personas.
            </li>

            <li>
                Introducir código malicioso o intentar afectar
                la seguridad de la plataforma.
            </li>

            <li>
                Utilizar los servicios para actividades contrarias
                a la legislación colombiana.
            </li>

        </ul>


        <!-- 10 -->

        <h2>10. Disponibilidad del servicio</h2>

        <p>
            Se realizarán esfuerzos razonables para mantener UTS Mujer
            disponible y funcionando correctamente.
        </p>

        <p>
            No obstante, pueden producirse interrupciones temporales por
            mantenimiento, actualizaciones, problemas de conectividad,
            fallas de proveedores tecnológicos o situaciones fuera del
            control de los responsables de la plataforma.
        </p>


        <!-- 11 -->

        <h2>11. Servicios de terceros</h2>

        <p>
            Algunas funciones pueden depender o integrarse con servicios
            tecnológicos proporcionados por terceros, tales como servicios
            de mensajería, inteligencia artificial, infraestructura en la
            nube, autenticación u otros mecanismos tecnológicos.
        </p>

        <p>
            El funcionamiento de estas integraciones también puede encontrarse
            sujeto a las condiciones y disponibilidad de dichos proveedores.
        </p>


        <!-- 12 -->

        <h2>12. Información jurídica, psicológica y social</h2>

        <p>
            Las herramientas de orientación disponibles a través de UTS Mujer
            tienen como objetivo facilitar información inicial y ayudar a
            identificar posibles mecanismos de apoyo.
        </p>

        <p>
            Estas herramientas no reemplazan una consulta profesional
            individualizada cuando las circunstancias requieran atención
            especializada.
        </p>


        <!-- 13 -->

        <h2>13. Suspensión del acceso</h2>

        <p>
            Se podrá limitar o suspender el acceso a determinadas
            funcionalidades cuando se detecte un uso fraudulento,
            abusivo, contrario a estas condiciones o que pueda comprometer
            la seguridad de otras personas o de la plataforma.
        </p>


        <!-- 14 -->

        <h2>14. Eliminación de información</h2>

        <p>
            Las usuarias podrán solicitar la eliminación de sus datos
            personales conforme a los procedimientos indicados en la
            Política de Privacidad y en la página de instrucciones de
            eliminación de datos de UTS Mujer.
        </p>

        <p>
            Determinada información podrá conservarse cuando exista una
            obligación legal, contractual, administrativa o de seguridad
            que justifique su conservación durante el periodo correspondiente.
        </p>


        <!-- 15 -->

        <h2>15. Modificaciones de estas condiciones</h2>

        <p>
            Estas Condiciones del Servicio podrán ser actualizadas cuando
            existan cambios legales, técnicos, operativos o funcionales
            relevantes.
        </p>

        <p>
            La versión vigente estará disponible permanentemente a través
            de esta página.
        </p>


        <!-- 16 -->

        <h2>16. Legislación aplicable</h2>

        <p>
            Estas condiciones se interpretarán de conformidad con la
            legislación de la República de Colombia y demás normas
            aplicables.
        </p>


        <!-- 17 -->

        <h2>17. Contacto</h2>

        <p>
            Para consultas relacionadas con estas Condiciones del Servicio,
            privacidad, funcionamiento de la plataforma o solicitudes
            relacionadas con datos personales, podrá comunicarse a través
            de los canales oficiales habilitados para UTS Mujer.
        </p>

        <!--
        CAMBIA ESTE CORREO POR EL CORREO OFICIAL
        -->

        <p>
            Correo electrónico:
            <a href="mailto:soporte@tudominio.com">
                soporte@tudominio.com
            </a>
        </p>


        <div class="important">

            <strong>Nota:</strong>

            <p style="margin-top:10px; margin-bottom:0;">
                Antes de publicar estas condiciones, debe reemplazarse el
                correo de contacto anterior por el correo institucional
                oficialmente autorizado para UTS Mujer.
            </p>

        </div>

    </article>

</main>


<footer>

    <strong>UTS Mujer</strong>

    <span>
        Orientación, prevención y acompañamiento.
    </span>

    <br>

    <span>
        © 2026. Todos los derechos reservados.
    </span>

</footer>

</body>
</html>