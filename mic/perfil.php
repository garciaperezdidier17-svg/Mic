<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();
$mensaje = '';
$error = '';

// Procesar subida de foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil'])) {
    $archivo = $_FILES['foto_perfil'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if(in_array($extension, $extensiones_permitidas)) {
        $nombre_archivo = 'user_' . $usuario['id'] . '_' . time() . '.' . $extension;
        $ruta_destino = 'uploads/' . $nombre_archivo;
        
        if(move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            $stmt = $conn->prepare("UPDATE usuarios SET foto_url = ? WHERE id = ?");
            $stmt->execute([$ruta_destino, $usuario['id']]);
            $_SESSION['user_foto'] = $ruta_destino;
            $mensaje = 'Foto actualizada correctamente';
            // Recargar datos del usuario
            $usuario = obtenerUsuarioActual();
        } else {
            $error = 'Error al subir la imagen';
        }
    } else {
        $error = 'Formato no permitido. Use JPG, PNG o GIF';
    }
}

// Procesar actualización de datos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, email = ? WHERE id = ?");
    $stmt->execute([$nombre, $telefono, $email, $usuario['id']]);
    
    $_SESSION['user_nombre'] = $nombre;
    $_SESSION['user_email'] = $email;
    $mensaje = 'Perfil actualizado correctamente';
    $usuario = obtenerUsuarioActual();
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirm = $_POST['password_confirm'];
    
    $stmt = $conn->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($password_actual, $user['password_hash'])) {
        if ($password_nueva == $password_confirm && strlen($password_nueva) >= 4) {
            $nueva_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
            $stmt->execute([$nueva_hash, $usuario['id']]);
            $mensaje = 'Contraseña actualizada correctamente';
        } else {
            $error = 'Las contraseñas no coinciden o son muy cortas';
        }
    } else {
        $error = 'Contraseña actual incorrecta';
    }
}

