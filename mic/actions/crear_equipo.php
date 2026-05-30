<?php
require_once '../config/conexion.php';

if (!estaLogueado() || !esAdmin()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo_interno']);
    $nombre = trim($_POST['nombre']);
    $id_tipo = !empty($_POST['id_tipo']) ? $_POST['id_tipo'] : null;
    $id_categoria = !empty($_POST['id_categoria']) ? $_POST['id_categoria'] : null;
    $marca = trim($_POST['marca']);
    $modelo = trim($_POST['modelo']);
    $id_sede = !empty($_POST['id_sede']) ? $_POST['id_sede'] : null;
    $estado = $_POST['estado'];
    $stock = intval($_POST['stock']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $fecha_ingreso = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO equipos (codigo_interno, nombre, id_tipo, id_categoria, marca, modelo, id_sede, estado, stock, stock_minimo, fecha_ingreso, activo) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $stmt->execute([$codigo, $nombre, $id_tipo, $id_categoria, $marca, $modelo, $id_sede, $estado, $stock, $stock_minimo, $fecha_ingreso]);
    
    $_SESSION['mensaje'] = 'Equipo agregado correctamente';
    header('Location: ../inventario.php');
    exit;
}
?>