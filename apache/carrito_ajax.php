<?php
// Configurar encoding y manejo de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión
session_start();

// DEBUG: Mostrar todos los datos recibidos
$debug_info = [
    'metodo' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'get_data' => $_GET,
    'raw_input' => file_get_contents('php://input'),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'no definido'
];

// Verificar si existe el archivo de conexión
if (!file_exists('conexion.php')) {
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Error: archivo de conexión no encontrado', 
        'cantidad_total' => 0,
        'debug' => $debug_info
    ]);
    exit;
}

require_once 'conexion.php';

// Verificar conexión a la base de datos
if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Error de conexión a la base de datos: ' . ($conn->connect_error ?? 'Conexión no definida'), 
        'cantidad_total' => 0,
        'debug' => $debug_info
    ]);
    exit;
}

$id = isset($_POST['id']) ? trim($_POST['id']) : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

$response = array(
    'exito' => false, 
    'mensaje' => '', 
    'cantidad_total' => 0,
    'debug' => $debug_info,
    'received_id' => $id,
    'received_accion' => $accion
);

if (!empty($id) && $accion === 'agregar') {
    try {
        // Verificar que el producto existe en la base de datos
        $sql = "SELECT IdProducto FROM articulo WHERE IdProducto = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $id);
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
            $response['carrito_actual'] = $_SESSION['carrito'];
        } else {
            $response['mensaje'] = 'El producto no existe en la base de datos';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['mensaje'] = 'Error del servidor: ' . $e->getMessage();
    }
} else {
    if ($id <= 0) {
        $response['mensaje'] = 'ID de producto inválido o no recibido';
    } elseif ($accion !== 'agregar') {
        $response['mensaje'] = 'Acción inválida o no recibida. Recibido: "' . $accion . '"';
    } else {
        $response['mensaje'] = 'Datos inválidos';
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>