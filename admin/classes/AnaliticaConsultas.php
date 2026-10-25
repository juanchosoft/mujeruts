<?php

/**
 * Consultas del dashboard de analítica (analitica.php). Todo se calcula con sentencias preparadas;
 * los filtros se validan antes y nunca se concatenan al SQL.
 */
class AnaliticaConsultas
{
    const ZONA = 'America/Bogota';
    const MAX_DIAS = 366;
    const LIMITE_FILAS_DETALLE = 20000;
    const SEGUNDOS_EN_LINEA = 150;
    const MIN_MOTIVO = 15;
    const MAX_MOTIVO = 300;

    const ASISTENTES = [
        'psicologico' => 'Violeta IA (psicológico)',
        'trabajo_social' => 'Asistente social',
        'juridico' => 'Asistente jurídica',
        'lgbtiq' => 'Asistente LGBTQ+'
    ];

    const DISPOSITIVOS = ['movil' => 'Móvil', 'tablet' => 'Tablet', 'escritorio' => 'Escritorio', 'desconocido' => 'Desconocido'];

    const ETIQUETAS_PAGINAS = [
        'panel_usuaria' => 'Panel de la usuaria',
        'emergencia' => 'Emergencia',
        'mis_evidencias' => 'Mis evidencias',
        'videollamada' => 'Videollamada',
        'violencia_intrafamiliar' => 'Violencia intrafamiliar',
        'maltrato' => 'Maltrato',
        'asistencia_psicologica_v2' => 'Violeta IA (psicológico)',
        'asistencia_trabajo_social_v2' => 'Asistente social',
        'asistencia_legal_v2' => 'Asistente jurídica',
        'asistente_legal_v2' => 'Asistente LGBTQ+'
    ];

    const TIPOS_DOCUMENTO = [
        'tutela' => 'Acción de tutela',
        'derecho_peticion' => 'Derecho de petición',
        'queja' => 'Queja',
        'denuncia' => 'Denuncia',
        'solicitud' => 'Solicitud',
        'carta' => 'Carta',
        'cronologia' => 'Cronología',
        'estudio_social' => 'Estudio social',
        'otro' => 'Otro'
    ];

