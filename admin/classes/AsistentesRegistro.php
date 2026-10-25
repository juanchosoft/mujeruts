<?php

/**
 * Definición fija de los asistentes de IA (lo que no se configura en config.ini).
 * Slugs canónicos: se usan en BD (tbl_chat_conversaciones.asistente), config.ini,
 * JS y analítica.
 *
 * @author SPIDERSOFTWARE
 */
class AsistentesRegistro
{
    const ASISTENTES = [
        'psicologico' => [
            'nombre_ia' => 'Violeta IA',
            'rol' => 'psicóloga orientadora virtual',
            'archivo_prompt' => 'violeta_system_prompt.txt',
            'pagina' => 'asistencia_psicologica_v2.php',
            'etiqueta_derivacion' => 'Hablar con apoyo psicológico',
            'prefijo_alerta' => 'Violeta IA'
        ],
        'trabajo_social' => [
            'nombre_ia' => 'Asistente social Mujer UTS',
            'rol' => 'asistente virtual de trabajo social',
            'archivo_prompt' => 'trabajadora_social_system_prompt.txt',
            'pagina' => 'asistencia_trabajo_social_v2.php',
            'etiqueta_derivacion' => 'Hablar con la asistente social',
            'prefijo_alerta' => 'Asistente social Mujer UTS'
        ],
        'juridico' => [
            'nombre_ia' => 'Asistente Jurídica Mujer UTS',
            'rol' => 'asistente virtual de orientación jurídica',
            'archivo_prompt' => 'abogada_system_prompt.txt',
            'pagina' => 'asistencia_legal_v2.php',
            'etiqueta_derivacion' => 'Hablar con la asistente jurídica',
            'prefijo_alerta' => 'Asistente Jurídica Mujer UTS'
        ],
        'lgbtiq' => [
            'nombre_ia' => 'Asistente LGBTQ+ UTS',
            'rol' => 'asistente virtual de orientación LGBTQ+',
            'archivo_prompt' => 'asesora_lgbtiq_system_prompt.txt',
            'pagina' => 'asistente_legal_v2.php',
            'etiqueta_derivacion' => 'Hablar con la asistente LGBTQ+',
            'prefijo_alerta' => 'Asistente LGBTQ+ UTS'
        ]
    ];

    /** Asistentes atendidos por api/chat_asistente.php (Violeta tiene su propio endpoint). */
    const CHAT_GENERICO = ['trabajo_social', 'juridico', 'lgbtiq'];

    public static function existe($slug)
    {
        return is_string($slug) && isset(self::ASISTENTES[$slug]);
    }

    public static function obtener($slug)
    {
        if (!self::existe($slug)) {
            throw new Exception('Asistente no reconocido: ' . (string) $slug);
        }

        return self::ASISTENTES[$slug];
    }

    public static function leerPrompt($slug)
    {
        $ruta = __DIR__ . '/../../prompts/' . self::obtener($slug)['archivo_prompt'];
        $contenido = is_file($ruta) ? file_get_contents($ruta) : false;

        if ($contenido === false || trim($contenido) === '') {
            throw new Exception('No se pudo leer el prompt del asistente ' . $slug . '.');
        }

        return $contenido;
    }

    /**
     * Botón de derivación hacia otro asistente, o null si no aplica.
     */
    public static function derivacion($slugDestino, $slugActual)
    {
        if (!self::existe($slugDestino) || $slugDestino === $slugActual) {
            return null;
        }

        $destino = self::ASISTENTES[$slugDestino];

        return [
            'slug' => $slugDestino,
            'etiqueta' => $destino['etiqueta_derivacion'],
            'url' => $destino['pagina']
        ];
    }
}
