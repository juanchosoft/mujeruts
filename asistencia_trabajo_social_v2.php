<?php
// Asistente de Trabajo Social con Claude. Plantilla y lógica: includes/plantilla_chat_asistente.php
$asistenteSlug = 'trabajo_social';
$pagina = [
    'titulo' => 'Aliada UTS | Asistente social Mujer UTS',
    'descripcion' => 'Aliada UTS, plataforma de trabajo social con IA para brindar acompañamiento, orientación y rutas de apoyo institucional.',
    'titulo_h1' => ['Trabajo Social', 'con IA'],
    'subtitulo' => 'Orientación humana, segura y cercana para activar rutas de apoyo.',
    'icono' => 'fa-solid fa-hands-holding-circle',
    'texto_info' => 'Recibe acompañamiento social inicial y orientación institucional.',
    'texto_footer' => 'Trabajo Social con IA.',
    'color_purpura' => '#8F1BA8',
    'color_purpura_oscuro' => '#4A0D60',
    'audio_storage_key' => 'aliada_uts_audio_trabajo_social_activo',
    'pregunta_saludo' => '¿Cómo puedo orientarte?'
];
require __DIR__ . '/includes/plantilla_chat_asistente.php';
