<?php
session_start();
require_once 'conexion.php';

// Configurar headers correctos
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$response = array(
    'productos' => [],
    'total' => 0,
    'cantidad_items' => 0,
    'error' => false,
    'mensaje' => ''
);

try {
    // Verificar conexión a la base de datos
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
    
    if (!empty($carrito)) {
        // Preparar IDs para la consulta de forma segura
        $ids_array = array_keys($carrito);
        $placeholders = str_repeat('?,', count($ids_array) - 1) . '?';
        
        $sql = "SELECT a.IdProducto, a.NomProducto, a.Precio, i.ruta
                FROM articulo a
                LEFT JOIN img i ON a.IdProducto = i.idProd
                WHERE a.IdProducto IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . $conn->error);
        }
        
        // Bind parameters dinámicamente
        $types = str_repeat('s', count($ids_array));
        $stmt->bind_param($types, ...$ids_array);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Almacenar productos encontrados
        $productos_encontrados = [];
        while ($row = $result->fetch_assoc()) {
            $productos_encontrados[$row['IdProducto']] = $row;
        }
        $stmt->close();
        
        // Limpiar carrito de productos inexistentes y procesar válidos
        $carrito_limpio = [];
        foreach ($carrito as $id => $cantidad) {
            if (isset($productos_encontrados[$id])) {
                $carrito_limpio[$id] = $cantidad;
                $producto = $productos_encontrados[$id];
                $precio = floatval($producto['Precio'] ?? 0);
                $subtotal = $precio * $cantidad;
                
                $response['productos'][] = array(
                    'id' => $id,
                    'nombre' => $producto['NomProducto'] ?? 'Producto sin nombre',
                    'precio' => $precio,
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
        
        $response['mensaje'] = count($response['productos']) > 0 ? 'Carrito cargado correctamente' : 'Carrito vacío';
    } else {
        $response['mensaje'] = 'Carrito vacío';
    }
    
} catch (Exception $e) {
    $response['error'] = true;
    $response['mensaje'] = 'Error del servidor: ' . $e->getMessage();
    error_log("Error en vistapreviacarrito.php: " . $e->getMessage());
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>