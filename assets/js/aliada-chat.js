/*
 * Chat propio de los asistentes migrados a Claude (Trabajo Social, Jurídica, LGBTQ+).
 * Basado en el JS de asistencia_psicologica_v2.php (que conserva su propia copia).
 * Uso: AliadaChat.iniciar({...}) — ver plantilla includes/plantilla_chat_asistente.php.
 */
(function () {
    'use strict';

    function iniciar(config) {
        const csrfToken = config.csrfToken;
        const AUDIO_STORAGE_KEY = config.audioStorageKey;
        const DURACION_MAXIMA_GRABACION_MS = 60000;
        const UMBRAL_VOLUMEN_VOZ = 0.02;
        const SILENCIO_MS_PARA_DETENER = 1300;
        const VELOCIDAD_ELEVENLABS = config.velocidadElevenLabs || 1.15;
        const VELOCIDAD_AUDIO_RESPUESTA = config.velocidadReproduccion || 1.08;
        const RETARDO_REACTIVAR_ESCUCHA_MS = 400;
        const MAX_CARACTERES_AUDIO = 1200;
        const ESPERA_AVISO_BUSQUEDA_MS = 8000;

        const listaMensajes = document.getElementById('chat-mensajes');
        const formulario = document.getElementById('chat-form');
        const campoTexto = document.getElementById('chat-texto');
        const botonEnviar = document.getElementById('btn-enviar');
        const btnMicrofono = document.getElementById('btn-microfono');
        const btnParlante = document.getElementById('btn-parlante');
        const voiceStatus = document.getElementById('alma-voice-status');

        let conversacionId = null;
        let esperandoRespuesta = false;
        let audioActivo = leerPreferenciaAudio();

        let grabadora = null;
        let fragmentosAudio = [];
        let grabando = false;
        let temporizadorLimiteGrabacion = null;
        let temporizadorEstado = null;

        let audioContextGrabacion = null;
        let analizadorGrabacion = null;
        let bufferAnalisisVoz = null;
        let cuadroDeteccionSilencio = null;
        let huboVozEnGrabacionActual = false;
        let silencioIniciadoEn = null;

        let colaAudio = [];
        let reproduciendoAudio = false;
        let audioActual = null;
        let parrafoEnRevelado = null;
        let avisoAudioNoDisponible = false;
        const derivacionesMostradas = new Set();

        function leerPreferenciaAudio() {
            try {
                return localStorage.getItem(AUDIO_STORAGE_KEY) === '1';
            } catch (error) {
                return false;
            }
        }

        function guardarPreferenciaAudio(valor) {
            try {
                localStorage.setItem(AUDIO_STORAGE_KEY, valor ? '1' : '0');
            } catch (error) {
                // Navegación privada o almacenamiento bloqueado: la preferencia no se recuerda.
            }
        }

        function mostrarEstado(mensaje, esError = false, duracion = 3200) {
            clearTimeout(temporizadorEstado);
            voiceStatus.textContent = mensaje;
            voiceStatus.classList.toggle('error', Boolean(esError));
            voiceStatus.classList.add('visible');
            temporizadorEstado = setTimeout(() => {
                voiceStatus.classList.remove('visible');
            }, duracion);
        }

        function desplazarAlFinal() {
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
        }

        function agregarBurbuja(rol, texto) {
            const li = document.createElement('li');
            li.className = 'chat-mensaje ' + rol;

            const parrafo = document.createElement('p');
            parrafo.textContent = texto;
            li.appendChild(parrafo);

            listaMensajes.appendChild(li);
            desplazarAlFinal();
            return parrafo;
        }

        function agregarElementoExtra(elemento) {
            const li = document.createElement('li');
            li.className = 'chat-extra';
            li.appendChild(elemento);
            listaMensajes.appendChild(li);
            desplazarAlFinal();
        }

        function registrarEvento(tipo, detalle) {
            if (window.AliadaAnalitica && typeof window.AliadaAnalitica.evento === 'function') {
                window.AliadaAnalitica.evento(tipo, detalle);
            }
        }

        function crearIcono(clase) {
            const i = document.createElement('i');
            i.className = clase;
            i.setAttribute('aria-hidden', 'true');
            return i;
        }

        let temporizadorAvisoBusqueda = null;

        function mostrarIndicadorEscribiendo() {
            const li = document.createElement('li');
            li.className = 'chat-escribiendo';
            li.id = 'indicador-escribiendo';
            li.setAttribute('aria-label', config.nombreIA + ' está escribiendo');
            for (let i = 0; i < 3; i++) {
                li.appendChild(document.createElement('span'));
            }
            listaMensajes.appendChild(li);
            desplazarAlFinal();

            temporizadorAvisoBusqueda = setTimeout(() => {
                mostrarEstado('Consultando información actualizada, un momento...', false, 6000);
            }, ESPERA_AVISO_BUSQUEDA_MS);
        }

        function quitarIndicadorEscribiendo() {
            clearTimeout(temporizadorAvisoBusqueda);
            const indicador = document.getElementById('indicador-escribiendo');
            if (indicador) {
                indicador.remove();
            }
        }

        /*
        | Documento entregado por la IA: tarjeta con copiar / descargar / imprimir.
        | Todo el contenido se inserta con textContent.
        */
        function copiarTexto(texto) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(texto);
            }

            return new Promise((resolve, reject) => {
                const area = document.createElement('textarea');
                area.value = texto;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                const ok = document.execCommand('copy');
                area.remove();
                ok ? resolve() : reject(new Error('copia no disponible'));
            });
        }

        function descargarTexto(documento) {
            const fecha = new Date().toISOString().slice(0, 10);
            const blob = new Blob([documento.titulo + '\n\n' + documento.contenido], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = (documento.tipo || 'documento') + '_' + fecha + '.txt';
            document.body.appendChild(enlace);
            enlace.click();
            enlace.remove();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        }

        function imprimirDocumento(documento) {
            const ventana = window.open('', '_blank');

            if (!ventana) {
                mostrarEstado('Permite las ventanas emergentes para imprimir o guardar en PDF.', true);
                return;
            }

            const doc = ventana.document;
            doc.title = documento.titulo;

            const estilo = doc.createElement('style');
            estilo.textContent = 'body{font-family:Georgia,"Times New Roman",serif;margin:2.5cm;color:#000;font-size:12pt;line-height:1.5}'
                + 'h1{font-size:14pt;margin:0 0 1em}pre{white-space:pre-wrap;font-family:inherit;margin:0}'
                + '.nota{margin-top:2em;font-size:9pt;color:#555}';
            doc.head.appendChild(estilo);

            const titulo = doc.createElement('h1');
            titulo.textContent = documento.titulo;
            const cuerpo = doc.createElement('pre');
            cuerpo.textContent = documento.contenido;
            const nota = doc.createElement('p');
            nota.className = 'nota';
            nota.textContent = 'Borrador generado con IA en Aliada UTS. Revísalo antes de radicarlo.';

            doc.body.appendChild(titulo);
            doc.body.appendChild(cuerpo);
            doc.body.appendChild(nota);

            ventana.focus();
            ventana.print();
        }

        function agregarTarjetaDocumento(documento) {
            const tarjeta = document.createElement('div');
            tarjeta.className = 'chat-documento';

            const cabecera = document.createElement('div');
            cabecera.className = 'chat-documento-cabecera';
            cabecera.appendChild(crearIcono('fa-solid fa-file-lines'));
            const titulo = document.createElement('span');
            titulo.textContent = documento.titulo;
            cabecera.appendChild(titulo);

            const cuerpo = document.createElement('pre');
            cuerpo.className = 'chat-documento-cuerpo';
            cuerpo.textContent = documento.contenido;
            cuerpo.tabIndex = 0;

            const nota = document.createElement('p');
            nota.className = 'chat-documento-nota';
            nota.textContent = 'Borrador generado con IA. Revísalo y completa los datos marcados antes de radicarlo; si puedes, valídalo con un consultorio jurídico.';

            const acciones = document.createElement('div');
            acciones.className = 'chat-documento-acciones';

            const botones = [
                ['fa-solid fa-copy', 'Copiar', () => copiarTexto(documento.contenido)
                    .then(() => { registrarEvento('documento_descargado', 'copiar'); mostrarEstado('Documento copiado.'); })
                    .catch(() => mostrarEstado('No fue posible copiar. Selecciona el texto manualmente.', true))],
                ['fa-solid fa-download', 'Descargar', () => { registrarEvento('documento_descargado', 'descargar'); descargarTexto(documento); }],
                ['fa-solid fa-print', 'Imprimir o PDF', () => { registrarEvento('documento_descargado', 'imprimir'); imprimirDocumento(documento); }]
            ];

            botones.forEach(([icono, texto, accion]) => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.appendChild(crearIcono(icono));
                boton.appendChild(document.createTextNode(texto));
                boton.addEventListener('click', accion);
                acciones.appendChild(boton);
            });

            tarjeta.append(cabecera, cuerpo, nota, acciones);
            agregarElementoExtra(tarjeta);
        }

        function agregarFuentes(fuentes) {
            const validas = (fuentes || []).filter((f) => f && typeof f.url === 'string' && f.url.indexOf('https://') === 0);

            if (validas.length === 0) {
                return;
            }

            const caja = document.createElement('div');
            caja.className = 'chat-fuentes';
            const titulo = document.createElement('strong');
            titulo.textContent = 'Fuentes consultadas';
            const lista = document.createElement('ul');

            validas.forEach((fuente) => {
                const item = document.createElement('li');
                const enlace = document.createElement('a');
                enlace.href = fuente.url;
                enlace.target = '_blank';
                enlace.rel = 'noopener noreferrer';
                enlace.textContent = fuente.titulo || fuente.url;
                item.appendChild(enlace);
                lista.appendChild(item);
            });

            caja.append(titulo, lista);
            agregarElementoExtra(caja);
        }

        function agregarDerivacion(derivacion) {
            if (!derivacion || typeof derivacion.url !== 'string' || !/^[a-z_0-9]+\.php$/.test(derivacion.url)) {
                return;
            }

            if (derivacionesMostradas.has(derivacion.url)) {
                return;
            }
            derivacionesMostradas.add(derivacion.url);

            const enlace = document.createElement('a');
            enlace.className = 'chat-derivacion';
            enlace.href = derivacion.url;
            enlace.addEventListener('click', () => registrarEvento('derivacion_click', derivacion.slug));
            enlace.appendChild(crearIcono('fa-solid fa-arrow-right'));
            enlace.appendChild(document.createTextNode(derivacion.etiqueta));
            agregarElementoExtra(enlace);
        }

        /*
        | Botones de emergencia (solo si config.botonesEmergencia).
        | Misma lógica de guardado que emergencia.php contra admin/ajax/guardar_alerta.php.
        */
        function obtenerUbicacionActual() {
            return new Promise((resolve) => {
                if (!navigator.geolocation) {
                    resolve(null);
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (posicion) => resolve(posicion.coords),
                    () => resolve(null),
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 }
                );
            });
        }

        async function guardarAlertaChat(tipo, descripcion) {
            const coords = await obtenerUbicacionActual();

            const formData = new FormData();
            formData.append('tipo', tipo);
            formData.append('usuario_id', config.usuarioId);
            formData.append('nombre_usuario', config.nombreUsuario);
            formData.append('latitud', coords ? coords.latitude : '');
            formData.append('longitud', coords ? coords.longitude : '');
            formData.append('precision_gps', coords ? coords.accuracy : '');
            formData.append('descripcion', descripcion);

            try {
                await fetch('admin/ajax/guardar_alerta.php', { method: 'POST', body: formData });
            } catch (error) {
                // La llamada de emergencia debe continuar aunque falle el registro.
            }
        }

        function agregarBotonesEmergencia() {
            const contenedor = document.createElement('li');
            contenedor.className = 'chat-emergencia';

            const titulo = document.createElement('span');
            titulo.className = 'chat-emergencia-titulo';
            titulo.textContent = 'Esto puede ser una emergencia. Acciones rápidas:';
            contenedor.appendChild(titulo);

            const crearBoton = (clase, icono, texto, tipo, destino) => {
                const enlace = document.createElement('a');
                enlace.href = 'tel:' + destino;
                enlace.className = 'chat-emergencia-boton ' + clase;
                enlace.appendChild(crearIcono(icono));
                enlace.appendChild(document.createTextNode(' ' + texto));
                enlace.addEventListener('click', (evento) => {
                    evento.preventDefault();
                    guardarAlertaChat(tipo, 'La usuaria activó ' + texto + ' desde ' + config.nombreIA + '.')
                        .finally(() => { window.location.href = 'tel:' + destino; });
                });
                contenedor.appendChild(enlace);
            };

            crearBoton('policia', 'fa-solid fa-shield-halved', 'Llamar a Policía (123)', 'policia', '123');

            if (config.contactoEmergenciaTel) {
                crearBoton('contacto', 'fa-solid fa-phone', 'Avisar a mi contacto de confianza', 'contacto_emergencia', config.contactoEmergenciaTel);
            }

            listaMensajes.appendChild(contenedor);
            desplazarAlFinal();
        }

        /*
        | Reproducción de audio (ElevenLabs TTS) con texto sincronizado palabra por palabra.
        */
        function extraerFrasesHablables(texto) {
            return texto
                .replace(/\s+/g, ' ')
                .trim()
                .split(/(?<=[.!?…])\s+/)
                .filter((frase) => frase.trim() !== '');
        }

        function decodificarAudioBase64(base64) {
            const binario = atob(base64);
            const bytes = new Uint8Array(binario.length);

            for (let i = 0; i < binario.length; i++) {
                bytes[i] = binario.charCodeAt(i);
            }

            return new Blob([bytes], { type: 'audio/mpeg' });
        }

        async function solicitarAudioConAlineacion(texto) {
            const respuesta = await fetch('elevenlabs_tts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    text: texto,
                    con_tiempos: true,
                    velocidad: VELOCIDAD_ELEVENLABS
                })
            });

            if (!respuesta.ok) {
                if (!avisoAudioNoDisponible) {
                    avisoAudioNoDisponible = true;
                    mostrarEstado('El audio de respuesta no está disponible en este momento. Te muestro la respuesta en texto.', true, 5000);
                }
                throw new Error('ElevenLabs TTS respondió con error.');
            }

            const datos = await respuesta.json();
            const blob = decodificarAudioBase64(datos.audio_base64);

            return {
                url: URL.createObjectURL(blob),
                alignment: datos.alignment || null
            };
        }

        function calcularPalabrasConTiempos(alignment) {
            if (!alignment || !Array.isArray(alignment.characters)) {
                return null;
            }

            const palabras = [];
            let palabraActual = '';
            let inicioPalabra = null;

            alignment.characters.forEach((caracter, indice) => {
                const inicio = alignment.character_start_times_seconds[indice] ?? 0;

                if (caracter.trim() === '') {
                    if (palabraActual !== '') {
                        palabras.push({ texto: palabraActual, inicio: inicioPalabra });
                        palabraActual = '';
                        inicioPalabra = null;
                    }
                    return;
                }

                if (inicioPalabra === null) {
                    inicioPalabra = inicio;
                }
                palabraActual += caracter;
            });

            if (palabraActual !== '') {
                palabras.push({ texto: palabraActual, inicio: inicioPalabra });
            }

            return palabras;
        }

        function revelarFragmentoDeTexto(fragmento) {
            if (!parrafoEnRevelado) {
                return;
            }

            const separador = parrafoEnRevelado.textContent.trim() === '' ? '' : ' ';
            parrafoEnRevelado.textContent += separador + fragmento;
            desplazarAlFinal();
        }

        function revelarPorPalabraSincronizado(audioElemento, palabras) {
            let indice = 0;

            const intervalo = setInterval(() => {
                if (audioActual !== audioElemento) {
                    clearInterval(intervalo);
                    return;
                }

                while (indice < palabras.length && audioElemento.currentTime >= palabras[indice].inicio) {
                    revelarFragmentoDeTexto(palabras[indice].texto);
                    indice += 1;
                }

                if (indice >= palabras.length || audioElemento.ended) {
                    for (; indice < palabras.length; indice += 1) {
                        revelarFragmentoDeTexto(palabras[indice].texto);
                    }
                    clearInterval(intervalo);
                }
            }, 60);
        }

        function finalizarRevelado() {
            if (colaAudio.length === 0) {
                if (parrafoEnRevelado && parrafoEnRevelado.dataset.textoRestante) {
                    revelarFragmentoDeTexto(parrafoEnRevelado.dataset.textoRestante);
                    delete parrafoEnRevelado.dataset.textoRestante;
                }
                parrafoEnRevelado = null;
                reactivarEscuchaSiCorresponde();
            }
        }

        async function reproducirSiguienteAudio() {
            if (reproduciendoAudio || colaAudio.length === 0) {
                return;
            }

            reproduciendoAudio = true;
            btnParlante.classList.add('hablando');

            const textoFragmento = colaAudio.shift();
            let yaSeRevelo = false;

            try {
                const { url: urlAudio, alignment } = await solicitarAudioConAlineacion(textoFragmento);
                audioActual = new Audio(urlAudio);
                audioActual.playbackRate = VELOCIDAD_AUDIO_RESPUESTA;

                audioActual.addEventListener('ended', () => {
                    URL.revokeObjectURL(urlAudio);
                    reproduciendoAudio = false;
                    btnParlante.classList.remove('hablando');
                    finalizarRevelado();
                    reproducirSiguienteAudio();
                });

                await audioActual.play();

                const palabras = calcularPalabrasConTiempos(alignment);
                yaSeRevelo = true;

                if (palabras && palabras.length > 0) {
                    revelarPorPalabraSincronizado(audioActual, palabras);
                } else {
                    revelarFragmentoDeTexto(textoFragmento);
                }
            } catch (error) {
                if (!yaSeRevelo) {
                    revelarFragmentoDeTexto(textoFragmento);
                }
                reproduciendoAudio = false;
                btnParlante.classList.remove('hablando');
                finalizarRevelado();
                reproducirSiguienteAudio();
            }
        }

        /*
         * Si el texto es muy largo (el modelo ignoró la regla de brevedad), solo
         * se leen las dos primeras frases; el resto aparece al terminar el audio.
         */
        function encolarRespuestaHablada(texto, parrafoDestino) {
            let frases = extraerFrasesHablables(texto);

            if (frases.length === 0) {
                parrafoDestino.textContent = texto;
                reactivarEscuchaSiCorresponde();
                return;
            }

            if (texto.length > MAX_CARACTERES_AUDIO && frases.length > 2) {
                parrafoDestino.dataset.textoRestante = frases.slice(2).join(' ');
                frases = frases.slice(0, 2).concat(['Te dejé el texto completo en pantalla.']);
            }

            parrafoEnRevelado = parrafoDestino;
            colaAudio.push(...frases);
            reproducirSiguienteAudio();
        }

        function reactivarEscuchaSiCorresponde() {
            if (!audioActivo || grabando || esperandoRespuesta) {
                return;
            }

            setTimeout(() => {
                if (audioActivo && !grabando && !esperandoRespuesta) {
                    iniciarGrabacion();
                }
            }, RETARDO_REACTIVAR_ESCUCHA_MS);
        }

        function actualizarBotonParlante() {
            btnParlante.classList.toggle('activo', audioActivo);
            btnParlante.setAttribute('aria-pressed', audioActivo ? 'true' : 'false');
            btnParlante.querySelector('i').className = audioActivo
                ? 'fa-solid fa-volume-high'
                : 'fa-solid fa-volume-xmark';
        }

        btnParlante.addEventListener('click', () => {
            audioActivo = !audioActivo;
            guardarPreferenciaAudio(audioActivo);
            if (audioActivo) {
                registrarEvento('audio_activado');
            }
            actualizarBotonParlante();
            mostrarEstado(audioActivo ? 'Respuestas por audio activadas.' : 'Respuestas por audio desactivadas.');
        });

        actualizarBotonParlante();

        /*
        | Envío de mensajes al endpoint del chat.
        */
        async function enviarMensajeAlServidor(mensaje, origen) {
            const respuesta = await fetch(config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    asistente: config.asistente,
                    conversacion_id: conversacionId,
                    mensaje: mensaje,
                    origen: origen
                })
            });

            let datos = null;
            try {
                datos = await respuesta.json();
            } catch (error) {
                datos = {};
            }

            if (respuesta.status === 401 || respuesta.status === 403) {
                throw new Error('Tu sesión venció. Vuelve a iniciar sesión para continuar.');
            }

            if (!respuesta.ok) {
                throw new Error(datos.error || 'No fue posible obtener respuesta de ' + config.nombreIA + '.');
            }

            return datos;
        }

        async function enviarMensaje(texto, origen) {
            const mensaje = texto.trim();

            if (mensaje === '' || esperandoRespuesta) {
                return;
            }

            esperandoRespuesta = true;
            botonEnviar.disabled = true;

            quitarOfertaRetomar();
            agregarBurbuja('usuaria', mensaje);
            campoTexto.value = '';
            campoTexto.style.height = 'auto';
            mostrarIndicadorEscribiendo();

            try {
                const datos = await enviarMensajeAlServidor(mensaje, origen);

                conversacionId = datos.conversacion_id;

                quitarIndicadorEscribiendo();

                if (audioActivo) {
                    const parrafoRespuesta = agregarBurbuja('asistente', '');
                    encolarRespuestaHablada(datos.respuesta_texto, parrafoRespuesta);
                } else {
                    agregarBurbuja('asistente', datos.respuesta_texto);
                }

                agregarFuentes(datos.fuentes);

                if (datos.documento && datos.documento.contenido) {
                    agregarTarjetaDocumento(datos.documento);
                }

                agregarDerivacion(datos.derivar_a);

                if (config.botonesEmergencia && datos.requiere_botones_emergencia) {
                    agregarBotonesEmergencia();
                }
            } catch (error) {
                quitarIndicadorEscribiendo();
                agregarBurbuja('sistema', error.message || 'Hubo un problema de conexión. Por favor intenta de nuevo.');
            } finally {
                esperandoRespuesta = false;
                botonEnviar.disabled = false;
                campoTexto.focus();
            }
        }

        formulario.addEventListener('submit', (evento) => {
            evento.preventDefault();
            enviarMensaje(campoTexto.value, 'texto');
        });

        campoTexto.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter' && !evento.shiftKey) {
                evento.preventDefault();
                formulario.requestSubmit();
            }
        });

        campoTexto.addEventListener('input', () => {
            campoTexto.style.height = 'auto';
            campoTexto.style.height = Math.min(campoTexto.scrollHeight, 110) + 'px';
        });

        /*
        | Captura de voz (grabación + ElevenLabs Speech-to-Text) con corte por silencio.
        */
        function actualizarBotonMicrofono() {
            btnMicrofono.classList.toggle('escuchando', grabando);
            btnMicrofono.setAttribute('aria-pressed', grabando ? 'true' : 'false');
        }

        function calcularVolumenActual() {
            analizadorGrabacion.getByteTimeDomainData(bufferAnalisisVoz);

            let sumaCuadrados = 0;
            for (let i = 0; i < bufferAnalisisVoz.length; i++) {
                const muestra = (bufferAnalisisVoz[i] - 128) / 128;
                sumaCuadrados += muestra * muestra;
            }

            return Math.sqrt(sumaCuadrados / bufferAnalisisVoz.length);
        }

        function monitorearSilencio() {
            if (!grabando || !analizadorGrabacion) {
                return;
            }

            const volumen = calcularVolumenActual();

            if (volumen > UMBRAL_VOLUMEN_VOZ) {
                huboVozEnGrabacionActual = true;
                silencioIniciadoEn = null;
            } else if (huboVozEnGrabacionActual) {
                if (silencioIniciadoEn === null) {
                    silencioIniciadoEn = Date.now();
                } else if (Date.now() - silencioIniciadoEn >= SILENCIO_MS_PARA_DETENER) {
                    detenerGrabacion();
                    return;
                }
            }

            cuadroDeteccionSilencio = requestAnimationFrame(monitorearSilencio);
        }

        function iniciarDeteccionSilencio(flujo) {
            const AudioContextClase = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClase) {
                return;
            }

            audioContextGrabacion = new AudioContextClase();
            const fuente = audioContextGrabacion.createMediaStreamSource(flujo);
            analizadorGrabacion = audioContextGrabacion.createAnalyser();
            analizadorGrabacion.fftSize = 2048;
            fuente.connect(analizadorGrabacion);

            bufferAnalisisVoz = new Uint8Array(analizadorGrabacion.fftSize);
            huboVozEnGrabacionActual = false;
            silencioIniciadoEn = null;

            monitorearSilencio();
        }

        function detenerDeteccionSilencio() {
            if (cuadroDeteccionSilencio !== null) {
                cancelAnimationFrame(cuadroDeteccionSilencio);
                cuadroDeteccionSilencio = null;
            }

            if (audioContextGrabacion) {
                audioContextGrabacion.close().catch(() => {});
                audioContextGrabacion = null;
            }

            analizadorGrabacion = null;
            bufferAnalisisVoz = null;
        }

        function tipoMimeGrabacion() {
            if (!window.MediaRecorder || typeof MediaRecorder.isTypeSupported !== 'function') {
                return '';
            }

            // Safari/iOS no soporta audio/webm en MediaRecorder; usa audio/mp4.
            return ['audio/webm', 'audio/mp4'].find((tipo) => MediaRecorder.isTypeSupported(tipo)) || '';
        }

        async function iniciarGrabacion() {
            if (!navigator.mediaDevices || !window.MediaRecorder) {
                mostrarEstado('Tu navegador no permite grabar audio. Puedes escribir tu mensaje.', true);
                return;
            }

            try {
                const flujo = await navigator.mediaDevices.getUserMedia({ audio: true });
                const mime = tipoMimeGrabacion();

                fragmentosAudio = [];
                grabadora = mime ? new MediaRecorder(flujo, { mimeType: mime }) : new MediaRecorder(flujo);

                grabadora.addEventListener('dataavailable', (evento) => {
                    if (evento.data.size > 0) {
                        fragmentosAudio.push(evento.data);
                    }
                });

                grabadora.addEventListener('stop', () => {
                    flujo.getTracks().forEach((pista) => pista.stop());
                    detenerDeteccionSilencio();
                    procesarGrabacion(grabadora.mimeType || mime || 'audio/webm');
                });

                grabadora.start();
                grabando = true;
                actualizarBotonMicrofono();
                mostrarEstado('Te escucho... el mensaje se enviará solo cuando dejes de hablar.');

                iniciarDeteccionSilencio(flujo);

                temporizadorLimiteGrabacion = setTimeout(() => {
                    detenerGrabacion();
                }, DURACION_MAXIMA_GRABACION_MS);
            } catch (error) {
                mostrarEstado('No fue posible acceder al micrófono. Revisa los permisos.', true);
            }
        }

        function detenerGrabacion() {
            clearTimeout(temporizadorLimiteGrabacion);

            if (grabadora && grabadora.state !== 'inactive') {
                grabadora.stop();
            }

            grabando = false;
            actualizarBotonMicrofono();
        }

        async function procesarGrabacion(mime) {
            if (fragmentosAudio.length === 0) {
                mostrarEstado('No se detectó audio en la grabación.', true);
                return;
            }

            const tipoBase = mime.split(';')[0];
            const blobAudio = new Blob(fragmentosAudio, { type: tipoBase });

            if (blobAudio.size < 800) {
                mostrarEstado('La grabación fue demasiado corta.', true);
                return;
            }

            mostrarEstado('Transcribiendo tu voz...');

            const formData = new FormData();
            formData.append('audio', blobAudio, tipoBase === 'audio/mp4' ? 'grabacion.m4a' : 'grabacion.webm');

            try {
                const respuesta = await fetch('elevenlabs_stt.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    throw new Error(datos.error || 'No fue posible transcribir el audio.');
                }

                const textoTranscrito = (datos.texto || '').trim();

                if (textoTranscrito === '') {
                    mostrarEstado('No se detectó texto en tu mensaje de voz.', true);
                    return;
                }

                const textoPrevio = campoTexto.value.trim();
                const mensajeAEnviar = textoPrevio === '' ? textoTranscrito : textoPrevio + ' ' + textoTranscrito;
                campoTexto.value = '';

                mostrarEstado('Enviando tu mensaje de voz...');
                enviarMensaje(mensajeAEnviar, 'voz');
            } catch (error) {
                mostrarEstado(error.message || 'No fue posible transcribir el audio.', true);
            }
        }

        btnMicrofono.addEventListener('click', () => {
            if (grabando) {
                detenerGrabacion();
            } else {
                iniciarGrabacion();
            }
        });

        /*
        | Retomar la conversación anterior: solo se ofrece (fecha y cantidad, sin
        | contenido) y el historial se carga únicamente si la usuaria lo pide.
        */
        async function consultarHistorial(cuerpo) {
            const respuesta = await fetch('api/chat_historial.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(Object.assign({ asistente: config.asistente }, cuerpo))
            });

            const datos = await respuesta.json().catch(() => ({}));

            if (!respuesta.ok) {
                throw new Error(datos.error || 'No fue posible cargar la conversación.');
            }

            return datos;
        }

        function quitarOfertaRetomar() {
            const oferta = document.getElementById('oferta-retomar');
            if (oferta) {
                oferta.remove();
            }
        }

        function formatearFecha(fechaSql) {
            const fecha = new Date(String(fechaSql).replace(' ', 'T'));
            if (isNaN(fecha.getTime())) {
                return '';
            }
            return fecha.toLocaleDateString('es-CO', { day: 'numeric', month: 'long' })
                + ' a las ' + fecha.toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit' });
        }

        async function retomarConversacion(id, boton) {
            boton.disabled = true;

            try {
                const datos = await consultarHistorial({ accion: 'cargar', conversacion_id: id });
                quitarOfertaRetomar();

                (datos.mensajes || []).forEach((mensaje) => {
                    agregarBurbuja(mensaje.rol === 'usuaria' ? 'usuaria' : 'asistente', mensaje.contenido);

                    if (mensaje.rol === 'asistente') {
                        agregarFuentes(mensaje.fuentes);
                        if (mensaje.documento && mensaje.documento.contenido) {
                            agregarTarjetaDocumento(mensaje.documento);
                        }
                    }
                });

                conversacionId = datos.conversacion_id;
                agregarBurbuja('sistema', 'Retomaste tu conversación anterior. Puedes continuar desde aquí.');
                campoTexto.focus();
            } catch (error) {
                boton.disabled = false;
                mostrarEstado(error.message, true);
            }
        }

        async function ofrecerRetomar() {
            let datos;

            try {
                datos = await consultarHistorial({ accion: 'ultima' });
            } catch (error) {
                return;
            }

            if (!datos.existe || conversacionId !== null || esperandoRespuesta) {
                return;
            }

            const li = document.createElement('li');
            li.className = 'chat-retomar';
            li.id = 'oferta-retomar';

            const texto = document.createElement('span');
            const fecha = formatearFecha(datos.fecha);
            texto.textContent = ('Tienes una conversación anterior' + (fecha ? ' del ' + fecha : '')).replace(/\.?$/, '.');

            const acciones = document.createElement('div');
            acciones.className = 'chat-retomar-acciones';

            const btnRetomar = document.createElement('button');
            btnRetomar.type = 'button';
            btnRetomar.className = 'chat-retomar-si';
            btnRetomar.appendChild(crearIcono('fa-solid fa-clock-rotate-left'));
            btnRetomar.appendChild(document.createTextNode(' Retomar conversación anterior'));
            btnRetomar.addEventListener('click', () => retomarConversacion(datos.conversacion_id, btnRetomar));

            const btnNueva = document.createElement('button');
            btnNueva.type = 'button';
            btnNueva.className = 'chat-retomar-no';
            btnNueva.textContent = 'Empezar una nueva';
            btnNueva.addEventListener('click', () => {
                quitarOfertaRetomar();
                campoTexto.focus();
            });

            acciones.append(btnRetomar, btnNueva);
            li.append(texto, acciones);
            listaMensajes.appendChild(li);
            desplazarAlFinal();
        }

        agregarBurbuja('asistente', config.saludo);
        ofrecerRetomar();
    }

    window.AliadaChat = { iniciar: iniciar };
})();
