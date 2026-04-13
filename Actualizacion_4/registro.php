<?php
require_once 'config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $hash = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, 'ciudadano')");
    $stmt->bind_param('sss', $nombre, $correo, $hash);
    if($stmt->execute()) { header('Location: login.php'); exit; }
}
?>
<!DOCTYPE html><html><head><title>Registro</title></head><head><style>body{font-family:'Segoe UI',sans-serif; background:#f0f2f5; padding:2vh;} h1,h2{color:#333;} input,button,select{padding:10px; margin:5px 0; border-radius:4px; border:1px solid #ccc;} button{background:#0056b3; color:#fff; border:none; cursor:pointer;} table{width:100%; border-collapse:collapse; background:#fff;} th,td{border:1px solid #ddd; padding:10px; text-align:left;} th{background:#eee;} .search-res div {padding:8px; border-bottom:1px solid #ddd; background:#fff; cursor:pointer;} .search-res div:hover {background:#f1f1f1;}</style></head>
<body>
<h2>Registro de Ciudadano</h2>
<form method="POST">
    Nombre: <input type="text" name="nombre" required><br><br>
    Correo: <input type="email" name="correo" required><br><br>
    Clave: <input type="password" name="contrasena" required><br><br>
    <button type="submit">Registrar</button>
</form>
</body></html>