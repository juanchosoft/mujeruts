<?php

/**
 * Cliente mínimo por cURL contra la API de mensajes de Anthropic (Claude).
 * Sin dependencias de Composer, consistente con enviar_whatsapp_emergencia.php
 * y elevenlabs_tts.php.
 *
 * @author SPIDERSOFTWARE
 */
class AnthropicClient
{
    private $apiKey;
    private $model;
    private $apiVersion;
    private $maxTokens;

    public function __construct(ConfigLoader $config)
    {
        $this->apiKey = $config->getAnthropicApiKey();
        $this->model = $config->getAnthropicModel();
        $this->apiVersion = $config->getAnthropicApiVersion();
        $this->maxTokens = $config->getAnthropicMaxTokens();
    }

    /**
     * Vocabulario de señales de riesgo, anclado literalmente a las secciones
     * 3, 6.4 y 7.3 del manual clínico (docs/RequerimientoFinal.txt).
     */
    private function definicionToolRiesgo()
    {
        return [
            'name' => 'registrar_evaluacion_riesgo',
            'description' => 'Registra internamente el nivel de riesgo de la usuaria detectado en este turno de la conversación. Debe llamarse en cada respuesta, incluso si el riesgo es bajo. Esta tool es un registro interno para el sistema, nunca un sustituto de tu respuesta a la usuaria: siempre debes escribir primero tu texto conversacional dirigido a ella y, además, llamar esta tool; nunca respondas únicamente con esta tool sin texto conversacional.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'nivel' => [
                        'type' => 'integer',
                        'enum' => [1, 2, 3, 4],
                        'description' => '1=Orientación, 2=Vulnerabilidad/violencia no inminente, 3=Riesgo alto, 4=Riesgo inminente'
                    ],
                    'senales' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => [
                                'ideacion_suicida', 'intento_en_curso', 'plan_definido', 'acceso_a_medios',
                                'despedida_o_preparacion_final', 'intoxicacion_con_ideacion', 'desconexion_subita_tras_amenaza',
                                'agresion_fisica_en_curso', 'amenaza_de_muerte', 'estrangulamiento', 'agresor_presente',
                                'agresor_con_acceso_armas', 'seguimiento_o_vigilancia', 'privacion_libertad',
                                'violencia_sexual_reciente', 'necesidad_atencion_medica',
                                'menor_de_edad', 'terceros_en_riesgo',
                                'violencia_psicologica', 'violencia_economica', 'violencia_digital', 'acoso',
                                'ninguna'
                            ]
                        ],
                        'description' => 'Señales concretas detectadas en el turno.'
                    ],
                    'requiere_botones_emergencia' => [
                        'type' => 'boolean'
                    ],
                    'resumen_auditoria' => [
                        'type' => 'string',
                        'description' => 'Una frase breve, sin detalles innecesariamente gráficos, para trazabilidad interna.'
                    ]
                ],
                'required' => ['nivel', 'senales', 'requiere_botones_emergencia', 'resumen_auditoria']
            ]
        ];
    }

    /**
     * Envía el turno actual a Claude y devuelve texto de respuesta + evaluación de riesgo.
     *
     * @param string $systemPrompt
     * @param array $historial Lista de ['rol' => 'usuaria'|'asistente', 'contenido' => string]
     * @param string $mensajeNuevo
     * @return array
     */
    public function enviarMensaje($systemPrompt, array $historial, $mensajeNuevo)
    {
        $mensajes = [];

        foreach ($historial as $turno) {
            $mensajes[] = [
                'role' => $turno['rol'] === 'asistente' ? 'assistant' : 'user',
                'content' => (string) $turno['contenido']
            ];
        }

        $mensajes[] = [
            'role' => 'user',
            'content' => $mensajeNuevo
        ];

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'system' => $systemPrompt,
            'messages' => $mensajes,
            'tools' => [$this->definicionToolRiesgo()],
            'tool_choice' => ['type' => 'auto']
        ];

        $curl = curl_init('https://api.anthropic.com/v1/messages');

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);

        $respuesta = curl_exec($curl);
        $errorCurl = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($respuesta === false) {
            return $this->resultadoError('No fue posible conectar con Claude. Detalle: ' . $errorCurl);
        }

        $json = json_decode((string) $respuesta, true);

        if ($httpCode === 401) {
            return $this->resultadoError('La API Key de Anthropic no es válida.');
        }

        if ($httpCode === 429) {
            return $this->resultadoError('Se alcanzó el límite de solicitudes a Claude. Intenta de nuevo en unos segundos.');
        }

        if ($httpCode >= 500) {
            return $this->resultadoError('El servicio de Claude no está disponible en este momento.');
        }

        if ($httpCode < 200 || $httpCode >= 300 || !is_array($json)) {
            $detalle = is_array($json) && !empty($json['error']['message']) ? $json['error']['message'] : 'Respuesta inesperada de Claude.';
            return $this->resultadoError($detalle);
        }

        return $this->interpretarRespuesta($json);
    }

    /**
     * Separa el texto conversacional del bloque tool_use con la evaluación de riesgo.
     * Si Claude no invoca la tool, se asume nivel 2 por salvaguarda conservadora
     * (nunca nivel 1, para no ocultar una anomalía como "todo bien").
     */
    private function interpretarRespuesta(array $json)
    {
        $bloques = $json['content'] ?? [];
        $textoRespuesta = '';
        $toolInvocada = false;
        $nivel = 2;
        $senales = [];
        $requiereBotones = false;
        $resumenAuditoria = '';

        foreach ($bloques as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $textoRespuesta .= (string) ($bloque['text'] ?? '');
            }

            if (($bloque['type'] ?? '') === 'tool_use' && ($bloque['name'] ?? '') === 'registrar_evaluacion_riesgo') {
                $toolInvocada = true;
                $input = $bloque['input'] ?? [];
                $nivel = (int) ($input['nivel'] ?? 2);
                $senales = is_array($input['senales'] ?? null) ? $input['senales'] : [];
                $requiereBotones = (bool) ($input['requiere_botones_emergencia'] ?? false);
                $resumenAuditoria = (string) ($input['resumen_auditoria'] ?? '');
            }
        }

        if (!$toolInvocada) {
            error_log('AnthropicClient: Claude respondió sin invocar registrar_evaluacion_riesgo. Se asume nivel 2 por salvaguarda.');
            $requiereBotones = true;
        }

        if ($nivel < 1 || $nivel > 4) {
            $nivel = 2;
        }

        $textoRespuesta = trim($textoRespuesta);

        /*
         * Claude puede devolver únicamente el tool_use sin bloque de texto
         * (más frecuente con extended thinking). Nunca debe llegar una
         * burbuja vacía a una usuaria que puede estar en crisis. El respaldo
         * se elige según el nivel/señales ya clasificados, para no caer en
         * una frase de relleno genérica en un momento que puede ser crítico.
         */
        if ($textoRespuesta === '') {
            error_log('AnthropicClient: Claude respondió sin texto conversacional (solo tool_use). Se usa mensaje de respaldo según nivel ' . $nivel . '.');
            $textoRespuesta = $this->mensajeRespaldoPorNivel($nivel, $senales);
        }

        return [
            'ok' => true,
            'texto_respuesta' => $textoRespuesta,
            'nivel_riesgo' => $nivel,
            'senales' => $senales,
            'requiere_botones_emergencia' => $requiereBotones,
            'resumen_auditoria' => $resumenAuditoria,
            'tool_invocada' => $toolInvocada
        ];
    }

    /**
     * Respaldo usado solo cuando Claude no devolvió texto conversacional.
     * No es una reclasificación propia: usa el nivel/señales que Claude ya
     * calculó en la misma respuesta para elegir una frase con sentido
     * clínico real, en vez de un relleno genérico ("cuénteme más").
     */
    private function mensajeRespaldoPorNivel($nivel, array $senales)
    {
        if (in_array('ideacion_suicida', $senales, true) || in_array('intento_en_curso', $senales, true)) {
            return 'Gracias por decírmelo. Siento que esté atravesando un dolor tan intenso. ¿Está pensando en suicidarse o en hacerse daño en este momento?';
        }

        if ($nivel >= 4) {
            return 'Su seguridad es lo más importante ahora. Necesitamos conectar de inmediato con una persona que pueda estar físicamente con usted o con un servicio de emergencia.';
        }

        if ($nivel === 3) {
            return 'Gracias por contarme esto, su seguridad es la prioridad ahora. ¿Puede hablar con tranquilidad en este momento?';
        }

        if ($nivel === 2) {
            return 'Gracias por contarme esto, lo que describe no es algo que tenga que enfrentar sola. Cuénteme un poco más sobre lo que está viviendo para poder orientarla mejor.';
        }

        return 'Cuénteme un poco más sobre lo que la trae hoy por aquí para poder orientarla de la mejor forma.';
    }

    private function resultadoError($mensaje)
    {
        return [
            'ok' => false,
            'error_mensaje' => $mensaje
        ];
    }
}
