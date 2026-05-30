<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();
$esAdmin = esAdmin();

// Procesar nuevo préstamo (solo admin)
if ($esAdmin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_prestamo'])) {
    $id_equipo = $_POST['id_equipo'];
    $id_estudiante = $_POST['id_estudiante'];
    $fecha_prestamo = $_POST['fecha_prestamo'];
    $fecha_devolucion = $_POST['fecha_devolucion'];
    $hora = date('H:i:s');
    
    $conn->beginTransaction();
    
    // Crear solicitud primero
    $stmt = $conn->prepare("INSERT INTO solicitudes (id_usuario, id_equipo, fecha_solicitud, hora_solicitud, motivo, estado, fecha_atencion) 
                            VALUES (?, ?, ?, ?, 'Préstamo directo', 'aprobada', NOW())");
    $stmt->execute([$usuario['id'], $id_equipo, $fecha_prestamo, $hora]);
    $id_solicitud = $conn->lastInsertId();
    
    // Crear préstamo
    $stmt = $conn->prepare("INSERT INTO prestamos (id_solicitud, id_equipo, id_estudiante, fecha_prestamo, fecha_devolucion_esperada, hora_prestamo, estado) 
                            VALUES (?, ?, ?, ?, ?, ?, 'activo')");
    $stmt->execute([$id_solicitud, $id_equipo, $id_estudiante, $fecha_prestamo, $fecha_devolucion, $hora]);
    
    // Actualizar estado del equipo
    $stmt = $conn->prepare("UPDATE equipos SET estado = 'prestado' WHERE id = ?");
    $stmt->execute([$id_equipo]);
    
    $conn->commit();
    
    $_SESSION['mensaje'] = 'Préstamo registrado correctamente';
    header('Location: prestamos.php');
    exit;
}

// Procesar devolución (solo admin)
if ($esAdmin && isset($_GET['devolver'])) {
    $id = $_GET['id'];
    $fecha_hoy = date('Y-m-d');
    $hora = date('H:i:s');
    
    // Obtener el equipo
    $stmt = $conn->prepare("SELECT id_equipo FROM prestamos WHERE id = ?");
    $stmt->execute([$id]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar préstamo
    $stmt = $conn->prepare("UPDATE prestamos SET fecha_devolucion_real = ?, hora_devolucion = ?, estado = 'devuelto' WHERE id = ?");
    $stmt->execute([$fecha_hoy, $hora, $id]);
    
    // Actualizar estado del equipo
    $stmt = $conn->prepare("UPDATE equipos SET estado = 'disponible' WHERE id = ?");
    $stmt->execute([$prestamo['id_equipo']]);
    
    $_SESSION['mensaje'] = 'Devolución registrada correctamente';
    header('Location: prestamos.php');
    exit;
}

// Obtener equipos disponibles
$equipos = $conn->query("SELECT id, nombre, stock FROM equipos WHERE estado = 'disponible' AND stock > 0 AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener estudiantes
$estudiantes = $conn->query("SELECT u.id, u.nombre, e.codigo_estudiante 
                              FROM usuarios u 
                              JOIN estudiantes e ON u.id = e.id_usuario 
                              WHERE u.activo = 1 AND u.rol_id = (SELECT id FROM roles WHERE nombre = 'estudiante')
                              ORDER BY u.nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener préstamos
if ($esAdmin) {
    $sql = "SELECT p.*, e.nombre as equipo_nombre, u.nombre as estudiante_nombre 
            FROM prestamos p 
            JOIN equipos e ON p.id_equipo = e.id 
            JOIN usuarios u ON p.id_estudiante = u.id 
            ORDER BY p.fecha_prestamo DESC";
    $prestamos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $prestamos = [];
}

$activos = count(array_filter($prestamos, function($p) { return $p['estado'] == 'activo'; }));
$devueltos = count(array_filter($prestamos, function($p) { return $p['estado'] == 'devuelto'; }));

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos - MIC</title>
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
            <div class="sidebar-header"><div class="sidebar-logo"><i class="fas fa-cubes"></i> Navegación</div></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span></a>
                <a href="inventario.php" class="nav-item"><i class="fas fa-boxes"></i><span class="nav-text">Inventario</span></a>
                <a href="solicitudes.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span></a>
                <a href="prestamos.php" class="nav-item active"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
                <a href="notificaciones.php" class="nav-item"><i class="fas fa-bell"></i><span class="nav-text">Notificaciones</span></a>
                <a href="mantenimiento.php" class="nav-item"><i class="fas fa-tools"></i><span class="nav-text">Mantenimiento</span></a>
                <a href="reportes.php" class="nav-item"><i class="fas fa-chart-bar"></i><span class="nav-text">Reportes</span></a>
                <?php if($esAdmin): ?>
                <a href="usuarios.php" class="nav-item admin-only"><i class="fas fa-users"></i><span class="nav-text">Usuarios</span></a>
                <a href="admin_panel.php" class="nav-item admin-only"><i class="fas fa-cogs"></i><span class="nav-text">Admin</span></a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="system-info"><i class="fas fa-circle"></i> Sistema en línea</div>
                <div class="system-version">v3.0.0 &copy; MIC</div>
            </div>
        </aside>

        <main class="content-right">
            <div class="page active">
                <div class="page-header">
                    <div class="page-title">
                        <h2><i class="fas fa-handshake"></i> Préstamos</h2>
                        <p>Control de préstamos de equipos</p>
                    </div>
                    <?php if($esAdmin): ?>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openModal('addPrestamoModal')">
                            <i class="fas fa-plus"></i> Nuevo Préstamo
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>

                <div class="inventory-stats" style="margin-bottom: 25px;">
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Préstamos Activos</span>
                        <div class="stat-mini-value" style="color:var(--success);"><?php echo $activos; ?></div>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Devueltos</span>
                        <div class="stat-mini-value"><?php echo $devueltos; ?></div>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Total</span>
                        <div class="stat-mini-value"><?php echo count($prestamos); ?></div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Equipo</th>
                                <th>Estudiante</th>
                                <th>Fecha Préstamo</th>
                                <th>Devolución Esperada</th>
                                <th>Estado</th>
                                <?php if($esAdmin): ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($prestamos) == 0): ?>
                            <tr>
                                <td colspan="<?php echo $esAdmin ? '7' : '6'; ?>" style="text-align:center;">No hay préstamos registrados</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($prestamos as $p): ?>
                                <tr>
                                    <td>#<?php echo $p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['equipo_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($p['estudiante_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_prestamo'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_devolucion_esperada'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $p['estado'] == 'activo' ? 'badge-success' : 'badge-info'; ?>">
                                            <?php echo ucfirst($p['estado']); ?>
                                        </span>
                                    </td>
                                    <?php if($esAdmin): ?>
                                    <td>
                                        <?php if($p['estado'] == 'activo'): ?>
                                        <a href="?devolver=1&id=<?php echo $p['id']; ?>" class="btn-icon" style="color:var(--success);" onclick="return confirm('¿Registrar devolución de este equipo?')">
                                            <i class="fas fa-undo"></i> Devolver
                                        </a>
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

<!-- MODAL NUEVO PRÉSTAMO -->
<?php if($esAdmin): ?>
<div class="modal" id="addPrestamoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-handshake"></i> Nuevo Préstamo</h3>
            <button class="modal-close" onclick="closeModal('addPrestamoModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="crear_prestamo" value="1">
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
                    <label>Estudiante <span class="required">*</span></label>
                    <select class="form-control" name="id_estudiante" required>
                        <option value="">Seleccionar estudiante</option>
                        <?php foreach($estudiantes as $est): ?>
                            <option value="<?php echo $est['id']; ?>"><?php echo htmlspecialchars($est['nombre']); ?> (<?php echo $est['codigo_estudiante']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha Préstamo</label>
                        <input type="date" class="form-control" name="fecha_prestamo" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Devolución</label>
                        <input type="date" class="form-control" name="fecha_devolucion" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Registrar Préstamo</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/main.js"></script>
</body>
</html>