-- Líneas SIP de Zadarma y candado de concurrencia compartido con las
-- videollamadas de 100ms (solo una comunicación activa a la vez).

CREATE TABLE IF NOT EXISTS tbl_zadarma_lineas (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  sip_login VARCHAR(50) NOT NULL,
  usuario_panel_id INT(11) DEFAULT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tbl_comunicacion_activa (
  id INT(11) NOT NULL AUTO_INCREMENT,
  tipo ENUM('llamada','videollamada') NOT NULL,
  alerta_id INT(11) NOT NULL,
  usuario_panel_id INT(11) DEFAULT NULL,
  linea_id INT(11) DEFAULT NULL,
  iniciado_en DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_iniciado_en (iniciado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tbl_zadarma_lineas (nombre, sip_login, usuario_panel_id, activa, creado_en)
SELECT 'Linea principal', '618410', NULL, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM tbl_zadarma_lineas WHERE sip_login = '618410');
