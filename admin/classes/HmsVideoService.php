<?php

require_once __DIR__ . '/../../config_100ms.php';

/**
 * Crea salas y enlaces de videollamada en 100ms.live usando Room Codes.
 * El enlace final es la página que 100ms hospeda en
 * <subdominio>.app.100ms.live/meeting/<code>.
 */
class HmsVideoService
{
    private const API_BASE = 'https://api.100ms.live/v2';

    public function iniciarVideollamada($alertaId, $nombreUsuaria, $nombreOperador = '')
    {
        $managementToken = $this->generarManagementToken();

        $nombreSala = 'emergencia-' . (int) $alertaId . '-' . time() . '-' . bin2hex(random_bytes(3));
        $descripcion = 'Videollamada de atención - ' . trim((string) $nombreUsuaria);

        $sala = $this->crearSala($managementToken, $nombreSala, $descripcion);

        if (empty($sala['ok'])) {
            return $sala;
        }

        $codigos = $this->generarCodigosSala($managementToken, $sala['id']);

        if (empty($codigos['ok'])) {
            return $codigos;
        }

        $hostCode = null;
        $guestCode = null;

        foreach ($codigos['data'] as $item) {
            $rol = (string) ($item['role'] ?? '');

            if ($rol === HMS_HOST_ROLE_NAME) {
                $hostCode = $item['code'];
            } elseif ($rol === HMS_GUEST_ROLE_NAME) {
                $guestCode = $item['code'];
            }
        }

        if ($hostCode === null && !empty($codigos['data'])) {
            $hostCode = $codigos['data'][0]['code'];
        }

        if ($guestCode === null) {
            $guestCode = $hostCode;
        }

        if ($hostCode === null || $guestCode === null) {
            return [
                'ok' => false,
                'message' => 'No fue posible obtener los códigos de sala de 100ms.'
            ];
        }

        $nombreOperadorFinal = trim((string) $nombreOperador) !== '' ? trim((string) $nombreOperador) : 'Operador';
        $nombreUsuariaFinal = trim((string) $nombreUsuaria) !== '' ? trim((string) $nombreUsuaria) : 'Usuaria';

        return [
            'ok' => true,
            'room_name' => $nombreSala,
            'host_url' => $this->construirEnlace($hostCode, $nombreOperadorFinal),
            'guest_url' => $this->construirEnlace($guestCode, $nombreUsuariaFinal)
        ];
    }

    private function construirEnlace($code, $nombre = '')
    {
        $url = 'https://' . HMS_SUBDOMAIN . '/meeting/' . $code;

        if ($nombre !== '') {
            $url .= '?name=' . rawurlencode($nombre);
        }

        return $url;
    }

    private function crearSala($managementToken, $nombre, $descripcion)
    {
        $body = [
            'name' => $nombre,
            'description' => $descripcion
        ];

        if (HMS_TEMPLATE_ID !== '') {
            $body['template_id'] = HMS_TEMPLATE_ID;
        }

        $respuesta = $this->ejecutarRequest(self::API_BASE . '/rooms', $managementToken, $body);

        if (!$respuesta['ok']) {
            return $respuesta;
        }

        $id = $respuesta['response']['id'] ?? null;

        if (!$id) {
            return [
                'ok' => false,
                'message' => 'La respuesta de 100ms no incluyó el id de la sala.'
            ];
        }

        return [
            'ok' => true,
            'id' => $id
        ];
    }

    private function generarCodigosSala($managementToken, $roomId)
    {
        $respuesta = $this->ejecutarRequest(
            self::API_BASE . '/room-codes/room/' . $roomId,
            $managementToken,
            null,
            'POST'
        );

        if (!$respuesta['ok']) {
            return $respuesta;
        }

        $data = $respuesta['response']['data'] ?? null;

        if (!is_array($data) || empty($data)) {
            return [
                'ok' => false,
                'message' => 'La respuesta de 100ms no incluyó códigos de sala.'
            ];
        }

        return [
            'ok' => true,
            'data' => $data
        ];
    }

    private function ejecutarRequest($url, $managementToken, $body = null, $metodo = 'POST')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $managementToken,
            'Content-Type: application/json'
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $respuesta = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('100ms CURL error [' . $url . ']: ' . $curlErr);
            return [
                'ok' => false,
                'message' => 'Error de conexión con 100ms: ' . $curlErr
            ];
        }

        $json = json_decode($respuesta, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'ok' => true,
                'response' => $json
            ];
        }

        error_log('100ms API error [' . $url . '] HTTP ' . $httpCode . ': ' . $respuesta);

        return [
            'ok' => false,
            'message' => 'Error de 100ms (HTTP ' . $httpCode . '): ' . ($json['message'] ?? $respuesta)
        ];
    }

    private function generarManagementToken()
    {
        $ahora = time();

        $payload = [
            'access_key' => HMS_ACCESS_KEY,
            'type' => 'management',
            'version' => 2,
            'jti' => $this->generarUuid(),
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + 300
        ];

        return $this->jwtEncode($payload);
    }

    private function jwtEncode(array $payload)
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $segmentos = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload))
        ];

        $firma = hash_hmac('sha256', implode('.', $segmentos), HMS_SECRET, true);
        $segmentos[] = $this->base64UrlEncode($firma);

        return implode('.', $segmentos);
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function generarUuid()
    {
        $datos = random_bytes(16);
        $datos[6] = chr(ord($datos[6]) & 0x0f | 0x40);
        $datos[8] = chr(ord($datos[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($datos), 4));
    }
}
