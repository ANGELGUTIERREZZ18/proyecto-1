<?php
session_start();
require_once 'config/db.php';
if(!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

// Buscador backend
if(isset($_GET['q'])) {
    $q = "%".$_GET['q']."%";
    $stmt = $conexion->prepare("SELECT id, nombre FROM colonias WHERE nombre LIKE ? LIMIT 5");
    $stmt->bind_param('s', $q); $stmt->execute();
    $res = $stmt->get_result(); $data = [];
    while($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode($data); exit;
}
?>
<!DOCTYPE html><html><head><title>Inicio</title></head><head><style>body{font-family:sans-serif;background:#f9f9f9;padding:2vh;} h1,h2{color:#444;} input,button{padding:10px;margin:5px 0;width:200px;} button{background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;} .search-res div {padding:8px; border-bottom:1px solid #ddd; background:#fff; cursor:pointer;} .search-res div:hover {background:#f1f1f1;}</style></head>
<body>
<h1>Panel Principal AguaVic</h1>
<p>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> (<?php echo $_SESSION['rol']; ?>)</p>

<h3>Buscar mi Colonia:</h3>
<input type="text" id="buscador" placeholder="Escribe tu colonia..." onkeyup="buscarColonia(this.value)">
<div id="resultados" class="search-res" style="width:220px; position:absolute;"></div>

<br><br><a href="logout.php">Cerrar Sesion</a>

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