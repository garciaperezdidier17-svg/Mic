<?php
require_once 'config/conexion.php';

if (!estaLogueado()) {
    header('Location: index.php');
    exit;
}

$usuario = obtenerUsuarioActual();
$esAdmin = esAdmin();

// Procesar nueva solicitud
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_solicitud'])) {
    $id_equipo = $_POST['id_equipo'];
    $motivo = trim($_POST['motivo']);
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    
    $stmt = $conn->prepare("INSERT INTO solicitudes (id_usuario, id_equipo, fecha_solicitud, hora_solicitud, motivo, estado) 
                            VALUES (?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$usuario['id'], $id_equipo, $fecha, $hora, $motivo]);
    
    $_SESSION['mensaje'] = 'Solicitud enviada correctamente';
    header('Location: solicitudes.php');
    exit;
}

// Procesar cambio de estado (solo admin)
if ($esAdmin && isset($_GET['cambiar_estado'])) {
    $id = $_GET['id'];
    $estado = $_GET['estado'];
    
    $stmt = $conn->prepare("UPDATE solicitudes SET estado = ?, fecha_atencion = NOW(), id_atendido = ? WHERE id = ?");
    $stmt->execute([$estado, $usuario['id'], $id]);
    
    $_SESSION['mensaje'] = "Solicitud $estado correctamente";
    header('Location: solicitudes.php');
    exit;
}

