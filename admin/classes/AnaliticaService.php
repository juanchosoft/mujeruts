<?php

if (!class_exists('DbConection')) {
    require_once __DIR__ . '/DbConection.php';
}

if (!class_exists('ConfigLoader')) {
    require_once __DIR__ . '/ConfigLoader.php';
}

require_once __DIR__ . '/GeoIpService.php';

/**
 * Captura de uso por usuaria: sesiones, tiempo activo por asistente, eventos y geolocalización aproximada.
 * Todas las fechas se calculan en PHP con America/Bogota (no con NOW() de MySQL).
 */
class AnaliticaService
{
    const ZONA = 'America/Bogota';
    const TOPE_SEGUNDOS_LATIDO = 90;
    const MIN_SEGUNDOS_ENTRE_LATIDOS = 20;
    const MIN_SEGUNDOS_CIERRE_TRAMO = 2;
    const MAX_EVENTOS_POR_SESION = 500;

    /** Página (nombre de archivo sin .php) => asistente que se usa en ella, o null. */
    const PAGINAS = [
        'panel_usuaria' => null,
        'emergencia' => null,
        'mis_evidencias' => null,
        'videollamada' => null,
        'violencia_intrafamiliar' => null,
        'maltrato' => null,
        'asistencia_psicologica_v2' => 'psicologico',
        'asistencia_trabajo_social_v2' => 'trabajo_social',
        'asistencia_legal_v2' => 'juridico',
        'asistente_legal_v2' => 'lgbtiq'
    ];

    const EVENTOS_PERMITIDOS = ['audio_activado', 'documento_descargado', 'derivacion_click'];

    private $pdo;
    private $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * Crea el servicio con su propia conexión y la configuración de config.ini.
     * El llamador debe cerrar la conexión con $db->closeConect() si le interesa liberarla antes.
     */
    public static function crear()
    {
        $db = new DbConection();
        $pdo = $db->openConect();
        $config = (new ConfigLoader())->getConfigAnalitica();

        return new self($pdo, $config);
    }

