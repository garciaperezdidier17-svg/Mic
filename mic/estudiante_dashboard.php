<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();

// Obtener datos del estudiante
$stmt = $conn->prepare("SELECT * FROM estudiantes WHERE id_usuario = ?");
$stmt->execute([$usuario['id']]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

// Préstamos activos del estudiante
$stmt = $conn->prepare("SELECT p.*, e.nombre as equipo_nombre FROM prestamos p JOIN equipos e ON p.id_equipo = e.id WHERE p.id_estudiante = ? AND p.estado = 'activo'");
$stmt->execute([$usuario['id']]);
$prestamosActivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Historial de préstamos
$stmt = $conn->prepare("SELECT p.*, e.nombre as equipo_nombre FROM prestamos p JOIN equipos e ON p.id_equipo = e.id WHERE p.id_estudiante = ? ORDER BY p.fecha_prestamo DESC LIMIT 10");
$stmt->execute([$usuario['id']]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Solicitudes pendientes
$stmt = $conn->prepare("SELECT s.*, e.nombre as equipo_nombre FROM solicitudes s JOIN equipos e ON s.id_equipo = e.id WHERE s.id_usuario = ? AND s.estado = 'pendiente'");
$stmt->execute([$usuario['id']]);
$solicitudesPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Multas pendientes
$stmt = $conn->prepare("SELECT SUM(multa) as total_multas FROM prestamos WHERE id_estudiante = ? AND multa > 0");
$stmt->execute([$usuario['id']]);
$totalMultas = $stmt->fetch(PDO::FETCH_ASSOC)['total_multas'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Panel - MIC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app" style="display:block;">
    <?php include 'includes/header.php'; ?>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="content-right">
            <div class="page active">
                <div class="welcome-section">
                    <div class="welcome-content">
                        <h1>¡Hola, <?php echo htmlspecialchars($usuario['nombre']); ?>!</h1>
                        <p>Código: <?php echo $estudiante['codigo_estudiante']; ?> | Grado: <?php echo $estudiante['grado']; ?>° <?php echo $estudiante['grupo']; ?></p>
                    </div>
                </div>

                <!-- Estadísticas del Estudiante -->
                <div class="kpi-grid">
                    <div class="glass-card kpi-card">
                        <div><i class="fas fa-book"></i></div>
                        <div>
                            <div class="kpi-value"><?php echo count($prestamosActivos); ?></div>
                            <div class="kpi-label">Préstamos Activos</div>
                        </div>
                    </div>
                    <div class="glass-card kpi-card">
                        <div><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="kpi-value"><?php echo count($solicitudesPendientes); ?></div>
                            <div class="kpi-label">Solicitudes Pendientes</div>
                        </div>
                    </div>
                    <div class="glass-card kpi-card">
                        <div><i class="fas fa-money-bill"></i></div>
                        <div>
                            <div class="kpi-value" style="color: <?php echo $totalMultas > 0 ? '#ef4444' : '#10b981'; ?>">$$<?php echo number_format($totalMultas); ?></div>
                            <div class="kpi-label">Multas Pendientes</div>
                        </div>
                    </div>
                </div>

                <!-- Préstamos Activos -->
                <div class="glass-card">
                    <h3><i class="fas fa-handshake"></i> Mis Préstamos Activos</h3>
                    <div class="table-container">
                        <table class="premium-table">
                            <thead><tr><th>Equipo</th><th>Fecha Préstamo</th><th>Fecha Devolución</th><th>Estado</th></tr></thead>
                            <tbody>
                                <?php foreach($prestamosActivos as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['equipo_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_prestamo'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_devolucion_esperada'])); ?></td>
                                    <td><span class="badge badge-success">Activo</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($prestamosActivos) == 0): ?>
                                <tr><td colspan="4" style="text-align:center;">No tienes préstamos activos</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Historial -->
                <div class="glass-card">
                    <h3><i class="fas fa-history"></i> Mi Historial</h3>
                    <div class="table-container">
                        <table class="premium-table">
                            <thead><tr><th>Equipo</th><th>Préstamo</th><th>Devolución</th><th>Multa</th></tr></thead>
                            <tbody>
                                <?php foreach($historial as $h): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($h['equipo_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($h['fecha_prestamo'])); ?></td>
                                    <td><?php echo $h['fecha_devolucion_real'] ? date('d/m/Y', strtotime($h['fecha_devolucion_real'])) : '-'; ?></td>
                                    <td><?php echo $h['multa'] ? '$'.number_format($h['multa']) : '-'; ?></td>
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
</body>
</html>