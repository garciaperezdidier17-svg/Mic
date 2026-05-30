<?php
// includes/header.php
// Asegurar que $usuario está definida
if(!isset($usuario)) {
    $usuario = obtenerUsuarioActual();
}
?>
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
                <div class="user-name"><?php echo htmlspecialchars($usuario['nombre'] ?? 'Usuario'); ?></div>
                <div class="user-role"><?php echo ucfirst($usuario['rol'] ?? ''); ?></div>
            </div>
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</header>

<div class="dropdown-menu" id="dropdownMenu">
    <div class="dropdown-header">
        <div class="dropdown-avatar"><i class="fas fa-user"></i></div>
        <div class="dropdown-info">
            <strong><?php echo htmlspecialchars($usuario['nombre'] ?? 'Usuario'); ?></strong>
            <span><?php echo htmlspecialchars($usuario['email'] ?? ''); ?></span>
        </div>
    </div>
    <div class="dropdown-divider"></div>
    <a href="perfil.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Mi Perfil</a>
    <a href="notificaciones.php" class="dropdown-item"><i class="fas fa-bell"></i> Notificaciones</a>
    <div class="dropdown-divider"></div>
    <a href="actions/cerrar_sesion.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
</div>