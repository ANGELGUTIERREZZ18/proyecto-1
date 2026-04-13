<?php
session_start(); require_once 'config/db.php';
if(!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

// Buscador backend (from stage 2)
if(isset($_GET['q'])) {
    $q = "%".$_GET['q']."%";
    $stmt = $conexion->prepare("SELECT id, nombre FROM colonias WHERE nombre LIKE ? LIMIT 5");
    $stmt->bind_param('s', $q); $stmt->execute();
    $res = $stmt->get_result(); $data = [];
    while($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode($data); exit;
}
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reportar'])) {
    $colonia_id = $_POST['colonia_id'];
    $tipo = $_POST['tipo'];
    $uid = $_SESSION['usuario_id'];
    $conexion->query("INSERT INTO reportes (usuario_id, colonia_id, tipo) VALUES ($uid, $colonia_id, '$tipo')");
    $mensaje = "Reporte guardado.";
}

$tandeos = $conexion->query("SELECT c.nombre, t.dia, t.hora_inicio, t.hora_fin FROM tandeos t JOIN colonias c ON t.colonia_id = c.id");
$colonias = $conexion->query("SELECT id, nombre FROM colonias");
?>
<!DOCTYPE html><html><head><title>Inicio</title></head><head><style>body{font-family:'Segoe UI',sans-serif; background:#f0f2f5; padding:2vh;} h1,h2{color:#333;} input,button,select{padding:10px; margin:5px 0; border-radius:4px; border:1px solid #ccc;} button{background:#0056b3; color:#fff; border:none; cursor:pointer;} table{width:100%; border-collapse:collapse; background:#fff;} th,td{border:1px solid #ddd; padding:10px; text-align:left;} th{background:#eee;} .search-res div {padding:8px; border-bottom:1px solid #ddd; background:#fff; cursor:pointer;} .search-res div:hover {background:#f1f1f1;}</style></head>
<body>
<h1>Panel Principal AguaVic</h1>
<p>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></p>

<h3>Buscar mi Colonia:</h3>
<input type="text" id="buscador" placeholder="Escribe tu colonia..." onkeyup="buscarColonia(this.value)">
<div id="resultados" class="search-res" style="width:220px; position:absolute;"></div>

<?php if(isset($mensaje)) echo "<p style='color:green'>$mensaje</p>"; ?>

<h2>Generar Reporte</h2>
<form method="POST">
    Colonia: <select name="colonia_id">
        <?php while($c = $colonias->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
    </select><br>
    Problema: <select name="tipo">
        <option value="sin_agua">Falta de agua</option>
        <option value="baja_presion">Baja presión</option>
    </select><br>
    <button type="submit" name="reportar">Enviar Reporte</button>
</form>

<h2>Horarios de Tandeo</h2>
<table border="1">
    <tr><th>Colonia</th><th>Día</th><th>Inicio</th><th>Fin</th></tr>
    <?php while($row = $tandeos->fetch_assoc()): ?>
    <tr><td><?php echo $row['nombre']; ?></td><td><?php echo $row['dia']; ?></td><td><?php echo $row['hora_inicio']; ?></td><td><?php echo $row['hora_fin']; ?></td></tr>
    <?php endwhile; ?>
</table><br><a href="logout.php">Salir</a>

<script>
function buscarColonia(q) {
    if(q.length < 2) { document.getElementById('resultados').innerHTML = ''; return; }
    fetch('index.php?q=' + q)
    .then(r => r.json())
    .then(data => {
        let html = '';
        data.forEach(c => html += `<div onclick="alert('Seleccionaste: ${c.nombre}')">${c.nombre}</div>`);
        document.getElementById('resultados').innerHTML = html;
    });
}
</script>
</body></html>