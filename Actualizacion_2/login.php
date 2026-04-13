<?php
session_start();
require_once 'config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $stmt = $conexion->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE correo = ?");
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        if (password_verify($contrasena, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            header('Location: index.php'); exit;
        } else { $error = 'Contrasena incorrecta.'; }
    } else { $error = 'Usuario no encontrado.'; }
}
?>
<!DOCTYPE html><html><head><title>Login</title></head><head><style>body{font-family:sans-serif;background:#f9f9f9;padding:2vh;} h1,h2{color:#444;} input,button{padding:10px;margin:5px 0;width:200px;} button{background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;} .search-res div {padding:8px; border-bottom:1px solid #ddd; background:#fff; cursor:pointer;} .search-res div:hover {background:#f1f1f1;}</style></head>
<body>
<h2>Iniciar Sesion</h2>
<?php if($error) echo "<p style='color:red'>$error</p>"; ?>
<form method="POST">
    Correo: <input type="email" name="correo" required><br><br>
    Clave: <input type="password" name="contrasena" required><br><br>
    <button type="submit">Entrar</button>
</form>
<a href="registro.php">Registrar cuenta</a>
</body></html>