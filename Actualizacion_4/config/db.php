<?php
$host = 'localhost'; $db = 'agua'; $user = 'root'; $pass = '';
$conexion = new mysqli($host, $user, $pass, $db);
if ($conexion->connect_error) die("Error de conexion: " . $conexion->connect_error);
$conexion->set_charset("utf8mb4");