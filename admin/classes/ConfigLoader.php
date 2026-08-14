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
}
