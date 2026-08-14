-- Migración: chat psicológico con Claude (Violeta IA)
-- Ejecutar en la base de datos: mujer
-- Ver docs/PLAN_MIGRACION_CHAT_PSICOLOGICO.md, sección 4.

CREATE TABLE `tbl_chat_conversaciones` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `asistente` varchar(50) NOT NULL DEFAULT 'psicologico',
  `session_token` varchar(64) NOT NULL,
  `estado` enum('activa','cerrada') NOT NULL DEFAULT 'activa',
  `nivel_riesgo_maximo` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `alerta_nivel4_generada` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_notificado` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_notificado_fecha` datetime DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_ultima_actividad` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_token` (`session_token`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_asistente` (`asistente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tbl_chat_mensajes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversacion_id` int(10) UNSIGNED NOT NULL,
  `rol` enum('usuaria','asistente','sistema') NOT NULL,
  `contenido` text NOT NULL,
  `origen_mensaje` enum('texto','voz') NOT NULL DEFAULT 'texto',
  `nivel_riesgo` tinyint(1) UNSIGNED DEFAULT NULL,
  `senales_riesgo` text DEFAULT NULL COMMENT 'JSON con señales detectadas',
  `accion_sugerida` varchar(100) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conversacion_id` (`conversacion_id`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `tbl_alerta_policia`
  ADD COLUMN `chat_conversacion_id` int(10) UNSIGNED DEFAULT NULL AFTER `estado_atencion_visualizador`,
  ADD COLUMN `chat_mensaje_id` int(10) UNSIGNED DEFAULT NULL AFTER `chat_conversacion_id`;
