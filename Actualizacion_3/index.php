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

$tandeos = $conexion->query("SELECT c.nombre, t.dia, t.hora_inicio, t.hora_fin FROM tandeos t JOIN colonias c ON t.colonia_id = c.id");
?>
<!DOCTYPE html><html><head><title>Inicio</title></head><head><style>body{font-family:'Segoe UI',sans-serif; background:#f5f5f5; padding:2vh;} h1,h2{color:#333;} input,button{padding:10px; margin:5px 0; border-radius:4px; border:1px solid #ccc;} button{background:#0056b3; color:#fff; border:none; cursor:pointer;} table{width:100%; border-collapse:collapse; background:#fff;} th,td{border:1px solid #ddd; padding:10px; text-align:left;} th{background:#eee;} .search-res div {padding:8px; border-bottom:1px solid #ddd; background:#fff; cursor:pointer;} .search-res div:hover {background:#f1f1f1;}</style></head>
<body>
<h1>Panel Principal AguaVic</h1>
<p>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></p>

<h3>Buscar mi Colonia:</h3>
<input type="text" id="buscador" placeholder="Escribe tu colonia..." onkeyup="buscarColonia(this.value)">
<div id="resultados" class="search-res" style="width:220px; position:absolute;"></div>

<h2>Horarios de Tandeo</h2>
<table border="1">
    <tr><th>Colonia</th><th>Día</th><th>Inicio</th><th>Fin</th></tr>
    <?php while($row = $tandeos->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['nombre']; ?></td>
        <td><?php echo $row['dia']; ?></td>
        <td><?php echo $row['hora_inicio']; ?></td>
        <td><?php echo $row['hora_fin']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<br><a href="logout.php">Cerrar Sesion</a>

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