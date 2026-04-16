<?php
// Actualizacion 5 - admin/index.php
// Dashboard de admin con: stats, alertas, feed, anuncios, estadisticas
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../index.php'); exit;
}

// ── STATS ──────────────────────────────────────────────────────
$total_usuarios      = $conexion->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='ciudadano'")->fetch_assoc()['t'] ?? 0;
$total_reportes_24h  = $conexion->query("SELECT COUNT(*) as t FROM reportes WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['t'] ?? 0;
$total_reportes_all  = $conexion->query("SELECT COUNT(*) as t FROM reportes")->fetch_assoc()['t'] ?? 0;
$total_fugas_act     = $conexion->query("SELECT COUNT(*) as t FROM fugas WHERE estado != 'resuelto'")->fetch_assoc()['t'] ?? 0;
$total_fugas_mes     = $conexion->query("SELECT COUNT(*) as t FROM fugas WHERE estado='resuelto' AND MONTH(fecha)=MONTH(NOW())")->fetch_assoc()['t'] ?? 0;
$total_colonias      = $conexion->query("SELECT COUNT(*) as t FROM colonias")->fetch_assoc()['t'] ?? 0;
$total_dir           = $conexion->query("SELECT COUNT(*) as t FROM directorio WHERE activo=1")->fetch_assoc()['t'] ?? 0;

// Distribución tipos reporte 24h
$dist = [];
$res = $conexion->query("SELECT tipo, COUNT(*) as n FROM reportes WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY tipo");
while ($r = $res->fetch_assoc()) $dist[$r['tipo']] = $r['n'];

// Alertas criticas (sin_agua últimas 2h)
$alertas = $conexion->query(
    "SELECT c.nombre, COUNT(*) as total FROM reportes r
     JOIN colonias c ON r.colonia_id = c.id
     WHERE r.tipo='sin_agua' AND r.fecha >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
     GROUP BY r.colonia_id ORDER BY total DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// Ultimas fugas
$fugas = $conexion->query(
    "SELECT f.id, f.folio, f.descripcion, f.estado, f.total_firmas, f.fecha,
            c.nombre as colonia FROM fugas f
     LEFT JOIN colonias c ON f.colonia_id=c.id
     ORDER BY f.fecha DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

// Ultimos reportes
$reportes = $conexion->query(
    "SELECT r.tipo, r.fecha, c.nombre as colonia, u.correo
     FROM reportes r
     JOIN colonias c ON r.colonia_id=c.id
     JOIN usuarios u ON r.usuario_id=u.id
     ORDER BY r.fecha DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

// Usuarios recientes
$usuarios = $conexion->query(
    "SELECT id, nombre, correo, karma, nivel_karma, rol, activo, fecha_registro
     FROM usuarios ORDER BY fecha_registro DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

// Ranking colonias
$ranking = $conexion->query(
    "SELECT c.nombre, COUNT(r.id) as total,
     SUM(r.tipo='sin_agua') as sin_agua
     FROM colonias c LEFT JOIN reportes r ON c.id=r.colonia_id
     GROUP BY c.id ORDER BY total DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Top karma
$top_karma = $conexion->query(
    "SELECT nombre, karma, nivel_karma FROM usuarios ORDER BY karma DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Anuncios (si existe tabla)
$tbl_avisos = $conexion->query("SHOW TABLES LIKE 'avisos'")->num_rows > 0;
$anuncios = $tbl_avisos
    ? $conexion->query("SELECT titulo, mensaje, fecha FROM avisos ORDER BY fecha DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC)
    : [];

// ── ACCIONES POST ───────────────────────────────────────────────
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Cambiar estado fuga
    if ($accion === 'estado_fuga') {
        $id  = intval($_POST['fuga_id']);
        $est = $_POST['estado'];
        if (in_array($est, ['pendiente','en_proceso','resuelto'])) {
            $conexion->query("UPDATE fugas SET estado='$est' WHERE id=$id");
            $msg = "Fuga actualizada a: $est";
        }
    }

    // Agregar colonia
    if ($accion === 'add_colonia') {
        $nom = $conexion->real_escape_string(trim($_POST['nombre']));
        $lat = floatval($_POST['lat']);
        $lng = floatval($_POST['lng']);
        if ($nom) { $conexion->query("INSERT INTO colonias (nombre,lat,lng) VALUES('$nom',$lat,$lng)"); $msg = "Colonia '$nom' agregada."; }
    }

    // Cambiar rol usuario
    if ($accion === 'cambiar_rol') {
        $uid = intval($_POST['uid']);
        $rol = in_array($_POST['rol'], ['ciudadano','admin']) ? $_POST['rol'] : 'ciudadano';
        $conexion->query("UPDATE usuarios SET rol='$rol' WHERE id=$uid");
        $msg = "Rol actualizado.";
    }

    // Enviar anuncio
    if ($accion === 'anuncio' && $tbl_avisos) {
        $tit = $conexion->real_escape_string(trim($_POST['titulo']));
        $men = $conexion->real_escape_string(trim($_POST['mensaje']));
        if ($tit && $men) {
            $conexion->query("INSERT INTO avisos (titulo, mensaje) VALUES('$tit','$men')");
            $conexion->query("INSERT INTO notificaciones (usuario_id,titulo,mensaje) SELECT id,'$tit','$men' FROM usuarios WHERE rol='ciudadano'");
            $msg = "Anuncio enviado.";
        }
    }
}

$conexion->close();

// Helper tiempo relativo
function tr($fecha) {
    $s = time() - strtotime($fecha);
    if ($s < 60)    return "hace {$s}s";
    if ($s < 3600)  return "hace ".floor($s/60)."min";
    if ($s < 86400) return "hace ".floor($s/3600)."h";
    return "hace ".floor($s/86400)."d";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | AguaVic Act.5</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
.header { background: #1a56db; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
.header h1 { margin: 0; font-size: 18px; }
.header a { color: #bfdbfe; font-size: 13px; text-decoration: none; }
.nav { background: #1e3a8a; padding: 8px 20px; display: flex; gap: 6px; flex-wrap: wrap; }
.nav a { color: #93c5fd; font-size: 13px; text-decoration: none; padding: 4px 10px; border-radius: 4px; }
.nav a:hover, .nav a.active { background: #1a56db; color: white; }
.main { padding: 16px 20px; max-width: 1100px; }
.msg { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; font-size: 13px; }
.warn { background: #fef9c3; border: 1px solid #fde047; color: #713f12; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; font-size: 13px; }

/* Stats */
.stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.stat { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 12px 16px; min-width: 130px; text-align: center; }
.stat .num { font-size: 26px; font-weight: bold; color: #1a56db; }
.stat .num.rojo { color: #dc2626; }
.stat .num.verde { color: #16a34a; }
.stat .lbl { font-size: 11px; color: #666; margin-top: 2px; }

/* Barras */
.barra-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 12px; }
.barra-bg { flex: 1; height: 14px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.barra-fill { height: 100%; border-radius: 4px; }
.barra-n { width: 30px; text-align: right; color: #555; font-size: 12px; }

/* Grid 2 col */
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
@media(max-width:720px){ .grid2 { grid-template-columns: 1fr; } }

/* Card */
.card { background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 14px; overflow: hidden; }
.card-head { background: #f8fafc; border-bottom: 1px solid #ddd; padding: 8px 14px; font-weight: bold; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
.card-body { padding: 12px 14px; }

/* Tablas */
table { width: 100%; border-collapse: collapse; font-size: 12px; }
th { background: #f1f5f9; text-align: left; padding: 6px 10px; color: #555; font-size: 11px; border-bottom: 1px solid #ddd; }
td { padding: 6px 10px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }

/* Badges */
.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
.b-pend { background: #fef9c3; color: #92400e; }
.b-proc { background: #dbeafe; color: #1e40af; }
.b-res  { background: #dcfce7; color: #166534; }
.b-adm  { background: #fce7f3; color: #9d174d; }

/* Formularios */
input[type=text], input[type=number], select, textarea {
    border: 1px solid #ccc; border-radius: 5px; padding: 5px 8px;
    font-size: 12px; width: 100%; box-sizing: border-box; margin-bottom: 6px;
}
textarea { resize: vertical; }
label { font-size: 12px; color: #444; display: block; margin-bottom: 2px; }
.form-row { display: flex; gap: 10px; flex-wrap: wrap; }
.form-row .f { flex: 1; min-width: 150px; }
button, .btn { background: #1a56db; color: white; border: none; padding: 6px 14px; border-radius: 5px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-block; }
button:hover, .btn:hover { background: #1e40af; }
.btn-sm { padding: 3px 8px; font-size: 11px; }
.btn-rojo { background: #dc2626; }
.btn-rojo:hover { background: #b91c1c; }
.btn-verde { background: #16a34a; }
.btn-verde:hover { background: #15803d; }
.btn-gris { background: #6b7280; }
.btn-gris:hover { background: #4b5563; }

/* Feed */
.feed-item { display: flex; gap: 10px; align-items: flex-start; padding: 7px 0; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
.feed-item:last-child { border-bottom: none; }
.feed-ico { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.feed-time { color: #999; font-size: 11px; margin-left: auto; flex-shrink: 0; white-space: nowrap; }

/* Secciones */
.seccion { display: none; }
.seccion.activo { display: block; }
</style>
</head>
<body>

<div class="header">
    <h1>💧 AguaVic — Panel de Administración (Act. 5)</h1>
    <a href="../index.php">← Volver al sistema</a>
</div>

<div class="nav">
    <a href="#" onclick="sec('dashboard')" id="n-dashboard" class="active">📊 Dashboard</a>
    <a href="#" onclick="sec('reportes')" id="n-reportes">📋 Reportes</a>
    <a href="#" onclick="sec('fugas')" id="n-fugas">🔧 Fugas</a>
    <a href="#" onclick="sec('colonias')" id="n-colonias">🏘️ Colonias</a>
    <a href="#" onclick="sec('usuarios')" id="n-usuarios">👥 Usuarios</a>
    <a href="#" onclick="sec('anuncios')" id="n-anuncios">📣 Anuncios</a>
    <a href="#" onclick="sec('estadisticas')" id="n-estadisticas">📈 Estadísticas</a>
</div>

<div class="main">

<?php if ($msg): ?><div class="msg">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- ===== DASHBOARD ===== -->
<div class="seccion activo" id="sec-dashboard">
    <h2 style="font-size:16px;margin:0 0 12px">📊 Dashboard</h2>

    <!-- Stats -->
    <div class="stats">
        <div class="stat"><div class="num"><?= $total_usuarios ?></div><div class="lbl">Usuarios</div></div>
        <div class="stat"><div class="num"><?= $total_reportes_24h ?></div><div class="lbl">Reportes (24h)</div></div>
        <div class="stat"><div class="num rojo"><?= $total_fugas_act ?></div><div class="lbl">Fugas activas</div></div>
        <div class="stat"><div class="num verde"><?= $total_fugas_mes ?></div><div class="lbl">Fugas resueltas (mes)</div></div>
        <div class="stat"><div class="num"><?= $total_reportes_all ?></div><div class="lbl">Reportes totales</div></div>
        <div class="stat"><div class="num"><?= $total_colonias ?></div><div class="lbl">Colonias</div></div>
        <div class="stat"><div class="num verde"><?= $total_dir ?></div><div class="lbl">Servicios activos</div></div>
    </div>

    <!-- Gráfica distribución tipos --> 
    <?php $tot_b = array_sum($dist) ?: 1; ?>
    <div class="card" style="margin-bottom:14px">
        <div class="card-head">📉 Tipos de reporte — últimas 24h</div>
        <div class="card-body">
            <?php
            $tipos = ['sin_agua'=>['🔴 Sin agua','#dc2626'], 'baja_presion'=>['🟡 Baja presión','#ca8a04'], 'presion_normal'=>['🟢 Normal','#16a34a']];
            foreach ($tipos as $k => [$lbl, $color]):
                $v = $dist[$k] ?? 0; $pct = round($v/$tot_b*100);
            ?>
            <div class="barra-row">
                <span style="width:110px"><?= $lbl ?></span>
                <div class="barra-bg"><div class="barra-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
                <span class="barra-n"><?= $v ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid2">
        <!-- Alertas criticas -->
        <div class="card">
            <div class="card-head">🚨 Alertas sin agua (últimas 2h)</div>
            <div class="card-body">
                <?php if (empty($alertas)): ?>
                    <p style="color:#666;font-size:12px;text-align:center">✅ Sin alertas</p>
                <?php else: foreach ($alertas as $a): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f0f0f0;font-size:12px">
                        <span>📍 <?= htmlspecialchars($a['nombre']) ?></span>
                        <span style="background:#dc2626;color:white;padding:1px 8px;border-radius:10px;font-size:11px"><?= $a['total'] ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Feed actividad -->
        <div class="card">
            <div class="card-head">⚡ Actividad reciente</div>
            <div class="card-body" style="padding:8px 14px">
                <?php
                // Armar feed simple con últimos reportes y fugas
                $feed_items = [];
                foreach (array_slice($reportes, 0, 5) as $rep) {
                    $feed_items[] = ['ico'=>'📋', 'desc'=>"Reporte: ".str_replace('_',' ',$rep['tipo']), 'lugar'=>$rep['colonia'], 'fecha'=>$rep['fecha']];
                }
                foreach (array_slice($fugas, 0, 5) as $f) {
                    $feed_items[] = ['ico'=>'🔧', 'desc'=>"Fuga ".$f['estado'], 'lugar'=>$f['colonia'], 'fecha'=>$f['fecha']];
                }
                usort($feed_items, fn($a,$b) => strtotime($b['fecha'])-strtotime($a['fecha']));
                $feed_items = array_slice($feed_items, 0, 8);
                ?>
                <?php if (empty($feed_items)): ?>
                    <p style="color:#666;font-size:12px">Sin actividad.</p>
                <?php else: foreach ($feed_items as $fi): ?>
                <div class="feed-item">
                    <span class="feed-ico"><?= $fi['ico'] ?></span>
                    <div>
                        <div><?= htmlspecialchars($fi['desc']) ?></div>
                        <div style="color:#888;font-size:11px">📍 <?= htmlspecialchars($fi['lugar'] ?? '—') ?></div>
                    </div>
                    <span class="feed-time"><?= tr($fi['fecha']) ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== REPORTES ===== -->
<div class="seccion" id="sec-reportes">
    <h2 style="font-size:16px;margin:0 0 12px">📋 Reportes de ciudadanos</h2>
    <div class="card">
        <div class="card-head">Últimos 10 reportes</div>
        <table>
            <thead><tr><th>Fecha</th><th>Colonia</th><th>Tipo</th><th>Usuario</th></tr></thead>
            <tbody>
                <?php foreach ($reportes as $r): ?>
                <tr>
                    <td><?= $r['fecha'] ?></td>
                    <td><?= htmlspecialchars($r['colonia']) ?></td>
                    <td><?= str_replace('_',' ', $r['tipo']) ?></td>
                    <td style="color:#666"><?= htmlspecialchars($r['correo']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reportes)): ?><tr><td colspan="4" style="text-align:center;color:#999">Sin reportes.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== FUGAS ===== -->
<div class="seccion" id="sec-fugas">
    <h2 style="font-size:16px;margin:0 0 12px">🔧 Gestión de Fugas</h2>
    <div class="card">
        <div class="card-head">Fugas registradas</div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Folio</th><th>Colonia</th><th>Firmas</th><th>Fecha</th><th>Estado</th><th>Cambiar</th></tr></thead>
            <tbody>
                <?php foreach ($fugas as $f): ?>
                <tr>
                    <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($f['folio']) ?></td>
                    <td><?= htmlspecialchars($f['colonia'] ?? '—') ?></td>
                    <td style="text-align:center"><?= $f['total_firmas'] ?></td>
                    <td style="font-size:11px"><?= date('d/m/y H:i', strtotime($f['fecha'])) ?></td>
                    <td>
                        <span class="badge <?= ['pendiente'=>'b-pend','en_proceso'=>'b-proc','resuelto'=>'b-res'][$f['estado']] ?? '' ?>"><?= ucfirst(str_replace('_',' ',$f['estado'])) ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="accion" value="estado_fuga">
                            <input type="hidden" name="fuga_id" value="<?= $f['id'] ?>">
                            <select name="estado" onchange="this.form.submit()" style="width:auto;padding:2px 5px;font-size:11px">
                                <?php foreach(['pendiente','en_proceso','resuelto'] as $e): ?>
                                <option value="<?= $e ?>" <?= $f['estado']===$e?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$e)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($fugas)): ?><tr><td colspan="6" style="text-align:center;color:#999">Sin fugas.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ===== COLONIAS ===== -->
<div class="seccion" id="sec-colonias">
    <h2 style="font-size:16px;margin:0 0 12px">🏘️ Colonias</h2>
    <div class="card" style="margin-bottom:12px">
        <div class="card-head">Agregar colonia</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="accion" value="add_colonia">
                <div class="form-row">
                    <div class="f"><label>Nombre *</label><input type="text" name="nombre" required></div>
                    <div class="f"><label>Latitud</label><input type="number" name="lat" step="0.000001" value="0"></div>
                    <div class="f"><label>Longitud</label><input type="number" name="lng" step="0.000001" value="0"></div>
                </div>
                <button type="submit">➕ Agregar</button>
            </form>
        </div>
    </div>
    <?php
    // Recargar conexion para lista
    require_once '../config/db.php';
    $cols = $conexion->query("SELECT id,nombre,lat,lng FROM colonias ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
    $conexion->close();
    ?>
    <div class="card">
        <div class="card-head">Colonias registradas (<?= count($cols) ?>)</div>
        <table>
            <thead><tr><th>#</th><th>Nombre</th><th>Lat</th><th>Lng</th></tr></thead>
            <tbody>
                <?php foreach ($cols as $c): ?>
                <tr>
                    <td style="color:#999"><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= $c['lat'] ?? '—' ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= $c['lng'] ?? '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== USUARIOS ===== -->
<div class="seccion" id="sec-usuarios">
    <h2 style="font-size:16px;margin:0 0 12px">👥 Gestión de Usuarios</h2>
    <div class="card">
        <div class="card-head">Últimos usuarios <span style="font-weight:normal;color:#666">(Total: <?= $total_usuarios ?>)</span></div>
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Karma</th><th>Nivel</th><th>Rol</th><th>Registro</th><th>Cambiar rol</th></tr></thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td style="color:#666;font-size:11px"><?= htmlspecialchars($u['correo'] ?? '—') ?></td>
                    <td style="color:#1a56db;font-weight:bold"><?= $u['karma'] ?></td>
                    <td><?= $u['nivel_karma'] ?></td>
                    <td><span class="badge <?= $u['rol']==='admin'?'b-adm':'b-proc' ?>"><?= $u['rol'] ?></span></td>
                    <td style="font-size:11px;color:#999"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="accion" value="cambiar_rol">
                            <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                            <select name="rol" onchange="this.form.submit()" style="width:auto;padding:2px 5px;font-size:11px">
                                <option value="ciudadano" <?= $u['rol']==='ciudadano'?'selected':'' ?>>Ciudadano</option>
                                <option value="admin" <?= $u['rol']==='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ===== ANUNCIOS ===== -->
<div class="seccion" id="sec-anuncios">
    <h2 style="font-size:16px;margin:0 0 12px">📣 Anuncios Masivos</h2>

    <?php if (!$tbl_avisos): ?>
    <div class="warn">⚠️ Ejecuta <code>setup_update5.sql</code> para habilitar los anuncios.</div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:12px">
        <div class="card-head">Enviar aviso a todos los ciudadanos</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="accion" value="anuncio">
                <label>Título *</label>
                <input type="text" name="titulo" placeholder="Ej: Corte programado el jueves" required>
                <label>Mensaje *</label>
                <textarea name="mensaje" rows="3" placeholder="Descripción del aviso..." required></textarea>
                <button type="submit">📣 Enviar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">Últimos anuncios</div>
        <?php if (empty($anuncios)): ?>
            <div class="card-body" style="color:#999;font-size:12px">Sin anuncios enviados aún.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Título</th><th>Mensaje</th><th>Fecha</th></tr></thead>
            <tbody>
                <?php foreach ($anuncios as $av): ?>
                <tr>
                    <td style="font-weight:bold"><?= htmlspecialchars($av['titulo']) ?></td>
                    <td style="color:#666;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($av['mensaje']) ?></td>
                    <td style="font-size:11px;color:#999"><?= date('d/m/Y H:i', strtotime($av['fecha'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ===== ESTADÍSTICAS ===== -->
<div class="seccion" id="sec-estadisticas">
    <h2 style="font-size:16px;margin:0 0 12px">📈 Estadísticas del sistema</h2>

    <div class="grid2">
        <!-- Colonias más activas -->
        <div class="card">
            <div class="card-head">🏘️ Colonias más activas</div>
            <table>
                <thead><tr><th>Colonia</th><th>Reportes</th><th>Sin agua</th></tr></thead>
                <tbody>
                    <?php foreach ($ranking as $rc): ?>
                    <tr>
                        <td><?= htmlspecialchars($rc['nombre']) ?></td>
                        <td style="font-weight:bold;color:#1a56db;text-align:center"><?= $rc['total'] ?></td>
                        <td style="color:#dc2626;text-align:center"><?= $rc['sin_agua'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ranking)): ?><tr><td colspan="3" style="color:#999;text-align:center">Sin datos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top karma -->
        <div class="card">
            <div class="card-head">🏆 Top usuarios por karma</div>
            <table>
                <thead><tr><th>#</th><th>Nombre</th><th>Karma</th><th>Nivel</th></tr></thead>
                <tbody>
                    <?php foreach ($top_karma as $i => $u): ?>
                    <tr>
                        <td style="color:#999"><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td style="font-weight:bold;color:#1a56db"><?= $u['karma'] ?></td>
                        <td><?= $u['nivel_karma'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fugas por estado -->
    <div class="card">
        <div class="card-head">🔧 Fugas por estado</div>
        <div class="card-body">
            <?php
            require_once '../config/db.php';
            $fest = $conexion->query("SELECT estado, COUNT(*) as n FROM fugas GROUP BY estado")->fetch_all(MYSQLI_ASSOC);
            $conexion->close();
            $tot_fest = array_sum(array_column($fest,'n')) ?: 1;
            $c_est = ['pendiente'=>'#ca8a04','en_proceso'=>'#1a56db','resuelto'=>'#16a34a'];
            foreach ($fest as $fe):
                $pct = round($fe['n']/$tot_fest*100);
            ?>
            <div class="barra-row">
                <span style="width:100px"><?= ucfirst(str_replace('_',' ',$fe['estado'])) ?></span>
                <div class="barra-bg"><div class="barra-fill" style="width:<?= $pct ?>%;background:<?= $c_est[$fe['estado']] ?? '#888' ?>"></div></div>
                <span class="barra-n"><?= $fe['n'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($fest)): ?><p style="color:#999;font-size:12px">Sin datos de fugas.</p><?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /main -->

<script>
function sec(id) {
    document.querySelectorAll('.seccion').forEach(s => s.classList.remove('activo'));
    document.querySelectorAll('.nav a').forEach(a => a.classList.remove('active'));
    document.getElementById('sec-' + id)?.classList.add('activo');
    document.getElementById('n-' + id)?.classList.add('active');
    return false;
}
</script>

</body>
</html>