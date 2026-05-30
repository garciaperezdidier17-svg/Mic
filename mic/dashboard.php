<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();
$esAdmin = esAdmin();

// Estadísticas para las gráficas
$stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM equipos WHERE activo=1) as total,
    (SELECT COUNT(*) FROM equipos WHERE estado='disponible') as disponibles,
    (SELECT COUNT(*) FROM equipos WHERE estado='prestado') as prestados,
    (SELECT COUNT(*) FROM equipos WHERE estado='mantenimiento') as mantenimiento,
    (SELECT COUNT(*) FROM solicitudes WHERE estado='pendiente') as solicitudes,
    (SELECT COUNT(*) FROM prestamos WHERE estado='activo') as prestamos_activos,
    (SELECT COUNT(*) FROM equipos WHERE stock<stock_minimo) as stock_bajo
")->fetch(PDO::FETCH_ASSOC);

// Datos para gráfica de equipos por sede
$sedes = $conn->query("SELECT s.nombre, COUNT(e.id) as total 
                       FROM sedes s 
                       LEFT JOIN equipos e ON s.id = e.id_sede AND e.activo=1 
                       GROUP BY s.id")->fetchAll(PDO::FETCH_ASSOC);

// Datos para gráfica de préstamos por mes (últimos 6 meses)
$prestamosMensuales = [];
for($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $nombreMes = date('M', strtotime("-$i months"));
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM prestamos WHERE DATE_FORMAT(fecha_prestamo, '%Y-%m') = ?");
    $stmt->execute([$mes]);
    $prestamosMensuales[] = ['mes' => $nombreMes, 'total' => $stmt->fetch(PDO::FETCH_ASSOC)['total']];
}

// Alertas de stock bajo
$alertasStock = $conn->query("SELECT e.nombre, e.stock, e.stock_minimo, s.nombre as sede 
                              FROM equipos e 
                              JOIN sedes s ON e.id_sede = s.id 
                              WHERE e.stock < e.stock_minimo AND e.activo = 1 
                              LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MIC</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chart-container { height: 300px; position: relative; margin-bottom: 20px; }
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 32px; }
        @media (max-width: 768px) { .charts-grid { grid-template-columns: 1fr; } }
    </style>
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
        <a href="notificaciones.php" class="dropdown-item"><i class="fas fa-bell"></i> Notificaciones</a>
        <div class="dropdown-divider"></div>
        <a href="actions/cerrar_sesion.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>

    <div class="main-layout">
        <aside class="sidebar-left" id="sidebar">
            <div class="sidebar-header"><div class="sidebar-logo"><i class="fas fa-cubes"></i> Navegación</div></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span></a>
                <a href="inventario.php" class="nav-item"><i class="fas fa-boxes"></i><span class="nav-text">Inventario</span></a>
                <a href="solicitudes.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span></a>
                <a href="prestamos.php" class="nav-item"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
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
                <!-- WELCOME SECTION -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <div class="welcome-badge">🎓 Sistema Activo</div>
                        <h1>¡Bienvenido, <?php echo explode(' ', $usuario['nombre'])[0]; ?>!</h1>
                        <p>Panel de control del inventario tecnológico</p>
                    </div>
                    <div class="welcome-stats">
                        <div class="stat-card-mini"><i class="fas fa-calendar-day"></i> <span id="fechaActual"></span></div>
                    </div>
                </div>

                <!-- KPI CARDS -->
                <div class="kpi-grid">
                    <div class="glass-card kpi-card animate-fade-up">
                        <div class="kpi-icon blue-gradient"><i class="fas fa-laptop"></i></div>
                        <div class="kpi-content">
                            <div class="kpi-value"><?php echo $stats['total']; ?></div>
                            <div class="kpi-label">Total Equipos</div>
                        </div>
                    </div>
                    <div class="glass-card kpi-card animate-fade-up delay-1">
                        <div class="kpi-icon green-gradient"><i class="fas fa-check-circle"></i></div>
                        <div class="kpi-content">
                            <div class="kpi-value"><?php echo $stats['disponibles']; ?></div>
                            <div class="kpi-label">Disponibles</div>
                        </div>
                    </div>
                    <div class="glass-card kpi-card animate-fade-up delay-2">
                        <div class="kpi-icon yellow-gradient"><i class="fas fa-clock"></i></div>
                        <div class="kpi-content">
                            <div class="kpi-value"><?php echo $stats['solicitudes']; ?></div>
                            <div class="kpi-label">Solicitudes Pendientes</div>
                        </div>
                    </div>
                    <div class="glass-card kpi-card animate-fade-up delay-3">
                        <div class="kpi-icon red-gradient"><i class="fas fa-handshake"></i></div>
                        <div class="kpi-content">
                            <div class="kpi-value"><?php echo $stats['prestamos_activos']; ?></div>
                            <div class="kpi-label">Préstamos Activos</div>
                        </div>
                    </div>
                </div>

                <!-- GRÁFICAS -->
                <div class="charts-grid">
                    <!-- Gráfica 1: Equipos por Estado (Doughnut) -->
                    <div class="glass-card chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-pie"></i> Equipos por Estado</h3>
                            <p>Distribución actual del inventario</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="estadoChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfica 2: Equipos por Sede (Barra) -->
                    <div class="glass-card chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-bar"></i> Equipos por Sede</h3>
                            <p>Cantidad de equipos por ubicación</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="sedeChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfica 3: Préstamos por Mes (Línea) -->
                    <div class="glass-card chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-line"></i> Préstamos por Mes</h3>
                            <p>Últimos 6 meses</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="prestamosChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfica 4: Stock Crítico (Barra Horizontal) -->
                    <div class="glass-card chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Stock Crítico</h3>
                            <p>Equipos con stock bajo</p>
                        </div>
                        <div class="chart-container">
                            <canvas id="stockChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- ALERTAS DE STOCK -->
                <?php if(count($alertasStock) > 0): ?>
                <div class="glass-card">
                    <h3><i class="fas fa-bell" style="color:var(--danger);"></i> Alertas de Stock Bajo</h3>
                    <div class="alert-list">
                        <?php foreach($alertasStock as $a): ?>
                        <div class="alert-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?php echo htmlspecialchars($a['nombre']); ?> (<?php echo $a['sede']; ?>)</span>
                            <span class="alert-badge">Stock: <?php echo $a['stock']; ?> / Mín: <?php echo $a['stock_minimo']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ACCIONES RÁPIDAS -->
                <div class="glass-card">
                    <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                    <div class="quick-actions">
                        <a href="inventario.php" class="quick-action-btn"><i class="fas fa-boxes"></i> Ver Inventario</a>
                        <a href="solicitudes.php" class="quick-action-btn"><i class="fas fa-clipboard-list"></i> Nueva Solicitud</a>
                        <?php if($esAdmin): ?>
                        <a href="inventario.php?action=nuevo" class="quick-action-btn admin-only" onclick="openModal('addEquipoModal')"><i class="fas fa-plus-circle"></i> Agregar Equipo</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
// Mostrar fecha actual
const hoy = new Date();
const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
const fechaElemento = document.getElementById('fechaActual');
if(fechaElemento) fechaElemento.textContent = hoy.toLocaleDateString('es-ES', opciones);

// ========== GRÁFICA 1: Equipos por Estado ==========
const ctxEstado = document.getElementById('estadoChart');
if(ctxEstado) {
    new Chart(ctxEstado, {
        type: 'doughnut',
        data: {
            labels: ['Disponibles', 'Prestados', 'Mantenimiento', 'Dañados'],
            datasets: [{
                data: [<?php echo $stats['disponibles']; ?>, <?php echo $stats['prestados']; ?>, <?php echo $stats['mantenimiento']; ?>, 0],
                backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 } } },
                tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': ' + ctx.raw + ' equipos'; } } }
            },
            cutout: '60%'
        }
    });
}

