<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();
$esAdmin = esAdmin();

// Exportar a CSV (funciona sin ninguna librería)
if (isset($_GET['exportar'])) {
    $tabla = $_GET['tabla'];
    $filename = "mic_{$tabla}_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // Agregar BOM para UTF-8 en Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if($tabla == 'equipos') {
        $stmt = $conn->query("SELECT e.codigo_interno, e.nombre, e.marca, e.modelo, e.stock, e.estado, s.nombre as sede 
                              FROM equipos e 
                              LEFT JOIN sedes s ON e.id_sede = s.id 
                              WHERE e.activo = 1");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($datos) > 0) {
            fputcsv($output, ['Código', 'Nombre', 'Marca', 'Modelo', 'Stock', 'Estado', 'Sede']);
            foreach($datos as $row) {
                fputcsv($output, $row);
            }
        }
    }
    elseif($tabla == 'prestamos') {
        $stmt = $conn->query("SELECT p.id, e.nombre as equipo, u.nombre as estudiante, p.fecha_prestamo, p.fecha_devolucion_esperada, p.estado, p.multa 
                              FROM prestamos p 
                              JOIN equipos e ON p.id_equipo = e.id 
                              JOIN usuarios u ON p.id_estudiante = u.id");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($datos) > 0) {
            fputcsv($output, ['ID', 'Equipo', 'Estudiante', 'Fecha Préstamo', 'Fecha Devolución', 'Estado', 'Multa']);
            foreach($datos as $row) {
                fputcsv($output, $row);
            }
        }
    }
    elseif($tabla == 'usuarios') {
        $stmt = $conn->query("SELECT u.id, u.nombre, u.email, u.telefono, r.nombre as rol, u.activo 
                              FROM usuarios u 
                              JOIN roles r ON u.rol_id = r.id");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($datos) > 0) {
            fputcsv($output, ['ID', 'Nombre', 'Email', 'Teléfono', 'Rol', 'Activo']);
            foreach($datos as $row) {
                fputcsv($output, $row);
            }
        }
    }
    elseif($tabla == 'solicitudes') {
        $stmt = $conn->query("SELECT s.id, e.nombre as equipo, u.nombre as solicitante, s.fecha_solicitud, s.motivo, s.estado 
                              FROM solicitudes s 
                              JOIN equipos e ON s.id_equipo = e.id 
                              JOIN usuarios u ON s.id_usuario = u.id 
                              ORDER BY s.fecha_solicitud DESC");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($datos) > 0) {
            fputcsv($output, ['ID', 'Equipo', 'Solicitante', 'Fecha', 'Motivo', 'Estado']);
            foreach($datos as $row) {
                fputcsv($output, $row);
            }
        }
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - MIC</title>
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
                <a href="prestamos.php" class="nav-item"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
                <a href="notificaciones.php" class="nav-item"><i class="fas fa-bell"></i><span class="nav-text">Notificaciones</span></a>
                <a href="mantenimiento.php" class="nav-item"><i class="fas fa-tools"></i><span class="nav-text">Mantenimiento</span></a>
                <a href="reportes.php" class="nav-item active"><i class="fas fa-chart-bar"></i><span class="nav-text">Reportes</span></a>
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
                        <h2><i class="fas fa-chart-bar"></i> Reportes</h2>
                        <p>Exporta los datos del sistema a archivos CSV (Excel)</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                    <div class="glass-card" style="text-align: center; cursor: pointer; padding: 30px;" onclick="window.location.href='?exportar=1&tabla=equipos'">
                        <i class="fas fa-boxes" style="font-size: 3rem; color: var(--primary);"></i>
                        <h3 style="margin: 15px 0;">Inventario</h3>
                        <p style="color: var(--gray);">Exportar todos los equipos a CSV</p>
                        <button class="btn btn-primary" style="margin-top: 15px;">📥 Descargar</button>
                    </div>
                    
                    <div class="glass-card" style="text-align: center; cursor: pointer; padding: 30px;" onclick="window.location.href='?exportar=1&tabla=prestamos'">
                        <i class="fas fa-handshake" style="font-size: 3rem; color: var(--primary);"></i>
                        <h3 style="margin: 15px 0;">Préstamos</h3>
                        <p style="color: var(--gray);">Exportar historial de préstamos</p>
                        <button class="btn btn-primary" style="margin-top: 15px;">📥 Descargar</button>
                    </div>
                    
                    <div class="glass-card" style="text-align: center; cursor: pointer; padding: 30px;" onclick="window.location.href='?exportar=1&tabla=usuarios'">
                        <i class="fas fa-users" style="font-size: 3rem; color: var(--primary);"></i>
                        <h3 style="margin: 15px 0;">Usuarios</h3>
                        <p style="color: var(--gray);">Exportar lista de usuarios</p>
                        <button class="btn btn-primary" style="margin-top: 15px;">📥 Descargar</button>
                    </div>
                    
                    <div class="glass-card" style="text-align: center; cursor: pointer; padding: 30px;" onclick="window.location.href='?exportar=1&tabla=solicitudes'">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--primary);"></i>
                        <h3 style="margin: 15px 0;">Solicitudes</h3>
                        <p style="color: var(--gray);">Exportar solicitudes de equipos</p>
                        <button class="btn btn-primary" style="margin-top: 15px;">📥 Descargar</button>
                    </div>
                </div>
                
                <div class="glass-card" style="margin-top: 30px; text-align: center;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--primary);"></i>
                    <p style="margin-top: 10px;">Los archivos se descargan en formato <strong>CSV</strong>, compatible con Excel y Google Sheets.</p>
                    <small style="color: var(--gray);">Para abrir en Excel: Abrir Excel → Datos → Desde texto/CSV</small>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>