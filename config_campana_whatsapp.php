<?php
/**
 * Credenciales WhatsApp Cloud API del número de la campaña (omarcampana).
 * Solo se usan para plantillas de invitaciones (pregunta_usuario / usuario_agregado).
 */
define('CAMPANA_WHATSAPP_TOKEN', 'EAAb0NZA7Na9UBR76vS07d91E64JHQQWfiJKHeq1cRQbqzU4ZAm2oJTxweizrERZCNSn4qYkILhxhO3jTXz2EmG3BBQHI5gWK58kNFZBcjrTOtKI1ZAhJqD9fGoeQQ8yrwa7QIcyZANdxuKTejwH7Ej2r3RInVAzdh7eqCI9AS69iHwEivGo1HUcVQjJGEAnUGbwgZDZD');
define('CAMPANA_WHATSAPP_PHONE_NUMBER_ID', '1203757566165126');
define('CAMPANA_WHATSAPP_API_VERSION', 'v25.0');
define('CAMPANA_WHATSAPP_COUNTRY_PREFIX', '57');
define('CAMPANA_WHATSAPP_TEMPLATE_LANGUAGE', 'es');

/** Endpoint en omarcampana que evalúa niveles/regalos al aceptar una invitación. */
define(
    'CAMPANA_EVALUAR_NIVELES_URL',
    'http://localhost/omarcampana/api/whatsapp_invitacion_aceptada.php'
);
/** Debe coincidir con config.ini [whatsapp] webhook_secret de omarcampana. */
define('CAMPANA_WEBHOOK_SECRET', 'oc_wa_inv_7f3a9c2e1b8d4e6a9f0c');
