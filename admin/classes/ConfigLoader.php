<?php

/**
 * Único punto del proyecto que lee config.ini.
 * No toca la sección [openai] existente, solo agrega getters
 * para las claves nuevas de la migración del chat psicológico.
 *
 * @author SPIDERSOFTWARE
 */
class ConfigLoader
{
    private static $config = null;

    private function cargar()
    {
        if (self::$config !== null) {
            return;
        }

        $ruta = __DIR__ . '/../../config.ini';

        if (!is_file($ruta)) {
            throw new Exception('No se encontró config.ini en la raíz del proyecto.');
        }

        $parsed = parse_ini_file($ruta, true);

        if ($parsed === false) {
            throw new Exception('config.ini no tiene un formato válido.');
        }

        self::$config = $parsed;
    }

    private function obtener($seccion, $clave, $requerida = true, $default = '')
    {
        $this->cargar();

        $valor = self::$config[$seccion][$clave] ?? null;

        $sinConfigurar = $valor === null || $valor === '' || strpos((string) $valor, 'PEGA_AQUI') === 0;

        if ($sinConfigurar) {
            if ($requerida) {
                throw new Exception("Falta configurar [$seccion] $clave en config.ini.");
            }
            return $default;
        }

        return $valor;
    }

    public function getAnthropicApiKey()
    {
        return $this->obtener('anthropic', 'api_key');
    }

    public function getAnthropicModel()
    {
        return $this->obtener('anthropic', 'model', false, 'claude-sonnet-5');
    }

    public function getAnthropicApiVersion()
    {
        return $this->obtener('anthropic', 'api_version', false, '2023-06-01');
    }

    public function getAnthropicMaxTokens()
    {
        return (int) $this->obtener('anthropic', 'max_tokens', false, '1024');
    }

    public function getElevenLabsApiKey()
    {
        return $this->obtener('elevenlabs', 'api_key');
    }

    public function getElevenLabsSttModel()
    {
        return $this->obtener('elevenlabs', 'stt_model', false, 'scribe_v1');
    }

    public function getElevenLabsTtsModel()
    {
        return $this->obtener('elevenlabs', 'tts_model', false, 'eleven_flash_v2_5');
    }

    public function getElevenLabsVoiceId()
    {
        return $this->obtener('elevenlabs', 'voice_id');
    }

    public function isWhatsappRiesgoHabilitado()
    {
        $this->cargar();
        $valor = self::$config['chat_psicologico']['whatsapp_riesgo_habilitado'] ?? '0';
        return in_array((string) $valor, ['1', 'true', 'si', 'SI'], true);
    }

    public function getWhatsappRiesgoTemplateName()
    {
        $this->cargar();
        return (string) (self::$config['chat_psicologico']['whatsapp_riesgo_template_name'] ?? '');
    }

    public function getWhatsappRiesgoTemplateLanguage()
    {
        $this->cargar();
        $valor = self::$config['chat_psicologico']['whatsapp_riesgo_template_language'] ?? 'es';
        return $valor === '' ? 'es' : $valor;
    }

    public function getVentanaHistorialMensajes()
    {
        $this->cargar();
        $valor = self::$config['chat_psicologico']['ventana_historial_mensajes'] ?? '20';
        return (int) $valor > 0 ? (int) $valor : 20;
    }

    public function getTwilioAccountSid()
    {
        return $this->obtener('twilio', 'account_sid');
    }

    public function getTwilioAuthToken()
    {
        return $this->obtener('twilio', 'auth_token');
    }

    public function getTwilioApiKeySid()
    {
        return $this->obtener('twilio', 'api_key_sid', false, '');
    }

    public function getTwilioApiKeySecret()
    {
        return $this->obtener('twilio', 'api_key_secret', false, '');
    }

    public function getTwilioVerifyServiceSid()
    {
        return $this->obtener('twilio', 'verify_service_sid', false, '');
    }

