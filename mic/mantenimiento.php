<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();

// Programar mantenimiento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['programar'])) {
    $stmt = $conn->prepare("INSERT INTO mantenimiento (id_equipo, id_usuario, fecha_inicio, descripcion_trabajo, estado) VALUES (?, ?, ?, ?, 'programado')");
    $stmt->execute([$_POST['id_equipo'], $usuario['id'], $_POST['fecha_inicio'], $_POST['descripcion']]);
    $exito = "Mantenimiento programado correctamente";
}

// Completar mantenimiento
if (isset($_GET['completar'])) {
    $stmt = $conn->prepare("UPDATE mantenimiento SET estado = 'completado', fecha_fin = CURDATE() WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: mantenimiento.php');
    exit;
}

// Obtener mantenimientos
$mantenimientos = $conn->query("SELECT m.*, e.nombre as equipo_nombre FROM mantenimiento m JOIN equipos e ON m.id_equipo = e.id ORDER BY m.fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$equipos = $conn->query("SELECT id, nombre FROM equipos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento - MIC</title>
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
                        <h2><i class="fas fa-tools"></i> Mantenimiento</h2>
                        <p>Gestión de mantenimiento de equipos</p>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('addMantenimientoModal')"><i class="fas fa-plus"></i> Programar Mantenimiento</button>
                </div>

                <?php if(isset($exito)): ?>
                    <div class="alert alert-success"><?php echo $exito; ?></div>
                <?php endif; ?>

                <div class="glass-card">
                    <div class="kpi-grid" style="margin-bottom: 20px;">
                        <div class="stat-mini-card">
                            <span class="stat-mini-label">Programados</span>
                            <div class="stat-mini-value"><?php echo count(array_filter($mantenimientos, fn($m) => $m['estado'] == 'programado')); ?></div>
                        </div>
                        <div class="stat-mini-card">
                            <span class="stat-mini-label">En Proceso</span>
                            <div class="stat-mini-value"><?php echo count(array_filter($mantenimientos, fn($m) => $m['estado'] == 'en_proceso')); ?></div>
                        </div>
                        <div class="stat-mini-card">
                            <span class="stat-mini-label">Completados</span>
                            <div class="stat-mini-value"><?php echo count(array_filter($mantenimientos, fn($m) => $m['estado'] == 'completado')); ?></div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="premium-table">
                            <thead>
                                <tr><th>Equipo</th><th>Fecha Inicio</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($mantenimientos as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['equipo_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($m['fecha_inicio'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($m['descripcion_trabajo'], 0, 50)); ?>...</td>
                                    <td><span class="badge badge-<?php echo $m['estado'] == 'completado' ? 'success' : ($m['estado'] == 'programado' ? 'warning' : 'info'); ?>"><?php echo ucfirst($m['estado']); ?></span></td>
                                    <td><?php if($m['estado'] != 'completado'): ?><a href="?completar=1&id=<?php echo $m['id']; ?>" class="btn-icon" onclick="return confirm('¿Completar este mantenimiento?')"><i class="fas fa-check"></i></a><?php else: ?>-<?php endif; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Programar Mantenimiento -->
<div class="modal" id="addMantenimientoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Programar Mantenimiento</h3>
            <button class="modal-close" onclick="closeModal('addMantenimientoModal')">&times;</button>
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
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción del Trabajo</label>
                    <textarea name="descripcion" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="programar" class="btn btn-primary btn-block">Programar</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>