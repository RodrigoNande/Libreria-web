<?php
session_start();
require_once 'conexion.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

$response = array('exito' => false, 'mensaje' => '', 'cantidad_total' => 0);

if ($id > 0 && $accion === 'agregar') {
    // Verificar que el producto existe en la base de datos
    $sql = "SELECT IdProducto FROM articulo WHERE IdProducto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Inicializar carrito si no existe
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
        
        // Agregar producto al carrito
        if (isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]++;
        } else {
            $_SESSION['carrito'][$id] = 1;
        }
        
        // Calcular cantidad total
        $cantidadTotal = 0;
        foreach ($_SESSION['carrito'] as $cantidad) {
            $cantidadTotal += $cantidad;
        }
        
        $response['exito'] = true;
        $response['mensaje'] = 'Producto agregado correctamente';
        $response['cantidad_total'] = $cantidadTotal;
    } else {
        $response['mensaje'] = 'El producto no existe';
    }
    $stmt->close();
} else {
    $response['mensaje'] = 'Datos inválidos';
}

echo json_encode($response);
?>