<?php
require_once 'config/conexion.php';

echo "<h1>Configuración de Administrador</h1>";

// 1. Verificar/Crear rol ADMIN
$stmt = $conn->prepare("SELECT id FROM roles WHERE nombre = 'admin'");
$stmt->execute();
$adminRol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminRol) {
    // Crear rol admin
    $stmt = $conn->prepare("INSERT INTO roles (nombre, descripcion) VALUES ('admin', 'Administrador del sistema')");
    $stmt->execute();
    $adminRolId = $conn->lastInsertId();
    echo "✅ Rol 'admin' creado<br>";
} else {
    $adminRolId = $adminRol['id'];
    echo "✅ Rol 'admin' ya existe (ID: $adminRolId)<br>";
}

// 2. Verificar/Crear usuario ADMIN
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = 'admin@mic.com'");
$stmt->execute();
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Contraseña: admin123
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);

if (!$adminUser) {
    // Crear usuario admin
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id, activo) 
                            VALUES ('Administrador Sistema', 'admin@mic.com', ?, ?, 1)");
    $stmt->execute([$password_hash, $adminRolId]);
    echo "✅ Usuario administrador creado<br>";
} else {
    // Actualizar contraseña y rol
    $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ?, rol_id = ?, activo = 1 WHERE email = 'admin@mic.com'");
    $stmt->execute([$password_hash, $adminRolId]);
    echo "✅ Usuario administrador actualizado<br>";
}

// 3. Verificar que todo está bien
$stmt = $conn->prepare("SELECT u.id, u.nombre, u.email, r.nombre as rol 
                        FROM usuarios u 
                        JOIN roles r ON u.rol_id = r.id 
                        WHERE u.email = 'admin@mic.com'");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<hr>";
echo "<h2>Credenciales de Acceso</h2>";
echo "<p><strong>Email:</strong> admin@mic.com</p>";
echo "<p><strong>Contraseña:</strong> admin123</p>";
echo "<p><strong>Rol:</strong> " . $admin['rol'] . "</p>";
echo "<hr>";
echo "<a href='index.php' style='background:#0a58ca; color:white; padding:10px 20px; text-decoration:none; border-radius:10px;'>Ir al Login</a>";
?>