    /**
     * Precios de referencia para la ESTIMACIÓN de costo de IA (USD por millón de tokens y por
     * 1 000 búsquedas web). Son editables: verifícalos con la tarifa vigente del proveedor.
     * tokens_entrada incluye tokens leídos de caché, que se cobran a menor tarifa, así que el
     * resultado es un máximo estimado.
     */
    const PRECIO_ENTRADA_USD_POR_MTOK = 2.00;
    const PRECIO_SALIDA_USD_POR_MTOK = 10.00;
    const PRECIO_BUSQUEDA_USD_POR_1000 = 10.00;
    const TRM_COP_REFERENCIA = 4000;

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ahora()
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::ZONA));
    }

    public static function seudonimo($usuarioId)
    {
        return 'U-' . str_pad((string) (int) $usuarioId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Valida y normaliza los filtros. Lanza InvalidArgumentException con un mensaje apto para la usuaria del panel.
     */
    public function validarFiltros(array $entrada)
    {
        $zona = new DateTimeZone(self::ZONA);
        $hoy = $this->ahora()->setTime(0, 0, 0);

        $leer = function ($clave, DateTimeImmutable $defecto) use ($entrada, $zona) {
            if (!isset($entrada[$clave]) || $entrada[$clave] === '') {
                return $defecto;
            }

            $valor = $entrada[$clave];

            if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                throw new InvalidArgumentException('Las fechas deben tener el formato AAAA-MM-DD.');
            }

            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor, $zona);

            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                throw new InvalidArgumentException('La fecha ' . $valor . ' no es válida.');
            }

            return $fecha;
        };

        $hasta = $leer('hasta', $hoy);
        $desde = $leer('desde', $hasta->modify('-29 days'));

        if ($desde > $hasta) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la final.');
        }

        $dias = (int) $desde->diff($hasta)->days + 1;

        if ($dias > self::MAX_DIAS) {
            throw new InvalidArgumentException('El rango máximo es de ' . self::MAX_DIAS . ' días.');
        }

        $asistente = $entrada['asistente'] ?? '';

        if ($asistente !== '' && (!is_string($asistente) || !isset(self::ASISTENTES[$asistente]))) {
            throw new InvalidArgumentException('Asistente no válido.');
        }

        $dispositivo = $entrada['dispositivo'] ?? '';

        if ($dispositivo !== '' && (!is_string($dispositivo) || !isset(self::DISPOSITIVOS[$dispositivo]))) {
            throw new InvalidArgumentException('Dispositivo no válido.');
        }

        $municipio = $entrada['municipio'] ?? '';

        if ($municipio !== '' && (!is_string($municipio) || !preg_match('/^\d{5}$/', $municipio))) {
            throw new InvalidArgumentException('Municipio no válido.');
        }

        return [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'desde_dt' => $desde->format('Y-m-d') . ' 00:00:00',
            'hasta_dt' => $hasta->format('Y-m-d') . ' 23:59:59',
            'dias' => $dias,
            'asistente' => $asistente !== '' ? $asistente : null,
            'dispositivo' => $dispositivo !== '' ? $dispositivo : null,
            'municipio' => $municipio !== '' ? (int) $municipio : null
        ];
    }

    /**
     * Fragmento WHERE para sesiones (alias $s; usuarios con alias $u). Los parámetros llevan $sufijo
     * para poder usar el mismo filtro más de una vez en una sentencia.
     *
     * @return array [sql, params]
     */
    private function filtroSesiones(array $f, $sufijo = '', $desdeDt = null, $hastaDt = null, $conAsistente = true)
    {
        $sql = 's.fecha_inicio >= :desde' . $sufijo . ' AND s.fecha_inicio <= :hasta' . $sufijo;
        $params = [
            ':desde' . $sufijo => $desdeDt ?? $f['desde_dt'],
            ':hasta' . $sufijo => $hastaDt ?? $f['hasta_dt']
        ];

        if ($f['dispositivo'] !== null) {
            $sql .= ' AND s.dispositivo = :disp' . $sufijo;
            $params[':disp' . $sufijo] = $f['dispositivo'];
        }

        if ($f['municipio'] !== null) {
            $sql .= ' AND u.tbl_municipio_id = :mun' . $sufijo;
            $params[':mun' . $sufijo] = $f['municipio'];
        }

        if ($conAsistente && $f['asistente'] !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM tbl_analitica_uso_asistente fa WHERE fa.sesion_id = s.id AND fa.asistente = :asis' . $sufijo . ')';
            $params[':asis' . $sufijo] = $f['asistente'];
        }

        return [$sql, $params];
    }

    /** Filtro para filas de uso (alias ua; sesiones s; usuarios u). */
    private function filtroUso(array $f, $sufijo = '')
    {
        $sql = 'ua.fecha_inicio >= :desde' . $sufijo . ' AND ua.fecha_inicio <= :hasta' . $sufijo;
        $params = [':desde' . $sufijo => $f['desde_dt'], ':hasta' . $sufijo => $f['hasta_dt']];

        if ($f['asistente'] !== null) {
            $sql .= ' AND ua.asistente = :asis' . $sufijo;
            $params[':asis' . $sufijo] = $f['asistente'];
        }

        if ($f['dispositivo'] !== null) {
            $sql .= ' AND s.dispositivo = :disp' . $sufijo;
            $params[':disp' . $sufijo] = $f['dispositivo'];
        }

        if ($f['municipio'] !== null) {
            $sql .= ' AND u.tbl_municipio_id = :mun' . $sufijo;
            $params[':mun' . $sufijo] = $f['municipio'];
        }

        return [$sql, $params];
    }

    /** Filtro para mensajes de chat (alias m; conversaciones c; usuarios u). El dispositivo no aplica. */
    private function filtroMensajes(array $f, $sufijo = '')
    {
        $sql = 'm.fecha_creacion >= :desde' . $sufijo . ' AND m.fecha_creacion <= :hasta' . $sufijo;
        $params = [':desde' . $sufijo => $f['desde_dt'], ':hasta' . $sufijo => $f['hasta_dt']];

        if ($f['asistente'] !== null) {
            $sql .= ' AND c.asistente = :asis' . $sufijo;
            $params[':asis' . $sufijo] = $f['asistente'];
        }

        if ($f['municipio'] !== null) {
            $sql .= ' AND u.tbl_municipio_id = :mun' . $sufijo;
            $params[':mun' . $sufijo] = $f['municipio'];
        }

        return [$sql, $params];
    }

    private function filas($sql, array $params)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function valor($sql, array $params)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function percentil(array $ordenados, $p)
    {
        $n = count($ordenados);

        if ($n === 0) {
            return null;
        }

        return (int) $ordenados[(int) max(0, min($n - 1, ceil($p * $n) - 1))];
    }

    // ---------------------------------------------------------------- KPIs

    public function kpis(array $f)
    {
        $umbral = $this->ahora()->modify('-' . self::SEGUNDOS_EN_LINEA . ' seconds')->format('Y-m-d H:i:s');

        [$wOnline, $pOnline] = $this->filtroSesiones($f, 'o', '1000-01-01 00:00:00', '9999-12-31 23:59:59', false);
        $enLinea = (int) $this->valor(
            'SELECT COUNT(DISTINCT s.usuario_id) FROM tbl_analitica_sesiones s
             LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id
             WHERE s.fecha_fin IS NULL AND s.fecha_ultima_actividad >= :umbral AND ' . $wOnline,
            $pOnline + [':umbral' => $umbral]
        );

        [$w, $p] = $this->filtroSesiones($f);
        $base = 'FROM tbl_analitica_sesiones s LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id WHERE ' . $w;

        $resumen = $this->filas(
            'SELECT COUNT(*) sesiones, COUNT(DISTINCT s.usuario_id) usuarias,
                    SUM(s.fecha_fin IS NULL) abiertas,
                    AVG(s.duracion_segundos) duracion_media,
                    SUM(s.duracion_segundos IS NOT NULL) cerradas ' . $base,
            $p
        )[0];

        $cerradas = (int) $resumen['cerradas'];
        $mediana = null;

        if ($cerradas > 0) {
            $offset = (int) floor(($cerradas - 1) / 2);
            $stmt = $this->pdo->prepare(
                'SELECT s.duracion_segundos ' . $base . ' AND s.duracion_segundos IS NOT NULL
                 ORDER BY s.duracion_segundos LIMIT 2 OFFSET ' . $offset
            );
            $stmt->execute($p);
            $valores = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            $mediana = ($cerradas % 2 === 1) ? $valores[0] : (int) round(($valores[0] + ($valores[1] ?? $valores[0])) / 2);
        }

        [$wu, $pu] = $this->filtroUso($f);
        $tiempoActivo = (int) $this->valor(
            'SELECT COALESCE(SUM(ua.segundos_activos), 0) FROM tbl_analitica_uso_asistente ua
             LEFT JOIN tbl_analitica_sesiones s ON s.id = ua.sesion_id
             LEFT JOIN tbl_usuarios u ON u.id = ua.usuario_id WHERE ' . $wu,
            $pu
        );

        $registrosSql = 'SELECT COUNT(*) FROM tbl_usuarios u WHERE u.dtcreate >= :desde AND u.dtcreate <= :hasta';
        $registrosP = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']];

        if ($f['municipio'] !== null) {
            $registrosSql .= ' AND u.tbl_municipio_id = :mun';
            $registrosP[':mun'] = $f['municipio'];
        }

        $rankings = $this->rankingsAsistentes($f);

        return [
            'en_linea' => $enLinea,
            'sesiones' => (int) $resumen['sesiones'],
            'usuarias_unicas' => (int) $resumen['usuarias'],
            'sesiones_abiertas' => (int) $resumen['abiertas'],
            'duracion_media_seg' => $resumen['duracion_media'] !== null ? (int) round((float) $resumen['duracion_media']) : null,
            'duracion_mediana_seg' => $mediana,
            'tiempo_activo_total_seg' => $tiempoActivo,
            'registros_nuevos' => (int) $this->valor($registrosSql, $registrosP),
            'asistente_mas_usado' => $rankings['por_tiempo'][0] ?? null,
            'rankings' => $rankings,
            'activas' => $this->usuariasActivas($f)
        ];
    }

    /**
     * Tres rankings: por tiempo activo, por usuarias únicas y por mensajes (asistentes con chat de IA).
     */
    public function rankingsAsistentes(array $f)
    {
        [$wu, $pu] = $this->filtroUso($f);
        $uso = $this->filas(
            'SELECT ua.asistente, COUNT(DISTINCT ua.sesion_id) sesiones, COUNT(DISTINCT ua.usuario_id) usuarias,
                    COALESCE(SUM(ua.segundos_activos), 0) segundos
             FROM tbl_analitica_uso_asistente ua
             LEFT JOIN tbl_analitica_sesiones s ON s.id = ua.sesion_id
             LEFT JOIN tbl_usuarios u ON u.id = ua.usuario_id
             WHERE ' . $wu . ' GROUP BY ua.asistente',
            $pu
        );

        [$wm, $pm] = $this->filtroMensajes($f);
        $mensajes = $this->filas(
            "SELECT c.asistente, COUNT(*) mensajes, COALESCE(SUM(m.origen_mensaje = 'voz'), 0) voz
             FROM tbl_chat_mensajes m JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
             LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
             WHERE m.rol = 'usuaria' AND " . $wm . ' GROUP BY c.asistente',
            $pm
        );

        $mapa = [];

        foreach (array_keys(self::ASISTENTES) as $slug) {
            $mapa[$slug] = ['asistente' => $slug, 'nombre' => self::ASISTENTES[$slug], 'sesiones' => 0, 'usuarias' => 0, 'segundos' => 0, 'mensajes' => 0, 'voz' => 0];
        }

        foreach ($uso as $fila) {
            if (isset($mapa[$fila['asistente']])) {
                $mapa[$fila['asistente']]['sesiones'] = (int) $fila['sesiones'];
                $mapa[$fila['asistente']]['usuarias'] = (int) $fila['usuarias'];
                $mapa[$fila['asistente']]['segundos'] = (int) $fila['segundos'];
            }
        }

        foreach ($mensajes as $fila) {
            if (isset($mapa[$fila['asistente']])) {
                $mapa[$fila['asistente']]['mensajes'] = (int) $fila['mensajes'];
                $mapa[$fila['asistente']]['voz'] = (int) $fila['voz'];
            }
        }

        $lista = array_values($mapa);

        $ordenar = function ($clave) use ($lista) {
            $copia = array_values(array_filter($lista, function ($a) use ($clave) { return $a[$clave] > 0; }));
            usort($copia, function ($a, $b) use ($clave) { return $b[$clave] <=> $a[$clave]; });
            return $copia;
        };

        return [
            'todos' => $lista,
            'por_tiempo' => $ordenar('segundos'),
            'por_usuarias' => $ordenar('usuarias'),
            'por_mensajes' => $ordenar('mensajes')
        ];
    }

    /**
     * Usuarias activas el día final del rango y en las ventanas de 7 y 30 días que terminan ese día.
     */
    public function usuariasActivas(array $f)
    {
        $resultado = [];

        foreach ([1 => 'diarias', 7 => 'semanales', 30 => 'mensuales'] as $dias => $clave) {
            $desde = (new DateTimeImmutable($f['hasta'], new DateTimeZone(self::ZONA)))->modify('-' . ($dias - 1) . ' days')->format('Y-m-d') . ' 00:00:00';
            [$w, $p] = $this->filtroSesiones($f, '', $desde, $f['hasta_dt']);

            $resultado[$clave] = (int) $this->valor(
                'SELECT COUNT(DISTINCT s.usuario_id) FROM tbl_analitica_sesiones s
                 LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id WHERE ' . $w,
                $p
            );
        }

        return $resultado;
    }

    // ---------------------------------------------------------------- Tiempo real

    public function enLinea(array $f)
    {
        $umbral = $this->ahora()->modify('-' . self::SEGUNDOS_EN_LINEA . ' seconds')->format('Y-m-d H:i:s');
        [$w, $p] = $this->filtroSesiones($f, 'o', '1000-01-01 00:00:00', '9999-12-31 23:59:59', false);

        $filas = $this->filas(
            'SELECT s.id, s.usuario_id, s.fecha_inicio, s.pagina_actual, s.dispositivo, s.geo_estado, s.geo_ciudad,
                    s.geo_region, s.geo_pais, s.geo_lat, s.geo_lon, s.ip, (SELECT MIN(c.municipio) FROM tbl_ciudades c WHERE c.codigo_muncipio = LPAD(u.tbl_municipio_id, 5, CHAR(48))) municipio
             FROM tbl_analitica_sesiones s
             LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id
             WHERE s.fecha_fin IS NULL AND s.fecha_ultima_actividad >= :umbral AND ' . $w . '
             ORDER BY s.fecha_inicio DESC LIMIT 300',
            $p + [':umbral' => $umbral]
        );

        $lista = [];
        $ciudades = [];

        foreach ($filas as $fila) {
            $pagina = (string) $fila['pagina_actual'];

            $lista[] = [
                'sid' => (int) $fila['id'],
                'uid' => (int) $fila['usuario_id'],
                'seudonimo' => self::seudonimo($fila['usuario_id']),
                'pagina' => self::ETIQUETAS_PAGINAS[$pagina] ?? '—',
                'desde' => $fila['fecha_inicio'],
                'dispositivo' => self::DISPOSITIVOS[$fila['dispositivo']] ?? '—',
                'ciudad_ip' => $fila['geo_estado'] === 'ok' ? trim(($fila['geo_ciudad'] ?? '') . ($fila['geo_pais'] ? ' (' . $fila['geo_pais'] . ')' : '')) : $this->etiquetaGeo($fila['geo_estado']),
                'municipio_registrado' => $fila['municipio'] ?? '—',
                'ip_mascara' => AnaliticaService::enmascararIp((string) $fila['ip'])
            ];

            if ($fila['geo_estado'] === 'ok' && $fila['geo_lat'] !== null && $fila['geo_lon'] !== null) {
                $clave = $fila['geo_ciudad'] . '|' . $fila['geo_pais'] . '|' . $fila['geo_lat'] . '|' . $fila['geo_lon'];

                if (!isset($ciudades[$clave])) {
                    $ciudades[$clave] = [
                        'ciudad' => ($fila['geo_ciudad'] ?: 'Ciudad desconocida') . ($fila['geo_pais'] ? ' (' . $fila['geo_pais'] . ')' : ''),
                        'lat' => (float) $fila['geo_lat'],
                        'lon' => (float) $fila['geo_lon'],
                        'n' => 0
                    ];
                }

                $ciudades[$clave]['n']++;
            }
        }

        return [
            'total' => count($lista),
            'sesiones' => $lista,
            'ciudades' => array_values($ciudades)
        ];
    }

    private function etiquetaGeo($estado)
    {
        return ['pendiente' => 'Pendiente', 'privada' => 'Red privada / local', 'error' => 'Sin dato'][$estado] ?? '—';
    }

    // ---------------------------------------------------------------- Series y distribuciones

    public function series(array $f)
    {
        [$w, $p] = $this->filtroSesiones($f);
        $from = 'FROM tbl_analitica_sesiones s LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id WHERE ' . $w;

        $dias = [];
        $cursor = new DateTimeImmutable($f['desde'], new DateTimeZone(self::ZONA));
        $fin = new DateTimeImmutable($f['hasta'], new DateTimeZone(self::ZONA));

        while ($cursor <= $fin) {
            $dias[$cursor->format('Y-m-d')] = ['fecha' => $cursor->format('Y-m-d'), 'sesiones' => 0, 'usuarias' => 0, 'registros' => 0];
            $cursor = $cursor->modify('+1 day');
        }

        foreach ($this->filas('SELECT DATE(s.fecha_inicio) g_dia, COUNT(*) sesiones, COUNT(DISTINCT s.usuario_id) usuarias ' . $from . ' GROUP BY g_dia', $p) as $fila) {
            if (isset($dias[$fila['g_dia']])) {
                $dias[$fila['g_dia']]['sesiones'] = (int) $fila['sesiones'];
                $dias[$fila['g_dia']]['usuarias'] = (int) $fila['usuarias'];
            }
        }

        $sqlReg = 'SELECT DATE(u.dtcreate) g_dia, COUNT(*) n FROM tbl_usuarios u WHERE u.dtcreate >= :desde AND u.dtcreate <= :hasta';
        $pReg = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']];

        if ($f['municipio'] !== null) {
            $sqlReg .= ' AND u.tbl_municipio_id = :mun';
            $pReg[':mun'] = $f['municipio'];
        }

        foreach ($this->filas($sqlReg . ' GROUP BY g_dia', $pReg) as $fila) {
            if (isset($dias[$fila['g_dia']])) {
                $dias[$fila['g_dia']]['registros'] = (int) $fila['n'];
            }
        }

        // Mapa de calor: lunes (0) a domingo (6) x hora 0-23.
        $calor = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($this->filas('SELECT WEEKDAY(s.fecha_inicio) g_dia, HOUR(s.fecha_inicio) g_hora, COUNT(*) n ' . $from . ' GROUP BY g_dia, g_hora', $p) as $fila) {
            $calor[(int) $fila['g_dia']][(int) $fila['g_hora']] = (int) $fila['n'];
        }

        $dispositivos = [];

        foreach ($this->filas('SELECT s.dispositivo clave, COUNT(*) n ' . $from . ' GROUP BY s.dispositivo ORDER BY n DESC', $p) as $fila) {
            $dispositivos[] = ['clave' => $fila['clave'], 'nombre' => self::DISPOSITIVOS[$fila['clave']] ?? $fila['clave'], 'n' => (int) $fila['n']];
        }

        $so = $this->etiquetar($this->filas("SELECT COALESCE(s.sistema_operativo, 'Desconocido') g_etiqueta, COUNT(*) n " . $from . ' GROUP BY g_etiqueta ORDER BY n DESC LIMIT 8', $p));
        $navegadores = $this->etiquetar($this->filas("SELECT COALESCE(s.navegador, 'Desconocido') g_etiqueta, COUNT(*) n " . $from . ' GROUP BY g_etiqueta ORDER BY n DESC LIMIT 8', $p));

        $ciudadesIp = $this->etiquetar($this->filas(
            "SELECT CONCAT(s.geo_ciudad, IF(s.geo_pais IS NULL, '', CONCAT(' (', s.geo_pais, ')'))) g_etiqueta, COUNT(*) n, COUNT(DISTINCT s.usuario_id) usuarias "
            . $from . " AND s.geo_estado = 'ok' AND s.geo_ciudad IS NOT NULL GROUP BY g_etiqueta ORDER BY n DESC LIMIT 15",
            $p
        ));

        $municipios = $this->etiquetar($this->filas(
            "SELECT COALESCE((SELECT MIN(c.municipio) FROM tbl_ciudades c WHERE c.codigo_muncipio = LPAD(u.tbl_municipio_id, 5, CHAR(48))), 'Sin municipio') g_etiqueta, COUNT(*) n, COUNT(DISTINCT s.usuario_id) usuarias
             FROM tbl_analitica_sesiones s LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id
             WHERE " . $w . ' GROUP BY g_etiqueta ORDER BY n DESC LIMIT 15',
            $p
        ));

        $geo = $this->filas(
            'SELECT s.geo_estado clave, COUNT(*) n ' . $from . ' GROUP BY s.geo_estado',
            $p
        );

        return [
            'dias' => array_values($dias),
            'calor' => $calor,
            'dispositivos' => $dispositivos,
            'sistemas' => $so,
            'navegadores' => $navegadores,
            'ciudades_ip' => $ciudadesIp,
            'municipios_registrados' => $municipios,
            'estado_geo' => $geo
        ];
    }

    /** Renombra el alias de agrupación g_etiqueta a nombre para la respuesta JSON. */
    private function etiquetar(array $filas)
    {
        return array_map(function ($fila) {
            $fila['nombre'] = $fila['g_etiqueta'];
            unset($fila['g_etiqueta']);
            return $fila;
        }, $filas);
    }

    // ---------------------------------------------------------------- Asistentes

    public function usoAsistentes(array $f)
    {
        $rankings = $this->rankingsAsistentes($f);
        $porAsistente = [];

        foreach ($rankings['todos'] as $a) {
            $porAsistente[$a['asistente']] = $a + [
                'tiempo_medio_por_usuaria_seg' => $a['usuarias'] > 0 ? (int) round($a['segundos'] / $a['usuarias']) : 0,
                'pct_voz' => $a['mensajes'] > 0 ? round(100 * $a['voz'] / $a['mensajes'], 1) : null,
                'conversaciones' => 0,
                'una_sola_vez' => 0,
                'pct_abandono' => null,
                'mensajes_por_conversacion' => null,
                'respuestas_medidas' => 0,
                'latencia_media_ms' => null,
                'latencia_p50_ms' => null,
                'latencia_p95_ms' => null,
                'tokens_entrada' => 0,
                'tokens_salida' => 0,
                'busquedas_web' => 0,
                'costo_estimado_usd' => null,
                'sin_respuesta' => 0
            ];
        }

        [$wm, $pm] = $this->filtroMensajes($f);

        foreach ($this->filas(
            "SELECT c.asistente, COUNT(*) conversaciones, SUM(t.n = 1) una
             FROM (SELECT m.conversacion_id, COUNT(*) n FROM tbl_chat_mensajes m
                   JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
                   LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
                   WHERE m.rol = 'usuaria' AND " . $wm . ' GROUP BY m.conversacion_id) t
             JOIN tbl_chat_conversaciones c ON c.id = t.conversacion_id GROUP BY c.asistente',
            $pm
        ) as $fila) {
            if (isset($porAsistente[$fila['asistente']])) {
                $a =& $porAsistente[$fila['asistente']];
                $a['conversaciones'] = (int) $fila['conversaciones'];
                $a['una_sola_vez'] = (int) $fila['una'];
                $a['pct_abandono'] = $a['conversaciones'] > 0 ? round(100 * $a['una_sola_vez'] / $a['conversaciones'], 1) : null;
                $a['mensajes_por_conversacion'] = $a['conversaciones'] > 0 ? round($a['mensajes'] / $a['conversaciones'], 1) : null;
                unset($a);
            }
        }

        [$wm2, $pm2] = $this->filtroMensajes($f, 'b');

        foreach ($this->filas(
            "SELECT c.asistente, COUNT(*) respuestas, AVG(m.latencia_ms) lat, SUM(m.tokens_entrada) tin,
                    SUM(m.tokens_salida) tout, COALESCE(SUM(m.busquedas_web), 0) busq
             FROM tbl_chat_mensajes m JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
             LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
             WHERE m.rol = 'asistente' AND m.tokens_entrada IS NOT NULL AND " . $wm2 . ' GROUP BY c.asistente',
            $pm2
        ) as $fila) {
            if (!isset($porAsistente[$fila['asistente']])) {
                continue;
            }

            $a =& $porAsistente[$fila['asistente']];
            $a['respuestas_medidas'] = (int) $fila['respuestas'];
            $a['latencia_media_ms'] = $fila['lat'] !== null ? (int) round((float) $fila['lat']) : null;
            $a['tokens_entrada'] = (int) $fila['tin'];
            $a['tokens_salida'] = (int) $fila['tout'];
            $a['busquedas_web'] = (int) $fila['busq'];
            $a['costo_estimado_usd'] = round(
                $a['tokens_entrada'] / 1000000 * self::PRECIO_ENTRADA_USD_POR_MTOK
                + $a['tokens_salida'] / 1000000 * self::PRECIO_SALIDA_USD_POR_MTOK
                + $a['busquedas_web'] / 1000 * self::PRECIO_BUSQUEDA_USD_POR_1000,
                4
            );
            unset($a);
        }

        foreach (array_keys($porAsistente) as $slug) {
            if ($porAsistente[$slug]['respuestas_medidas'] === 0) {
                continue;
            }

            [$wl, $pl] = $this->filtroMensajes($f, 'c');
            $latencias = array_map('intval', array_column($this->filas(
                "SELECT m.latencia_ms FROM tbl_chat_mensajes m JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
                 LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
                 WHERE m.rol = 'asistente' AND m.latencia_ms IS NOT NULL AND c.asistente = :slug AND " . $wl
                . ' ORDER BY m.latencia_ms LIMIT 100000',
                $pl + [':slug' => $slug]
            ), 'latencia_ms'));

            $porAsistente[$slug]['latencia_p50_ms'] = $this->percentil($latencias, 0.50);
            $porAsistente[$slug]['latencia_p95_ms'] = $this->percentil($latencias, 0.95);
        }

        [$wd, $pd] = $this->filtroMensajes($f, 'd');

        foreach ($this->filas(
            "SELECT c.asistente, SUM(GREATEST(t.u - t.a, 0)) sin_respuesta
             FROM (SELECT m.conversacion_id, SUM(m.rol = 'usuaria') u, SUM(m.rol = 'asistente') a
                   FROM tbl_chat_mensajes m JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
                   LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
                   WHERE " . $wd . ' GROUP BY m.conversacion_id) t
             JOIN tbl_chat_conversaciones c ON c.id = t.conversacion_id GROUP BY c.asistente',
            $pd
        ) as $fila) {
            if (isset($porAsistente[$fila['asistente']])) {
                $porAsistente[$fila['asistente']]['sin_respuesta'] = (int) $fila['sin_respuesta'];
            }
        }

        $totalCosto = 0.0;

        foreach ($porAsistente as $a) {
            $totalCosto += (float) $a['costo_estimado_usd'];
        }

        return [
            'asistentes' => array_values($porAsistente),
            'rankings' => $rankings,
            'costo_total_usd' => round($totalCosto, 4),
            'costo_total_cop' => (int) round($totalCosto * self::TRM_COP_REFERENCIA),
            'precios' => [
                'entrada_usd_mtok' => self::PRECIO_ENTRADA_USD_POR_MTOK,
                'salida_usd_mtok' => self::PRECIO_SALIDA_USD_POR_MTOK,
                'busqueda_usd_1000' => self::PRECIO_BUSQUEDA_USD_POR_1000,
                'trm_cop' => self::TRM_COP_REFERENCIA
            ]
        ];
    }

    // ---------------------------------------------------------------- Métricas adicionales

    public function metricas(array $f)
    {
        return [
            'retencion' => $this->retencion($f),
            'derivaciones' => $this->derivaciones($f),
            'documentos' => $this->documentos($f),
            'audio' => $this->audio($f),
            'alertas' => $this->alertas($f),
            'videollamadas' => $this->videollamadas($f),
            'evidencias' => $this->evidencias($f),
            'paginas' => $this->paginasMasVistas($f),
            'gestiones_operadoras' => $this->gestionesOperadoras($f)
        ];
    }

    /**
     * Retención: de las usuarias cuya primera sesión registrada cae en el rango (y tuvo al menos N días
     * para volver), % que inició otra sesión en un día distinto dentro de los N días siguientes.
     */
    public function retencion(array $f)
    {
        $salida = [];
        $hoy = $this->ahora()->setTime(0, 0, 0);

        foreach ([7, 30] as $n) {
            $tope = $hoy->modify('-' . $n . ' days')->format('Y-m-d') . ' 23:59:59';

            $sql = 'SELECT COUNT(*) elegibles, COALESCE(SUM(t.volvio), 0) volvieron FROM (
                        SELECT p.usuario_id,
                               EXISTS (SELECT 1 FROM tbl_analitica_sesiones s2
                                       WHERE s2.usuario_id = p.usuario_id
                                         AND DATE(s2.fecha_inicio) > DATE(p.primera)
                                         AND DATE(s2.fecha_inicio) <= DATE_ADD(DATE(p.primera), INTERVAL ' . (int) $n . ' DAY)) volvio
                        FROM (SELECT usuario_id, MIN(fecha_inicio) primera FROM tbl_analitica_sesiones GROUP BY usuario_id) p
                        LEFT JOIN tbl_usuarios u ON u.id = p.usuario_id
                        WHERE p.primera >= :desde AND p.primera <= :hasta AND p.primera <= :tope'
                . ($f['municipio'] !== null ? ' AND u.tbl_municipio_id = :mun' : '') . ') t';

            $params = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt'], ':tope' => $tope];

            if ($f['municipio'] !== null) {
                $params[':mun'] = $f['municipio'];
            }

            $fila = $this->filas($sql, $params)[0];
            $elegibles = (int) $fila['elegibles'];

            $salida['dias_' . $n] = [
                'elegibles' => $elegibles,
                'volvieron' => (int) $fila['volvieron'],
                'pct' => $elegibles > 0 ? round(100 * (int) $fila['volvieron'] / $elegibles, 1) : null
            ];
        }

        return $salida;
    }

    private function derivaciones(array $f)
    {
        [$w, $p] = $this->filtroMensajes($f);

        $sugeridas = $this->filas(
            "SELECT c.asistente origen, m.derivar_a destino, COUNT(*) n
             FROM tbl_chat_mensajes m JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
             LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
             WHERE m.rol = 'asistente' AND m.derivar_a IS NOT NULL AND " . $w . ' GROUP BY c.asistente, m.derivar_a ORDER BY n DESC',
            $p
        );

        $clics = (int) $this->valor(
            "SELECT COUNT(*) FROM tbl_analitica_eventos WHERE tipo = 'derivacion_click' AND fecha >= :desde AND fecha <= :hasta",
            [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']]
        );

        return [
            'sugeridas' => array_map(function ($fila) {
                return [
                    'origen' => self::ASISTENTES[$fila['origen']] ?? $fila['origen'],
                    'destino' => self::ASISTENTES[$fila['destino']] ?? $fila['destino'],
                    'n' => (int) $fila['n']
                ];
            }, $sugeridas),
            'clics' => $clics
        ];
    }

    private function documentos(array $f)
    {
        [$w, $p] = $this->filtroMensajes($f);

        $columnas = [];

        foreach (array_keys(self::TIPOS_DOCUMENTO) as $tipo) {
            $columnas[] = "COALESCE(SUM(m.documento_json LIKE '%\"tipo\":\"" . $tipo . "\"%'), 0) AS `" . $tipo . '`';
        }

        $fila = $this->filas(
            'SELECT COUNT(m.documento_json) total, ' . implode(', ', $columnas) . "
             FROM tbl_chat_mensajes m JOIN tbl_chat_conversaciones c ON c.id = m.conversacion_id
             LEFT JOIN tbl_usuarios u ON u.id = c.usuario_id
             WHERE m.rol = 'asistente' AND m.documento_json IS NOT NULL AND " . $w,
            $p
        )[0];

        $porTipo = [];

        foreach (self::TIPOS_DOCUMENTO as $tipo => $nombre) {
            $porTipo[] = ['tipo' => $nombre, 'n' => (int) $fila[$tipo]];
        }

        return ['total' => (int) $fila['total'], 'por_tipo' => $porTipo];
    }

    private function audio(array $f)
    {
        $params = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']];

        $conAudio = (int) $this->valor(
            "SELECT COUNT(DISTINCT sesion_id) FROM tbl_analitica_eventos WHERE tipo = 'audio_activado' AND fecha >= :desde AND fecha <= :hasta",
            $params
        );

        $conAsistente = (int) $this->valor(
            'SELECT COUNT(DISTINCT sesion_id) FROM tbl_analitica_uso_asistente WHERE fecha_inicio >= :desde AND fecha_inicio <= :hasta',
            $params
        );

        return [
            'sesiones_con_audio' => $conAudio,
            'sesiones_con_asistente' => $conAsistente,
            'pct' => $conAsistente > 0 ? round(100 * $conAudio / $conAsistente, 1) : null
        ];
    }

    private function alertas(array $f)
    {
        $params = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']];
        $join = '';
        $extra = '';

        if ($f['municipio'] !== null) {
            $join = ' LEFT JOIN tbl_usuarios u ON u.id = a.usuario_id';
            $extra = ' AND u.tbl_municipio_id = :mun';
            $params[':mun'] = $f['municipio'];
        }

        $base = 'FROM tbl_alerta_policia a' . $join . ' WHERE a.fecha_registro >= :desde AND a.fecha_registro <= :hasta' . $extra;

        $fila = $this->filas(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(a.tipo_alerta LIKE 'riesgo\\_chat\\_nivel\\_%'), 0) desde_chat,
                    COALESCE(SUM(COALESCE(a.estado_atencion_visualizador, 'no_atendida') = 'atendida'), 0) atendidas,
                    COALESCE(SUM(COALESCE(a.estado_atencion_visualizador, 'no_atendida') = 'en_atencion'), 0) en_atencion,
                    AVG(CASE WHEN a.fecha_atencion IS NOT NULL AND a.fecha_atencion >= a.fecha_registro
                             THEN TIMESTAMPDIFF(SECOND, a.fecha_registro, a.fecha_atencion) END) seg_atencion,
                    AVG(CASE WHEN a.fecha_apertura_modal IS NOT NULL AND a.fecha_apertura_modal >= a.fecha_registro
                             THEN TIMESTAMPDIFF(SECOND, a.fecha_registro, a.fecha_apertura_modal) END) seg_apertura " . $base,
            $params
        )[0];

        $total = (int) $fila['total'];

        return [
            'total' => $total,
            'desde_chat' => (int) $fila['desde_chat'],
            'desde_panico' => $total - (int) $fila['desde_chat'],
            'atendidas' => (int) $fila['atendidas'],
            'en_atencion' => (int) $fila['en_atencion'],
            'pct_atendidas' => $total > 0 ? round(100 * (int) $fila['atendidas'] / $total, 1) : null,
            'seg_hasta_apertura' => $fila['seg_apertura'] !== null ? (int) round((float) $fila['seg_apertura']) : null,
            'seg_hasta_atencion' => $fila['seg_atencion'] !== null ? (int) round((float) $fila['seg_atencion']) : null
        ];
    }

    private function videollamadas(array $f)
    {
        $params = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']];
        $join = '';
        $extra = '';

        if ($f['municipio'] !== null) {
            $join = ' LEFT JOIN tbl_usuarios u ON u.id = v.usuario_id';
            $extra = ' AND u.tbl_municipio_id = :mun';
            $params[':mun'] = $f['municipio'];
        }

        $fila = $this->filas(
            "SELECT COUNT(*) total, COALESCE(SUM(v.estado = 'unida'), 0) unidas,
                    COALESCE(SUM(v.canal = 'whatsapp'), 0) whatsapp, COALESCE(SUM(v.canal = 'sms'), 0) sms
             FROM tbl_videollamada_invitacion v" . $join . ' WHERE v.creado_en >= :desde AND v.creado_en <= :hasta' . $extra,
            $params
        )[0];

        $total = (int) $fila['total'];

        return [
            'invitaciones' => $total,
            'unidas' => (int) $fila['unidas'],
            'pct_unidas' => $total > 0 ? round(100 * (int) $fila['unidas'] / $total, 1) : null,
            'whatsapp' => (int) $fila['whatsapp'],
            'sms' => (int) $fila['sms']
        ];
    }

    private function evidencias(array $f)
    {
        $params = [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']];
        $join = '';
        $extra = '';

        if ($f['municipio'] !== null) {
            $join = ' LEFT JOIN tbl_usuarios u ON u.id = a.usuario_id';
            $extra = ' AND u.tbl_municipio_id = :mun';
            $params[':mun'] = $f['municipio'];
        }

        return array_map(function ($fila) {
            return ['tipo' => (string) $fila['g_tipo'], 'n' => (int) $fila['n']];
        }, $this->filas(
            "SELECT COALESCE(NULLIF(a.tipo_alerta, ''), 'sin tipo') g_tipo, COUNT(*) n
             FROM tbl_alerta_policia a" . $join . "
             WHERE a.archivo IS NOT NULL AND a.archivo <> '' AND a.fecha_registro >= :desde AND a.fecha_registro <= :hasta" . $extra . '
             GROUP BY g_tipo ORDER BY n DESC LIMIT 10',
            $params
        ));
    }

    private function paginasMasVistas(array $f)
    {
        return array_map(function ($fila) {
            return ['pagina' => self::ETIQUETAS_PAGINAS[$fila['detalle']] ?? (string) $fila['detalle'], 'n' => (int) $fila['n']];
        }, $this->filas(
            "SELECT detalle, COUNT(*) n FROM tbl_analitica_eventos
             WHERE tipo = 'pagina_vista' AND fecha >= :desde AND fecha <= :hasta GROUP BY detalle ORDER BY n DESC LIMIT 10",
            [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']]
        ));
    }

    private function gestionesOperadoras(array $f)
    {
        return array_map(function ($fila) {
            return ['operadora' => (string) $fila['g_operadora'], 'n' => (int) $fila['n']];
        }, $this->filas(
            "SELECT COALESCE(NULLIF(g.usuario_panel_nombre, ''), 'Sin nombre') g_operadora, COUNT(*) n
             FROM tbl_alerta_policia_gestion g WHERE g.fecha_gestion >= :desde AND g.fecha_gestion <= :hasta
             GROUP BY g_operadora ORDER BY n DESC LIMIT 10",
            [':desde' => $f['desde_dt'], ':hasta' => $f['hasta_dt']]
        ));
    }

    // ---------------------------------------------------------------- Detalle (tablas exportables)

    public function sesiones(array $f)
    {
        [$w, $p] = $this->filtroSesiones($f);

        $total = (int) $this->valor(
            'SELECT COUNT(*) FROM tbl_analitica_sesiones s LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id WHERE ' . $w,
            $p
        );

        if ($total > self::LIMITE_FILAS_DETALLE) {
            return ['excede' => true, 'total' => $total, 'limite' => self::LIMITE_FILAS_DETALLE];
        }

        $filas = $this->filas(
            'SELECT s.id, s.usuario_id, s.fecha_inicio, s.fecha_fin, s.duracion_segundos, s.motivo_cierre, s.geo_estado,
                    s.geo_ciudad, s.geo_pais, s.dispositivo, s.sistema_operativo, s.navegador, s.ip, (SELECT MIN(c.municipio) FROM tbl_ciudades c WHERE c.codigo_muncipio = LPAD(u.tbl_municipio_id, 5, CHAR(48))) municipio
             FROM tbl_analitica_sesiones s
             LEFT JOIN tbl_usuarios u ON u.id = s.usuario_id
             WHERE ' . $w . ' ORDER BY s.fecha_inicio DESC, s.id DESC',
            $p
        );

        $motivos = ['logout' => 'Cerró sesión', 'inactividad' => 'Inactividad', 'reemplazada' => 'Nueva sesión', 'supresion' => 'Supresión'];

        return [
            'excede' => false,
            'total' => $total,
            'filas' => array_map(function ($s) use ($motivos) {
                return [
                    'sid' => (int) $s['id'],
                    'uid' => (int) $s['usuario_id'],
                    'seudonimo' => self::seudonimo($s['usuario_id']),
                    'inicio' => $s['fecha_inicio'],
                    'fin' => $s['fecha_fin'],
                    'duracion_seg' => $s['duracion_segundos'] !== null ? (int) $s['duracion_segundos'] : null,
                    'cierre' => $s['fecha_fin'] === null ? 'Abierta' : ($motivos[$s['motivo_cierre']] ?? '—'),
                    'ciudad_ip' => $s['geo_estado'] === 'ok' ? trim(($s['geo_ciudad'] ?? '') . ($s['geo_pais'] ? ' (' . $s['geo_pais'] . ')' : '')) : $this->etiquetaGeo($s['geo_estado']),
                    'municipio_registrado' => $s['municipio'] ?? '—',
                    'dispositivo' => self::DISPOSITIVOS[$s['dispositivo']] ?? '—',
                    'sistema' => $s['sistema_operativo'] ?? '—',
                    'navegador' => $s['navegador'] ?? '—',
                    'ip_mascara' => AnaliticaService::enmascararIp((string) $s['ip'])
                ];
            }, $filas)
        ];
    }

    public function usoDetalle(array $f)
    {
        [$w, $p] = $this->filtroUso($f);

        $base = 'FROM tbl_analitica_uso_asistente ua
                 LEFT JOIN tbl_analitica_sesiones s ON s.id = ua.sesion_id
                 LEFT JOIN tbl_usuarios u ON u.id = ua.usuario_id WHERE ' . $w;

        $total = (int) $this->valor('SELECT COUNT(*) FROM (SELECT 1 ' . $base . ' GROUP BY ua.usuario_id, ua.asistente) t', $p);

        if ($total > self::LIMITE_FILAS_DETALLE) {
            return ['excede' => true, 'total' => $total, 'limite' => self::LIMITE_FILAS_DETALLE];
        }

        $filas = $this->filas(
            'SELECT ua.usuario_id, ua.asistente, COUNT(DISTINCT ua.sesion_id) sesiones, SUM(ua.segundos_activos) segundos,
                    MAX(ua.fecha_ultima_actividad) ultimo ' . $base . ' GROUP BY ua.usuario_id, ua.asistente ORDER BY segundos DESC',
            $p
        );

        return [
            'excede' => false,
            'total' => $total,
            'filas' => array_map(function ($r) {
                return [
                    'uid' => (int) $r['usuario_id'],
                    'seudonimo' => self::seudonimo($r['usuario_id']),
                    'asistente' => self::ASISTENTES[$r['asistente']] ?? $r['asistente'],
                    'sesiones' => (int) $r['sesiones'],
                    'segundos' => (int) $r['segundos'],
                    'ultimo' => $r['ultimo']
                ];
            }, $filas)
        ];
    }

    public function opcionesMunicipios()
    {
        return $this->filas(
            'SELECT DISTINCT c.codigo_muncipio codigo, c.municipio nombre
             FROM tbl_usuarios u JOIN tbl_ciudades c ON c.codigo_muncipio = u.tbl_municipio_id
             WHERE c.municipio IS NOT NULL ORDER BY c.municipio',
            []
        );
    }

    // ---------------------------------------------------------------- Revelación y auditoría

    public function validarMotivo($motivo)
    {
        if (!is_string($motivo)) {
            throw new InvalidArgumentException('El motivo es obligatorio.');
        }

        $motivo = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $motivo) ?? '');
        $longitud = mb_strlen($motivo, 'UTF-8');

        if ($longitud < self::MIN_MOTIVO) {
            throw new InvalidArgumentException('Escribe un motivo de al menos ' . self::MIN_MOTIVO . ' caracteres.');
        }

        if ($longitud > self::MAX_MOTIVO) {
            throw new InvalidArgumentException('El motivo no puede superar ' . self::MAX_MOTIVO . ' caracteres.');
        }

        return $motivo;
    }

    public function registrarAuditoria($usuarioPanelId, $accion, $usuarioAfectado, $detalle, $ipPanel)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_panel_auditoria (usuario_panel_id, accion, usuario_id_afectado, detalle, ip, fecha)
             VALUES (:panel, :accion, :afectado, :detalle, :ip, :fecha)'
        );
        $stmt->execute([
            ':panel' => (int) $usuarioPanelId,
            ':accion' => substr((string) $accion, 0, 40),
            ':afectado' => $usuarioAfectado !== null ? (int) $usuarioAfectado : null,
            ':detalle' => mb_substr((string) $detalle, 0, 500, 'UTF-8'),
            ':ip' => $ipPanel !== '' ? substr((string) $ipPanel, 0, 45) : null,
            ':fecha' => $this->ahora()->format('Y-m-d H:i:s')
        ]);
    }

    public function revelarIdentidad($usuarioId)
    {
        $fila = $this->filas('SELECT nombre, apellido FROM tbl_usuarios WHERE id = :id LIMIT 1', [':id' => (int) $usuarioId]);

        if (!$fila) {
            return null;
        }

        return trim(($fila[0]['nombre'] ?? '') . ' ' . ($fila[0]['apellido'] ?? ''));
    }

    public function revelarIp($sesionId)
    {
        $fila = $this->filas('SELECT ip, usuario_id, ip_anonimizada FROM tbl_analitica_sesiones WHERE id = :id LIMIT 1', [':id' => (int) $sesionId]);

        if (!$fila) {
            return null;
        }

        return [
            'ip' => (string) $fila[0]['ip'],
            'usuario_id' => (int) $fila[0]['usuario_id'],
            'anonimizada' => (bool) $fila[0]['ip_anonimizada']
        ];
    }
}
