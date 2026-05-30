<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();

// Obtener notificaciones
$stmt = $conn->prepare("SELECT * FROM notificaciones WHERE id_usuario = ? ORDER BY fecha DESC LIMIT 20");
$stmt->execute([$usuario['id']]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar como leídas
if (isset($_GET['marcar_leida'])) {
    $stmt = $conn->prepare("UPDATE notificaciones SET leido = 1, fecha_leido = NOW() WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header('Location: notificaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones - MIC</title>
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
                        <h2><i class="fas fa-bell"></i> Notificaciones</h2>
                    </div>
                </div>
                <div class="glass-card">
                    <?php if(count($notificaciones) == 0): ?>
                        <div style="text-align:center; padding: 60px;">
                            <i class="fas fa-bell-slash" style="font-size: 4rem; color: var(--gray);"></i>
                            <p style="margin-top: 20px;">No tienes notificaciones</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($notificaciones as $notif): ?>
                        <div class="notificacion-item <?php echo $notif['leido'] ? 'leido' : 'no-leido'; ?>" style="padding: 15px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4><i class="fas fa-<?php echo $notif['tipo'] == 'alerta' ? 'exclamation-triangle' : ($notif['tipo'] == 'exito' ? 'check-circle' : 'info-circle'); ?>" style="color: <?php echo $notif['tipo'] == 'alerta' ? '#f59e0b' : ($notif['tipo'] == 'exito' ? '#10b981' : '#3b82f6'); ?>;"></i> <?php echo htmlspecialchars($notif['titulo']); ?></h4>
                                <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                                <small><?php echo date('d/m/Y H:i', strtotime($notif['fecha'])); ?></small>
                            </div>
                            <?php if(!$notif['leido']): ?>
                            <a href="?marcar_leida=1&id=<?php echo $notif['id']; ?>" class="btn btn-sm">Marcar como leída</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>