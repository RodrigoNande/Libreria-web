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

// Función para obtener todos los productos
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

// Función para obtener categorías principales
function obtenerCategorias($conn) {
    $sql = "SELECT Id, Nombre_Categoria 
            FROM Categoria 
            WHERE IdCategoriaPadre IS NULL 
            ORDER BY Nombre_Categoria";
    
    $result = $conn->query($sql);
    $categorias = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    } else {
        // Fallback si no hay categorías en BD
        $categorias = [
            ['Id' => 1, 'Nombre_Categoria' => 'GENERAL'],
            ['Id' => 2, 'Nombre_Categoria' => 'OFICINA'],
            ['Id' => 3, 'Nombre_Categoria' => 'ESCOLAR']
        ];
    }
    
    return $categorias;
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
            
            if (empty($nombre) || empty($marca) || empty($tipo) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                // Generar ID único
                $idProducto = 'PROD_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
                
                $stmt = $conn->prepare("INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssddi", $idProducto, $nombre, $marca, $tipo, $precio, $precio_unitario, $stock);
                
                if ($stmt->execute()) {
                    $mensaje = 'Producto agregado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al agregar el producto';
                    $tipoMensaje = 'error';
                }
            }
            break;
            
        case 'eliminar':
            $idProducto = $_POST['id_producto'] ?? '';
            
            if (!empty($idProducto)) {
                // Eliminar imagen primero
                $stmt = $conn->prepare("DELETE FROM img WHERE idProd = ?");
                $stmt->bind_param("s", $idProducto);
                $stmt->execute();
                
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
            
            if (empty($idProducto) || empty($nombre) || empty($marca) || empty($tipo) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE articulo SET NomProducto = ?, Marca = ?, TipoProducto = ?, Precio = ?, Precio_Unitario = ?, Stock = ? WHERE IdProducto = ?");
                $stmt->bind_param("sssddis", $nombre, $marca, $tipo, $precio, $precio_unitario, $stock, $idProducto);
                
                if ($stmt->execute()) {
                    $mensaje = 'Producto modificado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al modificar el producto';
                    $tipoMensaje = 'error';
                }
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

        /* Barra de búsqueda para admin */
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
                    <span class="icon">📊</span>
                    <h1>Panel de Administración</h1>
                </div>
                <div class="admin-user-info">
                    <div class="welcome-text">
                        Bienvenido, <span class="user-name"><?php echo htmlspecialchars($usuarioActual['Nombre'] ?? 'Admin'); ?></span>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="verificar_db.php" style="
                            background: rgba(255, 255, 255, 0.1);
                            backdrop-filter: blur(10px);
                            border: 1px solid rgba(255, 255, 255, 0.2);
                            color: white;
                            padding: 0.5rem 1rem;
                            border-radius: var(--radius-md);
                            text-decoration: none;
                            font-weight: 500;
                            font-size: 0.85rem;
                            transition: var(--transition-normal);
                        " title="Verificar estado de la base de datos">🔍 Verificar BD</a>
                        <a href="test_crud.php" style="
                            background: rgba(255, 255, 255, 0.1);
                            backdrop-filter: blur(10px);
                            border: 1px solid rgba(255, 255, 255, 0.2);
                            color: white;
                            padding: 0.5rem 1rem;
                            border-radius: var(--radius-md);
                            text-decoration: none;
                            font-weight: 500;
                            font-size: 0.85rem;
                            transition: var(--transition-normal);
                        " title="Probar operaciones CRUD">🧪 Test CRUD</a>
                        <a href="home.php" class="btn-volver">
                            <span>🏠</span>
                            Volver a la Tienda
                        </a>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                <!-- Stats Cards -->
                <section class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <h3><?php echo count($productos); ?></h3>
                            <p>Productos Totales</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>$<?php echo number_format(array_sum(array_column($productos, 'Precio')), 2); ?></h3>
                            <p>Valor Total</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-info">
                            <h3><?php echo array_sum(array_column($productos, 'Stock')); ?></h3>
                            <p>Unidades en Stock</p>
                        </div>
                    </div>
                </section>

                <?php if ($mensaje): ?>
                    <div class="mensaje <?php echo $tipoMensaje; ?>">
                        <span><?php echo $tipoMensaje === 'exito' ? '✅' : '❌'; ?></span>
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario para agregar producto -->
                <section class="formulario-producto">
                    <div class="form-header">
                        <span class="form-icon">➕</span>
                        <h3>Agregar Nuevo Producto</h3>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="accion" value="agregar">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto *</label>
                                <input type="text" id="nombre" name="nombre" placeholder="Ej: Cuaderno Profesional" required>
                            </div>
                            <div class="form-group">
                                <label for="marca">Marca *</label>
                                <input type="text" id="marca" name="marca" placeholder="Ej: Faber-Castell" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo">Categoría *</label>
                                <select id="tipo" name="tipo" required>
                                    <option value="">Seleccionar categoría...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>">
                                            <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color: var(--text-medium); font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                                    Selecciona la categoría a la que pertenece el producto
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock Inicial</label>
                                <input type="number" id="stock" name="stock" placeholder="0" min="0" value="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="precio">Precio de Venta *</label>
                                <input type="number" id="precio" name="precio" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="precio_unitario">Precio Unitario</label>
                                <input type="number" id="precio_unitario" name="precio_unitario" placeholder="0.00" step="0.01" min="0">
                            </div>
                        </div>

                        <button type="submit" class="btn-agregar">
                            <span>📦</span>
                            Agregar Producto
                        </button>
                    </form>
                </section>

                <!-- Lista de productos -->
                <section class="productos-section">
                    <div class="productos-header">
                        <h2 class="productos-title">Productos Existentes</h2>
                        <span class="productos-count"><?php echo count($productos); ?> productos</span>
                    </div>

                    <!-- Barra de búsqueda -->
                    <div class="search-container-admin">
                        <input type="text" class="search-input-admin" placeholder="Buscar productos por nombre, marca o tipo..." id="search-input-admin">
                        <button class="search-btn-admin" id="search-btn-admin">🔍</button>
                    </div>

                    <div class="productos-grid" id="productos-grid">
                        <?php foreach ($productos as $producto):
                            $stockStatus = $producto['Stock'] > 50 ? 'high' : ($producto['Stock'] > 10 ? 'medium' : 'low');
                            $stockIcon = $producto['Stock'] > 50 ? '📈' : ($producto['Stock'] > 10 ? '⚡' : '⚠️');
                            $hasImage = !empty($producto['ruta']);
                        ?>
                            <article class="producto-admin">
                                <div class="product-image-container <?php echo !$hasImage ? 'no-image' : ''; ?>">
                                    <?php if ($hasImage): ?>
                                        <img src="<?php echo $producto['ruta']; ?>"
                                             alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>"
                                             onerror="this.style.display='none'; this.parentElement.classList.add('image-error');">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <div class="no-image-icon">📷</div>
                                            <div class="no-image-text">Sin imagen</div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="producto-content">
                                    <h4><?php echo htmlspecialchars($producto['NomProducto']); ?></h4>

                                    <div class="producto-info">
                                        <p>
                                            <span class="info-label">Marca:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($producto['Marca']); ?></span>
                                        </p>
                                        <p>
                                            <span class="info-label">Tipo:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($producto['TipoProducto']); ?></span>
                                        </p>
                                        <p>
                                            <span class="info-label">Precio:</span>
                                            <span class="info-value price-highlight">$<?php echo number_format($producto['Precio'], 2); ?></span>
                                        </p>
                                        <p>
                                            <span class="info-label">Stock:</span>
                                            <span class="stock-status stock-<?php echo $stockStatus; ?>">
                                                <span><?php echo $stockIcon; ?></span>
                                                <?php echo $producto['Stock']; ?> unidades
                                            </span>
                                        </p>
                                    </div>

                                    <div class="producto-acciones">
                                        <button class="btn-admin btn-modificar"
                                                onclick="abrirModalModificar('<?php echo $producto['IdProducto']; ?>',
                                                                             '<?php echo htmlspecialchars($producto['NomProducto']); ?>',
                                                                             '<?php echo htmlspecialchars($producto['Marca']); ?>',
                                                                             '<?php echo htmlspecialchars($producto['TipoProducto']); ?>',
                                                                             <?php echo $producto['Precio']; ?>,
                                                                             <?php echo $producto['Precio_Unitario']; ?>,
                                                                             <?php echo $producto['Stock']; ?>)">
                                            <span>✏️</span>
                                            Modificar
                                        </button>

                                        <button class="btn-admin btn-eliminar"
                                                onclick="confirmarEliminar('<?php echo $producto['IdProducto']; ?>', '<?php echo htmlspecialchars($producto['NomProducto']); ?>')">
                                            <span>🗑️</span>
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
    
    <!-- Modal para modificar producto -->
    <div id="modalModificar" class="modal">
        <div class="modal-content">
            <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
            <h3>✏️ Modificar Producto</h3>

            <form method="POST">
                <input type="hidden" name="accion" value="modificar">
                <input type="hidden" id="mod_id_producto" name="id_producto">

                <div class="form-group">
                    <label for="mod_nombre">Nombre del Producto *</label>
                    <input type="text" id="mod_nombre" name="nombre" placeholder="Ej: Cuaderno Profesional" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mod_marca">Marca *</label>
                        <input type="text" id="mod_marca" name="marca" placeholder="Ej: Faber-Castell" required>
                    </div>
                    <div class="form-group">
                        <label for="mod_tipo">Categoría *</label>
                        <select id="mod_tipo" name="tipo" required>
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>">
                                    <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-medium); font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                            Selecciona la categoría a la que pertenece el producto
                        </small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mod_precio">Precio de Venta *</label>
                        <input type="number" id="mod_precio" name="precio" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="mod_precio_unitario">Precio Unitario</label>
                        <input type="number" id="mod_precio_unitario" name="precio_unitario" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="mod_stock">Stock</label>
                    <input type="number" id="mod_stock" name="stock" placeholder="0" min="0">
                </div>

                <button type="submit" class="btn-agregar">
                    <span>💾</span>
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Animación de entrada para las tarjetas de productos
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.producto-admin');
            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Auto-hide mensajes después de 5 segundos
            const mensajes = document.querySelectorAll('.mensaje');
            mensajes.forEach(mensaje => {
                setTimeout(() => {
                    mensaje.style.transition = 'all 0.5s ease';
                    mensaje.style.opacity = '0';
                    setTimeout(() => {
                        mensaje.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });

        function abrirModalModificar(id, nombre, marca, tipo, precio, precioUnitario, stock) {
            document.getElementById('mod_id_producto').value = id;
            document.getElementById('mod_nombre').value = nombre;
            document.getElementById('mod_marca').value = marca;
            document.getElementById('mod_tipo').value = tipo;
            document.getElementById('mod_precio').value = precio;
            document.getElementById('mod_precio_unitario').value = precioUnitario;
            document.getElementById('mod_stock').value = stock;

            document.getElementById('modalModificar').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevenir scroll del body
        }

        function cerrarModal() {
            const modal = document.getElementById('modalModificar');
            modal.style.animation = 'slideOutScale 0.3s ease-out';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.animation = '';
                document.body.style.overflow = 'auto'; // Restaurar scroll del body
            }, 300);
        }

        function confirmarEliminar(id, nombre) {
            // Crear modal de confirmación personalizado
            const confirmModal = document.createElement('div');
            confirmModal.innerHTML = `
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.6);
                    backdrop-filter: blur(4px);
                    z-index: 10001;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease-out;
                ">
                    <div style="
                        background: white;
                        padding: 2rem;
                        border-radius: 16px;
                        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
                        max-width: 400px;
                        width: 90%;
                        text-align: center;
                        animation: slideInScale 0.4s ease-out;
                    ">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                        <h3 style="margin: 0 0 1rem 0; color: #1a202c;">¿Eliminar Producto?</h3>
                        <p style="margin: 0 0 2rem 0; color: #4a5568;">
                            ¿Estás seguro de que quieres eliminar <strong>"${nombre}"</strong>?<br>
                            <small style="color: #e53e3e;">Esta acción no se puede deshacer.</small>
                        </p>
                        <div style="display: flex; gap: 1rem;">
                            <button onclick="this.parentElement.parentElement.parentElement.remove()" style="
                                flex: 1;
                                padding: 0.75rem 1rem;
                                background: #e2e8f0;
                                color: #4a5568;
                                border: none;
                                border-radius: 8px;
                                cursor: pointer;
                                font-weight: 600;
                                transition: all 0.3s ease;
                            ">Cancelar</button>
                            <button onclick="eliminarProducto('${id}'); this.parentElement.parentElement.parentElement.remove()" style="
                                flex: 1;
                                padding: 0.75rem 1rem;
                                background: linear-gradient(135deg, #e53e3e, #c53030);
                                color: white;
                                border: none;
                                border-radius: 8px;
                                cursor: pointer;
                                font-weight: 600;
                                transition: all 0.3s ease;
                            ">Eliminar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmModal);
        }

        function eliminarProducto(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_producto" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalModificar');
            if (event.target === modal) {
                cerrarModal();
            }
        }

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('modalModificar');
                if (modal.style.display === 'block') {
                    cerrarModal();
                }
            }
        });

        // Mejorar UX de formularios
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // ===================================================
        // FUNCIONALIDAD DE BÚSQUEDA
        // ===================================================
        const searchInputAdmin = document.getElementById('search-input-admin');
        const searchBtnAdmin = document.getElementById('search-btn-admin');
        const productosGrid = document.getElementById('productos-grid');
        const productosCount = document.querySelector('.productos-count');

        function searchProductsAdmin() {
            const searchTerm = searchInputAdmin.value.toLowerCase().trim();
            const productCards = productosGrid.querySelectorAll('.producto-admin');
            let visibleCount = 0;

            productCards.forEach(card => {
                const productName = card.querySelector('h4').textContent.toLowerCase();
                const productInfo = card.querySelector('.producto-info').textContent.toLowerCase();

                if (productName.includes(searchTerm) || productInfo.includes(searchTerm)) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeInUp 0.5s ease-out';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Actualizar contador
            productosCount.textContent = `${visibleCount} producto${visibleCount !== 1 ? 's' : ''}`;

            // Mostrar mensaje si no hay resultados
            let noResultsMsg = productosGrid.querySelector('.no-results-message');
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results-message';
                    noResultsMsg.innerHTML = `
                        <div style="
                            text-align: center;
                            padding: 3rem 2rem;
                            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
                            border-radius: var(--radius-lg);
                            border: 2px dashed var(--border-color);
                            margin: 2rem 0;
                        ">
                            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🔍</div>
                            <h3 style="color: var(--text-dark); margin: 0 0 0.5rem 0;">No se encontraron productos</h3>
                            <p style="color: var(--text-medium); margin: 0;">
                                No hay productos que coincidan con "<strong>${searchTerm}</strong>"
                            </p>
                        </div>
                    `;
                    productosGrid.appendChild(noResultsMsg);
                }
            } else {
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        }

        // Event listeners para búsqueda
        if (searchInputAdmin) {
            searchInputAdmin.addEventListener('input', searchProductsAdmin);
        }

        if (searchBtnAdmin) {
            searchBtnAdmin.addEventListener('click', searchProductsAdmin);
        }

        // Búsqueda con Enter
        if (searchInputAdmin) {
            searchInputAdmin.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchProductsAdmin();
                }
            });
        }
    </script>
</body>
</html>