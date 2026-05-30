<?php
require_once 'config/conexion.php';
if (!estaLogueado() || !esAdmin()) { header('Location: index.php'); exit; }

// Configuración del sistema
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_config'])) {
    foreach($_POST['config'] as $clave => $valor) {
        $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $stmt->execute([$valor, $clave]);
    }
    $mensaje = "Configuración guardada";
}

// Respaldar BD
if (isset($_GET['backup'])) {
    $backupFile = 'backup_mic_' . date('Y-m-d_H-i-s') . '.sql';
    $command = "mysqldump --user=root --password= --host=localhost mic > $backupFile";
    exec($command);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $backupFile . '"');
    readfile($backupFile);
    unlink($backupFile);
    exit;
}

// Limpiar logs
if (isset($_GET['limpiar_logs'])) {
    $conn->query("DELETE FROM notificaciones WHERE fecha < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $conn->query("DELETE FROM movimientos WHERE fecha < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $mensaje = "Logs antiguos eliminados";
}

$config = $conn->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - MIC</title>
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
                        <h2><i class="fas fa-cogs"></i> Panel de Administración</h2>
                    </div>
                </div>

                <?php if(isset($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <div class="kpi-grid">
                    <div class="glass-card">
                        <h3><i class="fas fa-database"></i> Respaldos</h3>
                        <p>Generar copia de seguridad de la base de datos</p>
                        <a href="?backup=1" class="btn btn-primary" style="margin-top: 15px;"><i class="fas fa-download"></i> Descargar Backup</a>
                    </div>
                    <div class="glass-card">
                        <h3><i class="fas fa-chart-line"></i> Estadísticas</h3>
                        <p>Usuarios: <?php echo $conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(); ?></p>
                        <p>Equipos: <?php echo $conn->query("SELECT COUNT(*) FROM equipos WHERE activo = 1")->fetchColumn(); ?></p>
                        <p>Préstamos: <?php echo $conn->query("SELECT COUNT(*) FROM prestamos")->fetchColumn(); ?></p>
                    </div>
                    <div class="glass-card">
                        <h3><i class="fas fa-trash-alt"></i> Mantenimiento</h3>
                        <p>Limpiar logs antiguos</p>
                        <a href="?limpiar_logs=1" class="btn btn-outline" style="margin-top: 15px;" onclick="return confirm('¿Limpiar logs antiguos?')"><i class="fas fa-broom"></i> Limpiar Logs</a>
                    </div>
                </div>

                <div class="glass-card">
                    <h3><i class="fas fa-sliders-h"></i> Configuración del Sistema</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Días máximos préstamo docente</label>
                                <input type="number" name="config[dias_prestamo_docente]" class="form-control" value="<?php echo $config['dias_prestamo_docente']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Días máximos préstamo estudiante</label>
                                <input type="number" name="config[dias_prestamo_estudiante]" class="form-control" value="<?php echo $config['dias_prestamo_estudiante']; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Máx equipos por docente</label>
                                <input type="number" name="config[max_equipos_docente]" class="form-control" value="<?php echo $config['max_equipos_docente']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Máx equipos por estudiante</label>
                                <input type="number" name="config[max_equipos_estudiante]" class="form-control" value="<?php echo $config['max_equipos_estudiante']; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Multa por día de retraso (COP)</label>
                            <input type="number" name="config[multa_dia_retraso]" class="form-control" value="<?php echo $config['multa_dia_retraso']; ?>">
                        </div>
                        <button type="submit" name="guardar_config" class="btn btn-primary">Guardar Configuración</button>
                    </form>
                </div>

                <div class="glass-card">
                    <h3><i class="fas fa-chart-simple"></i> Información del Sistema</h3>
                    <div class="info-row"><label>Versión</label><span>MIC v3.0.0</span></div>
                    <div class="info-row"><label>PHP</label><span><?php echo phpversion(); ?></span></div>
                    <div class="info-row"><label>MySQL</label><span><?php echo $conn->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span></div>
                    <div class="info-row"><label>Último Backup</label><span><?php echo date('d/m/Y H:i:s'); ?></span></div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>