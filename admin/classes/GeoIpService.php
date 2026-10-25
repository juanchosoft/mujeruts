<?php

/**
 * Contrato de un proveedor de geolocalización por IP.
 * Devuelve ['pais','region','ciudad','lat','lon'] o null si el proveedor no pudo responder.
 */
interface GeoIpProveedor
{
    public function consultar($ip);
}

/**
 * Proveedor ipinfo.io. El token viaja en la cabecera Authorization, nunca en la URL,
 * para que no aparezca en logs de errores ni de acceso.
 */
class IpinfoProveedor implements GeoIpProveedor
{
    private $token;
    private $timeout;

    public function __construct($token, $timeout = 3)
    {
        $this->token = (string) $token;
        $this->timeout = (int) $timeout;
    }

    public function consultar($ip)
    {
        if ($this->token === '') {
            error_log('GeoIpService: falta geoip_token en config.ini [analitica].');
            return null;
        }

        $curl = curl_init('https://ipinfo.io/' . rawurlencode($ip) . '/json');

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json'
            ]
        ]);

        $respuesta = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorCurl = curl_errno($curl);
        curl_close($curl);

        if ($respuesta === false || $httpCode !== 200) {
            error_log('GeoIpService: ipinfo respondió HTTP ' . $httpCode . ($errorCurl ? ' (cURL ' . $errorCurl . ')' : '') . '.');
            return null;
        }

        $json = json_decode((string) $respuesta, true);

        if (!is_array($json) || isset($json['error']) || !empty($json['bogon'])) {
            return null;
        }

        $lat = null;
        $lon = null;

        if (isset($json['loc']) && preg_match('/^(-?\d{1,3}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)$/', (string) $json['loc'], $m)) {
            $lat = round((float) $m[1], 4);
            $lon = round((float) $m[2], 4);
        }

        return [
            'pais' => strtoupper(substr((string) ($json['country'] ?? ''), 0, 2)),
            'region' => (string) ($json['region'] ?? ''),
            'ciudad' => (string) ($json['city'] ?? ''),
            'lat' => $lat,
            'lon' => $lon
        ];
    }
}

/**
 * Resuelve ciudad aproximada de una IP con caché por prefijo de red (/24 en IPv4, /48 en IPv6).
 * La caché evita enviar al proveedor cada IP de una misma red y limita el costo y la latencia.
 */
class GeoIpService
{
    const DIAS_VIGENCIA_CACHE = 30;

    private $pdo;
    private $proveedor;

    public function __construct(PDO $pdo, GeoIpProveedor $proveedor)
    {
        $this->pdo = $pdo;
        $this->proveedor = $proveedor;
    }

    public static function desdeConfig(PDO $pdo, array $configAnalitica)
    {
        return new self($pdo, new IpinfoProveedor($configAnalitica['geoip_token'] ?? '', 3));
    }

    public static function esIpPublica($ip)
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * IPv4 -> "a.b.c.0/24"; IPv6 -> "xxxx:xxxx:xxxx::/48". Devuelve null si la IP no es válida.
     */
    public static function prefijoRed($ip)
    {
        $binaria = @inet_pton((string) $ip);

        if ($binaria === false) {
            return null;
        }

        if (strlen($binaria) === 4) {
            $o = unpack('C4', $binaria);
            return $o[1] . '.' . $o[2] . '.' . $o[3] . '.0/24';
        }

        $prefijo = substr($binaria, 0, 6) . str_repeat("\0", 10);
        return inet_ntop($prefijo) . '/48';
    }

    /**
     * @return array ['estado' => 'ok'|'privada'|'error', 'pais', 'region', 'ciudad', 'lat', 'lon']
     */
    public function resolver($ip, DateTimeImmutable $ahora)
    {
        $vacio = ['pais' => null, 'region' => null, 'ciudad' => null, 'lat' => null, 'lon' => null];

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['estado' => 'error'] + $vacio;
        }

        if (!self::esIpPublica($ip)) {
            return ['estado' => 'privada'] + $vacio;
        }

        $prefijo = self::prefijoRed($ip);
        $limite = $ahora->modify('-' . self::DIAS_VIGENCIA_CACHE . ' days')->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'SELECT geo_pais, geo_region, geo_ciudad, geo_lat, geo_lon FROM tbl_analitica_geo_cache
             WHERE prefijo_red = :prefijo AND fecha_consulta >= :limite LIMIT 1'
        );
        $stmt->execute([':prefijo' => $prefijo, ':limite' => $limite]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cache) {
            return [
                'estado' => 'ok',
                'pais' => $cache['geo_pais'],
                'region' => $cache['geo_region'],
                'ciudad' => $cache['geo_ciudad'],
                'lat' => $cache['geo_lat'] !== null ? (float) $cache['geo_lat'] : null,
                'lon' => $cache['geo_lon'] !== null ? (float) $cache['geo_lon'] : null
            ];
        }

        $datos = $this->proveedor->consultar($ip);

        if ($datos === null) {
            return ['estado' => 'error'] + $vacio;
        }

        $datos['region'] = mb_substr($datos['region'], 0, 80, 'UTF-8');
        $datos['ciudad'] = mb_substr($datos['ciudad'], 0, 80, 'UTF-8');

        $stmt = $this->pdo->prepare(
            'INSERT INTO tbl_analitica_geo_cache (prefijo_red, geo_pais, geo_region, geo_ciudad, geo_lat, geo_lon, fecha_consulta)
             VALUES (:prefijo, :pais, :region, :ciudad, :lat, :lon, :fecha)
             ON DUPLICATE KEY UPDATE geo_pais = VALUES(geo_pais), geo_region = VALUES(geo_region),
                 geo_ciudad = VALUES(geo_ciudad), geo_lat = VALUES(geo_lat), geo_lon = VALUES(geo_lon),
                 fecha_consulta = VALUES(fecha_consulta)'
        );
        $stmt->execute([
            ':prefijo' => $prefijo,
            ':pais' => $datos['pais'] !== '' ? $datos['pais'] : null,
            ':region' => $datos['region'] !== '' ? $datos['region'] : null,
            ':ciudad' => $datos['ciudad'] !== '' ? $datos['ciudad'] : null,
            ':lat' => $datos['lat'],
            ':lon' => $datos['lon'],
            ':fecha' => $ahora->format('Y-m-d H:i:s')
        ]);

        return [
            'estado' => 'ok',
            'pais' => $datos['pais'] !== '' ? $datos['pais'] : null,
            'region' => $datos['region'] !== '' ? $datos['region'] : null,
            'ciudad' => $datos['ciudad'] !== '' ? $datos['ciudad'] : null,
            'lat' => $datos['lat'],
            'lon' => $datos['lon']
        ];
    }
}
