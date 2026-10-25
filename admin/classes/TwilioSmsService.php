<?php

require_once __DIR__ . '/ConfigLoader.php';

/**
 * Envio de SMS de texto libre por Twilio Messages API (sin Composer).
 *
 * @author SPIDERSOFTWARE
 */
class TwilioSmsService
{
    private $accountSid;
    private $authToken;
    private $fromNumber;
    private $messagingServiceSid;

    public function __construct(ConfigLoader $config = null)
    {
        $config = $config ?: new ConfigLoader();
        $this->accountSid = $config->getTwilioAccountSid();
        $this->authToken = $config->getTwilioAuthToken();
        $this->fromNumber = $config->getTwilioFromNumber();
        $this->messagingServiceSid = $config->getTwilioMessagingServiceSid();
    }

    /**
     * @param string $numeroDestino Celular en formato internacional (solo digitos, con prefijo pais).
     * @param string $mensaje
     * @return array{ok:bool,message:string,response?:mixed}
     */
    public function enviarSms($numeroDestino, $mensaje)
    {
        $numeroDestino = preg_replace('/[^0-9]/', '', (string) $numeroDestino);
        $mensaje = trim((string) $mensaje);

        if ($numeroDestino === '') {
            return [
                'ok' => false,
                'message' => 'Número de destino vacío.'
            ];
        }

        if ($mensaje === '') {
            return [
                'ok' => false,
                'message' => 'El mensaje SMS está vacío.'
            ];
        }

        if ($this->fromNumber === '' && $this->messagingServiceSid === '') {
            return [
                'ok' => false,
                'message' => 'Falta configurar twilio.from_number (o messaging_service_sid) en config.ini.'
            ];
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' .
            rawurlencode($this->accountSid) .
            '/Messages.json';

        $payload = [
            'To' => '+' . $numeroDestino,
            'Body' => $mensaje
        ];

        if ($this->messagingServiceSid !== '') {
            $payload['MessagingServiceSid'] = $this->messagingServiceSid;
        } else {
            $payload['From'] = $this->fromNumber;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $this->accountSid . ':' . $this->authToken,
            CURLOPT_POSTFIELDS => http_build_query($payload)
        ]);

        $respuesta = curl_exec($curl);
        $curlErr = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($respuesta === false) {
            error_log('Twilio CURL error: ' . $curlErr);
            return [
                'ok' => false,
                'message' => 'Error de conexión con Twilio: ' . $curlErr
            ];
        }

        $json = json_decode((string) $respuesta, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'ok' => true,
                'message' => 'SMS enviado correctamente.',
                'response' => $json
            ];
        }

        $detalle = is_array($json)
            ? (string) ($json['message'] ?? $respuesta)
            : (string) $respuesta;

        error_log('Twilio API error HTTP ' . $httpCode . ': ' . $detalle);

        return [
            'ok' => false,
            'message' => 'Error al enviar SMS por Twilio: ' . $detalle,
            'http_code' => $httpCode,
            'response' => $json
        ];
    }
}
