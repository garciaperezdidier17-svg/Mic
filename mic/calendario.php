<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

// Obtener préstamos para el calendario
$prestamos = $conn->query("SELECT p.*, e.nombre as equipo, u.nombre as estudiante 
                           FROM prestamos p 
                           JOIN equipos e ON p.id_equipo = e.id 
                           JOIN usuarios u ON p.id_estudiante = u.id 
                           WHERE p.estado = 'activo'
                           ORDER BY p.fecha_devolucion_esperada ASC")->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por fecha
$calendario = [];
foreach($prestamos as $p) {
    $fecha = $p['fecha_devolucion_esperada'];
    if (!isset($calendario[$fecha])) {
        $calendario[$fecha] = [];
    }
    $calendario[$fecha][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario de Préstamos - MIC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css">
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
                        <h2><i class="fas fa-calendar-alt"></i> Calendario de Préstamos</h2>
                    </div>
                </div>

                <div class="glass-card">
                    <div id="calendar"></div>
                </div>

                <div class="glass-card" style="margin-top: 20px;">
                    <h3>Próximas Devoluciones</h3>
                    <div class="table-container">
                        <table class="premium-table">
                            <thead><tr><th>Fecha</th><th>Equipo</th><th>Estudiante</th><th>Días Restantes</th></tr></thead>
                            <tbody>
                                <?php 
                                $hoy = new DateTime();
                                foreach($prestamos as $p): 
                                    $fechaDev = new DateTime($p['fecha_devolucion_esperada']);
                                    $dias = $hoy->diff($fechaDev)->days;
                                    $color = $dias <= 2 ? '#ef4444' : ($dias <= 5 ? '#f59e0b' : '#10b981');
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($p['fecha_devolucion_esperada'])); ?></td>
                                    <td><?php echo htmlspecialchars($p['equipo']); ?></td>
                                    <td><?php echo htmlspecialchars($p['estudiante']); ?></td>
                                    <td style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo $dias; ?> días</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/fullcalendar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/locale/es.js"></script>
<script>
$(document).ready(function() {
    $('#calendar').fullCalendar({
        header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
        defaultView: 'month',
        locale: 'es',
        events: [
            <?php foreach($prestamos as $p): ?>
            {
                title: '🔁 Devolución: <?php echo addslashes($p['equipo']); ?>',
                start: '<?php echo $p['fecha_devolucion_esperada']; ?>',
                color: '#3b82f6',
                textColor: 'white'
            },
            <?php endforeach; ?>
        ],
        eventClick: function(event) {
            alert(event.title);
        }
    });
});
</script>
</body>
</html>