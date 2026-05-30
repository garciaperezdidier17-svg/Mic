<?php
require_once 'config/conexion.php';

// Si ya está logueado, va al dashboard
if (estaLogueado()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$exito = '';

// Procesar LOGIN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Complete todos los campos';
    } else {
        $stmt = $conn->prepare("SELECT u.*, r.nombre as rol_nombre 
                                FROM usuarios u 
                                JOIN roles r ON u.rol_id = r.id 
                                WHERE u.email = ? AND u.activo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nombre'] = $usuario['nombre'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_rol'] = $usuario['rol_nombre'];
            $_SESSION['user_rol_id'] = $usuario['rol_id'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email o contraseña incorrectos';
        }
    }
}

// Procesar REGISTRO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registro'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $rol_id = $_POST['rol_id'];
    $telefono = trim($_POST['telefono']);
    
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Complete los campos obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } elseif (strlen($password) < 4) {
        $error = 'La contraseña debe tener al menos 4 caracteres';
    } else {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'El email ya está registrado';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, telefono, password_hash, rol_id) 
                                    VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $email, $telefono, $password_hash, $rol_id])) {
                $exito = 'Registro exitoso. Ahora puedes iniciar sesión.';
            } else {
                $error = 'Error al registrar';
            }
        }
    }
}

// Obtener roles para el registro
$roles = $conn->query("SELECT id, nombre FROM roles WHERE nombre IN ('docente', 'estudiante')")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC - Sistema de Inventario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
   
</head>
<body>

<div class="auth-screen">
    <div class="auth-bg-animation">
        <div class="auth-bg-circle"></div>
        <div class="auth-bg-circle2"></div>
        <div class="auth-bg-circle3"></div>
    </div>
    
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">🖥️</div>
            <div class="auth-logo-text">
                <h1>MIC</h1>
                <p>Sistema de Gestión de Inventario</p>
            </div>
        </div>
        
        <?php if($error): ?>
            <div style="background:#fee; color:#c00; padding:12px; border-radius:12px; margin-bottom:20px; text-align:center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if($exito): ?>
            <div style="background:#d4edda; color:#155724; padding:12px; border-radius:12px; margin-bottom:20px; text-align:center;">
                <?php echo $exito; ?>
            </div>
        <?php endif; ?>
        
        <div class="auth-tabs">
            <button class="auth-tab active" onclick="mostrarTab('login')">Ingresar</button>
            <button class="auth-tab" onclick="mostrarTab('registro')">Registrarse</button>
        </div>

        <!-- LOGIN -->
        <div id="loginPanel" class="auth-form active">
            <form method="POST" action="">
                <input type="hidden" name="login" value="1">
                <div class="input-group floating">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder=" " required>
                    <label>Correo electrónico</label>
                </div>
                <div class="input-group floating">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder=" " required>
                    <label>Contraseña</label>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
            <div class="auth-link" style="margin-top:15px; padding:12px; background:#f8fafc; border-radius:10px;">
                <strong>Demo:</strong> admin@mic.com / admin123
            </div>
        </div>

        <!-- REGISTRO -->
        <div id="registroPanel" class="auth-form">
            <form method="POST" action="">
                <input type="hidden" name="registro" value="1">
                <div class="input-group floating">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nombre" placeholder=" " required>
                    <label>Nombre completo</label>
                </div>
                <div class="input-group floating">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder=" " required>
                    <label>Correo electrónico</label>
                </div>
                <div class="input-group floating">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="telefono" placeholder=" ">
                    <label>Teléfono (opcional)</label>
                </div>
                <div class="input-group floating">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder=" " required>
                    <label>Contraseña</label>
                </div>
                <div class="input-group floating">
                    <i class="fas fa-user-tag"></i>
                    <select name="rol_id" required>
                        <option value="">Seleccionar rol</option>
                        <?php foreach($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>"><?php echo ucfirst($rol['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Rol</label>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
            </form>
        </div>
    </div>
</div>

<script>
function mostrarTab(tab) {
    const loginPanel = document.getElementById('loginPanel');
    const registroPanel = document.getElementById('registroPanel');
    const tabs = document.querySelectorAll('.auth-tab');
    
    if(tab === 'login') {
        loginPanel.classList.add('active');
        registroPanel.classList.remove('active');
        tabs[0].classList.add('active');
        tabs[1].classList.remove('active');
    } else {
        loginPanel.classList.remove('active');
        registroPanel.classList.add('active');
        tabs[0].classList.remove('active');
        tabs[1].classList.add('active');
    }
}
</script>

<style>
.auth-form { display: none; }
.auth-form.active { display: block; }
</style>

</body>
</html>