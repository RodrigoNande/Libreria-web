<?php
// Capturamos el id del producto
$id = $_GET['id'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de compra</title>
</head>
<body>
    <h1>Formulario de Compra</h1>
    <p>Has seleccionado el producto con ID: <?php echo htmlspecialchars($id); ?></p>

    <!-- Aquí luego agregamos el formulario de compra -->
</body>
</html>
