-- Invitaciones de videollamada (100ms) enviadas a la usuaria por WhatsApp/SMS.

CREATE TABLE IF NOT EXISTS tbl_videollamada_invitacion (
  id INT(11) NOT NULL AUTO_INCREMENT,
  alerta_id INT(11) NOT NULL,
  usuario_id INT(11) NOT NULL,
  room_name VARCHAR(150) NOT NULL,
  guest_url VARCHAR(500) NOT NULL,
  canal ENUM('whatsapp','sms') NOT NULL,
  estado ENUM('pendiente','unida','cerrada') NOT NULL DEFAULT 'pendiente',
  creado_en DATETIME NOT NULL,
  unido_en DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_usuario_estado (usuario_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