    public function ahora()
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::ZONA));
    }

    private function fechaSql(DateTimeImmutable $fecha)
    {
        return $fecha->format('Y-m-d H:i:s');
    }

    private function desdeSql($fechaSql)
    {
        return new DateTimeImmutable($fechaSql, new DateTimeZone(self::ZONA));
    }

    public static function seudonimo($usuarioId)
    {
        return 'U-' . str_pad((string) (int) $usuarioId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * IP del cliente. X-Forwarded-For solo se considera si REMOTE_ADDR es un proxy de confianza;
     * en ese caso se recorre de derecha a izquierda descartando proxies de confianza, porque el
     * extremo izquierdo lo controla el cliente.
     */
    public static function obtenerIp(array $proxiesConfiables, array $server = null)
    {
        $server = $server ?? $_SERVER;
        $remota = trim((string) ($server['REMOTE_ADDR'] ?? ''));

        if (filter_var($remota, FILTER_VALIDATE_IP) === false) {
            return '';
        }

        if (!in_array($remota, $proxiesConfiables, true)) {
            return $remota;
        }

        $reenviadas = array_reverse(array_map('trim', explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''))));
        $primeraValida = null;

        foreach ($reenviadas as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                continue;
            }

            $primeraValida = $ip;

            if (!in_array($ip, $proxiesConfiables, true)) {
                return $ip;
            }
        }

        return $primeraValida ?? $remota;
    }

    /**
     * Dispositivo, sistema operativo y navegador a partir de un vocabulario fijo: nunca se guarda
     * el User-Agent crudo, así que su contenido no llega a la base ni al dashboard.
     */
    public static function parsearUserAgent($ua)
    {
        $ua = substr((string) $ua, 0, 512);

        if ($ua === '') {
            return ['dispositivo' => 'desconocido', 'so' => null, 'navegador' => null];
        }

        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) {
            $dispositivo = 'tablet';
        } elseif (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/i', $ua)) {
            $dispositivo = 'movil';
        } elseif (preg_match('/Windows NT|Macintosh|X11|Linux|CrOS/i', $ua)) {
            $dispositivo = 'escritorio';
        } else {
            $dispositivo = 'desconocido';
        }

        if (preg_match('/Windows Phone/i', $ua)) {
            $so = 'Windows Phone';
        } elseif (preg_match('/Android/i', $ua)) {
            $so = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod|iOS/i', $ua)) {
            $so = 'iOS';
        } elseif (preg_match('/Windows NT/i', $ua)) {
            $so = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            $so = 'macOS';
        } elseif (preg_match('/CrOS/i', $ua)) {
            $so = 'ChromeOS';
        } elseif (preg_match('/Linux|X11/i', $ua)) {
            $so = 'Linux';
        } else {
            $so = null;
        }

        if (preg_match('/Edg(e|A|iOS)?\//i', $ua)) {
            $navegador = 'Edge';
        } elseif (preg_match('/OPR\/|Opera/i', $ua)) {
            $navegador = 'Opera';
        } elseif (preg_match('/SamsungBrowser/i', $ua)) {
            $navegador = 'Samsung Internet';
        } elseif (preg_match('/Firefox\/|FxiOS/i', $ua)) {
            $navegador = 'Firefox';
        } elseif (preg_match('/Chrome\/|CriOS/i', $ua)) {
            $navegador = 'Chrome';
        } elseif (preg_match('/Safari\//i', $ua)) {
            $navegador = 'Safari';
        } elseif (preg_match('/MSIE|Trident/i', $ua)) {
            $navegador = 'Internet Explorer';
        } else {
            $navegador = null;
        }

        return ['dispositivo' => $dispositivo, 'so' => $so, 'navegador' => $navegador];
    }

    /**
     * Abre una sesión de analítica. Si $sesionPrevia es una sesión abierta de la misma usuaria
     * (mismo navegador), se cierra como 'reemplazada'.
     */
    public function iniciarSesion($usuarioId, $origen = 'login', $sesionPrevia = 0)
    {
        $usuarioId = (int) $usuarioId;

        if ($usuarioId <= 0) {
            throw new InvalidArgumentException('usuario_id inválido.');
        }

        if ((int) $sesionPrevia > 0) {
            $this->cerrarSesion((int) $sesionPrevia, 'reemplazada', $usuarioId);
        }

        $ahora = $this->fechaSql($this->ahora());
        $ip = self::obtenerIp($this->config['proxies_confiables'] ?? []);
        $ua = self::parsearUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($ip === '') {
            $geoEstado = 'error';
        } elseif (!GeoIpService::esIpPublica($ip)) {
            $geoEstado = 'privada';
        } else {
            $geoEstado = 'pendiente';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_analitica_sesiones
                (usuario_id, origen, fecha_inicio, fecha_ultima_actividad, ip, geo_estado,
                 dispositivo, sistema_operativo, navegador)
             VALUES
                (:usuario_id, :origen, :inicio, :actividad, :ip, :geo_estado,
                 :dispositivo, :so, :navegador)'
        );
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':origen' => $origen === 'recuperada' ? 'recuperada' : 'login',
            ':inicio' => $ahora,
            ':actividad' => $ahora,
            ':ip' => $ip !== '' ? $ip : null,
            ':geo_estado' => $geoEstado,
            ':dispositivo' => $ua['dispositivo'],
            ':so' => $ua['so'],
            ':navegador' => $ua['navegador']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return string 'ok' | 'ignorado' | 'cerrada' | 'inexistente'
     *
     * accion: 'pagina' (carga de página), 'latido' (cada heartbeat con pestaña visible),
     * 'reanudar' (la pestaña vuelve a ser visible) o 'cierre' (la pestaña se oculta o se cierra).
     * Solo 'latido' y 'cierre' suman tiempo activo, con tope de 90 s por llamada.
     */
    public function registrarActividad($sesionId, $usuarioId, $accion, $pagina, $visible = true)
    {
        if (!array_key_exists($pagina, self::PAGINAS)) {
            return 'ignorado';
        }

        $stmt = $this->pdo->prepare(
            'SELECT fecha_ultima_actividad, fecha_fin FROM tbl_analitica_sesiones WHERE id = :id AND usuario_id = :usuario_id LIMIT 1'
        );
        $stmt->execute([':id' => (int) $sesionId, ':usuario_id' => (int) $usuarioId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return 'inexistente';
        }

        if ($fila['fecha_fin'] !== null) {
            return 'cerrada';
        }

        $ahoraObj = $this->ahora();
        $ahora = $this->fechaSql($ahoraObj);
        $delta = max(0, $ahoraObj->getTimestamp() - $this->desdeSql($fila['fecha_ultima_actividad'])->getTimestamp());

        // Un latido que llega tras el tiempo de inactividad no extiende la sesión: se cierra en su
        // última actividad y el llamador abre una nueva, sin depender de la limpieza perezosa.
        if ($delta > (int) $this->config['inactividad_minutos'] * 60) {
            $this->cerrarSesion((int) $sesionId, 'inactividad', (int) $usuarioId);
            return 'cerrada';
        }

        $asistente = self::PAGINAS[$pagina];
        $segundos = 0;

        if ($accion === 'latido') {
            if (!$visible || $delta < self::MIN_SEGUNDOS_ENTRE_LATIDOS) {
                return 'ignorado';
            }
            $segundos = min($delta, self::TOPE_SEGUNDOS_LATIDO);
        } elseif ($accion === 'cierre') {
            if ($delta < self::MIN_SEGUNDOS_CIERRE_TRAMO) {
                return 'ignorado';
            }
            $segundos = min($delta, self::TOPE_SEGUNDOS_LATIDO);
        } elseif ($accion !== 'pagina' && $accion !== 'reanudar') {
            return 'ignorado';
        }

        if ($asistente !== null && ($segundos > 0 || $accion === 'pagina')) {
            $this->sumarUsoAsistente((int) $sesionId, (int) $usuarioId, $asistente, $segundos, $ahora);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE tbl_analitica_sesiones SET fecha_ultima_actividad = :actividad, pagina_actual = :pagina
             WHERE id = :id AND fecha_fin IS NULL'
        );
        $stmt->execute([':actividad' => $ahora, ':pagina' => $pagina, ':id' => (int) $sesionId]);

        if ($accion === 'pagina') {
            $this->insertarEvento((int) $sesionId, (int) $usuarioId, 'pagina_vista', $pagina, $ahora);
        }

        return 'ok';
    }

    private function sumarUsoAsistente($sesionId, $usuarioId, $asistente, $segundos, $ahora)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM tbl_analitica_uso_asistente WHERE sesion_id = :sesion AND asistente = :asistente ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':sesion' => $sesionId, ':asistente' => $asistente]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $stmt = $this->pdo->prepare(
                'UPDATE tbl_analitica_uso_asistente
                 SET segundos_activos = segundos_activos + :segundos, fecha_ultima_actividad = :ahora WHERE id = :id'
            );
            $stmt->execute([':segundos' => $segundos, ':ahora' => $ahora, ':id' => (int) $id]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_analitica_uso_asistente
                (sesion_id, usuario_id, asistente, fecha_inicio, fecha_ultima_actividad, segundos_activos)
             VALUES (:sesion, :usuario, :asistente, :ahora, :ahora2, :segundos)'
        );
        $stmt->execute([
            ':sesion' => $sesionId, ':usuario' => $usuarioId, ':asistente' => $asistente,
            ':ahora' => $ahora, ':ahora2' => $ahora, ':segundos' => $segundos
        ]);
    }

    private function insertarEvento($sesionId, $usuarioId, $tipo, $detalle, $ahora)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_analitica_eventos (sesion_id, usuario_id, tipo, detalle, fecha)
             VALUES (:sesion, :usuario, :tipo, :detalle, :fecha)'
        );
        $stmt->execute([
            ':sesion' => $sesionId, ':usuario' => $usuarioId, ':tipo' => $tipo,
            ':detalle' => $detalle !== null ? substr((string) $detalle, 0, 60) : null, ':fecha' => $ahora
        ]);
    }

    public function registrarEvento($sesionId, $usuarioId, $tipo, $detalle = null)
    {
        if (!in_array($tipo, self::EVENTOS_PERMITIDOS, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tbl_analitica_eventos WHERE sesion_id = :sesion');
        $stmt->execute([':sesion' => (int) $sesionId]);

        if ((int) $stmt->fetchColumn() >= self::MAX_EVENTOS_POR_SESION) {
            return false;
        }

        $this->insertarEvento((int) $sesionId, (int) $usuarioId, $tipo, $detalle, $this->fechaSql($this->ahora()));
        return true;
    }

    /**
     * Cierra una sesión abierta. logout/reemplazada/supresion: fin = ahora; inactividad: fin = última actividad.
     */
    public function cerrarSesion($sesionId, $motivo, $usuarioId = null)
    {
        $porInactividad = $motivo === 'inactividad';

        $sql = "UPDATE tbl_analitica_sesiones
                SET fecha_fin = " . ($porInactividad ? 'fecha_ultima_actividad' : ':fin') . ",
                    motivo_cierre = :motivo,
                    duracion_segundos = GREATEST(0, TIMESTAMPDIFF(SECOND, fecha_inicio, " . ($porInactividad ? 'fecha_ultima_actividad' : ':fin2') . "))
                WHERE id = :id AND fecha_fin IS NULL";

        $params = [':motivo' => $motivo, ':id' => (int) $sesionId];

        if (!$porInactividad) {
            $fin = $this->fechaSql($this->ahora());
            $params[':fin'] = $fin;
            $params[':fin2'] = $fin;
        }

        if ($usuarioId !== null) {
            $sql .= ' AND usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $usuarioId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Cierra por inactividad las sesiones sin actividad en inactividad_minutos.
     * Perezoso: como máximo una pasada por minuto, salvo $forzar.
     */
    public function cerrarInactivas($forzar = false)
    {
        $marca = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aliada_analitica_cerrar_inactivas.mark';

        if (!$forzar && is_file($marca) && (time() - (int) @filemtime($marca)) < 60) {
            return 0;
        }

        @touch($marca);

        $limite = $this->ahora()->modify('-' . (int) $this->config['inactividad_minutos'] . ' minutes');

        $stmt = $this->pdo->prepare(
            "UPDATE tbl_analitica_sesiones
             SET fecha_fin = fecha_ultima_actividad,
                 motivo_cierre = 'inactividad',
                 duracion_segundos = GREATEST(0, TIMESTAMPDIFF(SECOND, fecha_inicio, fecha_ultima_actividad))
             WHERE fecha_fin IS NULL AND fecha_ultima_actividad < :limite"
        );
        $stmt->execute([':limite' => $this->fechaSql($limite)]);

        return $stmt->rowCount();
    }

    /**
     * Resuelve la ciudad aproximada de una sesión 'pendiente'. Se reclama la fila antes de consultar
     * al proveedor, de modo que un fallo nunca se reintenta dentro de la misma sesión.
     */
    public function resolverGeoPendiente($sesionId, GeoIpService $geo = null)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tbl_analitica_sesiones SET geo_estado = 'error' WHERE id = :id AND geo_estado = 'pendiente'"
        );
        $stmt->execute([':id' => (int) $sesionId]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT ip FROM tbl_analitica_sesiones WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $sesionId]);
        $ip = (string) $stmt->fetchColumn();

        $geo = $geo ?? GeoIpService::desdeConfig($this->pdo, $this->config);

        try {
            $resultado = $geo->resolver($ip, $this->ahora());
        } catch (Throwable $e) {
            error_log('AnaliticaService: geolocalización falló: ' . get_class($e));
            return 'error';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE tbl_analitica_sesiones
             SET geo_estado = :estado, geo_pais = :pais, geo_region = :region, geo_ciudad = :ciudad,
                 geo_lat = :lat, geo_lon = :lon
             WHERE id = :id'
        );
        $stmt->execute([
            ':estado' => $resultado['estado'],
            ':pais' => $resultado['pais'] ?? null,
            ':region' => $resultado['region'] ?? null,
            ':ciudad' => $resultado['ciudad'] ?? null,
            ':lat' => $resultado['lat'] ?? null,
            ':lon' => $resultado['lon'] ?? null,
            ':id' => (int) $sesionId
        ]);

        return $resultado['estado'];
    }

    /**
     * Anonimiza IPs con más de retencion_ip_dias: IPv4 pierde el último octeto; IPv6 se reduce a /48.
     */
    public function anonimizarIps()
    {
        $limite = $this->fechaSql($this->ahora()->modify('-' . (int) $this->config['retencion_ip_dias'] . ' days'));
        $total = 0;

        $seleccion = $this->pdo->prepare(
            'SELECT id, ip FROM tbl_analitica_sesiones
             WHERE ip_anonimizada = 0 AND ip IS NOT NULL AND fecha_inicio < :limite ORDER BY id LIMIT 500'
        );
        $actualizar = $this->pdo->prepare(
            'UPDATE tbl_analitica_sesiones SET ip = :ip, ip_anonimizada = 1 WHERE id = :id'
        );

        do {
            $seleccion->execute([':limite' => $limite]);
            $filas = $seleccion->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filas as $fila) {
                $actualizar->execute([':ip' => self::anonimizarIp($fila['ip']), ':id' => (int) $fila['id']]);
                $total++;
            }
        } while (count($filas) === 500);

        $this->pdo->prepare(
            'UPDATE tbl_analitica_sesiones SET ip_anonimizada = 1 WHERE ip_anonimizada = 0 AND ip IS NULL AND fecha_inicio < :limite'
        )->execute([':limite' => $limite]);

        return $total;
    }

    public static function anonimizarIp($ip)
    {
        $binaria = @inet_pton((string) $ip);

        if ($binaria === false) {
            return null;
        }

        if (strlen($binaria) === 4) {
            $o = unpack('C4', $binaria);
            return $o[1] . '.' . $o[2] . '.' . $o[3] . '.0';
        }

        return inet_ntop(substr($binaria, 0, 6) . str_repeat("\0", 10));
    }

    /**
     * IP enmascarada para mostrar en pantalla y en exportaciones: "181.52.x.x" / "2001:4860:x".
     */
    public static function enmascararIp($ip)
    {
        $binaria = @inet_pton((string) $ip);

        if ($binaria === false) {
            return '';
        }

        if (strlen($binaria) === 4) {
            $o = unpack('C4', $binaria);
            return $o[1] . '.' . $o[2] . '.x.x';
        }

        $h = explode(':', inet_ntop($binaria));
        return ($h[0] !== '' ? $h[0] : '0') . ':' . ($h[1] ?? '0') . ':x';
    }

    /**
     * Elimina sesiones, usos y eventos con más de retencion_meses y limpia la caché geográfica vencida.
     */
    public function purgarAntiguos()
    {
        $ahora = $this->ahora();
        $limite = $this->fechaSql($ahora->modify('-' . (int) $this->config['retencion_meses'] . ' months'));
        $limiteCache = $this->fechaSql($ahora->modify('-' . GeoIpService::DIAS_VIGENCIA_CACHE . ' days'));
        $resumen = ['sesiones' => 0, 'uso' => 0, 'eventos' => 0, 'cache_geo' => 0];

        $tareas = [
            'eventos' => ['DELETE FROM tbl_analitica_eventos WHERE fecha < :limite LIMIT 5000', $limite],
            'uso' => ['DELETE FROM tbl_analitica_uso_asistente WHERE fecha_inicio < :limite LIMIT 5000', $limite],
            'sesiones' => ['DELETE FROM tbl_analitica_sesiones WHERE fecha_fin IS NOT NULL AND fecha_inicio < :limite LIMIT 5000', $limite],
            'cache_geo' => ['DELETE FROM tbl_analitica_geo_cache WHERE fecha_consulta < :limite LIMIT 5000', $limiteCache]
        ];

        foreach ($tareas as $clave => [$sql, $valor]) {
            $stmt = $this->pdo->prepare($sql);

            do {
                $stmt->execute([':limite' => $valor]);
                $borradas = $stmt->rowCount();
                $resumen[$clave] += $borradas;
            } while ($borradas === 5000);
        }

        return $resumen;
    }

    /**
     * Mantenimiento diario completo. Deja una marca para que el dashboard sepa si debe ejecutarlo.
     */
    public function ejecutarMantenimiento()
    {
        $resumen = [
            'inactivas_cerradas' => $this->cerrarInactivas(true),
            'ips_anonimizadas' => $this->anonimizarIps()
        ];

        $resumen += ['purga' => $this->purgarAntiguos()];

        @touch(self::archivoMarcaMantenimiento());

        return $resumen;
    }

    public static function archivoMarcaMantenimiento()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aliada_analitica_mantenimiento.mark';
    }

    public static function mantenimientoPendiente()
    {
        $marca = self::archivoMarcaMantenimiento();
        return !is_file($marca) || (time() - (int) @filemtime($marca)) >= 86400;
    }

    /**
     * Ganchos para login/logout: nunca lanzan excepciones ni interrumpen el flujo de la usuaria.
     */
    public static function alIniciarSesionUsuaria($usuarioId)
    {
        try {
            $servicio = self::crear();
            $previa = (int) ($_SESSION['analitica_sesion_id'] ?? 0);
            $_SESSION['analitica_sesion_id'] = $servicio->iniciarSesion((int) $usuarioId, 'login', $previa);
            $_SESSION['analitica_usuario_id'] = (int) $usuarioId;
        } catch (Throwable $e) {
            error_log('AnaliticaService::alIniciarSesionUsuaria: ' . $e->getMessage());
        }
    }

    public static function alCerrarSesionUsuaria()
    {
        try {
            $sesionId = (int) ($_SESSION['analitica_sesion_id'] ?? 0);
            $usuarioId = (int) ($_SESSION['analitica_usuario_id'] ?? 0);

            if ($sesionId > 0 && $usuarioId > 0) {
                self::crear()->cerrarSesion($sesionId, 'logout', $usuarioId);
            }
        } catch (Throwable $e) {
            error_log('AnaliticaService::alCerrarSesionUsuaria: ' . $e->getMessage());
        }
    }
}
