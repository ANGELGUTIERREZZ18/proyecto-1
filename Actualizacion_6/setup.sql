USE agua;
CREATE TABLE `usuarios` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nombre` VARCHAR(100), `correo` VARCHAR(150), `password` VARCHAR(255), `rol` VARCHAR(20) DEFAULT 'ciudadano' );
INSERT INTO `usuarios` (`nombre`,`correo`,`password`,`rol`) VALUES ('Admin','admin@aguavic.mx','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');

CREATE TABLE `colonias` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nombre` VARCHAR(100) );
CREATE TABLE `tandeos` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `colonia_id` INT, `dia` VARCHAR(20), `hora_inicio` TIME, `hora_fin` TIME );
INSERT INTO `colonias` (id, nombre) VALUES (1, 'Centro'), (2, 'Las Palmas');
INSERT INTO `tandeos` (colonia_id, dia, hora_inicio, hora_fin) VALUES (1, 'Lunes', '06:00:00', '10:00:00'), (2, 'Martes', '08:00:00', '12:00:00');
CREATE TABLE `reportes` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `usuario_id` INT, `colonia_id` INT, `tipo` VARCHAR(50), `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE `directorio` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nombre` VARCHAR(150), `telefono` VARCHAR(20) );
INSERT INTO `directorio` (nombre, telefono) VALUES ('Pipas Juan', '834-111-2222');