// Obtener equipos disponibles para el formulario
$equipos = $conn->query("SELECT id, nombre, stock FROM equipos WHERE estado = 'disponible' AND stock > 0 AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener solicitudes
if ($esAdmin) {
    $sql = "SELECT s.*, e.nombre as equipo_nombre, u.nombre as usuario_nombre 
            FROM solicitudes s 
            JOIN equipos e ON s.id_equipo = e.id 
            JOIN usuarios u ON s.id_usuario = u.id 
            ORDER BY s.creado_en DESC";
    $solicitudes = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT s.*, e.nombre as equipo_nombre 
            FROM solicitudes s 
            JOIN equipos e ON s.id_equipo = e.id 
            WHERE s.id_usuario = ? 
            ORDER BY s.creado_en DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario['id']]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estadísticas
$pendientes = count(array_filter($solicitudes, fn($s) => $s['estado'] == 'pendiente'));
$aprobadas = count(array_filter($solicitudes, fn($s) => $s['estado'] == 'aprobada'));
$rechazadas = count(array_filter($solicitudes, fn($s) => $s['estado'] == 'rechazada'));

$total = count($solicitudes);
$porcPendientes = $total > 0 ? round($pendientes / $total * 100) : 0;
$porcAprobadas = $total > 0 ? round($aprobadas / $total * 100) : 0;
$porcRechazadas = $total > 0 ? round($rechazadas / $total * 100) : 0;

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes - MIC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?php echo $esAdmin ? 'user-admin' : ''; ?>">

<div class="app" style="display:block;">
    <header class="glass-header">
        <div class="logo">
            <div class="logo-icon">🖥️</div>
            <div class="logo-text">
                <h2>MIC Inventario</h2>
                <p>Sistema de Gestión</p>
            </div>
        </div>
        <div class="header-actions">
            <div class="user-menu" onclick="toggleDropdown()">
                <div class="user-avatar"><i class="fas fa-user"></i></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                    <div class="user-role"><?php echo ucfirst($usuario['rol']); ?></div>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </header>

    <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-header">
            <div class="dropdown-avatar"><i class="fas fa-user"></i></div>
            <div class="dropdown-info">
                <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                <span><?php echo htmlspecialchars($usuario['email']); ?></span>
            </div>
        </div>
        <div class="dropdown-divider"></div>
        <a href="perfil.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Mi Perfil</a>
        <a href="dashboard.php" class="dropdown-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <div class="dropdown-divider"></div>
        <a href="actions/cerrar_sesion.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>

    <div class="main-layout">
        <aside class="sidebar-left" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-cubes"></i> Navegación</div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span></a>
                <a href="inventario.php" class="nav-item"><i class="fas fa-boxes"></i><span class="nav-text">Inventario</span></a>
                <a href="solicitudes.php" class="nav-item active"><i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span></a>
                <a href="prestamos.php" class="nav-item"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
                <?php if($esAdmin): ?>
                <a href="usuarios.php" class="nav-item admin-only"><i class="fas fa-users"></i><span class="nav-text">Usuarios</span></a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="system-info"><i class="fas fa-circle"></i> Sistema en línea</div>
                <div class="system-version">v2.0.0 &copy; MIC 2024</div>
            </div>
        </aside>

        <main class="content-right">
            <div class="page active">
                <div class="page-header">
                    <div class="page-title">
                        <h2><i class="fas fa-clipboard-list"></i> Solicitudes</h2>
                        <p>Gestión de solicitudes de equipos</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openModal('addSolicitudModal')">
                            <i class="fas fa-plus"></i> Nueva Solicitud
                        </button>
                    </div>
                </div>

                <?php if($mensaje): ?>
                <div style="background:#d4edda; color:#155724; padding:12px; border-radius:12px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="glass-card" style="padding:20px; margin-bottom:25px;">
                    <h3 style="margin-bottom:15px;">Resumen de Solicitudes</h3>
                    <div class="stat-progress">
                        <div class="stat-progress-item">
                            <span class="stat-progress-label">Pendientes</span>
                            <div class="progress-bar"><div class="progress-fill warning" style="width:<?php echo $porcPendientes; ?>%"></div></div>
                            <span class="stat-progress-value"><?php echo $pendientes; ?></span>
                        </div>
                        <div class="stat-progress-item">
                            <span class="stat-progress-label">Aprobadas</span>
                            <div class="progress-bar"><div class="progress-fill success" style="width:<?php echo $porcAprobadas; ?>%"></div></div>
                            <span class="stat-progress-value"><?php echo $aprobadas; ?></span>
                        </div>
                        <div class="stat-progress-item">
                            <span class="stat-progress-label">Rechazadas</span>
                            <div class="progress-bar"><div class="progress-fill danger" style="width:<?php echo $porcRechazadas; ?>%"></div></div>
                            <span class="stat-progress-value"><?php echo $rechazadas; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de solicitudes -->
                <div class="table-container">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equipo</th>
                                <th>Fecha</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <?php if($esAdmin): ?><th>Solicitante</th><?php endif; ?>
                                <?php if($esAdmin): ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($solicitudes) == 0): ?>
                            <tr><td colspan="<?php echo $esAdmin ? '7' : '5'; ?>" style="text-align:center;">No hay solicitudes</td></tr>
                            <?php else: ?>
                                <?php foreach($solicitudes as $sol): ?>
                                <tr>
                                    <td>#<?php echo $sol['id']; ?></td>
                                    <td><?php echo htmlspecialchars($sol['equipo_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($sol['fecha_solicitud'])); ?></td>
                                    <td style="max-width:250px;"><?php echo htmlspecialchars(substr($sol['motivo'], 0, 80)) . (strlen($sol['motivo']) > 80 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $sol['estado'] == 'pendiente' ? 'badge-warning' : ($sol['estado'] == 'aprobada' ? 'badge-success' : 'badge-danger'); 
                                        ?>">
                                            <?php echo ucfirst($sol['estado']); ?>
                                        </span>
                                    </td>
                                    <?php if($esAdmin): ?>
                                    <td><?php echo htmlspecialchars($sol['usuario_nombre'] ?? '-'); ?></td>
                                    <td>
                                        <?php if($sol['estado'] == 'pendiente'): ?>
                                        <div class="action-buttons">
                                            <a href="?cambiar_estado=1&id=<?php echo $sol['id']; ?>&estado=aprobada" class="btn-icon" style="color:var(--success);" onclick="return confirm('¿Aprobar esta solicitud?')"><i class="fas fa-check"></i></a>
                                            <a href="?cambiar_estado=1&id=<?php echo $sol['id']; ?>&estado=rechazada" class="btn-icon delete" onclick="return confirm('¿Rechazar esta solicitud?')"><i class="fas fa-times"></i></a>
                                        </div>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODAL NUEVA SOLICITUD -->
<div class="modal" id="addSolicitudModal">
    <div class="modal-content glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-clipboard-plus"></i> Nueva Solicitud</h3>
            <button class="modal-close" onclick="closeModal('addSolicitudModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="crear_solicitud" value="1">
                <div class="form-group">
                    <label>Equipo <span class="required">*</span></label>
                    <select class="form-control" name="id_equipo" required>
                        <option value="">Seleccionar equipo</option>
                        <?php foreach($equipos as $eq): ?>
                            <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['nombre']); ?> (Stock: <?php echo $eq['stock']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Motivo <span class="required">*</span></label>
                    <textarea class="form-control" name="motivo" rows="4" placeholder="Describe el motivo de la solicitud..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Enviar Solicitud</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById('dropdownMenu').classList.toggle('show');
}
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu') && !e.target.closest('.dropdown-menu')) {
        document.getElementById('dropdownMenu').classList.remove('show');
    }
});
</script>

<style>
.stat-progress { display: flex; flex-direction: column; gap: 15px; }
.stat-progress-item { display: flex; align-items: center; gap: 12px; }
.stat-progress-label { width: 100px; font-size: 0.85rem; font-weight: 500; }
.progress-bar { flex: 1; height: 8px; background: #eef2f6; border-radius: 9999px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 9999px; }
.progress-fill.warning { background: #ffd166; }
.progress-fill.success { background: #06d6a0; }
.progress-fill.danger { background: #ef476f; }
.stat-progress-value { width: 45px; font-weight: 600; text-align: right; }
</style>

</body>
</html>