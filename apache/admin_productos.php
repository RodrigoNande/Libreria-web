<?php
// admin_productos.php - Panel de gestión de productos para administradores
session_start();
require_once 'conexion.php';
require_once 'auth.php';

// Verificar que el usuario esté logueado y sea admin
if (!estaLogueado() || !esAdmin()) {
    header('Location: home.php');
    exit;
}

$usuarioActual = obtenerUsuarioActual();

// Función para obtener todos los productos con sus categorías
function obtenerProductos($conn) {
    $sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, a.Stock, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            ORDER BY a.NomProducto";
    
    $result = $conn->query($sql);
    $productos = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
    }
    
    return $productos;
}

// Función para obtener categorías (sin filtrar por padre)
function obtenerCategorias($conn) {
    $sql = "SELECT Id, Nombre_Categoria, Descripcion, IdCategoriaPadre 
            FROM Categoria 
            ORDER BY Nombre_Categoria";
    
    $result = $conn->query($sql);
    $categorias = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    
    return $categorias;
}

// Función para asignar producto a categoría
function asignarProductoCategoria($conn, $idProducto, $idCategoria) {
    // Primero verificar si ya existe la relación
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM producto_categoria WHERE Id_Producto = ? AND Id_Categoria = ?");
    $checkStmt->bind_param("ss", $idProducto, $idCategoria);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->fetch_assoc()['count'] > 0;
    $checkStmt->close();
    
    if (!$exists) {
        $stmt = $conn->prepare("INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES (?, ?)");
        $stmt->bind_param("ss", $idProducto, $idCategoria);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    return true;
}

// Procesar acciones POST
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'agregar':
            $nombre = trim($_POST['nombre'] ?? '');
            $marca = trim($_POST['marca'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
            $stock = intval($_POST['stock'] ?? 0);
            $idCategoria = trim($_POST['id_categoria'] ?? '');
            
            if (empty($nombre) || empty($marca) || empty($tipo) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                // Generar ID único
                $idProducto = 'PROD_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
                
                $stmt = $conn->prepare("INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssddi", $idProducto, $nombre, $marca, $tipo, $precio, $precio_unitario, $stock);
                
                if ($stmt->execute()) {
                    // Asignar categoría si se seleccionó
                    if (!empty($idCategoria)) {
                        asignarProductoCategoria($conn, $idProducto, $idCategoria);
                    }
                    
                    $mensaje = 'Producto agregado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al agregar el producto';
                    $tipoMensaje = 'error';
                }
                $stmt->close();
            }
            break;
            
        case 'eliminar':
            $idProducto = $_POST['id_producto'] ?? '';
            
            if (!empty($idProducto)) {
                // Eliminar relaciones en producto_categoria primero
                $stmt = $conn->prepare("DELETE FROM producto_categoria WHERE Id_Producto = ?");
                $stmt->bind_param("s", $idProducto);
                $stmt->execute();
                $stmt->close();
                
                // Eliminar imagen
                $stmt = $conn->prepare("DELETE FROM img WHERE idProd = ?");
                $stmt->bind_param("s", $idProducto);
                $stmt->execute();
                $stmt->close();
                
                // Eliminar producto
                $stmt = $conn->prepare("DELETE FROM articulo WHERE IdProducto = ?");
                $stmt->bind_param("s", $idProducto);
                
                if ($stmt->execute()) {
                    $mensaje = 'Producto eliminado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al eliminar el producto';
                    $tipoMensaje = 'error';
                }
                $stmt->close();
            }
            break;
            
        case 'modificar':
            $idProducto = $_POST['id_producto'] ?? '';
            $nombre = trim($_POST['nombre'] ?? '');
            $marca = trim($_POST['marca'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
            $stock = intval($_POST['stock'] ?? 0);
            $idCategoria = trim($_POST['id_categoria'] ?? '');
            
            if (empty($idProducto) || empty($nombre) || empty($marca) || empty($tipo) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE articulo SET NomProducto = ?, Marca = ?, TipoProducto = ?, Precio = ?, Precio_Unitario = ?, Stock = ? WHERE IdProducto = ?");
                $stmt->bind_param("sssddis", $nombre, $marca, $tipo, $precio, $precio_unitario, $stock, $idProducto);
                
                if ($stmt->execute()) {
                    // Actualizar categoría si se seleccionó
                    if (!empty($idCategoria)) {
                        // Eliminar relaciones anteriores
                        $delStmt = $conn->prepare("DELETE FROM producto_categoria WHERE Id_Producto = ?");
                        $delStmt->bind_param("s", $idProducto);
                        $delStmt->execute();
                        $delStmt->close();
                        
                        // Crear nueva relación
                        asignarProductoCategoria($conn, $idProducto, $idCategoria);
                    }
                    
                    $mensaje = 'Producto modificado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al modificar el producto';
                    $tipoMensaje = 'error';
                }
                $stmt->close();
            }
            break;
    }
}

$productos = obtenerProductos($conn);
$categorias = obtenerCategorias($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Productos</title>
    <link rel="stylesheet" href="estilopruebas.css">
    <style>
      :root {
            --primary-color: #1a365d;
            --primary-light: #2d3748;
            --primary-dark: #0f1419;
            --secondary-color: #3182ce;
            --secondary-light: #4299e1;
            --accent-color: #38b2ac;
            --success-color: #38a169;
            --warning-color: #d69e2e;
            --error-color: #e53e3e;
            --text-dark: #1a202c;
            --text-medium: #4a5568;
            --text-light: #718096;
            --background-light: #f7fafc;
            --background-white: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition-fast: all 0.2s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .admin-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1rem;
        }

        .admin-panel {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--background-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .admin-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-title h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(45deg, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .admin-title .icon {
            font-size: 2.5rem;
            opacity: 0.9;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .welcome-text {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .user-name {
            font-weight: 600;
            color: #ffffff;
        }

        .btn-volver {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-volver:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .admin-content {
            padding: 3rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-light));
            color: white;
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .stat-info p {
            color: var(--text-medium);
            font-size: 0.9rem;
            margin: 0.25rem 0 0 0;
        }

        /* Formulario mejorado */
        .formulario-producto {
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-sm);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--secondary-color);
        }

        .form-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .form-header .form-icon {
            font-size: 1.5rem;
            color: var(--secondary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: var(--transition-normal);
            background: white;
            font-family: inherit;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
            transform: translateY(-1px);
        }

        .form-group select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: var(--transition-normal);
            background: white;
            font-family: inherit;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            padding-right: 3rem;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: var(--text-light);
        }

        .btn-agregar {
            background: linear-gradient(135deg, var(--success-color), #38a169);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            box-shadow: var(--shadow-md);
        }

        .btn-agregar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, #38a169, var(--success-color));
        }

        .btn-agregar:active {
            transform: translateY(0);
        }

        /* Mensajes */
        .mensaje {
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInRight 0.4s ease-out;
        }

        .mensaje.exito {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Productos Grid */
        .productos-section {
            margin-bottom: 3rem;
        }

        .productos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .productos-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .productos-count {
            background: var(--secondary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Barra de bÃºsqueda para admin */
        .search-container-admin {
            margin-bottom: 2rem;
            position: relative;
            max-width: 500px;
        }

        .search-input-admin {
            width: 100%;
            padding: 1rem 3rem 1rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            font-size: 0.95rem;
            outline: none;
            transition: var(--transition-normal);
            background: white;
            font-family: inherit;
        }

        .search-input-admin:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
            transform: translateY(-1px);
        }

        .search-input-admin::placeholder {
            color: var(--text-light);
        }

        .search-btn-admin {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--secondary-color);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-normal);
        }

        .search-btn-admin:hover {
            background: var(--secondary-dark);
            transform: translateY(-50%) scale(1.05);
        }

        /* Mensaje de no resultados */
        .no-results-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--border-color);
            margin: 2rem 0;
        }

        .no-results-message h3 {
            color: var(--text-dark);
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
        }

        .no-results-message p {
            color: var(--text-medium);
            margin: 0;
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .producto-admin {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition-normal);
            position: relative;
        }

        .producto-admin:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        /* Estilos para el contenedor de imagen */
        .product-image-container {
            width: 100%;
            height: 180px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #edf2f7);
        }

        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-normal);
        }

        .product-image-container:hover img {
            transform: scale(1.05);
        }

        /* Estilos para cuando no hay imagen */
        .product-image-container.no-image {
            background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%);
            border: 2px dashed #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .no-image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #6c757d;
            padding: 1rem;
        }

        .no-image-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            opacity: 0.6;
        }

        .no-image-text {
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Estilos para cuando la imagen falla al cargar */
        .product-image-container.image-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 2px dashed var(--error-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .producto-content {
            padding: 1.5rem;
        }

        .producto-admin h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0 0 1rem 0;
            line-height: 1.4;
        }

        .producto-info {
            margin-bottom: 1.5rem;
        }

        .producto-info p {
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-medium);
        }

        .info-value {
            color: var(--text-dark);
        }

        .price-highlight {
            color: var(--secondary-color);
            font-weight: 700;
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .stock-high {
            background: #d4edda;
            color: #155724;
        }

        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }

        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }

        .producto-acciones {
            display: flex;
            gap: 0.75rem;
        }

        .btn-admin {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-modificar {
            background: linear-gradient(135deg, var(--warning-color), #d69e2e);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-modificar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #d69e2e, var(--warning-color));
        }

        .btn-eliminar {
            background: linear-gradient(135deg, var(--error-color), #e53e3e);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-eliminar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #e53e3e, var(--error-color));
        }

        /* Modal mejorado */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: var(--shadow-xl);
            animation: slideInScale 0.4s ease-out;
        }

        @keyframes slideInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .cerrar-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition-fast);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .cerrar-modal:hover {
            color: var(--error-color);
            background: rgba(229, 62, 62, 0.1);
        }

        .modal-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 2rem;
            padding-right: 3rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .admin-header {
                padding: 1.5rem 2rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .admin-title h1 {
                font-size: 1.5rem;
            }

            .admin-content {
                padding: 2rem 1.5rem;
            }

            .productos-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem 0.5rem;
            }

            .admin-header {
                padding: 1rem;
            }

            .admin-title {
                flex-direction: column;
                gap: 0.5rem;
            }

            .admin-title h1 {
                font-size: 1.25rem;
            }

            .admin-user-info {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .productos-grid {
                grid-template-columns: 1fr;
            }

            .producto-acciones {
                flex-direction: column;
            }

            .btn-admin {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .admin-content {
                padding: 1rem;
            }

            .formulario-producto {
                padding: 1.5rem;
            }

            .producto-content {
                padding: 1rem;
            }
        }

        /* Animaciones adicionales */
        @keyframes slideOutScale {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(0.9);
            }
        }

        /* Estilos para estados de carga */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mejorar accesibilidad */
        .btn-admin:focus,
        .btn-volver:focus,
        .btn-agregar:focus {
            outline: 2px solid var(--secondary-color);
            outline-offset: 2px;
        }

        /* Estilos para tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--text-dark);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--text-dark) transparent transparent transparent;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-panel">
            <header class="admin-header">
                <div class="admin-title">
                    <h1>Panel de Administración - Productos</h1>
                </div>
                <a href="home.php" class="btn-volver">Volver a la Tienda</a>
            </header>

            <main class="admin-content">
                <section class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($productos); ?></h3>
                        <p>Productos Totales</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count($categorias); ?></h3>
                        <p>Categorías</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo array_sum(array_column($productos, 'Stock')); ?></h3>
                        <p>Unidades en Stock</p>
                    </div>
                </section>

                <?php if ($mensaje): ?>
                    <div class="mensaje <?php echo $tipoMensaje; ?>">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <section class="formulario-producto">
                    <h3>Agregar Nuevo Producto</h3>
                    <form method="POST">
                        <input type="hidden" name="accion" value="agregar">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto *</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="marca">Marca *</label>
                                <input type="text" id="marca" name="marca" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo">Tipo de Producto *</label>
                                <input type="text" id="tipo" name="tipo" required>
                            </div>
                            <div class="form-group">
                                <label for="id_categoria">Categoría</label>
                                <select id="id_categoria" name="id_categoria">
                                    <option value="">Seleccionar categoría...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['Id']); ?>">
                                            <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                                            <?php if (!empty($categoria['Descripcion'])): ?>
                                                - <?php echo htmlspecialchars(substr($categoria['Descripcion'], 0, 30)); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="precio">Precio de Venta *</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="precio_unitario">Precio Unitario</label>
                                <input type="number" id="precio_unitario" name="precio_unitario" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock Inicial</label>
                                <input type="number" id="stock" name="stock" min="0" value="0">
                            </div>
                        </div>

                        <button type="submit" class="btn-agregar">Agregar Producto</button>
                    </form>
                </section>

                <section class="productos-section">
                    <h2>Productos Existentes (<?php echo count($productos); ?>)</h2>
                    <div class="productos-grid">
                        <?php foreach ($productos as $producto): ?>
                            <article class="producto-admin">
                                <div class="product-image-container">
                                    <?php if (!empty($producto['ruta'])): ?>
                                        <img src="<?php echo $producto['ruta']; ?>" alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>">
                                    <?php else: ?>
                                        <p>Sin imagen</p>
                                    <?php endif; ?>
                                </div>

                                <div class="producto-content">
                                    <h4><?php echo htmlspecialchars($producto['NomProducto']); ?></h4>
                                    <p><strong>Marca:</strong> <?php echo htmlspecialchars($producto['Marca']); ?></p>
                                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($producto['TipoProducto']); ?></p>
                                    <p><strong>Precio:</strong> $<?php echo number_format($producto['Precio'], 2); ?></p>
                                    <p><strong>Stock:</strong> <?php echo $producto['Stock']; ?> unidades</p>

                                    <div class="producto-acciones">
                                        <button class="btn-admin btn-modificar"
                                                onclick="abrirModalModificar('<?php echo $producto['IdProducto']; ?>',
                                                                             '<?php echo htmlspecialchars($producto['NomProducto']); ?>',
                                                                             '<?php echo htmlspecialchars($producto['Marca']); ?>',
                                                                             '<?php echo htmlspecialchars($producto['TipoProducto']); ?>',
                                                                             <?php echo $producto['Precio']; ?>,
                                                                             <?php echo $producto['Precio_Unitario']; ?>,
                                                                             <?php echo $producto['Stock']; ?>)">
                                            Modificar
                                        </button>

                                        <button class="btn-admin btn-eliminar"
                                                onclick="confirmarEliminar('<?php echo $producto['IdProducto']; ?>', '<?php echo htmlspecialchars($producto['NomProducto']); ?>')">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
    
    <div id="modalModificar" class="modal">
        <div class="modal-content">
            <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
            <h3>Modificar Producto</h3>

            <form method="POST">
                <input type="hidden" name="accion" value="modificar">
                <input type="hidden" id="mod_id_producto" name="id_producto">

                <div class="form-group">
                    <label for="mod_nombre">Nombre del Producto *</label>
                    <input type="text" id="mod_nombre" name="nombre" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mod_marca">Marca *</label>
                        <input type="text" id="mod_marca" name="marca" required>
                    </div>
                    <div class="form-group">
                        <label for="mod_tipo">Tipo *</label>
                        <input type="text" id="mod_tipo" name="tipo" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mod_id_categoria">Categoría</label>
                    <select id="mod_id_categoria" name="id_categoria">
                        <option value="">Seleccionar categoría...</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria['Id']); ?>">
                                <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mod_precio">Precio *</label>
                        <input type="number" id="mod_precio" name="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="mod_precio_unitario">Precio Unitario</label>
                        <input type="number" id="mod_precio_unitario" name="precio_unitario" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="mod_stock">Stock</label>
                        <input type="number" id="mod_stock" name="stock" min="0">
                    </div>
                </div>

                <button type="submit" class="btn-agregar">Guardar Cambios</button>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalModificar(id, nombre, marca, tipo, precio, precioUnitario, stock) {
            document.getElementById('mod_id_producto').value = id;
            document.getElementById('mod_nombre').value = nombre;
            document.getElementById('mod_marca').value = marca;
            document.getElementById('mod_tipo').value = tipo;
            document.getElementById('mod_precio').value = precio;
            document.getElementById('mod_precio_unitario').value = precioUnitario;
            document.getElementById('mod_stock').value = stock;
            document.getElementById('modalModificar').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalModificar').style.display = 'none';
        }

        function confirmarEliminar(id, nombre) {
            if (confirm('¿Estás seguro de eliminar "' + nombre + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_producto" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalModificar');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>