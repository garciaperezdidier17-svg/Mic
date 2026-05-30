<aside class="sidebar-left" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fas fa-cubes"></i> Navegación</div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span>
        </a>
        <a href="inventario.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inventario.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i><span class="nav-text">Inventario</span>
        </a>
        <a href="solicitudes.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'solicitudes.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i><span class="nav-text">Solicitudes</span>
        </a>
        <a href="prestamos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'prestamos.php' ? 'active' : ''; ?>">
            <i class="fas fa-handshake"></i><span class="nav-text">Préstamos</span>
        </a>
        <a href="calendario.php" class="nav-item">
            <i class="fas fa-calendar-alt"></i><span class="nav-text">Calendario</span>
        </a>
        <a href="buscador_avanzado.php" class="nav-item">
            <i class="fas fa-search"></i><span class="nav-text">Buscador</span>
        </a>
        <a href="reportes.php" class="nav-item">
            <i class="fas fa-chart-bar"></i><span class="nav-text">Reportes</span>
        </a>
        <a href="feedback.php" class="nav-item">
            <i class="fas fa-star"></i><span class="nav-text">Feedback</span>
        </a>
        <?php if(esAdmin()): ?>
        <div class="nav-divider" style="height: 1px; background: var(--gray-light); margin: 15px 0;"></div>
        <a href="usuarios.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-text">Usuarios</span></a>
        <a href="mantenimiento.php" class="nav-item"><i class="fas fa-tools"></i><span class="nav-text">Mantenimiento</span></a>
        <a href="equipos_dañados.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i><span class="nav-text">Equipos Dañados</span></a>
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-cogs"></i><span class="nav-text">Admin</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="system-info"><i class="fas fa-circle"></i> Sistema en línea</div>
        <div class="system-version">v3.0.0 &copy; MIC 2024</div>
    </div>
</aside>

<button class="sidebar-toggle" onclick="toggleSidebar()" style="position: fixed; left: 10px; bottom: 20px; background: var(--primary); color: white; border: none; border-radius: 50%; width: 45px; height: 45px; cursor: pointer; z-index: 160; display: none;">
    <i class="fas fa-bars"></i>
</button>

<style>
@media (max-width: 768px) {
    .sidebar-toggle { display: block; }
}
</style>