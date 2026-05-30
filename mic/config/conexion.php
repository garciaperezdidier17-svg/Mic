<?php
session_start();

$host = 'localhost';
$dbname = 'mic';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function estaLogueado() {
    return isset($_SESSION['user_id']);
}

function esAdmin() {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin';
}

function obtenerUsuarioActual() {
    if (!estaLogueado()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'],
        'email' => $_SESSION['user_email'],
        'rol' => $_SESSION['user_rol']
    ];
}

function enviarNotificacion($id_usuario, $tipo, $titulo, $mensaje) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$id_usuario, $tipo, $titulo, $mensaje]);
}