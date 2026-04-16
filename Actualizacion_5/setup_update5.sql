-- Actualizacion 5: tablas para anuncios y config
-- Ejecutar sobre la base de datos "agua" en phpMyAdmin

-- Tabla de avisos masivos
CREATE TABLE IF NOT EXISTS `avisos` (
    `id`      INT NOT NULL AUTO_INCREMENT,
    `titulo`  VARCHAR(200) NOT NULL,
    `mensaje` TEXT NOT NULL,
    `fecha`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de configuracion del sistema (clave-valor)
CREATE TABLE IF NOT EXISTS `sistema_config` (
    `clave`       VARCHAR(80) NOT NULL,
    `valor`       TEXT DEFAULT NULL,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `sistema_config` (`clave`, `valor`, `descripcion`) VALUES
('app_nombre',         'AguaVic',         'Nombre de la app'),
('karma_por_reporte',  '10',              'Karma por reporte validado'),
('modo_mantenimiento', '0',               '1 = pantalla de mantenimiento activa');
