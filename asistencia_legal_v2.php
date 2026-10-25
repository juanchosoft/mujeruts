<?php
// Asistente jurídica con Claude. Plantilla y lógica: includes/plantilla_chat_asistente.php
$asistenteSlug = 'juridico';
$pagina = [
    'titulo' => 'Aliada UTS | Asistente Jurídica Mujer UTS',
    'descripcion' => 'Orientación legal con IA sobre los derechos de las mujeres, asuntos de familia y rutas de atención en Santander, Colombia.',
    'titulo_h1' => ['Orientación', 'Legal con IA'],
    'subtitulo' => 'Guía inicial, cercana y segura para conocer rutas de protección.',
    'icono' => 'fa-solid fa-scale-balanced',
    'texto_info' => 'Recibe orientación legal clara, respetuosa y segura sobre tus derechos como mujer.',
    'texto_footer' => 'Orientación legal con IA.',
    'color_purpura' => '#921CA9',
    'color_purpura_oscuro' => '#4C0D62',
    'audio_storage_key' => 'aliada_uts_audio_activo',
    'pregunta_saludo' => '¿Cómo puedo orientarte?'
];
require __DIR__ . '/includes/plantilla_chat_asistente.php';
