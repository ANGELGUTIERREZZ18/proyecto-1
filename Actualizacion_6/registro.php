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
<!DOCTYPE html><html><head><title>Registro</title></head><head><style>body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; } table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top:10px; } th, td { border: 1px solid #ccc; padding: 8px; text-align: left; } th { background-color: #e0e0e0; } h1, h2 { color: #333; }</style></head>
<body>
<h2>Registro de Ciudadano</h2>
<form method="POST">
    Nombre: <input type="text" name="nombre" required><br><br>
    Correo: <input type="email" name="correo" required><br><br>
    Clave: <input type="password" name="contrasena" required><br><br>
    <button type="submit">Registrar</button>
</form>
</body></html>