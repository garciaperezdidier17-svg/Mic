<?php
require_once 'config/conexion.php';
if (!estaLogueado() || !esAdmin()) { header('Location: index.php'); exit; }

// Exportar a CSV
if (isset($_GET['exportar'])) {
    $tabla = $_GET['tabla'];
    $filename = "mic_{$tabla}_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Obtener datos
    $stmt = $conn->query("SELECT * FROM $tabla");
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($datos) > 0) {
        fputcsv($output, array_keys($datos[0]));
        foreach($datos as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

// Importar desde CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $tabla = $_POST['tabla'];
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 1000, ",");
        $count = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $placeholders = implode(',', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO $tabla (" . implode(',', $headers) . ") VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($data);
            $count++;
        }
        fclose($handle);
        $mensaje = "Se importaron $count registros correctamente";
    }
}

$tablas = ['equipos', 'usuarios', 'sedes', 'tipo_equipo', 'categorias'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar/Exportar - MIC</title>
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
                        <h2><i class="fas fa-exchange-alt"></i> Importar / Exportar Datos</h2>
                    </div>
                </div>

                <?php if(isset($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <div class="kpi-grid">
                    <?php foreach($tablas as $tabla): ?>
                    <div class="glass-card" style="text-align: center;">
                        <i class="fas fa-table" style="font-size: 2rem; color: var(--primary);"></i>
                        <h3 style="margin: 10px 0;"><?php echo ucfirst($tabla); ?></h3>
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <a href="?exportar=1&tabla=<?php echo $tabla; ?>" class="btn btn-primary"><i class="fas fa-download"></i> Exportar</a>
                            <button onclick="mostrarImportar('<?php echo $tabla; ?>')" class="btn btn-outline"><i class="fas fa-upload"></i> Importar</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Modal Importar -->
                <div class="modal" id="importModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Importar CSV</h3>
                            <button class="modal-close" onclick="closeModal('importModal')">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="tabla" id="importTabla">
                                <div class="form-group">
                                    <label>Archivo CSV</label>
                                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Importar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function mostrarImportar(tabla) {
    document.getElementById('importTabla').value = tabla;
    openModal('importModal');
}
</script>
</body>
</html>