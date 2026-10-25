<?php
require_once __DIR__ . '/config_whatsapp.php';
require_once __DIR__ . '/admin/classes/ConfigLoader.php';

/**
 * Normaliza un celular colombiano a formato internacional (prefijo + 10 dígitos).
 */
function normalizarTelefonoWhatsApp($numero)
{
    $digitos = preg_replace('/[^0-9]/', '', (string)$numero);
    $prefijo = defined('WHATSAPP_COUNTRY_PREFIX') ? (string) WHATSAPP_COUNTRY_PREFIX : '57';

    if ($digitos === '') {
        return '';
    }

    if (strpos($digitos, $prefijo) === 0 && strlen($digitos) === strlen($prefijo) + 10) {
        return $digitos;
    }

    if (strlen($digitos) === 10) {
        return $prefijo . $digitos;
    }

    return $digitos;
}

/**
 * Envía un mensaje de texto libre por WhatsApp Cloud API.
 */
function enviarWhatsAppTexto($numeroDestino, $mensaje)
{
    $numeroDestino = normalizarTelefonoWhatsApp($numeroDestino);

    if ($numeroDestino === '') {
        return [
            'ok' => false,
            'message' => 'Número de destino vacío.'
        ];
    }

    $url = 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'   => $numeroDestino,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $mensaje
        ]
    ];

    return ejecutarRequestWhatsApp($url, $payload, $numeroDestino);
}

/**
 * Envía la plantilla de registro al contacto de emergencia.
 * {{1}} = nombre del contacto, {{2}} = nombre de la persona registrada.
 */
function enviarWhatsAppPlantillaRegistro($telefonoContacto, $nombreContacto, $nombreUsuaria)
{
    $numeroDestino = normalizarTelefonoWhatsApp($telefonoContacto);

    if ($numeroDestino === '') {
        return [
            'ok' => false,
            'message' => 'Número de destino vacío.'
        ];
    }

    $nombreContacto = trim((string)$nombreContacto);
    $nombreUsuaria = trim((string)$nombreUsuaria);

    if ($nombreContacto === '' || $nombreUsuaria === '') {
        return [
            'ok' => false,
            'message' => 'Faltan parámetros de la plantilla.'
        ];
    }

    $url = 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $numeroDestino,
        'type' => 'template',
        'template' => [
            'name' => WHATSAPP_TEMPLATE_NAME,
            'language' => [
                'code' => WHATSAPP_TEMPLATE_LANGUAGE,
                'policy' => 'deterministic'
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $nombreContacto
                        ],
                        [
                            'type' => 'text',
                            'text' => $nombreUsuaria
                        ]
                    ]
                ]
            ]
        ]
    ];

    return ejecutarRequestWhatsApp($url, $payload, $numeroDestino);
}

/**
 * Notifica al contacto de emergencia cuando el chat psicológico clasifica
 * riesgo nivel 4 (decisión D2). Usa la plantilla configurada en
 * [chat_psicologico] de config.ini, distinta de la plantilla de registro
 * (uts_mujer2), porque su copy no aplica a una alerta de riesgo.
 *
 * Mientras no exista una plantilla aprobada, no intenta la llamada HTTP:
 * el interruptor real está en config.ini, no en el código.
 */
function enviarWhatsAppAlertaRiesgo($telefonoContacto, $nombreContacto, $nombreUsuaria)
{
    $config = new ConfigLoader();
    $templateName = $config->getWhatsappRiesgoTemplateName();

    if ($templateName === '') {
        return [
            'ok' => false,
            'message' => 'Plantilla de riesgo no configurada aún.'
        ];
    }

    $numeroDestino = normalizarTelefonoWhatsApp($telefonoContacto);

    if ($numeroDestino === '') {
        return [
            'ok' => false,
            'message' => 'Número de destino vacío.'
        ];
    }

    $nombreContacto = trim((string) $nombreContacto);
    $nombreUsuaria = trim((string) $nombreUsuaria);

    $url = 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $numeroDestino,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $config->getWhatsappRiesgoTemplateLanguage(),
                'policy' => 'deterministic'
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        /*
                         * Plantilla "notificacion_aliada": {{1}} = nombre de la usuaria
                         * ("...que *{{1}}* está atravesando..."), {{2}} = nombre del
                         * contacto de confianza ("Hola. *{{2}}*💚" - a quien se saluda).
                         */
                        ['type' => 'text', 'text' => $nombreUsuaria !== '' ? $nombreUsuaria : 'una usuaria'],
                        ['type' => 'text', 'text' => $nombreContacto !== '' ? $nombreContacto : 'contacto']
                    ]
                ]
            ]
        ]
    ];

    return ejecutarRequestWhatsApp($url, $payload, $numeroDestino);
}

/**
 * Invita a la usuaria a una videollamada con la plantilla Meta
 * "mujeruts_acompanamiento". Envia el link PUBLICO completo en el boton CTA
 * (no solo el token): {public_base_url}/videollamada.php?id={uuid}.
 *
 * Body {{1}} = nombre de la usuaria.
 * Button URL = URL publica completa construida desde config.ini.
 */
function enviarWhatsAppPlantillaVideollamada($telefonoUsuaria, $nombreUsuaria, $urlPublica)
{
    $config = new ConfigLoader();
    $templateName = $config->getVideollamadaWhatsappTemplateName();

    if ($templateName === '') {
        return [
            'ok' => false,
            'message' => 'Plantilla de videollamada no configurada.'
        ];
    }

    $numeroDestino = normalizarTelefonoWhatsApp($telefonoUsuaria);

    if ($numeroDestino === '') {
        return [
            'ok' => false,
            'message' => 'Número de destino vacío.'
        ];
    }

    $nombreUsuaria = trim((string) $nombreUsuaria);
    $urlPublica = trim((string) $urlPublica);

    if ($urlPublica === '') {
        return [
            'ok' => false,
            'message' => 'URL pública de videollamada vacía.'
        ];
    }

    $url = 'https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $numeroDestino,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $config->getVideollamadaWhatsappTemplateLanguage(),
                'policy' => 'deterministic'
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $nombreUsuaria !== '' ? $nombreUsuaria : 'usuaria'
                        ]
                    ]
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $urlPublica
                        ]
                    ]
                ]
            ]
        ]
    ];

    return ejecutarRequestWhatsApp($url, $payload, $numeroDestino);
}

/**
 * Ejecuta el POST a Graph API.
 */
function ejecutarRequestWhatsApp($url, array $payload, $numeroDestino)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . WHATSAPP_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log('WhatsApp CURL error [' . $numeroDestino . ']: ' . $curlErr);
        return [
            'ok' => false,
            'message' => 'Error CURL: ' . $curlErr
        ];
    }

    $json = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'ok' => true,
            'message' => 'WhatsApp enviado correctamente.',
            'response' => $json
        ];
    }

    error_log('WhatsApp API error [' . $numeroDestino . '] HTTP ' . $httpCode . ': ' . $response. "URL: " . $url);
    error_log('Payload: ' . json_encode($payload));

    return [
        'ok' => false,
        'message' => 'Error al enviar WhatsApp.',
        'http_code' => $httpCode,
        'response' => $json
    ];
}
