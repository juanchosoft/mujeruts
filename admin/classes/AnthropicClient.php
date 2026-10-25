<?php

/**
 * Cliente mínimo por cURL contra la API de mensajes de Anthropic (Claude).
 * Sin dependencias de Composer, consistente con enviar_whatsapp_emergencia.php
 * y elevenlabs_tts.php.
 *
 * Sin $opciones, el payload es exactamente el original de Violeta IA; las
 * opciones (búsqueda web, documentos, riesgo extendido) son para el chat
 * genérico de los otros asistentes.
 *
 * @author SPIDERSOFTWARE
 */
class AnthropicClient
{
    private $apiKey;
    private $model;
    private $apiVersion;
    private $maxTokens;
    private $reintentos = 0;

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
     * Extensiones aditivas de la tool de riesgo para los asistentes del chat
     * genérico (trabajo social, jurídica, LGBTQ+): señales propias de esos
     * prompts y el campo opcional derivar_a.
     */
    private function definicionToolRiesgoExtendida()
    {
        $tool = $this->definicionToolRiesgo();

        $senalesExtra = [
            'violencia_por_prejuicio', 'revelacion_no_consentida_identidad', 'expulsion_del_hogar',
            'inseguridad_alimentaria', 'riesgo_habitacional', 'violencia_patrimonial', 'acoso_laboral'
        ];

        $enum = $tool['input_schema']['properties']['senales']['items']['enum'];
        array_splice($enum, array_search('ninguna', $enum, true), 0, $senalesExtra);
        $tool['input_schema']['properties']['senales']['items']['enum'] = $enum;

        $tool['input_schema']['properties']['derivar_a'] = [
            'type' => 'string',
            'enum' => ['psicologico', 'trabajo_social', 'juridico', 'lgbtiq', 'ninguno'],
            'description' => 'Área a la que conviene derivar a la usuaria además de la actual, solo si realmente lo necesita. Usa "ninguno" en caso contrario.'
        ];

        return $tool;
    }

