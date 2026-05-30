<?php
require_once '../config/conexion.php';

if (!estaLogueado() || !esAdmin()) {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // En lugar de eliminar, solo desactivamos
    $stmt = $conn->prepare("UPDATE equipos SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['mensaje'] = 'Equipo eliminado correctamente';
}

header('Location: ../inventario.php');
exit;
?>