    public function getTwilioFromNumber()
    {
        return $this->obtener('twilio', 'from_number', false, '');
    }

    public function getTwilioMessagingServiceSid()
    {
        return $this->obtener('twilio', 'messaging_service_sid', false, '');
    }

    public function getVideollamadaPublicBaseUrl()
    {
        $base = rtrim((string) $this->obtener(
            'videollamada',
            'public_base_url',
            false,
            'https://darkenergy.com.co/mujer'
        ), '/');

        return $base !== '' ? $base : 'https://darkenergy.com.co/mujer';
    }

    public function getVideollamadaWhatsappTemplateName()
    {
        return (string) $this->obtener(
            'videollamada',
            'whatsapp_template_name',
            false,
            'mujeruts_acompanamiento'
        );
    }

    public function getVideollamadaWhatsappTemplateLanguage()
    {
        $valor = (string) $this->obtener(
            'videollamada',
            'whatsapp_template_language',
            false,
            'es'
        );

        return $valor !== '' ? $valor : 'es';
    }

    /**
     * Configuración por asistente migrado (sección [asistente_{slug}]).
     * Los valores por defecto son los más restrictivos: sin búsqueda web,
     * sin escalamiento y sin documentos.
     */
    public function getConfigAsistente($slug)
    {
        if (!in_array($slug, ['trabajo_social', 'juridico', 'lgbtiq'], true)) {
            throw new Exception('Asistente no reconocido: ' . $slug);
        }

        $this->cargar();
        $seccion = self::$config['asistente_' . $slug] ?? [];

        $entero = function ($clave, $default) use ($seccion) {
            $valor = $seccion[$clave] ?? '';
            return is_numeric($valor) ? (int) $valor : $default;
        };

        $booleano = function ($clave, $default) use ($seccion) {
            if (!array_key_exists($clave, $seccion)) {
                return $default;
            }
            return in_array((string) $seccion[$clave], ['1', 'true', 'si', 'SI'], true);
        };

        return [
            'max_tokens' => max(256, $entero('max_tokens', 1024)),
            'timeout_segundos' => max(10, $entero('timeout_segundos', 60)),
            'web_search_habilitado' => $booleano('web_search_habilitado', false),
            'web_search_max_usos' => max(1, $entero('web_search_max_usos', 3)),
            'escalamiento_alerta' => $booleano('escalamiento_alerta', false),
            'escalamiento_whatsapp' => $booleano('escalamiento_whatsapp', false),
            'botones_emergencia' => $booleano('botones_emergencia', true),
            'documentos_habilitado' => $booleano('documentos_habilitado', false)
        ];
    }

    /**
     * Parámetros de analítica de uso ([analitica]).
     */
    public function getConfigAnalitica()
    {
        $this->cargar();
        $s = self::$config['analitica'] ?? [];

        $entero = function ($clave, $default, $min) use ($s) {
            $v = $s[$clave] ?? '';
            return is_numeric($v) ? max($min, (int) $v) : $default;
        };

        $proxies = array_values(array_filter(array_map('trim', explode(',', (string) ($s['proxies_confiables'] ?? '')))));

        return [
            'geoip_proveedor' => (string) ($s['geoip_proveedor'] ?? 'ipinfo'),
            'geoip_token' => (string) ($s['geoip_token'] ?? ''),
            'inactividad_minutos' => $entero('inactividad_minutos', 30, 5),
            'heartbeat_segundos' => $entero('heartbeat_segundos', 60, 15),
            'retencion_ip_dias' => $entero('retencion_ip_dias', 90, 1),
            'retencion_meses' => $entero('retencion_meses', 24, 1),
            'proxies_confiables' => $proxies
        ];
    }

    /**
     * Arma la URL publica completa: {base}/videollamada.php?id={token}
     */
    public function construirUrlPublicaVideollamada($token)
    {
        $token = trim((string) $token);

        return $this->getVideollamadaPublicBaseUrl() . '/videollamada.php?id=' . rawurlencode($token);
    }
}