    private function definicionToolDocumento()
    {
        return [
            'name' => 'entregar_documento',
            'description' => 'Entrega un documento completo (tutela, derecho de petición, queja, denuncia, solicitud, carta, cronología, estudio social) para que la usuaria lo copie, descargue o imprima. Úsala solo cuando ya tengas los datos necesarios. El contenido va en texto plano, sin markdown. Tu texto conversacional debe seguir siendo breve e indicar que el documento está listo en pantalla.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'titulo' => ['type' => 'string'],
                    'tipo' => [
                        'type' => 'string',
                        'enum' => ['tutela', 'derecho_peticion', 'queja', 'denuncia', 'solicitud', 'carta', 'cronologia', 'estudio_social', 'otro']
                    ],
                    'contenido' => ['type' => 'string']
                ],
                'required' => ['titulo', 'tipo', 'contenido']
            ]
        ];
    }

    private function definicionToolBusquedaWeb($maxUsos, $incluirUbicacion)
    {
        /*
         * Variante básica a propósito: con web_search_20260209 (filtrado dinámico)
         * las citas llegan vacías y el consumo de tokens sube ~50%; las citas son
         * necesarias para mostrar a la usuaria la fuente del dato de contacto.
         */
        $tool = [
            'type' => 'web_search_20250305',
            'name' => 'web_search',
            'max_uses' => (int) $maxUsos
        ];

        // Sin 'country': el proveedor de búsqueda responde 400 con country "CO".
        if ($incluirUbicacion) {
            $tool['user_location'] = [
                'type' => 'approximate',
                'city' => 'Bucaramanga',
                'region' => 'Santander',
                'timezone' => 'America/Bogota'
            ];
        }

        return $tool;
    }

    /**
     * Opciones, todas opcionales:
     *  - max_tokens (int)
     *  - web_search (['max_uses' => int])
     *  - tool_documento (bool)
     *  - riesgo_extendido (bool): señales extra + campo derivar_a
     */
    public function construirPayload($systemPrompt, array $historial, $mensajeNuevo, array $opciones = [], $incluirUbicacion = true)
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

        $tools = [
            !empty($opciones['riesgo_extendido']) ? $this->definicionToolRiesgoExtendida() : $this->definicionToolRiesgo()
        ];

        if (!empty($opciones['tool_documento'])) {
            $tools[] = $this->definicionToolDocumento();
        }

        if (!empty($opciones['web_search'])) {
            $tools[] = $this->definicionToolBusquedaWeb($opciones['web_search']['max_uses'] ?? 3, $incluirUbicacion);
        }

        // Los prompts de los asistentes genéricos son largos y fijos: se cachean
        // (tools + system) para no pagarlos completos en cada turno.
        $system = !empty($opciones['cachear_system'])
            ? [['type' => 'text', 'text' => $systemPrompt, 'cache_control' => ['type' => 'ephemeral']]]
            : $systemPrompt;

        return [
            'model' => $this->model,
            'max_tokens' => isset($opciones['max_tokens']) ? (int) $opciones['max_tokens'] : $this->maxTokens,
            'system' => $system,
            'messages' => $mensajes,
            'tools' => $tools,
            'tool_choice' => ['type' => 'auto']
        ];
    }

    /**
     * Envía el turno actual a Claude y devuelve texto de respuesta + evaluación de riesgo.
     *
     * @param string $systemPrompt
     * @param array $historial Lista de ['rol' => 'usuaria'|'asistente', 'contenido' => string]
     * @param string $mensajeNuevo
     * @param array $opciones Ver construirPayload(); además 'timeout' (segundos, 45 por defecto).
     * @return array
     */
    public function enviarMensaje($systemPrompt, array $historial, $mensajeNuevo, array $opciones = [])
    {
        $timeout = isset($opciones['timeout']) ? (int) $opciones['timeout'] : 45;
        $this->reintentos = isset($opciones['reintentos']) ? max(0, (int) $opciones['reintentos']) : 0;
        $inicio = microtime(true);

        $payload = $this->construirPayload($systemPrompt, $historial, $mensajeNuevo, $opciones);
        $resultado = $this->ejecutarRequest($payload, $timeout);

        /*
         * El proveedor de búsqueda puede no admitir la ubicación configurada;
         * en ese caso se reintenta una vez sin user_location (el prompt ya fija Santander).
         */
        if (!$resultado['ok'] && $resultado['http_code'] === 400 && !empty($opciones['web_search'])
            && stripos($resultado['detalle'], 'web_search') !== false) {
            error_log('AnthropicClient: user_location rechazado por la API, se reintenta sin ubicación. Detalle: ' . $resultado['detalle']);
            $payload = $this->construirPayload($systemPrompt, $historial, $mensajeNuevo, $opciones, false);
            $resultado = $this->ejecutarRequest($payload, $timeout);
        }

        if (!$resultado['ok']) {
            return $this->resultadoError($resultado['mensaje']);
        }

        $json = $resultado['json'];
        $bloques = $json['content'] ?? [];
        $uso = $this->sumarUso([], $json);

        /*
         * pause_turn: el bucle de herramientas del servidor (búsqueda web) se pausó.
         * Se reenvía con el turno del asistente tal cual, sin agregar un mensaje
         * de usuario, y el servidor continúa donde quedó.
         */
        $continuaciones = 0;

        while (($json['stop_reason'] ?? '') === 'pause_turn' && $continuaciones < 3) {
            $continuaciones++;
            $payloadContinuacion = $payload;
            $payloadContinuacion['messages'][] = ['role' => 'assistant', 'content' => $bloques];
            $resultado = $this->ejecutarRequest($payloadContinuacion, $timeout);

            if (!$resultado['ok']) {
                error_log('AnthropicClient: fallo al continuar tras pause_turn: ' . $resultado['mensaje']);
                break;
            }

            $json = $resultado['json'];
            $bloques = array_merge($bloques, $json['content'] ?? []);
            $uso = $this->sumarUso($uso, $json);
        }

        /*
         * Si Claude llamó las tools internas (riesgo/documento) antes de escribir
         * y cerró el turno sin texto, se le devuelven los tool_result para que
         * continúe y escriba su respuesta, en vez de usar el texto de respaldo.
         */
        if (!empty($opciones['continuar_si_sin_texto'])
            && ($json['stop_reason'] ?? '') === 'tool_use'
            && !$this->tieneTexto($bloques)) {
            $resultadosTools = [];

            foreach ($bloques as $bloque) {
                if (($bloque['type'] ?? '') === 'tool_use' && !empty($bloque['id'])) {
                    $resultadosTools[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $bloque['id'],
                        'content' => ($bloque['name'] ?? '') === 'entregar_documento'
                            ? 'Documento mostrado a la usuaria.'
                            : 'Registrado. Ahora escribe tu respuesta conversacional a la usuaria.'
                    ];
                }
            }

            if ($resultadosTools) {
                $payloadContinuacion = $payload;
                $payloadContinuacion['messages'][] = ['role' => 'assistant', 'content' => $bloques];
                $payloadContinuacion['messages'][] = ['role' => 'user', 'content' => $resultadosTools];
                $resultado = $this->ejecutarRequest($payloadContinuacion, $timeout);

                if ($resultado['ok']) {
                    $json = $resultado['json'];
                    $bloquesNuevos = $json['content'] ?? [];

                    // Si en la continuación vuelve a clasificar, prevalece la última clasificación.
                    $bloques = array_merge($bloques, $bloquesNuevos);
                    $uso = $this->sumarUso($uso, $json);
                } else {
                    error_log('AnthropicClient: fallo al continuar tras tool_use sin texto: ' . $resultado['mensaje']);
                }
            }
        }

        if (($json['stop_reason'] ?? '') === 'max_tokens') {
            error_log('AnthropicClient: respuesta cortada por max_tokens (' . $payload['max_tokens'] . ').');
        }

        $interpretado = $this->interpretarRespuesta(['content' => $bloques]);
        $interpretado['uso'] = $uso;
        $interpretado['latencia_ms'] = (int) round((microtime(true) - $inicio) * 1000);
        $interpretado['stop_reason'] = (string) ($json['stop_reason'] ?? '');

        return $interpretado;
    }

    /**
     * @return array ['ok' => bool, 'json' => array|null, 'mensaje' => string, 'http_code' => int, 'detalle' => string]
     */
    /**
     * Reintenta ante 429, 529 (overloaded) y 5xx con espera exponencial,
     * respetando el encabezado retry-after (máximo 8 s por espera).
     */
    protected function ejecutarRequest(array $payload, $timeout)
    {
        $intento = 0;

        while (true) {
            $resultado = $this->ejecutarUnaVez($payload, $timeout);
            $reintentable = !$resultado['ok'] && ($resultado['http_code'] === 429 || $resultado['http_code'] >= 500);

            if (!$reintentable || $intento >= $this->reintentos) {
                return $resultado;
            }

            $espera = $resultado['retry_after'] > 0 ? $resultado['retry_after'] : (2 ** $intento);
            $espera = min(8.0, $espera) + mt_rand(0, 500) / 1000;
            error_log('AnthropicClient: HTTP ' . $resultado['http_code'] . ', reintento ' . ($intento + 1) . ' en ' . round($espera, 1) . ' s.');
            usleep((int) ($espera * 1000000));
            $intento++;
        }
    }

    private function ejecutarUnaVez(array $payload, $timeout)
    {
        $retryAfter = 0;
        $curl = curl_init('https://api.anthropic.com/v1/messages');

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HEADERFUNCTION => function ($curl, $linea) use (&$retryAfter) {
                if (stripos($linea, 'retry-after:') === 0) {
                    $retryAfter = (float) trim(substr($linea, 12));
                }
                return strlen($linea);
            }
        ]);

        $respuesta = curl_exec($curl);
        $errorCurl = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $fallo = function ($mensaje, $detalle) use ($httpCode, &$retryAfter) {
            return ['ok' => false, 'json' => null, 'mensaje' => $mensaje, 'http_code' => $httpCode, 'detalle' => (string) $detalle, 'retry_after' => $retryAfter];
        };

        if ($respuesta === false) {
            return $fallo('No fue posible conectar con Claude. Detalle: ' . $errorCurl, $errorCurl);
        }

        $json = json_decode((string) $respuesta, true);
        $detalle = is_array($json) && !empty($json['error']['message']) ? (string) $json['error']['message'] : '';

        if ($httpCode === 401) {
            return $fallo('La API Key de Anthropic no es válida.', $detalle);
        }

        if ($httpCode === 429) {
            return $fallo('Se alcanzó el límite de solicitudes a Claude. Intenta de nuevo en unos segundos.', $detalle);
        }

        if ($httpCode >= 500) {
            return $fallo('El servicio de Claude no está disponible en este momento.', $detalle);
        }

        if ($httpCode < 200 || $httpCode >= 300 || !is_array($json)) {
            return $fallo($detalle !== '' ? $detalle : 'Respuesta inesperada de Claude.', $detalle);
        }

        return ['ok' => true, 'json' => $json, 'mensaje' => '', 'http_code' => $httpCode, 'detalle' => '', 'retry_after' => 0];
    }

    private function tieneTexto(array $bloques)
    {
        foreach ($bloques as $bloque) {
            if (($bloque['type'] ?? '') === 'text' && trim((string) ($bloque['text'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function sumarUso(array $uso, array $json)
    {
        $usage = $json['usage'] ?? [];

        // tokens_entrada incluye los leídos/escritos en caché, para reflejar el volumen real.
        return [
            'tokens_entrada' => (int) ($uso['tokens_entrada'] ?? 0) + (int) ($usage['input_tokens'] ?? 0)
                + (int) ($usage['cache_read_input_tokens'] ?? 0) + (int) ($usage['cache_creation_input_tokens'] ?? 0),
            'tokens_cache_leidos' => (int) ($uso['tokens_cache_leidos'] ?? 0) + (int) ($usage['cache_read_input_tokens'] ?? 0),
            'tokens_salida' => (int) ($uso['tokens_salida'] ?? 0) + (int) ($usage['output_tokens'] ?? 0),
            'busquedas_web' => (int) ($uso['busquedas_web'] ?? 0) + (int) ($usage['server_tool_use']['web_search_requests'] ?? 0)
        ];
    }

    /**
     * Separa el texto conversacional del bloque tool_use con la evaluación de riesgo.
     * Si Claude no invoca la tool, se asume nivel 2 por salvaguarda conservadora
     * (nunca nivel 1, para no ocultar una anomalía como "todo bien").
     * Extrae además, si existen, el documento entregado, la derivación y las
     * fuentes citadas de la búsqueda web.
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
        $derivarA = null;
        $documento = null;
        $fuentes = [];
        $fuentesConsultadas = [];

        foreach ($bloques as $bloque) {
            $tipo = $bloque['type'] ?? '';

            if ($tipo === 'text') {
                $textoRespuesta .= (string) ($bloque['text'] ?? '');

                foreach (($bloque['citations'] ?? []) as $cita) {
                    $this->agregarFuente($fuentes, $cita['url'] ?? '', $cita['title'] ?? '');
                }
            }

            if ($tipo === 'tool_use' && ($bloque['name'] ?? '') === 'registrar_evaluacion_riesgo') {
                $toolInvocada = true;
                $input = $bloque['input'] ?? [];
                $nivel = (int) ($input['nivel'] ?? 2);
                $senales = is_array($input['senales'] ?? null) ? $input['senales'] : [];
                $requiereBotones = (bool) ($input['requiere_botones_emergencia'] ?? false);
                $resumenAuditoria = (string) ($input['resumen_auditoria'] ?? '');

                $derivacion = (string) ($input['derivar_a'] ?? '');
                if ($derivacion !== '' && $derivacion !== 'ninguno') {
                    $derivarA = $derivacion;
                }
            }

            if ($tipo === 'tool_use' && ($bloque['name'] ?? '') === 'entregar_documento') {
                $input = $bloque['input'] ?? [];
                $contenido = trim((string) ($input['contenido'] ?? ''));

                if ($contenido !== '' && mb_strlen($contenido, 'UTF-8') <= 30000) {
                    $documento = [
                        'titulo' => trim((string) ($input['titulo'] ?? '')) ?: 'Documento',
                        'tipo' => (string) ($input['tipo'] ?? 'otro'),
                        'contenido' => $contenido
                    ];
                } else {
                    error_log('AnthropicClient: entregar_documento con contenido vacío o demasiado largo; se descarta.');
                }
            }

            if ($tipo === 'web_search_tool_result') {
                $contenidoBusqueda = $bloque['content'] ?? null;

                // Éxito = lista de resultados; error = objeto con error_code (la API responde 200).
                if (is_array($contenidoBusqueda) && array_is_list($contenidoBusqueda)) {
                    foreach ($contenidoBusqueda as $resultadoBusqueda) {
                        $this->agregarFuente($fuentesConsultadas, $resultadoBusqueda['url'] ?? '', $resultadoBusqueda['title'] ?? '');
                    }
                } else {
                    error_log('AnthropicClient: error en búsqueda web: ' . json_encode($contenidoBusqueda, JSON_UNESCAPED_UNICODE));
                }
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
            if ($documento !== null) {
                $textoRespuesta = 'Te dejé el documento listo en pantalla. Revísalo con calma antes de usarlo.';
            } else {
                error_log('AnthropicClient: Claude respondió sin texto conversacional (solo tool_use). Se usa mensaje de respaldo según nivel ' . $nivel . '.');
                $textoRespuesta = $this->mensajeRespaldoPorNivel($nivel, $senales);
            }
        }

        return [
            'ok' => true,
            'texto_respuesta' => $textoRespuesta,
            'nivel_riesgo' => $nivel,
            'senales' => $senales,
            'requiere_botones_emergencia' => $requiereBotones,
            'resumen_auditoria' => $resumenAuditoria,
            'tool_invocada' => $toolInvocada,
            'derivar_a' => $derivarA,
            'documento' => $documento,
            'fuentes' => !empty($fuentes) ? array_slice($fuentes, 0, 5) : $this->priorizarFuentesOficiales($fuentesConsultadas)
        ];
    }

    /**
     * Si Claude no citó explícitamente, se muestran hasta 3 de los resultados
     * consultados, primero los de dominios oficiales (.gov.co, .edu.co).
     */
    private function priorizarFuentesOficiales(array $fuentes)
    {
        usort($fuentes, function ($a, $b) {
            $oficial = function ($url) {
                $host = (string) parse_url($url, PHP_URL_HOST);
                return (bool) preg_match('/\.(gov|edu)\.co$/i', $host);
            };
            return (int) $oficial($b['url']) - (int) $oficial($a['url']);
        });

        return array_slice($fuentes, 0, 3);
    }

    private function agregarFuente(array &$fuentes, $url, $titulo)
    {
        $url = trim((string) $url);

        if ($url === '' || stripos($url, 'https://') !== 0) {
            return;
        }

        foreach ($fuentes as $fuente) {
            if ($fuente['url'] === $url) {
                return;
            }
        }

        $titulo = trim((string) $titulo);
        $fuentes[] = ['titulo' => $titulo !== '' ? $titulo : $url, 'url' => $url];
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
