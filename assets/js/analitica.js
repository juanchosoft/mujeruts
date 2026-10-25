/* Dashboard de analítica (analitica.php). Configuración inyectada en window.ANALITICA. */
(function () {
    'use strict';

    var C = window.ANALITICA;
    var $ = window.jQuery;

    var DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    var COLORES = ['#D81B9C', '#7B1FA2', '#4DD0E1', '#FFB74D', '#FF4FB8', '#81C784'];
    var IDIOMA = {
        search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros', info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Sin registros', infoFiltered: '(filtrado de _MAX_)', zeroRecords: 'No hay registros para este rango y filtros',
        emptyTable: 'No hay registros para este rango y filtros', loadingRecords: 'Cargando…',
        paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
    };

    var TEXTO = $.fn.dataTable.render.text();

    var estado = {
        filtros: null,
        graficas: {},
        mapa: null,
        capa: null,
        tablaSesiones: null,
        tablaUso: null,
        tablaResumen: null,
        abortos: {},
        ultimosKpis: null,
        ultimoUso: null,
        reveal: null,
        revealTimer: null,
        timerEnLinea: null
    };

    // ------------------------------------------------------------ utilidades

    function el(tag, atributos, hijos) {
        var nodo = document.createElement(tag);

        Object.keys(atributos || {}).forEach(function (k) {
            if (k === 'texto') {
                nodo.textContent = atributos[k];
            } else if (k === 'clase') {
                nodo.className = atributos[k];
            } else {
                nodo.setAttribute(k, atributos[k]);
            }
        });

        (hijos || []).forEach(function (h) {
            if (h !== null && h !== undefined) {
                nodo.appendChild(typeof h === 'string' ? document.createTextNode(h) : h);
            }
        });

        return nodo;
    }

    function vaciar(nodo) {
        while (nodo.firstChild) {
            nodo.removeChild(nodo.firstChild);
        }
    }

    function num(n) {
        return Number(n || 0).toLocaleString('es-CO');
    }

    function dec(n, d) {
        return Number(n).toLocaleString('es-CO', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function dur(seg) {
        if (seg === null || seg === undefined) {
            return '—';
        }

        seg = Math.round(Number(seg));

        if (seg < 60) {
            return seg + ' s';
        }

        var h = Math.floor(seg / 3600);
        var m = Math.floor((seg % 3600) / 60);
        var s = seg % 60;

        if (h > 0) {
            return h + ' h ' + String(m).padStart(2, '0') + ' min';
        }

        return m + ' min' + (s > 0 ? ' ' + String(s).padStart(2, '0') + ' s' : '');
    }

    function fecha(sql) {
        if (!sql) {
            return '—';
        }

        var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(sql);
        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : '—';
    }

    function hora(sql) {
        var m = /(\d{2}:\d{2})/.exec(sql || '');
        return m ? m[1] : '—';
    }

    function fechaIso(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function sumarDias(iso, dias) {
        var p = iso.split('-');
        var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]) + dias);
        return fechaIso(d);
    }

    function pct(v) {
        return v === null || v === undefined ? '—' : dec(v, 1) + ' %';
    }

    function pedir(clave, accion, extra) {
        if (estado.abortos[clave]) {
            estado.abortos[clave].abort();
        }

        var control = new AbortController();
        estado.abortos[clave] = control;

        var p = new URLSearchParams(Object.assign({ accion: accion }, estado.filtros, extra || {}));
        Object.keys(estado.filtros).forEach(function (k) {
            if (estado.filtros[k] === '') {
                p.delete(k);
            }
        });

        return fetch(C.ajax + '?' + p.toString(), { credentials: 'same-origin', signal: control.signal, cache: 'no-store' })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, error: 'Respuesta no válida del servidor.' }; })
                    .then(function (j) {
                        if (!r.ok || !j.ok) {
                            throw new Error(j.error || 'No fue posible cargar los datos.');
                        }
                        return j.datos;
                    });
            });
    }

    function enviar(cuerpo) {
        return fetch(C.ajax, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': C.csrf },
            body: JSON.stringify(cuerpo)
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, error: 'Respuesta no válida del servidor.' }; })
                .then(function (j) {
                    if (!r.ok || !j.ok) {
                        throw new Error(j.error || 'No fue posible completar la acción.');
                    }
                    return j;
                });
        });
    }

    function mostrarGlobal(texto) {
        var caja = document.getElementById('estadoGlobal');
        caja.textContent = texto;
        caja.hidden = !texto;
    }

    function errorFila(tbody, columnas, mensaje) {
        vaciar(tbody);
        tbody.appendChild(el('tr', {}, [el('td', { colspan: String(columnas), clase: 'an-vacio-celda', role: 'alert', texto: mensaje })]));
    }

    function errorSeccion(contenedor, mensaje, reintentar) {
        vaciar(contenedor);
        var caja = el('div', { clase: 'an-vacio', role: 'alert' }, [mensaje + ' ']);
        var boton = el('button', { type: 'button', clase: 'an-btn an-btn-chico', texto: 'Reintentar' });
        boton.addEventListener('click', reintentar);
        caja.appendChild(boton);
        contenedor.appendChild(caja);
    }

    // ------------------------------------------------------------ filtros

    function leerFiltros() {
        return {
            desde: document.getElementById('fDesde').value,
            hasta: document.getElementById('fHasta').value,
            asistente: document.getElementById('fAsistente').value,
            dispositivo: document.getElementById('fDispositivo').value,
            municipio: document.getElementById('fMunicipio').value
        };
    }

    function validarFiltros(f) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(f.desde) || !/^\d{4}-\d{2}-\d{2}$/.test(f.hasta)) {
            return 'Indica las dos fechas.';
        }

        if (f.desde > f.hasta) {
            return 'La fecha inicial no puede ser posterior a la final.';
        }

        var dias = Math.round((new Date(f.hasta + 'T00:00:00') - new Date(f.desde + 'T00:00:00')) / 86400000) + 1;

        if (dias > C.maxDias) {
            return 'El rango máximo es de ' + C.maxDias + ' días.';
        }

        return '';
    }

    function aplicarRango(tipo) {
        var hasta = C.hoy;
        var desde = hasta;

        if (tipo === '7') {
            desde = sumarDias(hasta, -6);
        } else if (tipo === '30') {
            desde = sumarDias(hasta, -29);
        } else if (tipo === 'mes') {
            desde = C.inicioMes;
        }

        document.getElementById('fDesde').value = desde;
        document.getElementById('fHasta').value = hasta;
        aplicar();
    }

    function aplicar() {
        var f = leerFiltros();
        var error = validarFiltros(f);
        var caja = document.getElementById('filtroError');

        caja.hidden = error === '';
        caja.textContent = error;

        if (error) {
            return;
        }

        estado.filtros = f;
        cargarTodo();
    }

    function textoFiltros() {
        var f = estado.filtros;
        var partes = ['Rango: ' + f.desde + ' a ' + f.hasta];
        partes.push('Asistente: ' + (f.asistente ? C.asistentes[f.asistente] : 'todos'));
        partes.push('Dispositivo: ' + (f.dispositivo || 'todos'));
        var sel = document.getElementById('fMunicipio');
        partes.push('Municipio: ' + (f.municipio ? sel.options[sel.selectedIndex].text : 'todos'));
        return partes.join(' · ');
    }

    // ------------------------------------------------------------ gráficas

    function configurarChart() {
        Chart.defaults.color = '#c7b7d9';
        Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    }

    function grafica(id, config, hayDatos) {
        var canvas = document.getElementById(id);

        if (estado.graficas[id]) {
            estado.graficas[id].destroy();
            delete estado.graficas[id];
        }

        var previo = canvas.parentElement.querySelector('.chart-empty');

        if (previo) {
            previo.remove();
        }

        if (!hayDatos) {
            canvas.style.display = 'none';
            canvas.parentElement.appendChild(el('div', { clase: 'chart-empty', texto: 'Sin datos en este rango y filtros.' }));
            return;
        }

        canvas.style.display = '';
        config.options = Object.assign({ responsive: true, maintainAspectRatio: false }, config.options || {});
        estado.graficas[id] = new Chart(canvas, config);
    }

    // ------------------------------------------------------------ KPIs

    function tarjetaKpi(etiqueta, valor, sub, ayuda) {
        var boton = el('button', { type: 'button', clase: 'an-ayuda', 'aria-label': 'Qué significa: ' + etiqueta }, [el('i', { clase: 'fas fa-circle-question', 'aria-hidden': 'true' })]);
        boton.setAttribute('data-bs-toggle', 'popover');
        boton.setAttribute('data-bs-trigger', 'focus');
        boton.setAttribute('data-bs-placement', 'bottom');
        boton.setAttribute('data-bs-content', ayuda);
        boton.setAttribute('data-bs-title', etiqueta);

        var tarjeta = el('div', { clase: 'an-kpi' }, [
            el('p', { clase: 'an-kpi-etiqueta', texto: etiqueta }),
            el('div', { clase: 'an-kpi-valor', texto: valor }),
            sub ? el('div', { clase: 'an-kpi-sub', texto: sub }) : null,
            boton
        ]);

        new bootstrap.Popover(boton);
        return tarjeta;
    }

    function pintarKpis(k) {
        var cont = document.getElementById('kpis');
        vaciar(cont);
        estado.ultimosKpis = k;

        var top = k.asistente_mas_usado;
        var porUsuarias = k.rankings.por_usuarias[0];
        var porMensajes = k.rankings.por_mensajes[0];

        [
            ['En línea ahora', num(k.en_linea), 'Usuarias conectadas en este momento',
                'Usuarias con una sesión abierta y actividad en los últimos 150 segundos. No depende del rango de fechas.'],
            ['Sesiones', num(k.sesiones), num(k.sesiones_abiertas) + ' abiertas ahora',
                'Sesiones iniciadas dentro del rango. Una sesión empieza al iniciar sesión y termina al cerrarla o tras 30 minutos sin actividad.'],
            ['Usuarias únicas', num(k.usuarias_unicas), 'Personas distintas con sesión en el rango',
                'Cantidad de personas distintas que iniciaron sesión en el rango, sin contar repeticiones.'],
            ['Duración media', dur(k.duracion_media_seg), 'Por sesión cerrada',
                'Tiempo de inicio a fin de la sesión (solo sesiones ya cerradas). En cierres por inactividad el fin es la última actividad: los 30 minutos de espera no se cuentan.'],
            ['Duración mediana', dur(k.duracion_mediana_seg), 'La mitad de las sesiones duró menos',
                'Valor central de las duraciones de las sesiones cerradas. Es menos sensible a sesiones muy largas que el promedio.'],
            ['Tiempo activo en asistentes', dur(k.tiempo_activo_total_seg), 'Suma de todas las usuarias',
                'Suma de los tramos con la pestaña visible dentro de un asistente, con tope de 90 segundos por latido. No es el tiempo con la página abierta.'],
            ['Asistente más usado', top ? top.nombre : 'Sin datos', top ? dur(top.segundos) + ' de tiempo activo' + (porUsuarias ? ' · Más usuarias: ' + porUsuarias.nombre : '') + (porMensajes ? ' · Más mensajes: ' + porMensajes.nombre : '') : '',
                'Se muestra el de mayor tiempo activo. Entre paréntesis se indican también los líderes por usuarias únicas y por mensajes enviados, porque miden cosas distintas.'],
            ['Registros nuevos', num(k.registros_nuevos), 'Cuentas creadas en el rango',
                'Cuentas de usuarias creadas dentro del rango (fecha de registro).'],
            ['Usuarias activas', num(k.activas.diarias) + ' · ' + num(k.activas.semanales) + ' · ' + num(k.activas.mensuales), 'Diarias · semanales · mensuales',
                'Personas distintas con sesión el último día del rango, en los 7 días y en los 30 días que terminan ese día.']
        ].forEach(function (d) {
            cont.appendChild(tarjetaKpi(d[0], d[1], d[2], d[3]));
        });

        document.getElementById('totalEnLinea').textContent = num(k.en_linea);
        construirResumen();
    }

    // ------------------------------------------------------------ tiempo real

    function pintarEnLinea(d) {
        document.getElementById('totalEnLinea').textContent = num(d.total);
        document.getElementById('actualizado').textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        var cuerpo = document.querySelector('#tablaEnLinea tbody');
        vaciar(cuerpo);

        if (!d.sesiones.length) {
            var fila = el('tr', {}, [el('td', { colspan: '6', clase: 'an-vacio-celda', texto: 'Nadie en línea en este momento.' })]);
            cuerpo.appendChild(fila);
        }

        d.sesiones.forEach(function (s) {
            cuerpo.appendChild(el('tr', {}, [
                el('td', { texto: s.seudonimo }),
                el('td', { texto: s.pagina }),
                el('td', { texto: hora(s.desde) }),
                el('td', { texto: s.dispositivo }),
                el('td', { texto: s.ciudad_ip }),
                el('td', { texto: s.municipio_registrado })
            ]));
        });

        if (!estado.mapa) {
            estado.mapa = L.map('mapaEnLinea', { scrollWheelZoom: false }).setView([7.1, -73.1], 7);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 12,
                attribution: '&copy; Colaboradores de OpenStreetMap'
            }).addTo(estado.mapa);
            estado.capa = L.layerGroup().addTo(estado.mapa);
            setTimeout(function () { estado.mapa.invalidateSize(); }, 300);
        }

        estado.capa.clearLayers();
        var puntos = [];

        d.ciudades.forEach(function (c) {
            var circulo = L.circleMarker([c.lat, c.lon], {
                radius: 10 + 7 * Math.sqrt(c.n),
                color: '#FF4FB8',
                weight: 2,
                fillColor: '#D81B9C',
                fillOpacity: 0.55
            });
            var etiqueta = document.createElement('span');
            etiqueta.textContent = c.ciudad + ': ' + c.n + ' en línea';
            circulo.bindTooltip(etiqueta);
            circulo.addTo(estado.capa);
            puntos.push([c.lat, c.lon]);
        });

        if (puntos.length === 1) {
            estado.mapa.setView(puntos[0], 8);
        } else if (puntos.length > 1) {
            estado.mapa.fitBounds(puntos, { padding: [40, 40], maxZoom: 9 });
        } else {
            estado.mapa.setView([7.1, -73.1], 7);
        }
    }

    function cargarEnLinea() {
        return pedir('en_linea', 'en_linea').then(pintarEnLinea).catch(function (e) {
            if (e.name !== 'AbortError') {
                document.getElementById('actualizado').textContent = 'No se pudo actualizar: ' + e.message;
            }
        });
    }

    // ------------------------------------------------------------ asistentes

    function barras(id, datos, clave, formato, etiqueta) {
        var valores = datos.map(function (a) { return a[clave]; });
        var hay = valores.some(function (v) { return v > 0; });

        grafica(id, {
            type: 'bar',
            data: {
                labels: datos.map(function (a) { return a.nombre; }),
                datasets: [{ label: etiqueta, data: valores.map(function (v) { return formato(v); }), backgroundColor: COLORES, borderRadius: 8 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        }, hay);
    }

    function pintarUso(d) {
        estado.ultimoUso = d;
        var asistentes = d.rankings.todos;

        barras('chTiempo', asistentes, 'segundos', function (s) { return Math.round(s / 60 * 10) / 10; }, 'Minutos activos');
        barras('chUsuarias', asistentes, 'usuarias', function (v) { return v; }, 'Usuarias');
        barras('chMensajes', asistentes, 'mensajes', function (v) { return v; }, 'Mensajes');

        var cuerpo = document.querySelector('#tablaAsistentes tbody');
        vaciar(cuerpo);

        d.asistentes.forEach(function (a) {
            var medido = a.respuestas_medidas > 0;
            var celdas = [
                a.nombre, num(a.sesiones), num(a.usuarias), dur(a.segundos), dur(a.tiempo_medio_por_usuaria_seg),
                num(a.mensajes), pct(a.pct_voz), num(a.conversaciones),
                a.mensajes_por_conversacion === null ? '—' : dec(a.mensajes_por_conversacion, 1), pct(a.pct_abandono),
                medido && a.latencia_p50_ms !== null ? dec(a.latencia_p50_ms / 1000, 1) + ' s' : 'Sin medición',
                medido && a.latencia_p95_ms !== null ? dec(a.latencia_p95_ms / 1000, 1) + ' s' : 'Sin medición',
                num(a.sin_respuesta),
                medido ? num(a.tokens_entrada) + ' / ' + num(a.tokens_salida) : 'Sin medición',
                medido ? num(a.busquedas_web) : 'Sin medición',
                medido ? dec(a.costo_estimado_usd, 2) : 'Sin medición'
            ];

            cuerpo.appendChild(el('tr', {}, celdas.map(function (c) { return el('td', { texto: c }); })));
        });

        var p = d.precios;
        document.getElementById('avisoCostos').textContent =
            'Costo estimado (máximo, sin descuento por caché): total ' + dec(d.costo_total_usd, 2) + ' USD ≈ ' + num(d.costo_total_cop) +
            ' COP (referencia ' + num(p.trm_cop) + ' COP/USD). Precios usados: entrada ' + dec(p.entrada_usd_mtok, 2) + ' y salida ' + dec(p.salida_usd_mtok, 2) +
            ' USD por millón de tokens; búsqueda web ' + dec(p.busqueda_usd_1000, 2) + ' USD por 1 000. Son valores de referencia editables en AnaliticaConsultas; verifícalos con la tarifa vigente. «Sin medición» indica asistentes cuyas respuestas aún no guardan tokens ni latencia.';

        construirResumen();
    }

    // ------------------------------------------------------------ series

    function pintarSeries(d) {
        var etiquetas = d.dias.map(function (x) { return x.fecha.slice(8, 10) + '/' + x.fecha.slice(5, 7); });
        var hay = d.dias.some(function (x) { return x.sesiones > 0 || x.registros > 0; });

        grafica('chDias', {
            type: 'line',
            data: {
                labels: etiquetas,
                datasets: [
                    { label: 'Sesiones', data: d.dias.map(function (x) { return x.sesiones; }), borderColor: '#D81B9C', backgroundColor: 'rgba(216,27,156,.2)', tension: .3, fill: true },
                    { label: 'Usuarias únicas', data: d.dias.map(function (x) { return x.usuarias; }), borderColor: '#4DD0E1', tension: .3 },
                    { label: 'Registros nuevos', data: d.dias.map(function (x) { return x.registros; }), borderColor: '#FFB74D', borderDash: [6, 4], tension: .3 }
                ]
            },
            options: { interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        }, hay);

        grafica('chDispositivos', {
            type: 'doughnut',
            data: {
                labels: d.dispositivos.map(function (x) { return x.nombre; }),
                datasets: [{ data: d.dispositivos.map(function (x) { return x.n; }), backgroundColor: COLORES, borderColor: '#1a0f28' }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        }, d.dispositivos.length > 0);

        function lista(id, filas) {
            var ul = document.getElementById(id);
            vaciar(ul);
            if (!filas.length) {
                ul.appendChild(el('li', { texto: 'Sin datos' }));
            }
            filas.forEach(function (f) {
                ul.appendChild(el('li', {}, [el('span', { texto: f.nombre }), el('strong', { texto: num(f.n) })]));
            });
        }

        lista('listaSistemas', d.sistemas);
        lista('listaNavegadores', d.navegadores);

        pintarCalor(d.calor);

        barrasHorizontales('chCiudades', d.ciudades_ip, 'Sesiones');
        barrasHorizontales('chMunicipios', d.municipios_registrados, 'Sesiones');

        var geo = {};
        d.estado_geo.forEach(function (g) { geo[g.clave] = Number(g.n); });
        var total = Object.keys(geo).reduce(function (a, k) { return a + geo[k]; }, 0);
        document.getElementById('avisoGeo').textContent = total > 0
            ? 'Ciudad resuelta en ' + num(geo.ok || 0) + ' de ' + num(total) + ' sesiones; ' + num(geo.privada || 0) + ' con red privada o local, ' + num(geo.error || 0) + ' sin dato y ' + num(geo.pendiente || 0) + ' pendientes.'
            : 'Sin sesiones en el rango.';
    }

    function barrasHorizontales(id, filas, etiqueta) {
        grafica(id, {
            type: 'bar',
            data: {
                labels: filas.map(function (f) { return f.nombre; }),
                datasets: [{ label: etiqueta, data: filas.map(function (f) { return Number(f.n); }), backgroundColor: '#7B1FA2', borderRadius: 6 }]
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
        }, filas.length > 0);
    }

    function pintarCalor(calor) {
        var tabla = document.getElementById('mapaCalor');
        vaciar(tabla);

        var max = 0;
        calor.forEach(function (fila) { fila.forEach(function (n) { if (n > max) { max = n; } }); });

        var cabecera = el('tr', {}, [el('th', {})]);
        for (var h = 0; h < 24; h++) {
            cabecera.appendChild(el('th', { texto: String(h) }));
        }
        tabla.appendChild(el('thead', {}, [cabecera]));

        var cuerpo = el('tbody');

        calor.forEach(function (fila, i) {
            var tr = el('tr', {}, [el('th', { clase: 'dia', scope: 'row', texto: DIAS[i] })]);

            fila.forEach(function (n, hora) {
                var td = el('td', { texto: n > 0 ? String(n) : '', title: DIAS[i] + ' ' + hora + ':00 — ' + n + ' sesiones', 'aria-label': DIAS[i] + ' ' + hora + ' horas: ' + n + ' sesiones' });

                if (n > 0 && max > 0) {
                    td.style.background = 'rgba(216,27,156,' + (0.15 + 0.85 * n / max).toFixed(2) + ')';
                }

                tr.appendChild(td);
            });

            cuerpo.appendChild(tr);
        });

        tabla.appendChild(cuerpo);
    }

    // ------------------------------------------------------------ otras métricas

    function tarjetaMetrica(titulo, nodos) {
        return el('div', { clase: 'panel-card an-metrica' }, [
            el('div', { clase: 'panel-head' }, [el('div', {}, [el('h3', { texto: titulo })])]),
            el('div', { clase: 'panel-body' }, nodos)
        ]);
    }

    function listaMetrica(filas, vacio) {
        var ul = el('ul', { clase: 'an-lista' });

        if (!filas.length) {
            ul.appendChild(el('li', { texto: vacio || 'Sin datos en este rango.' }));
        }

        filas.forEach(function (f) {
            ul.appendChild(el('li', {}, [el('span', { texto: f[0] }), el('strong', { texto: f[1] })]));
        });

        return ul;
    }

    function pintarMetricas(m) {
        var cont = document.getElementById('metricas');
        vaciar(cont);

        var r7 = m.retencion.dias_7;
        var r30 = m.retencion.dias_30;
        cont.appendChild(tarjetaMetrica('Retención', [
            listaMetrica([
                ['A 7 días', r7.pct === null ? 'Sin cohorte' : pct(r7.pct) + ' (' + r7.volvieron + ' de ' + r7.elegibles + ')'],
                ['A 30 días', r30.pct === null ? 'Sin cohorte' : pct(r30.pct) + ' (' + r30.volvieron + ' de ' + r30.elegibles + ')']
            ]),
            el('p', { clase: 'an-cifra-sub', texto: 'De las usuarias cuya primera sesión cae en el rango (y ya tuvieron esos días para volver), % que inició otra sesión en un día distinto dentro de ese plazo.' })
        ]));

        cont.appendChild(tarjetaMetrica('Derivaciones entre asistentes', [
            el('div', { clase: 'an-cifra', texto: num(m.derivaciones.clics) }),
            el('div', { clase: 'an-cifra-sub', texto: 'clics en el botón de derivación' }),
            listaMetrica(m.derivaciones.sugeridas.map(function (s) { return [s.origen + ' → ' + s.destino, num(s.n)]; }), 'Sin sugerencias en este rango.')
        ]));

        cont.appendChild(tarjetaMetrica('Documentos generados', [
            el('div', { clase: 'an-cifra', texto: num(m.documentos.total) }),
            el('div', { clase: 'an-cifra-sub', texto: 'solo conteo por tipo, nunca el contenido' }),
            listaMetrica(m.documentos.por_tipo.filter(function (t) { return t.n > 0; }).map(function (t) { return [t.tipo, num(t.n)]; }), 'Sin documentos en este rango.')
        ]));

        cont.appendChild(tarjetaMetrica('Respuestas por audio', [
            el('div', { clase: 'an-cifra', texto: pct(m.audio.pct) }),
            el('div', { clase: 'an-cifra-sub', texto: num(m.audio.sesiones_con_audio) + ' de ' + num(m.audio.sesiones_con_asistente) + ' sesiones con asistente activaron el audio' })
        ]));

        var a = m.alertas;
        cont.appendChild(tarjetaMetrica('Operación de emergencias', [
            el('div', { clase: 'an-cifra', texto: num(a.total) }),
            el('div', { clase: 'an-cifra-sub', texto: 'alertas registradas en el rango' }),
            listaMetrica([
                ['Desde el chat de IA (nivel 3-4)', num(a.desde_chat)],
                ['Botón de pánico y otras', num(a.desde_panico)],
                ['Atendidas', num(a.atendidas) + ' (' + pct(a.pct_atendidas) + ')'],
                ['En atención', num(a.en_atencion)],
                ['Tiempo medio hasta abrirla', a.seg_hasta_apertura === null ? '—' : dur(a.seg_hasta_apertura)],
                ['Tiempo medio hasta la atención', a.seg_hasta_atencion === null ? '—' : dur(a.seg_hasta_atencion)]
            ])
        ]));

        var v = m.videollamadas;
        cont.appendChild(tarjetaMetrica('Videollamadas', [
            el('div', { clase: 'an-cifra', texto: num(v.invitaciones) }),
            el('div', { clase: 'an-cifra-sub', texto: 'invitaciones enviadas' }),
            listaMetrica([
                ['Usuarias que se unieron', num(v.unidas) + ' (' + pct(v.pct_unidas) + ')'],
                ['Por WhatsApp', num(v.whatsapp)],
                ['Por SMS', num(v.sms)]
            ])
        ]));

        cont.appendChild(tarjetaMetrica('Evidencias subidas por tipo', [
            el('div', { clase: 'an-cifra-sub', texto: 'solo conteo, nunca el contenido' }),
            listaMetrica(m.evidencias.map(function (e) { return [e.tipo, num(e.n)]; }), 'Sin evidencias en este rango.')
        ]));

        cont.appendChild(tarjetaMetrica('Páginas más vistas', [
            listaMetrica(m.paginas.map(function (p) { return [p.pagina, num(p.n)]; }), 'Sin visitas registradas en este rango.')
        ]));

        cont.appendChild(tarjetaMetrica('Gestiones por operadora', [
            el('div', { clase: 'an-cifra-sub', texto: 'gestiones de alertas registradas por el equipo del panel' }),
            listaMetrica(m.gestiones_operadoras.map(function (g) { return [g.operadora, num(g.n)]; }), 'Sin gestiones en este rango.')
        ]));

        estado.ultimasMetricas = m;
    }

    // ------------------------------------------------------------ tablas exportables

    function registrarExportacion(tabla, formato) {
        return enviar(Object.assign({ accion: 'registrar_exportacion', tabla: tabla, formato: formato }, estado.filtros))
            .then(function () { return true; })
            .catch(function (e) {
                mostrarGlobal('No se generó el archivo porque no se pudo registrar la exportación en la auditoría: ' + e.message);
                return false;
            });
    }

    /*
     * Ajusta la tabla del PDF (pdfmake) a la hoja A4 horizontal: ancho en puntos por
     * columna según su contenido, tipografía según el número de columnas y cortes
     * suaves (U+200B) en textos largos sin espacios para que siempre se partan en
     * lugar de salirse de la página.
     */
    function ajustarTablaPdf(doc) {
        var nodo = null;

        (doc.content || []).forEach(function (n) {
            if (n && n.table) {
                nodo = n;
            }
        });

        if (!nodo) {
            return;
        }

        var cuerpo = nodo.table.body;
        var ncol = cuerpo[0].length;
        var margen = 24;
        var anchoHoja = 842;
        var disponible = anchoHoja - margen * 2;
        var tam = ncol > 9 ? 6.5 : (ncol > 6 ? 7.5 : 9);
        var maxSinCorte = 12;
        var relleno = 3;

        doc.pageMargins = [margen, 46, margen, 38];
        doc.defaultStyle.fontSize = tam;
        doc.styles.tableHeader.fontSize = tam;

        var textoDe = function (celda) {
            return String(celda && typeof celda === 'object' ? (celda.text || '') : (celda === null || celda === undefined ? '' : celda));
        };

        var conCortes = function (texto) {
            return texto.replace(new RegExp('(\\S{' + maxSinCorte + '})(?=\\S)', 'g'), '$1​');
        };

        var suma = [];
        var maxPalabra = [];

        cuerpo.forEach(function (fila) {
            fila.forEach(function (celda, j) {
                var texto = conCortes(textoDe(celda));

                if (celda && typeof celda === 'object') {
                    celda.text = texto;
                } else {
                    fila[j] = texto;
                }

                suma[j] = (suma[j] || 0) + Math.min(texto.length, 40);
                var palabra = texto.split(/[\s​]+/).reduce(function (m, p) { return Math.max(m, p.length); }, 0);
                maxPalabra[j] = Math.max(maxPalabra[j] || 0, palabra);
            });
        });

        // pdfmake suma el relleno de cada celda al ancho indicado: se descuenta del espacio útil.
        var util = disponible - ncol * relleno * 2;
        var minimos = maxPalabra.map(function (p) { return Math.ceil(p * tam * 0.56 + 2); });
        var pesos = suma.map(function (s) { return Math.max(s / cuerpo.length, 4); });
        var totalMin = minimos.reduce(function (a, b) { return a + b; }, 0);
        var sobrante = Math.max(util - totalMin, 0);
        var totalPesos = pesos.reduce(function (a, b) { return a + b; }, 0);

        nodo.table.widths = minimos.map(function (m, j) {
            return Math.floor(m + sobrante * pesos[j] / totalPesos);
        });
        nodo.table.dontBreakRows = true;
        nodo.layout = {
            hLineWidth: function () { return 0.4; },
            vLineWidth: function () { return 0; },
            hLineColor: function () { return '#d9c9e2'; },
            paddingLeft: function () { return relleno; },
            paddingRight: function () { return relleno; },
            paddingTop: function () { return 2; },
            paddingBottom: function () { return 2; }
        };
    }

    function botonesExportacion(tabla, titulo, columnas) {
        var comun = function () {
            return {
                exportOptions: { columns: columnas, orthogonal: 'display' },
                title: function () { return 'Aliada UTS · Analítica de uso · ' + titulo; },
                filename: function () { return 'aliada_analitica_' + tabla + '_' + estado.filtros.desde + '_' + estado.filtros.hasta; },
                messageTop: function () { return textoFiltros() + ' · Generado: ' + new Date().toLocaleString('es-CO'); },
                messageBottom: function () { return 'Generado por ' + C.usuarioPanel + ' — Documento con datos personales, uso interno.'; }
            };
        };

        function conAuditoria(base, formato) {
            return function (e, dt, node, config, cb) {
                var contexto = this;
                registrarExportacion(tabla, formato).then(function (ok) {
                    if (ok) {
                        $.fn.dataTable.ext.buttons[base].action.call(contexto, e, dt, node, config, cb);
                    }
                });
            };
        }

        return [
            Object.assign({ extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', action: conAuditoria('excelHtml5', 'excel') }, comun()),
            Object.assign({
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                orientation: 'landscape',
                pageSize: 'A4',
                action: conAuditoria('pdfHtml5', 'pdf'),
                customize: function (doc) {
                    ajustarTablaPdf(doc);
                    doc.footer = function (pagina, paginas) {
                        return { text: 'Documento con datos personales, uso interno — página ' + pagina + ' de ' + paginas, alignment: 'center', fontSize: 7, margin: [0, 6, 0, 0] };
                    };
                }
            }, comun())
        ];
    }

    function aviso(id, d) {
        var caja = document.getElementById(id);

        if (d.excede) {
            caja.hidden = false;
            caja.textContent = 'El rango tiene ' + num(d.total) + ' filas y supera el máximo de ' + num(d.limite) + ' para esta tabla. Acota las fechas o los filtros.';
        } else {
            caja.hidden = true;
        }
    }

    function crearTablas() {
        var domTabla = "<'row align-items-center'<'col-lg-6 mb-2'B><'col-lg-6 mb-2'f>>rt<'row align-items-center'<'col-md-5'i><'col-md-7'p>>";

        estado.tablaSesiones = $('#tablaSesiones').DataTable({
            data: [],
            deferRender: true,
            responsive: true,
            pageLength: 25,
            order: [[1, 'desc']],
            language: IDIOMA,
            dom: domTabla,
            buttons: botonesExportacion('sesiones', 'Sesiones', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]),
            columns: [
                { data: 'seudonimo', render: TEXTO },
                { data: 'inicio', render: function (v, t) { return t === 'display' ? fecha(v) : v; } },
                { data: 'fin', render: function (v, t) { return v ? (t === 'display' ? fecha(v) : v) : (t === 'display' ? '—' : ''); } },
                { data: 'duracion_seg', render: function (v, t) { return (t === 'display' || t === 'export') ? dur(v) : (v === null ? -1 : v); } },
                { data: 'cierre', render: TEXTO },
                { data: 'ciudad_ip', render: TEXTO },
                { data: 'municipio_registrado', render: TEXTO },
                { data: 'dispositivo', render: TEXTO },
                { data: 'sistema', render: TEXTO },
                { data: 'navegador', render: TEXTO },
                { data: 'ip_mascara', render: TEXTO },
                {
                    data: null, orderable: false, searchable: false, className: 'no-export',
                    render: function (d, t, fila) {
                        if (t !== 'display') {
                            return '';
                        }
                        return '<button type="button" class="an-accion" data-tipo="nombre" data-uid="' + Number(fila.uid) + '" data-cod="' + String(fila.seudonimo).replace(/[^A-Z0-9-]/g, '') + '">Ver identidad</button>'
                            + '<button type="button" class="an-accion" data-tipo="ip" data-sid="' + Number(fila.sid) + '" data-cod="' + String(fila.seudonimo).replace(/[^A-Z0-9-]/g, '') + '">Ver IP</button>';
                    }
                }
            ]
        });

        estado.tablaUso = $('#tablaUso').DataTable({
            data: [],
            deferRender: true,
            responsive: true,
            pageLength: 25,
            order: [[3, 'desc']],
            language: IDIOMA,
            dom: domTabla,
            buttons: botonesExportacion('uso_detalle', 'Uso por usuaria y asistente', [0, 1, 2, 3, 4]),
            columns: [
                { data: 'seudonimo', render: TEXTO },
                { data: 'asistente', render: TEXTO },
                { data: 'sesiones', render: TEXTO },
                { data: 'segundos', render: function (v, t) { return (t === 'display' || t === 'export') ? dur(v) : v; } },
                { data: 'ultimo', render: function (v, t) { return t === 'display' ? fecha(v) : v; } },
                {
                    data: null, orderable: false, searchable: false, className: 'no-export',
                    render: function (d, t, fila) {
                        return t === 'display'
                            ? '<button type="button" class="an-accion" data-tipo="nombre" data-uid="' + Number(fila.uid) + '" data-cod="' + String(fila.seudonimo).replace(/[^A-Z0-9-]/g, '') + '">Ver identidad</button>'
                            : '';
                    }
                }
            ]
        });

        estado.tablaResumen = $('#tablaResumen').DataTable({
            data: [],
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            dom: 'B',
            language: IDIOMA,
            buttons: botonesExportacion('resumen', 'Resumen', [0, 1, 2, 3]),
            columns: [{ data: 0, render: TEXTO }, { data: 1, render: TEXTO }, { data: 2, render: TEXTO }, { data: 3, render: TEXTO }]
        });

        $('#tablaSesiones, #tablaUso').on('click', '.an-accion', function () {
            var b = this;
            abrirRevelar(b.getAttribute('data-tipo'), Number(b.getAttribute('data-tipo') === 'ip' ? b.getAttribute('data-sid') : b.getAttribute('data-uid')), b.getAttribute('data-cod'));
        });

        document.getElementById('btnResumenExcel').addEventListener('click', function () {
            $('#tablaResumen_wrapper .buttons-excel').trigger('click');
        });

        document.getElementById('btnResumenPdf').addEventListener('click', function () {
            $('#tablaResumen_wrapper .buttons-pdf').trigger('click');
        });
    }

    function construirResumen() {
        if (!estado.tablaResumen || !estado.ultimosKpis || !estado.ultimoUso) {
            return;
        }

        var k = estado.ultimosKpis;
        var filas = [
            ['Indicadores', 'En línea ahora', num(k.en_linea), 'Sin relación con el rango de fechas'],
            ['Indicadores', 'Sesiones', num(k.sesiones), num(k.sesiones_abiertas) + ' abiertas'],
            ['Indicadores', 'Usuarias únicas', num(k.usuarias_unicas), ''],
            ['Indicadores', 'Duración media de sesión', dur(k.duracion_media_seg), 'Sesiones cerradas'],
            ['Indicadores', 'Duración mediana de sesión', dur(k.duracion_mediana_seg), 'Sesiones cerradas'],
            ['Indicadores', 'Tiempo activo en asistentes', dur(k.tiempo_activo_total_seg), ''],
            ['Indicadores', 'Asistente más usado', k.asistente_mas_usado ? k.asistente_mas_usado.nombre : 'Sin datos', k.asistente_mas_usado ? dur(k.asistente_mas_usado.segundos) : ''],
            ['Indicadores', 'Registros nuevos', num(k.registros_nuevos), ''],
            ['Indicadores', 'Usuarias activas (diarias · semanales · mensuales)', num(k.activas.diarias) + ' · ' + num(k.activas.semanales) + ' · ' + num(k.activas.mensuales), '']
        ];

        estado.ultimoUso.asistentes.forEach(function (a) {
            filas.push(['Asistentes', a.nombre, dur(a.segundos) + ' de tiempo activo',
                num(a.usuarias) + ' usuarias · ' + num(a.sesiones) + ' sesiones · ' + num(a.mensajes) + ' mensajes · ' + pct(a.pct_voz) + ' por voz'
                + (a.respuestas_medidas > 0 ? ' · costo estimado ' + dec(a.costo_estimado_usd, 2) + ' USD' : '')]);
        });

        estado.tablaResumen.clear().rows.add(filas).draw();
    }

    // ------------------------------------------------------------ revelar datos protegidos

    function abrirRevelar(tipo, id, codigo) {
        estado.reveal = { tipo: tipo, id: id };
        limpiarReveal();

        document.getElementById('modalRevelarTitulo').textContent = tipo === 'ip' ? 'Ver IP completa de la sesión' : 'Ver identidad de ' + codigo;
        document.getElementById('modalRevelarDescripcion').textContent = tipo === 'ip'
            ? 'La IP completa es un dato personal. Indica por qué la necesitas; el acceso queda registrado con tu usuario.'
            : 'El nombre es un dato personal. Indica por qué lo necesitas; el acceso queda registrado con tu usuario.';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRevelar')).show();
    }

    function limpiarReveal() {
        clearInterval(estado.revealTimer);
        document.getElementById('revelarValor').textContent = '';
        document.getElementById('revelarResultado').hidden = true;
        document.getElementById('revelarFormulario').hidden = false;
        document.getElementById('revelarConfirmar').hidden = false;
        document.getElementById('revelarConfirmar').disabled = false;
        document.getElementById('revelarMotivo').value = '';
        document.getElementById('revelarContador').textContent = '0';
        var error = document.getElementById('revelarError');
        error.hidden = true;
        error.textContent = '';
    }

    function iniciarReveal() {
        var motivo = document.getElementById('revelarMotivo').value.trim();
        var error = document.getElementById('revelarError');

        if (motivo.length < C.minMotivo) {
            error.hidden = false;
            error.textContent = 'Escribe un motivo de al menos ' + C.minMotivo + ' caracteres.';
            return;
        }

        error.hidden = true;
        var boton = document.getElementById('revelarConfirmar');
        boton.disabled = true;

        var cuerpo = estado.reveal.tipo === 'ip'
            ? { accion: 'revelar_ip', sesion_id: estado.reveal.id, motivo: motivo }
            : { accion: 'revelar_identidad', usuario_id: estado.reveal.id, motivo: motivo };

        enviar(cuerpo).then(function (j) {
            var valor = estado.reveal.tipo === 'ip' ? j.ip + (j.anonimizada ? ' (anonimizada por retención)' : '') : j.nombre;
            document.getElementById('revelarFormulario').hidden = true;
            boton.hidden = true;
            document.getElementById('revelarValor').textContent = valor;
            document.getElementById('revelarResultado').hidden = false;

            var restante = j.expira_seg || 60;
            var cuenta = document.getElementById('revelarCuenta');
            cuenta.textContent = String(restante);

            estado.revealTimer = setInterval(function () {
                restante -= 1;
                cuenta.textContent = String(Math.max(restante, 0));

                if (restante <= 0) {
                    limpiarReveal();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRevelar')).hide();
                }
            }, 1000);
        }).catch(function (e) {
            boton.disabled = false;
            error.hidden = false;
            error.textContent = e.message;
        });
    }

    // ------------------------------------------------------------ carga

    function cargarTodo() {
        var cont = document.getElementById('kpis');
        mostrarGlobal('');
        ['excedeSesiones', 'excedeUso'].forEach(function (id) { document.getElementById(id).hidden = true; });

        pedir('kpis', 'kpis').then(pintarKpis).catch(function (e) {
            if (e.name !== 'AbortError') {
                errorSeccion(cont, e.message, cargarTodo);
            }
        });

        pedir('series', 'series').then(pintarSeries).catch(function (e) {
            if (e.name !== 'AbortError') {
                window.console && console.error('series', e.message);
            }
        });

        pedir('uso', 'uso_asistentes').then(pintarUso).catch(function (e) {
            if (e.name !== 'AbortError') {
                errorFila(document.querySelector('#tablaAsistentes tbody'), 16, e.message);
            }
        });

        pedir('metricas', 'metricas').then(pintarMetricas).catch(function (e) {
            if (e.name !== 'AbortError') {
                errorSeccion(document.getElementById('metricas'), e.message, cargarTodo);
            }
        });

        pedir('sesiones', 'sesiones').then(function (d) {
            aviso('excedeSesiones', d);
            estado.tablaSesiones.clear().rows.add(d.excede ? [] : d.filas).draw();
        }).catch(function (e) {
            if (e.name !== 'AbortError') {
                aviso('excedeSesiones', { excede: true, total: 0, limite: 0 });
                document.getElementById('excedeSesiones').textContent = e.message;
            }
        });

        pedir('uso_detalle', 'uso_detalle').then(function (d) {
            aviso('excedeUso', d);
            estado.tablaUso.clear().rows.add(d.excede ? [] : d.filas).draw();
        }).catch(function (e) {
            if (e.name !== 'AbortError') {
                aviso('excedeUso', { excede: true, total: 0, limite: 0 });
                document.getElementById('excedeUso').textContent = e.message;
            }
        });

        cargarEnLinea();
    }

    function iniciar() {
        configurarChart();
        crearTablas();

        document.getElementById('fDesde').value = sumarDias(C.hoy, -29);
        document.getElementById('fHasta').value = C.hoy;

        document.getElementById('formFiltros').addEventListener('submit', function (e) {
            e.preventDefault();
            aplicar();
        });

        document.querySelectorAll('[data-rango]').forEach(function (b) {
            b.addEventListener('click', function () { aplicarRango(b.getAttribute('data-rango')); });
        });

        document.getElementById('btnRestablecer').addEventListener('click', function () {
            document.getElementById('fAsistente').value = '';
            document.getElementById('fDispositivo').value = '';
            document.getElementById('fMunicipio').value = '';
            aplicarRango('30');
        });

        var motivo = document.getElementById('revelarMotivo');
        motivo.addEventListener('input', function () {
            document.getElementById('revelarContador').textContent = String(motivo.value.length);
        });

        document.getElementById('revelarConfirmar').addEventListener('click', iniciarReveal);
        document.getElementById('modalRevelar').addEventListener('hidden.bs.modal', limpiarReveal);

        document.querySelectorAll('.an-tab').forEach(function (t) {
            t.addEventListener('shown.bs.tab', function () {
                estado.tablaSesiones.columns.adjust().responsive.recalc();
                estado.tablaUso.columns.adjust().responsive.recalc();
            });
        });

        aplicar();

        estado.timerEnLinea = setInterval(function () {
            if (document.visibilityState === 'visible' && estado.filtros) {
                cargarEnLinea();
            }
        }, C.refrescoSeg * 1000);
    }

    document.addEventListener('DOMContentLoaded', iniciar);
})();
