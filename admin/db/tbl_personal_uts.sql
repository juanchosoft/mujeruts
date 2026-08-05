-- Personal asociado a UTS (whitelist para registro)
-- Ejecutar en la base de datos: mujer

CREATE TABLE IF NOT EXISTS tbl_personal_uts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(200) NOT NULL,
  identificacion VARCHAR(45) NOT NULL,
  programa VARCHAR(150) DEFAULT NULL,
  ocupacion VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_identificacion (identificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
