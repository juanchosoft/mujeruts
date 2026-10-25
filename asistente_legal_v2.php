<?php
// Asistente LGBTQ+ con Claude. Plantilla y lógica: includes/plantilla_chat_asistente.php
$asistenteSlug = 'lgbtiq';
$pagina = [
    'titulo' => 'Aliada UTS | Asistente LGBTQ+ UTS',
    'descripcion' => 'Aliada UTS, plataforma de orientación LGBTQ+ con IA para brindar acompañamiento, orientación y rutas de apoyo institucional.',
    'titulo_h1' => ['Orientación', 'LGBTQ+ con IA'],
    'subtitulo' => 'Acompañamiento respetuoso, seguro y confidencial para la diversidad sexual y de género.',
    'icono' => 'fa-solid fa-rainbow',
    'texto_info' => 'Recibe orientación respetuosa, segura y confidencial sobre diversidad sexual y de género.',
    'texto_footer' => 'Orientación LGBTQ+ con IA.',
    'color_purpura' => '#8F1BA8',
    'color_purpura_oscuro' => '#4A0D60',
    'audio_storage_key' => 'aliada_uts_audio_lgbti_activo',
    'pregunta_saludo' => '¿Cómo puedo acompañarte hoy?'
];
require __DIR__ . '/includes/plantilla_chat_asistente.php';