// ========== GRÁFICA 2: Equipos por Sede ==========
const ctxSede = document.getElementById('sedeChart');
if(ctxSede) {
    const sedesLabels = <?php echo json_encode(array_column($sedes, 'nombre')); ?>;
    const sedesData = <?php echo json_encode(array_column($sedes, 'total')); ?>;
    new Chart(ctxSede, {
        type: 'bar',
        data: {
            labels: sedesLabels,
            datasets: [{
                label: 'Cantidad de Equipos',
                data: sedesData,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderRadius: 10,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Cantidad' } } }
        }
    });
}

// ========== GRÁFICA 3: Préstamos por Mes ==========
const ctxPres = document.getElementById('prestamosChart');
if(ctxPres) {
    const mesesLabels = <?php echo json_encode(array_column($prestamosMensuales, 'mes')); ?>;
    const mesesData = <?php echo json_encode(array_column($prestamosMensuales, 'total')); ?>;
    new Chart(ctxPres, {
        type: 'line',
        data: {
            labels: mesesLabels,
            datasets: [{
                label: 'Préstamos Realizados',
                data: mesesData,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#8b5cf6',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Número de Préstamos' } } }
        }
    });
}

// ========== GRÁFICA 4: Stock Crítico ==========
const ctxStock = document.getElementById('stockChart');
if(ctxStock) {
    // Obtener equipos con stock bajo desde PHP
    const stockBajo = <?php 
        $bajo = $conn->query("SELECT nombre, stock, stock_minimo FROM equipos WHERE stock < stock_minimo AND activo = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($bajo);
    ?>;
    
    const stockLabels = stockBajo.map(item => item.nombre);
    const stockActual = stockBajo.map(item => item.stock);
    const stockMinimo = stockBajo.map(item => item.stock_minimo);
    
    new Chart(ctxStock, {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [
                {
                    label: 'Stock Actual',
                    data: stockActual,
                    backgroundColor: '#ef4444',
                    borderRadius: 8
                },
                {
                    label: 'Stock Mínimo',
                    data: stockMinimo,
                    backgroundColor: '#f59e0b',
                    borderRadius: 8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { position: 'top' } },
            scales: { x: { title: { display: true, text: 'Cantidad' } } }
        }
    });
}
</script>

<style>
.welcome-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 9999px; font-size: 0.8rem; margin-bottom: 12px; }
.welcome-stats { display: flex; gap: 12px; }
.stat-card-mini { background: rgba(255,255,255,0.15); padding: 10px 18px; border-radius: 9999px; display: flex; align-items: center; gap: 10px; color: white; }
.chart-header { margin-bottom: 15px; border-bottom: 1px solid var(--gray-light); padding-bottom: 10px; }
.chart-header h3 { display: flex; align-items: center; gap: 8px; font-size: 1rem; }
.chart-header p { font-size: 0.75rem; color: var(--gray); margin-top: 5px; }
</style>
</body>
</html>