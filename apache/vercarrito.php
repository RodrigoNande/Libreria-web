<?php
session_start();
require_once 'conexion.php';


$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

if (empty($carrito)) {
    echo "<h2>Tu carrito está vacío.</h2>";
    exit;
}

$ids = implode(',', array_keys($carrito));
$sql = "SELECT IdProducto, NomProducto, Precio, Precio_Unitario FROM articulo WHERE IdProducto IN ($ids)";
$result = $conn->query($sql);

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[$row['IdProducto']] = $row;
}

$total = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="estilocarrito.css">
</head>
<body>
    <h1>Tu Carrito</h1>
    <table>
        <tr>
            <th>Producto</th>
            <th>Precio Unitario</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($carrito as $id => $cantidad): 
            $producto = $productos[$id];
            $subtotal = $producto['Precio'] * $cantidad;
            $total += $subtotal;
        ?>
        <tr>
            <td><?php echo htmlspecialchars($producto['NomProducto']); ?></td>
            <td>$<?php echo number_format($producto['Precio'], 2); ?></td>
            <td><?php echo $cantidad; ?></td>
            <td>$<?php echo number_format($subtotal, 2); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3"><strong>Total</strong></td>
            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
        </tr>
    </table>
    <a href="home.php">Seguir comprando</a>
</body>
</html>