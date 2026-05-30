<?php
require_once 'config/conexion.php';

if (!estaLogueado() || !esAdmin()) {
    header('Location: index.php');
    exit;
}

$usuario = obtenerUsuarioActual();

// Procesar creación de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $rol_id = $_POST['rol_id'];
    $telefono = trim($_POST['telefono']);
    
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Complete los campos obligatorios';
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'El email ya existe';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, telefono, password_hash, rol_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $telefono, $password_hash, $rol_id]);
            $_SESSION['mensaje'] = 'Usuario creado correctamente';
            header('Location: usuarios.php');
            exit;
        }
    }
}

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = $_GET['id'];
    if ($id != $usuario['id']) { // No eliminarse a sí mismo
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensaje'] = 'Usuario eliminado';
    }
    header('Location: usuarios.php');
    exit;
}

// Obtener usuarios
$usuarios = $conn->query("SELECT u.*, r.nombre as rol_nombre 
                          FROM usuarios u 
                          JOIN roles r ON u.rol_id = r.id 
                          ORDER BY u.creado_en DESC")->fetchAll(PDO::FETCH_ASSOC);

// Obtener roles
$roles = $conn->query("SELECT id, nombre FROM roles")->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - MIC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="user-admin">

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
        <a href="dashboard.php" class="dropdown-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <div class="dropdown-divider"></div>
        <a href="actions/cerrar_sesion.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>

    <div class="main-layout">
        <aside class="sidebar-left" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-cubes"></i> Navegación</div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span></a>
                <a href="inventario.php" class="nav-item"><i class="fas fa-boxes"></i><span class="nav-text">Inventario</span></a>
                <a href="solicitudes.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span></a>
                <a href="prestamos.php" class="nav-item"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
                <a href="usuarios.php" class="nav-item active"><i class="fas fa-users"></i><span class="nav-text">Usuarios</span></a>
            </nav>
            <div class="sidebar-footer">
                <div class="system-info"><i class="fas fa-circle"></i> Sistema en línea</div>
                <div class="system-version">v2.0.0 &copy; MIC 2024</div>
            </div>
        </aside>

        <main class="content-right">
            <div class="page active">
                <div class="page-header">
                    <div class="page-title">
                        <h2><i class="fas fa-users"></i> Usuarios</h2>
                        <p>Administración de usuarios del sistema</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openModal('addUserModal')">
                            <i class="fas fa-user-plus"></i> Agregar Usuario
                        </button>
                    </div>
                </div>

                <?php if($mensaje): ?>
                <div style="background:#d4edda; color:#155724; padding:12px; border-radius:12px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                <div style="background:#fee; color:#c00; padding:12px; border-radius:12px; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <div class="users-grid">
                    <?php foreach($usuarios as $user): ?>
                    <div class="user-card">
                        <div class="user-avatar-large">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($user['nombre']); ?></h4>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo $user['telefono'] ?: 'N/A'; ?></p>
                            <span class="badge badge-info"><?php echo ucfirst($user['rol_nombre']); ?></span>
                        </div>
                        <?php if($user['id'] != $usuario['id']): ?>
                        <div>
                            <a href="?eliminar=1&id=<?php echo $user['id']; ?>" class="btn-icon delete" onclick="return confirm('¿Eliminar este usuario?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- MODAL AGREGAR USUARIO -->
<div class="modal" id="addUserModal">
    <div class="modal-content glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Agregar Usuario</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="crear_usuario" value="1">
                <div class="form-group">
                    <label>Nombre Completo <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" class="form-control" name="telefono">
                </div>
                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="form-group">
                    <label>Rol <span class="required">*</span></label>
                    <select class="form-control" name="rol_id" required>
                        <option value="">Seleccionar rol</option>
                        <?php foreach($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>"><?php echo ucfirst($rol['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar Usuario</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById('dropdownMenu').classList.toggle('show');
}
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu') && !e.target.closest('.dropdown-menu')) {
        document.getElementById('dropdownMenu').classList.remove('show');
    }
});
</script>

</body>
</html>