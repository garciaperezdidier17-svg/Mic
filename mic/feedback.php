<?php
require_once 'config/conexion.php';
if (!estaLogueado()) { header('Location: index.php'); exit; }

$usuario = obtenerUsuarioActual();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar'])) {
    $stmt = $conn->prepare("INSERT INTO feedback (id_usuario, tipo_encuesta, comentario, puntuacion) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario['id'], $_POST['tipo'], $_POST['comentario'], $_POST['puntuacion']]);
    $mensaje = "Gracias por tu feedback";
}

// Obtener feedbacks (solo admin)
$feedbacks = [];
if (esAdmin()) {
    $feedbacks = $conn->query("SELECT f.*, u.nombre as usuario FROM feedback f JOIN usuarios u ON f.id_usuario = u.id ORDER BY f.creado_en DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Feedback - MIC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app" style="display:block;">
    <?php include 'includes/header.php'; ?>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="content-right">
            <div class="page active">
                <div class="page-header">
                    <div class="page-title">
                        <h2><i class="fas fa-star"></i> Tu Opinión Nos Importa</h2>
                    </div>
                </div>

                <?php if(isset($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <div class="glass-card">
                    <form method="POST">
                        <div class="form-group">
                            <label>Tipo de Feedback</label>
                            <select name="tipo" class="form-control" required>
                                <option value="satisfaccion">Satisfacción general</option>
                                <option value="sugerencia">Sugerencia de mejora</option>
                                <option value="reporte_error">Reporte de error</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Puntuación (1-5)</label>
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                <label style="font-size: 2rem; cursor: pointer;">
                                    <input type="radio" name="puntuacion" value="<?php echo $i; ?>" style="display: none;" required>
                                    <i class="far fa-star" data-val="<?php echo $i; ?>"></i>
                                </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tu Comentario</label>
                            <textarea name="comentario" class="form-control" rows="5" placeholder="Cuéntanos tu experiencia..." required></textarea>
                        </div>
                        <button type="submit" name="enviar" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Feedback</button>
                    </form>
                </div>

                <?php if(esAdmin() && count($feedbacks) > 0): ?>
                <div class="glass-card" style="margin-top: 20px;">
                    <h3>Feedback de Usuarios</h3>
                    <?php foreach($feedbacks as $f): ?>
                    <div style="padding: 15px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between;">
                            <strong><?php echo htmlspecialchars($f['usuario']); ?></strong>
                            <small><?php echo date('d/m/Y H:i', strtotime($f['creado_en'])); ?></small>
                        </div>
                        <div>⭐ <?php echo str_repeat('★', $f['puntuacion']) . str_repeat('☆', 5 - $f['puntuacion']); ?></div>
                        <p><?php echo htmlspecialchars($f['comentario']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
// Sistema de rating
document.querySelectorAll('.rating .fa-star, .rating .far').forEach(star => {
    star.addEventListener('click', function() {
        const val = this.getAttribute('data-val');
        const ratingContainer = this.closest('.rating');
        for(let i = 1; i <= 5; i++) {
            const stars = ratingContainer.querySelectorAll(`[data-val="${i}"]`);
            stars.forEach(s => {
                if(i <= val) {
                    s.classList.remove('far');
                    s.classList.add('fas');
                } else {
                    s.classList.remove('fas');
                    s.classList.add('far');
                }
            });
        }
        ratingContainer.querySelector(`input[value="${val}"]`).checked = true;
    });
});
</script>
</body>
</html>