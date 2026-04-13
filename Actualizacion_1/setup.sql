USE agua;
CREATE TABLE `usuarios` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nombre` VARCHAR(100), `correo` VARCHAR(150), `password` VARCHAR(255), `rol` VARCHAR(20) DEFAULT 'ciudadano' );
INSERT INTO `usuarios` (`nombre`,`correo`,`password`,`rol`) VALUES ('Admin','admin@aguavic.mx','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');
