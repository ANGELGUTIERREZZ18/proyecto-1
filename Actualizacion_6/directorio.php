<?php
session_start(); require_once 'config/db.php';
$dir = $conexion->query("SELECT * FROM directorio");
?>
<!DOCTYPE html><html><head><style>body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; } table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top:10px; } th, td { border: 1px solid #ccc; padding: 8px; text-align: left; } th { background-color: #e0e0e0; } h1, h2 { color: #333; }</style></head>
<body>
<h1>Directorio de Servicios (Pipas)</h1>
<ul>
<?php while($row = $dir->fetch_assoc()): ?>
<li><?php echo $row['nombre'] . ' - Tel: ' . $row['telefono']; ?></li>
<?php endwhile; ?>
</ul><br><a href="index.php">Volver al mapa</a>
</body></html>