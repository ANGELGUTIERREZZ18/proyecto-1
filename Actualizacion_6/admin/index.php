<?php
session_start(); require_once '../config/db.php';
if($_SESSION['rol'] !== 'admin') { header('Location: ../index.php'); exit; }
$reportes = $conexion->query("SELECT r.tipo, r.fecha, c.nombre, u.correo FROM reportes r JOIN colonias c ON r.colonia_id=c.id JOIN usuarios u ON r.usuario_id=u.id");
?>
<!DOCTYPE html><html><head><style>body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; } table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top:10px; } th, td { border: 1px solid #ccc; padding: 8px; text-align: left; } th { background-color: #e0e0e0; } h1, h2 { color: #333; }</style></head>
<body>
<h1>Panel de Administrador</h1>
<h2>Reportes de ciudadanos</h2>
<table border="1">
<tr><th>Fecha</th><th>Colonia</th><th>Problema</th><th>Usuario</th></tr>
<?php while($row = $reportes->fetch_assoc()): ?>
<tr><td><?php echo $row['fecha']; ?></td><td><?php echo $row['nombre']; ?></td><td><?php echo $row['tipo']; ?></td><td><?php echo $row['correo']; ?></td></tr>
<?php endwhile; ?>
</table><br><a href="../index.php">Volver</a>
</body></html>