// Obtener estadísticas del usuario
$stmt = $conn->prepare("SELECT COUNT(*) as total_solicitudes FROM solicitudes WHERE id_usuario = ?");
$stmt->execute([$usuario['id']]);
$total_solicitudes = $stmt->fetch(PDO::FETCH_ASSOC)['total_solicitudes'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_prestamos FROM prestamos WHERE id_estudiante = ? OR id_estudiante IN (SELECT id FROM estudiantes WHERE id_usuario = ?)");
$stmt->execute([$usuario['id'], $usuario['id']]);
$total_prestamos = $stmt->fetch(PDO::FETCH_ASSOC)['total_prestamos'];

$stmt = $conn->prepare("SELECT COUNT(*) as prestamos_activos FROM prestamos WHERE (id_estudiante = ? OR id_estudiante IN (SELECT id FROM estudiantes WHERE id_usuario = ?)) AND estado = 'activo'");
$stmt->execute([$usuario['id'], $usuario['id']]);
$prestamos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['prestamos_activos'];

// Obtener últimas actividades
$stmt = $conn->prepare("(SELECT 'solicitud' as tipo, fecha_solicitud as fecha, motivo as descripcion FROM solicitudes WHERE id_usuario = ? LIMIT 3)
                        UNION ALL
                        (SELECT 'préstamo' as tipo, fecha_prestamo as fecha, 'Préstamo de equipo' as descripcion FROM prestamos WHERE id_estudiante = ? OR id_estudiante IN (SELECT id FROM estudiantes WHERE id_usuario = ?) LIMIT 3)
                        ORDER BY fecha DESC LIMIT 5");
$stmt->execute([$usuario['id'], $usuario['id'], $usuario['id']]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$foto_url = $usuario['foto_url'] ?? 'assets/img/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - MIC</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: var(--shadow-md);
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
        }
        .activity-timeline {
            max-height: 300px;
            overflow-y: auto;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid var(--gray-light);
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--secondary);
        }
        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--gray-light);
            flex-wrap: wrap;
        }
        .profile-tab {
            padding: 12px 24px;
            background: none;
            border: none;
            font-weight: 600;
            cursor: pointer;
            color: var(--gray);
            transition: all 0.3s;
        }
        .profile-tab.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            margin-bottom: -2px;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeInUp 0.3s ease; }
        .upload-btn {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        .upload-btn input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
    </style>
</head>
<body class="<?php echo esAdmin() ? 'user-admin' : ''; ?>">
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
            <div class="sidebar-header"><div class="sidebar-logo"><i class="fas fa-cubes"></i> Navegación</div></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span></a>
                <a href="inventario.php" class="nav-item"><i class="fas fa-boxes"></i><span class="nav-text">Inventario</span></a>
                <a href="solicitudes.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span></a>
                <a href="prestamos.php" class="nav-item"><i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span></a>
                <a href="notificaciones.php" class="nav-item"><i class="fas fa-bell"></i><span class="nav-text">Notificaciones</span></a>
                <a href="reportes.php" class="nav-item"><i class="fas fa-chart-bar"></i><span class="nav-text">Reportes</span></a>
                <?php if(esAdmin()): ?>
                <a href="usuarios.php" class="nav-item admin-only"><i class="fas fa-users"></i><span class="nav-text">Usuarios</span></a>
                <a href="admin_panel.php" class="nav-item admin-only"><i class="fas fa-cogs"></i><span class="nav-text">Admin</span></a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="system-info"><i class="fas fa-circle"></i> Sistema en línea</div>
                <div class="system-version">v3.0.0 &copy; MIC</div>
            </div>
        </aside>

        <main class="content-right">
            <div class="page active">
                <div class="page-header">
                    <div class="page-title">
                        <h2><i class="fas fa-user-circle"></i> Mi Perfil</h2>
                        <p>Gestiona tu información personal</p>
                    </div>
                </div>

                <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                <div class="alert alert-error" style="background:#f8d7da; color:#721c24;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- HEADER DEL PERFIL -->
                <div class="glass-card">
                    <div class="profile-header">
                        <div style="position: relative;">
                            <img src="<?php echo $foto_url; ?>" class="profile-avatar-large" id="fotoPerfil" alt="Foto de perfil" onerror="this.src='https://ui-avatars.com/api/?background=0a58ca&color=fff&name=<?php echo urlencode($usuario['nombre']); ?>'">
                            <form method="POST" enctype="multipart/form-data" style="margin-top: 10px; text-align: center;">
                                <label class="btn btn-outline upload-btn" style="padding: 5px 15px; font-size: 0.8rem;">
                                    <i class="fas fa-camera"></i> Cambiar foto
                                    <input type="file" name="foto_perfil" accept="image/*" onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?></p>
                            <p><i class="fas fa-tag"></i> Rol: <span class="badge badge-info"><?php echo ucfirst($usuario['rol']); ?></span></p>
                            <p><i class="fas fa-calendar"></i> Miembro desde: <?php echo date('d/m/Y'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- ESTADÍSTICAS -->
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_solicitudes; ?></div>
                        <div class="stat-label">Solicitudes Realizadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_prestamos; ?></div>
                        <div class="stat-label">Préstamos Totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $prestamos_activos; ?></div>
                        <div class="stat-label">Préstamos Activos</div>
                    </div>
                </div>

                <!-- TABS -->
                <div class="glass-card">
                    <div class="profile-tabs">
                        <button class="profile-tab active" onclick="showTab('datos')"><i class="fas fa-edit"></i> Datos Personales</button>
                        <button class="profile-tab" onclick="showTab('password')"><i class="fas fa-lock"></i> Cambiar Contraseña</button>
                        <button class="profile-tab" onclick="showTab('actividad')"><i class="fas fa-history"></i> Mi Actividad</button>
                        <button class="profile-tab" onclick="showTab('qr')"><i class="fas fa-qrcode"></i> Mi Código QR</button>
                    </div>

                    <!-- TAB DATOS PERSONALES -->
                    <div id="tab-datos" class="tab-content active">
                        <form method="POST">
                            <input type="hidden" name="actualizar_perfil" value="1">
                            <div class="form-group">
                                <label>Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                        </form>
                    </div>

                    <!-- TAB CAMBIAR CONTRASEÑA -->
                    <div id="tab-password" class="tab-content">
                        <form method="POST">
                            <input type="hidden" name="cambiar_password" value="1">
                            <div class="form-group">
                                <label>Contraseña Actual</label>
                                <input type="password" class="form-control" name="password_actual" required>
                            </div>
                            <div class="form-group">
                                <label>Nueva Contraseña</label>
                                <input type="password" class="form-control" name="password_nueva" required>
                                <small>Mínimo 4 caracteres</small>
                            </div>
                            <div class="form-group">
                                <label>Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" name="password_confirm" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Cambiar Contraseña</button>
                        </form>
                    </div>

                    <!-- TAB ACTIVIDAD RECIENTE -->
                    <div id="tab-actividad" class="tab-content">
                        <div class="activity-timeline">
                            <?php if(count($actividades) == 0): ?>
                            <p style="text-align:center; padding: 30px;">No hay actividades recientes</p>
                            <?php else: ?>
                                <?php foreach($actividades as $act): ?>
                                <div class="activity-item">
                                    <div class="activity-icon" style="background: <?php echo $act['tipo'] == 'solicitud' ? '#f59e0b20' : '#10b98120'; ?>">
                                        <i class="fas fa-<?php echo $act['tipo'] == 'solicitud' ? 'clipboard-list' : 'handshake'; ?>" style="color: <?php echo $act['tipo'] == 'solicitud' ? '#f59e0b' : '#10b981'; ?>"></i>
                                    </div>
                                    <div style="flex:1">
                                        <strong><?php echo ucfirst($act['tipo']); ?></strong>
                                        <p><?php echo htmlspecialchars(substr($act['descripcion'], 0, 100)); ?></p>
                                        <small><?php echo date('d/m/Y H:i', strtotime($act['fecha'])); ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TAB CÓDIGO QR PERSONAL -->
                    <div id="tab-qr" class="tab-content" style="text-align: center;">
                        <div style="padding: 20px;">
                            <div id="qrPersonal" style="display: flex; justify-content: center; margin-bottom: 20px;"></div>
                            <h4><?php echo htmlspecialchars($usuario['nombre']); ?></h4>
                            <p><?php echo htmlspecialchars($usuario['email']); ?></p>
                            <button class="btn btn-primary" onclick="imprimirQRPersonal()"><i class="fas fa-print"></i> Imprimir QR</button>
                            <button class="btn btn-outline" onclick="descargarQRPersonal()"><i class="fas fa-download"></i> Descargar QR</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function showTab(tab) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    
    // Mostrar el tab seleccionado
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
    
    // Si es el tab QR, generar el código
    if(tab === 'qr') {
        generarQRPersonal();
    }
}

function generarQRPersonal() {
    const qrDiv = document.getElementById('qrPersonal');
    if(qrDiv && !qrDiv.hasChildNodes()) {
        const url = window.location.origin + '/mic/perfil.php?id=<?php echo $usuario['id']; ?>';
        new QRCode(qrDiv, {
            text: url,
            width: 200,
            height: 200,
            colorDark: '#0a58ca',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    }
}

function imprimirQRPersonal() {
    const img = document.querySelector('#qrPersonal img');
    if(img) {
        const ventana = window.open('');
        ventana.document.write('<html><head><title>QR Personal</title></head><body style="display:flex;justify-content:center;align-items:center;min-height:100vh;"><img src="' + img.src + '"></body></html>');
        ventana.print();
    }
}

function descargarQRPersonal() {
    const img = document.querySelector('#qrPersonal img');
    if(img) {
        const link = document.createElement('a');
        link.download = 'qr-<?php echo $usuario['id']; ?>.png';
        link.href = img.src;
        link.click();
    }
}

// Cargar QR si es necesario al iniciar
if(document.getElementById('tab-qr').classList.contains('active')) {
    generarQRPersonal();
}
</script>

<style>
.alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 12px; margin-bottom: 20px; }
</style>
</body>
</html>