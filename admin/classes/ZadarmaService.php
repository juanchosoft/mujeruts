<?php

require_once __DIR__ . '/../../config_zadarma.php';

/**
 * Cliente mínimo de la API de Zadarma: solo pide la key temporal del
 * widget WebRTC. Firma HMAC-SHA1 igual que la librería oficial
 * zadarma/user-api-v1, reimplementada a mano porque no hay vendor/ instalado.
 */
class ZadarmaService
{
    private const API_BASE = 'https://api.zadarma.com';

    public function obtenerWebrtcKey($sipLogin)
    {
        $respuesta = $this->llamar('/v1/webrtc/get_key/', ['sip' => $sipLogin]);

        if (!$respuesta['ok']) {
            return $respuesta;
        }

        $key = $respuesta['response']['key'] ?? null;

        if (!$key) {
            return [
                'ok' => false,
                'message' => 'La respuesta de Zadarma no incluyó la key del widget.'
            ];
        }

        return [
            'ok' => true,
            'key' => $key
        ];
    }

    private function llamar($metodo, array $params = [])
    {
        $params['format'] = 'json';

        $ch = curl_init(self::API_BASE . $metodo . '?' . $this->construirQuery($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->construirAuthorization($metodo, $params)
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $respuesta = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('Zadarma CURL error [' . $metodo . ']: ' . $curlErr);
            return [
                'ok' => false,
                'message' => 'Error de conexión con Zadarma: ' . $curlErr
            ];
        }

        $json = json_decode($respuesta, true);

        if ($httpCode >= 200 && $httpCode < 300 && empty($json['status']) === false && $json['status'] !== 'error') {
            return [
                'ok' => true,
                'response' => $json
            ];
        }

        error_log('Zadarma API error [' . $metodo . '] HTTP ' . $httpCode . ': ' . $respuesta);

        return [
            'ok' => false,
            'message' => 'Error de Zadarma (HTTP ' . $httpCode . '): ' . ($json['message'] ?? $respuesta)
        ];
    }

    private function construirAuthorization($metodo, array $params)
    {
        ksort($params);
        $paramsString = $this->construirQuery($params);
        $firma = base64_encode(hash_hmac('sha1', $metodo . $paramsString . md5($paramsString), ZADARMA_API_SECRET));

        return ZADARMA_API_KEY . ':' . $firma;
    }

    private function construirQuery(array $params)
    {
        ksort($params);
        return http_build_query($params, '', '&', PHP_QUERY_RFC1738);
    }
}
