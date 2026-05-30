<?php
require_once '../config/conexion.php';
if (!estaLogueado()) { header('Location: ../index.php'); exit; }

$id = $_GET['id'];
$multa = $_GET['multa'];
$obs = $_GET['obs'];

$conn->beginTransaction();

// Actualizar préstamo
$stmt = $conn->prepare("UPDATE prestamos SET fecha_devolucion_real = CURDATE(), hora_devolucion = CURTIME(), estado = 'devuelto', multa = ?, observaciones = ? WHERE id = ?");
$stmt->execute([$multa, $obs, $id]);

// Obtener equipo_id
$stmt = $conn->prepare("SELECT id_equipo FROM prestamos WHERE id = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

// Actualizar estado del equipo
$stmt = $conn->prepare("UPDATE equipos SET estado = 'disponible' WHERE id = ?");
$stmt->execute([$equipo['id_equipo']]);

$conn->commit();

// Enviar notificación si hay multa
if ($multa > 0) {
    $stmt = $conn->prepare("SELECT id_estudiante FROM prestamos WHERE id = ?");
    $stmt->execute([$id]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    enviarNotificacion($prestamo['id_estudiante'], 'alerta', 'Multa por retraso', "Se ha generado una multa de $$multa por devolución tardía.");
}

header('Location: ../prestamos.php?msg=devolucion_exitosa');
exit;
?>