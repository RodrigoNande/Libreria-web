<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

$response = array(
    'productos' => [],
    'total' => 0,
    'cantidad_items' => 0
);

$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
 
if (!empty($carrito)) {
    $ids = implode(',', array_keys($carrito));
    $sql = "SELECT a.IdProducto, a.NomProducto, a.Precio, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            WHERE a.IdProducto IN ($ids)";
    
    $result = $conn->query($sql);
    
    // Almacenar productos encontrados
    $productos_encontrados = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $productos_encontrados[$row['IdProducto']] = $row;
        }
    }
    
    // Limpiar carrito de productos inexistentes y procesar válidos
    $carrito_limpio = [];
    foreach ($carrito as $id => $cantidad) {
        if (isset($productos_encontrados[$id])) {
            $carrito_limpio[$id] = $cantidad;
            $producto = $productos_encontrados[$id];
            $subtotal = (float)($producto['Precio'] ?? 0) * $cantidad;
            
            $response['productos'][] = array(
                'id' => $id,
                'nombre' => $producto['NomProducto'] ?? 'Producto sin nombre',
                'precio' => (float)($producto['Precio'] ?? 0),
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
                'imagen' => !empty($producto['ruta']) ? $producto['ruta'] : 'img/no-image.png'
            );
            
            $response['total'] += $subtotal;
            $response['cantidad_items'] += $cantidad;
        }
    }
    
    // Actualizar sesión si se eliminaron productos
    if (count($carrito_limpio) !== count($carrito)) {
        $_SESSION['carrito'] = $carrito_limpio;
    }
}

echo json_encode($response);
?>