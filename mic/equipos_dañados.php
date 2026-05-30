<?php
require_once 'config/conexion.php';
if (!estaLogueado() || !esAdmin()) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reportar'])) {
    $stmt = $conn->prepare("INSERT INTO inventario_dañados (id_equipo, descripcion_daño, fecha_daño, reportado_por, estado) VALUES (?, ?, CURDATE(), ?, 'pendiente')");
    $stmt->execute([$_POST['id_equipo'], $_POST['descripcion'], $_SESSION['user_id']]);
    $mensaje = "Daño reportado correctamente";
}

if (isset($_GET['reparar'])) {
    $stmt = $conn->prepare("UPDATE inventario_dañados SET estado = 'reparado', fecha_reparacion = CURDATE() WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: equipos_dañados.php');
    exit;
}

$dañados = $conn->query("SELECT d.*, e.nombre as equipo_nombre, e.codigo_interno FROM inventario_dañados d JOIN equipos e ON d.id_equipo = e.id ORDER BY d.fecha_daño DESC")->fetchAll(PDO::FETCH_ASSOC);
$equipos = $conn->query("SELECT id, nombre FROM equipos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Equipos Dañados - MIC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app" style="display:block;">
    <?php include 'includes/header.php'; ?>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="content-right">
            <div class="page active">
                <div class="page-header">
                    <div class="page-title">
                        <h2><i class="fas fa-exclamation-triangle"></i> Equipos Dañados</h2>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('addDañoModal')"><i class="fas fa-plus"></i> Reportar Daño</button>
                </div>

                <?php if(isset($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="premium-table">
                        <thead>
                            <tr><th>Código</th><th>Equipo</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($dañados as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['codigo_interno']); ?></td>
                                <td><?php echo htmlspecialchars($d['equipo_nombre']); ?></td>
                                <td><?php echo htmlspecialchars(substr($d['descripcion_daño'], 0, 50)); ?>...</td>
                                <td><?php echo date('d/m/Y', strtotime($d['fecha_daño'])); ?></td>
                                <td><span class="badge badge-<?php echo $d['estado'] == 'reparado' ? 'success' : 'danger'; ?>"><?php echo ucfirst($d['estado']); ?></span></td>
                                <td><?php if($d['estado'] != 'reparado'): ?><a href="?reparar=1&id=<?php echo $d['id']; ?>" class="btn-icon" onclick="return confirm('¿Marcar como reparado?')"><i class="fas fa-check"></i></a><?php else: ?>-<?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Reportar Daño -->
<div class="modal" id="addDañoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reportar Equipo Dañado</h3>
            <button class="modal-close" onclick="closeModal('addDañoModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    <label>Equipo</label>
                    <select name="id_equipo" class="form-control" required>
                        <option value="">Seleccionar equipo</option>
                        <?php foreach($equipos as $eq): ?>
                            <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripción del Daño</label>
                    <textarea name="descripcion" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="reportar" class="btn btn-primary btn-block">Reportar</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>