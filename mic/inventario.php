<?php
require_once 'config/conexion.php';

if (!estaLogueado()) {
    header('Location: index.php');
    exit;
}

$usuario = obtenerUsuarioActual();

// Procesar filtro de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT e.*, s.nombre as sede_nombre, t.nombre_tipo as tipo_nombre 
        FROM equipos e 
        LEFT JOIN sedes s ON e.id_sede = s.id 
        LEFT JOIN tipo_equipo t ON e.id_tipo = t.id 
        WHERE e.activo = 1";

if ($search != '') {
    $sql .= " AND (e.codigo_interno LIKE '%$search%' OR e.nombre LIKE '%$search%' OR e.marca LIKE '%$search%')";
}

$sql .= " ORDER BY e.nombre ASC";
$equipos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$total = count($equipos);
$disponibles = count(array_filter($equipos, fn($e) => $e['estado'] == 'disponible'));
$mantenimiento = count(array_filter($equipos, fn($e) => $e['estado'] == 'mantenimiento'));
$stockBajo = count(array_filter($equipos, fn($e) => $e['stock'] < $e['stock_minimo']));

// Obtener sedes para el formulario
$sedes = $conn->query("SELECT id, nombre FROM sedes")->fetchAll(PDO::FETCH_ASSOC);
$tiposEquipo = $conn->query("SELECT id, nombre_tipo FROM tipo_equipo")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $conn->query("SELECT id, nombre FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

// Mensajes de sesión
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - MIC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?php echo esAdmin() ? 'user-admin' : ''; ?>">

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
                <a href="inventario.php" class="nav-item active"><i class="fas fa-boxes"></i><span class="nav-text">Inventario</span></a>
                <a href="solicitudes.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span></a>
                <a href="prestamos.php" class="nav-item"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
                <?php if(esAdmin()): ?>
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
                        <h2><i class="fas fa-boxes"></i> Inventario</h2>
                        <p>Gestión completa del inventario tecnológico</p>
                    </div>
                    <div class="page-actions">
                        <form method="GET" style="display:flex; gap:10px;">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Buscar equipos..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Buscar</button>
                        </form>
                        <?php if(esAdmin()): ?>
                        <button class="btn btn-primary" onclick="openModal('addEquipoModal')">
                            <i class="fas fa-plus"></i> Agregar Equipo
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if($mensaje): ?>
                <div style="background:#d4edda; color:#155724; padding:12px; border-radius:12px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
                <?php endif; ?>

                <div class="inventory-stats">
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Total Equipos</span>
                        <div class="stat-mini-value"><?php echo $total; ?></div>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Disponibles</span>
                        <div class="stat-mini-value" style="color:var(--success);"><?php echo $disponibles; ?></div>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Mantenimiento</span>
                        <div class="stat-mini-value" style="color:var(--warning);"><?php echo $mantenimiento; ?></div>
                    </div>
                    <div class="stat-mini-card">
                        <span class="stat-mini-label">Stock Bajo</span>
                        <div class="stat-mini-value" style="color:var(--danger);"><?php echo $stockBajo; ?></div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="premium-table">
                        <thead>
                            <tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Sede</th><th>Stock</th><th>Estado</th><?php if(esAdmin()): ?><th>Acciones</th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php if(count($equipos) == 0): ?>
                            <tr><td colspan="<?php echo esAdmin() ? '7' : '6'; ?>" style="text-align:center;">No hay equipos registrados</td></tr>
                            <?php else: ?>
                                <?php foreach($equipos as $eq): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($eq['codigo_interno']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($eq['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($eq['tipo_nombre'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($eq['sede_nombre'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $eq['stock'] < $eq['stock_minimo'] ? 'badge-danger' : 'badge-success'; ?>">
                                            <?php echo $eq['stock']; ?> uds
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $eq['estado'] == 'disponible' ? 'success' : ($eq['estado'] == 'mantenimiento' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($eq['estado']); ?>
                                        </span>
                                    </td>
                                    <?php if(esAdmin()): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="actions/editar_equipo.php?id=<?php echo $eq['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                            <a href="actions/eliminar_equipo.php?id=<?php echo $eq['id']; ?>" class="btn-icon delete" onclick="return confirm('¿Eliminar este equipo?')"><i class="fas fa-trash-alt"></i></a>
                                        </div>
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

<!-- MODAL AGREGAR EQUIPO -->
<div class="modal" id="addEquipoModal">
    <div class="modal-content glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Agregar Equipo</h3>
            <button class="modal-close" onclick="closeModal('addEquipoModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="actions/crear_equipo.php">
                <div class="form-row">
                    <div class="form-group">
                        <label>Código Interno <span class="required">*</span></label>
                        <input type="text" class="form-control" name="codigo_interno" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select class="form-control" name="id_tipo">
                            <option value="">Seleccionar</option>
                            <?php foreach($tiposEquipo as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre_tipo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Categoría</label>
                        <select class="form-control" name="id_categoria">
                            <option value="">Seleccionar</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" class="form-control" name="marca">
                    </div>
                    <div class="form-group">
                        <label>Modelo</label>
                        <input type="text" class="form-control" name="modelo">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Sede</label>
                        <select class="form-control" name="id_sede">
                            <option value="">Seleccionar</option>
                            <?php foreach($sedes as $sede): ?>
                                <option value="<?php echo $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control" name="estado">
                            <option value="disponible">Disponible</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="dañado">Dañado</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock <span class="required">*</span></label>
                        <input type="number" class="form-control" name="stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Mínimo</label>
                        <input type="number" class="form-control" name="stock_minimo" min="0" value="5">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar Equipo</button>
            </form>
        </div>
    </div>
</div>

<!-- En la tabla de inventario, agregar columna QR -->
<th>Código QR</th>
<td>
    <button class="btn-icon" onclick="verQR(<?php echo $eq['id']; ?>, '<?php echo htmlspecialchars($eq['codigo_interno']); ?>')">
        <i class="fas fa-qrcode"></i>
    </button>
</td>

<!-- Modal QR -->
<div class="modal" id="qrModal">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <div class="modal-header">
            <h3><i class="fas fa-qrcode"></i> Código QR del Equipo</h3>
            <button class="modal-close" onclick="closeModal('qrModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="qrCodeContainer" style="display: flex; justify-content: center; margin-bottom: 20px;"></div>
            <p><strong id="qrEquipoNombre"></strong></p>
            <p>Código: <span id="qrEquipoCodigo"></span></p>
            <button class="btn btn-primary" onclick="imprimirQR()"><i class="fas fa-print"></i> Imprimir QR</button>
            <button class="btn btn-outline" onclick="descargarQR()"><i class="fas fa-download"></i> Descargar</button>
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

</body>
</html>