<?php
session_start();
require_once 'conexion.php';
require_once 'auth.php';

// Verificar autenticación del usuario
$usuarioLogueado = estaLogueado();
$esAdmin = estaLogueado() && esAdmin();
$usuarioActual = null;
if ($usuarioLogueado) {
    $usuarioActual = obtenerUsuarioActual();
    
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nombre'])) {
        $usuarioActual = [
            'IdUsuario' => $_SESSION['usuario_id'],
            'Nombre' => $_SESSION['usuario_nombre'],
            'Correo' => $_SESSION['usuario_email'] ?? ''
        ];
    }
}

// Obtener parámetros de URL
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$subcategoria = isset($_GET['subcategoria']) ? $_GET['subcategoria'] : '';

// Contar items en carrito
$cantidadCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        $cantidadCarrito += $cantidad;
    }
}

// =============================================
// FUNCIÓN PARA OBTENER CATEGORÍAS CON SUBCATEGORÍAS
// =============================================
function obtenerCategoriasConSubcategorias($conn) {
    $categorias = [];
    
    // 1. Obtener categorías principales
    $sql_categorias = "
        SELECT Id, Nombre_Categoria 
        FROM Categoria 
        WHERE IdCategoriaPadre IS NULL 
        ORDER BY Nombre_Categoria
    ";
    
    $result_categorias = mysqli_query($conn, $sql_categorias);
    
    if ($result_categorias && mysqli_num_rows($result_categorias) > 0) {
        // 2. Para cada categoría padre, obtener subcategorías
        while ($categoria_padre = mysqli_fetch_assoc($result_categorias)) {
            $categoria_id = $categoria_padre['Id'];
            $categoria_nombre = $categoria_padre['Nombre_Categoria'];
            
            // Obtener subcategorías
            $sql_subcategorias = "
                SELECT Nombre_Categoria
                FROM Categoria 
                WHERE IdCategoriaPadre = '$categoria_id'
                ORDER BY Nombre_Categoria
            ";
            
            $result_subcategorias = mysqli_query($conn, $sql_subcategorias);
            $subcategorias = [];
            
            if ($result_subcategorias && mysqli_num_rows($result_subcategorias) > 0) {
                while ($subcategoria = mysqli_fetch_assoc($result_subcategorias)) {
                    $subcategorias[] = $subcategoria['Nombre_Categoria'];
                }
            }
            
            // Si no hay subcategorías, obtener productos directamente
            if (empty($subcategorias)) {
                $sql_productos = "
                    SELECT DISTINCT a.NomProducto
                    FROM articulo a
                    JOIN producto_categoria pc ON a.IdProducto = pc.Id_Producto
                    JOIN Categoria c ON pc.Id_Categoria = c.Id
                    WHERE c.Id = '$categoria_id'
                    ORDER BY a.NomProducto
                    LIMIT 8
                ";
                
                $result_productos = mysqli_query($conn, $sql_productos);
                if ($result_productos && mysqli_num_rows($result_productos) > 0) {
                    while ($producto = mysqli_fetch_assoc($result_productos)) {
                        $subcategorias[] = $producto['NomProducto'];
                    }
                }
            }
            
            // Agregar al array de categorías
            $categorias[$categoria_nombre] = $subcategorias;
        }
    } else {
        // Fallback a datos estáticos si no hay datos en BD
        $categorias = [
            "ARTE" => [
                "Acrílico profesional",
                "Artículo para arte",
                "Acrílicos para tela",
                "Acrílica neon",
                "Barniz",
                "Barniz en aerosol"
            ],
            "PAPELES Y DERIVADOS" => [
                "Bollo de lana",
                "Carboncillos",
                "Fieltro",
                "Gesso transparente",
                "Lápiz de color acuarelables",
                "Lienzo"
            ],
            "ESCOLAR" => [
                "Lápiz graduado",
                "Lápiz 3 técnicas",
                "Laca plástica",
                "Óleo",
                "Plumillas"
            ],
            "OFICINA" => [],
            "INFANTIL" => [
                "Plumones profesionales",
                "Pinceles",
                "Prismacolor",
                "Spray",
                "Tela das"
            ]
        ];
    }
    
    return $categorias;
}

$categorias = obtenerCategoriasConSubcategorias($conn);

// =============================================
// OBTENER PRODUCTOS FILTRADOS
// =============================================
function obtenerProductosFiltrados($conn, $categoria, $subcategoria) {
    $sql = "";
    $productos = [];
    
    if (!empty($subcategoria)) {
        // Buscar por subcategoría específica
        $sql = "
            SELECT DISTINCT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, 
                   a.Precio, a.Precio_Unitario, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            JOIN producto_categoria pc ON a.IdProducto = pc.Id_Producto
            JOIN Categoria c ON pc.Id_Categoria = c.Id
            JOIN Categoria cp ON c.IdCategoriaPadre = cp.Id
            WHERE (c.Nombre_Categoria LIKE '%{$subcategoria}%' OR 
                   a.NomProducto LIKE '%{$subcategoria}%' OR
                   a.TipoProducto LIKE '%{$subcategoria}%')
            ORDER BY a.NomProducto
        ";
    } elseif (!empty($categoria)) {
        // Buscar por categoría principal
        $sql = "
            SELECT DISTINCT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, 
                   a.Precio, a.Precio_Unitario, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            JOIN producto_categoria pc ON a.IdProducto = pc.Id_Producto
            JOIN Categoria c ON pc.Id_Categoria = c.Id
            LEFT JOIN Categoria cp ON c.IdCategoriaPadre = cp.Id
            WHERE (c.Nombre_Categoria LIKE '%{$categoria}%' OR 
                   cp.Nombre_Categoria LIKE '%{$categoria}%' OR
                   a.TipoProducto LIKE '%{$categoria}%')
            ORDER BY a.NomProducto
        ";
    }
    
    if (!empty($sql)) {
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $productos[] = $row;
            }
        }
    }
    
    return $productos;
}

$productos_filtrados = obtenerProductosFiltrados($conn, $categoria, $subcategoria);

// Determinar el título de la página
$titulo_pagina = "Todos los Productos";
if (!empty($subcategoria)) {
    $titulo_pagina = htmlspecialchars($subcategoria);
} elseif (!empty($categoria)) {
    $titulo_pagina = htmlspecialchars($categoria);
}

// Obtener productos para mostrar
if (empty($categoria) && empty($subcategoria)) {
    // Si no hay filtros, obtener todos los productos
    $sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd";
    $result_productos = $conn->query($sql);
    $productos_a_mostrar = [];
    if ($result_productos && $result_productos->num_rows > 0) {
        while ($row = $result_productos->fetch_assoc()) {
            $productos_a_mostrar[] = $row;
        }
    }
} else {
    $productos_a_mostrar = $productos_filtrados;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Productos - Librería RL</title>
    <style>
        :root {
            --primary-color: #120049;
            --primary-light: #1a0066;
            --secondary-color: #ffb703;
            --secondary-dark: #ff9500;
            --accent-color: #ff6b35;
            --text-dark: #1a202c;
            --text-medium: #4a5568;
            --text-light: #718096;
            --background-light: #f7fafc;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --success-color: #38a169;
            --error-color: #e53e3e;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Header mejorado */
        .header {
            background: var(--primary-color);
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }

        .logo .star {
            color: var(--secondary-color);
            font-size: 1.8rem;
        }

        .search-container {
            flex: 1;
            max-width: 600px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 3rem 0.75rem 1rem;
            border: none;
            border-radius: 50px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-input:focus {
            box-shadow: 0 4px 12px rgba(255, 183, 3, 0.3);
            transform: translateY(-1px);
        }

        .search-btn {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--secondary-color);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: var(--secondary-dark);
            transform: translateY(-50%) scale(1.05);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .cart-count {
            background: var(--secondary-color);
            color: var(--primary-color);
            padding: 0.125rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        /* === ESTILOS PARA AUTENTICACIÓN === */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .auth-container {
            position: relative;
            display: inline-block;
        }

        .auth-link {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .auth-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        #auth-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            min-width: 320px;
            max-width: 400px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        #auth-dropdown.mostrar {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .auth-dropdown-oculto {
            display: none !important;
        }

        .auth-form {
            padding: 25px;
            display: block;
        }

        .auth-form-oculto {
            display: none !important;
        }

        .auth-form h3 {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-row {
            display: flex;
            gap: 10px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(255, 183, 3, 0.1);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .btn-auth {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-auth:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            transform: translateY(-2px);
        }

        .auth-separator {
            text-align: center;
            margin: 20px 0;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .auth-separator span {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-size: 13px;
        }

        .auth-separator a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
        }

        .auth-separator a:hover {
            text-decoration: underline;
        }

        /* === USUARIO LOGUEADO === */
        .usuario-logueado {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .usuario-info {
            color: white !important;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 14px;
            white-space: nowrap;
        }

        .usuario-info:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-usuario {
            position: relative;
        }

        .dropdown-usuario-content {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            min-width: 200px;
            max-width: 280px;
            z-index: 1100;
            display: none;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-8px) scale(0.95);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .dropdown-usuario:hover .dropdown-usuario-content {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .dropdown-usuario-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            color: #2c3e50;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .dropdown-usuario-content a:last-child {
            border-bottom: none;
        }

        .dropdown-usuario-content a:hover {
            background: linear-gradient(135deg, rgba(18, 0, 73, 0.05) 0%, rgba(255, 183, 3, 0.08) 100%);
            color: var(--primary-color);
            transform: translateX(4px);
            padding-left: 22px;
        }

        /* === CARRITO === */
        .carrito-container {
            position: relative;
            display: inline-block;
        }

        .carrito-link {
            color: white !important;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .carrito-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        #contador-carrito {
            background: var(--secondary-color);
            color: var(--primary-color);
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }

        /* === NOTIFICACIONES TOAST === */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 350px;
        }

        .toast {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            padding: 20px;
            border-left: 4px solid var(--success-color);
            animation: slideInRight 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        .toast.success {
            border-left-color: var(--success-color);
        }

        .toast.error {
            border-left-color: var(--error-color);
        }

        .toast.warning {
            border-left-color: var(--warning-color);
        }

        .toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            animation: slideProgress 3s linear;
        }

        @keyframes slideProgress {
            from { width: 100%; }
            to { width: 0%; }
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .toast-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition-fast);
        }

        .toast-close:hover {
            color: var(--error-color);
        }

        .toast-message {
            color: var(--text-medium);
            font-size: 13px;
            line-height: 1.4;
        }

        /* Navegación secundaria */
        .nav-secondary {
            background: rgba(18, 0, 73, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: center;
            gap: 2rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255, 183, 3, 0.1);
            color: var(--secondary-color);
        }

        /* Dropdown de Categorías */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background: none !important;
            pading: 1rem 1.5rem;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-family: inherit;
            color: inherit;
            text-decoration: none;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 800px;
            max-width: 90vw;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: var(--radius-lg);
            z-index: 1000;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .categorias-container {
            display: flex;
            flex-wrap: wrap;
            padding: 1rem;
        }

        .columna {
            flex: 1;
            min-width: 200px;
            padding: 0 1rem;
            border-right: 1px solid var(--border-color);
        }

        .columna:last-child {
            border-right: none;
        }

        .columna h4 {
            color: var(--primary-color);
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary-color);
        }

        .columna ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .columna li {
            margin-bottom: 0.5rem;
        }

        .columna a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.25rem 0;
            display: block;
            transition: all 0.3s ease;
            border-radius: var(--radius-sm);
        }

        .columna a:hover {
            color: var(--secondary-color);
            background: rgba(255, 183, 3, 0.1);
            padding-left: 0.5rem;
        }

        /* Responsive para Dropdown */
        @media (max-width: 768px) {
            .dropdown-content {
                min-width: 300px;
                max-width: 95vw;
                left: -50px;
            }

            .categorias-container {
                flex-direction: column;
                padding: 0.5rem;
            }

            .columna {
                min-width: auto;
                padding: 0.5rem;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }

            .columna:last-child {
                border-bottom: none;
            }
        }

        /* Breadcrumb mejorado */
        .breadcrumb {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .breadcrumb-list {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            list-style: none;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .breadcrumb-item {
            color: var(--text-medium);
            font-size: 0.9rem;
        }

        .breadcrumb-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-link:hover {
            color: var(--secondary-color);
        }

        .breadcrumb-separator {
            color: var(--text-light);
            margin: 0 0.5rem;
        }

        /* Header de productos */
        .products-header {
            max-width: 1400px;
            margin: 0 auto 2rem;
            padding: 0 2rem;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .results-count {
            color: var(--text-medium);
            font-size: 1rem;
        }

        /* Filtros mejorados */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: white;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(255, 183, 3, 0.1);
        }

        .view-toggle {
            display: flex;
            background: var(--background-light);
            border-radius: var(--radius-md);
            padding: 0.25rem;
        }

        .view-btn {
            padding: 0.5rem;
            border: none;
            background: transparent;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn.active {
            background: white;
            box-shadow: var(--shadow-sm);
        }

        /* Grid de productos mejorado */
        .products-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 4rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .products-grid.list-view {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        /* Tarjeta de producto mejorada */
        .product-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            group: hover;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .product-image-container {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            background: var(--background-light);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-new {
            background: var(--error-color);
            color: white;
        }

        .badge-sale {
            background: var(--secondary-color);
            color: var(--primary-color);
        }

        .product-actions {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .action-btn {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .action-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-brand {
            color: var(--text-light);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price-container {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .product-price-unit {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .add-to-cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .add-to-cart-btn:hover::before {
            left: 100%;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 183, 3, 0.4);
        }

        /* Vista de lista */
        .product-card.list-view {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding: 1.5rem;
        }

        .product-card.list-view .product-image-container {
            width: 120px;
            flex-shrink: 0;
            aspect-ratio: 1;
        }

        .product-card.list-view .product-info {
            flex: 1;
            padding: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-card.list-view .add-to-cart-btn {
            width: auto;
            padding: 0.5rem 1.5rem;
        }

        /* Estado vacío mejorado */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin: 2rem auto;
            max-width: 600px;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-medium);
            margin-bottom: 2rem;
        }

        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .suggestion-link {
            padding: 1rem;
            background: var(--background-light);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }

        .suggestion-link:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .product-skeleton .product-image-container {
            background: var(--background-light);
        }

        .product-skeleton .skeleton-line {
            height: 1rem;
            margin: 0.5rem 0;
            border-radius: var(--radius-sm);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .header-content {
                padding: 1rem;
                gap: 1rem;
            }

            .search-container {
                order: 3;
                width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .filter-group {
                justify-content: space-between;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .product-card.list-view {
                flex-direction: column;
                text-align: center;
            }

            .product-card.list-view .product-image-container {
                width: 100%;
                max-width: 200px;
                margin: 0 auto;
            }

            .product-card.list-view .product-info {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-content {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(n+5) { animation-delay: 0.5s; }

        /* Estilos para la vista previa del carrito */
        .cart-btn {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .cart-count {
            background: var(--secondary-color);
            color: var(--primary-color);
            padding: 0.125rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        .vista-previa-oculta {
            display: none !important;
        }

        .vista-previa-carrito {
            position: fixed;
            width: 350px;
            background: white !important;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            z-index: 3000;
            overflow: hidden;
            animation: slideUp 0.3s ease-out;
            border: 1px solid var(--border-color);
            top: 80px; /* Posición inicial, se ajustará con JS */
            right: 20px; /* Posición inicial, se ajustará con JS */
        }

        .vista-previa-carrito.mostrar {
            display: block !important;
        }

        .carrito-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--primary-color);
            color: white;
        }

        .carrito-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .cerrar-vista-previa {
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            transition: opacity 0.2s ease;
        }

        .cerrar-vista-previa:hover {
            opacity: 0.7;
        }

        #productos-vista-previa {
            max-height: 300px;
            overflow-y: auto;
            padding: 0;
        }

        .carrito-loading {
            padding: 2rem;
            text-align: center;
            color: var(--text-medium);
        }

        .producto-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .producto-preview:last-child {
            border-bottom: none;
        }

        .producto-preview img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: var(--radius-md);
        }

        .producto-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .producto-info p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-medium);
        }

        .carrito-vacio {
            padding: 2rem;
            text-align: center;
            color: var(--text-medium);
        }

        .carrito-error {
            padding: 2rem;
            text-align: center;
            color: var(--error-color);
        }

        .carrito-footer {
            padding: 1rem 1.5rem;
            background: var(--background-light);
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .btn-ver-carrito {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-ver-carrito:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Header Principal -->
    <header class="header">
        <div class="header-content">
            <a href="home.php" class="logo">
                <span>Librería RL</span>
                <span class="star">★</span>
            </a>

            <div class="search-container">
                <input type="text" class="search-input" placeholder="¿Qué estás buscando?" id="search-input">
                <button class="search-btn" id="search-btn">🔍</button>
            </div>

            <!-- NAVEGACIÓN DEL USUARIO -->
            <nav class="nav-links">
                <?php if ($usuarioLogueado): ?>
                    <!-- USUARIO LOGUEADO -->
                    <div class="usuario-logueado">
                        <!-- Info del usuario -->
                        <div class="dropdown-usuario">
                            <a href="#" class="usuario-info">
                                👤 Hola, <?php echo htmlspecialchars($usuarioActual['Nombre'] ?? ($usuarioActual['usuario'] ?? 'Usuario')); ?>
                                 <?php if ($esAdmin): ?>
                                    <span style="color: #ffc107; font-weight: bold;">[ADMIN]</span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-usuario-content">
                                <?php if ($esAdmin): ?>
                                    <a href="admin_productos.php" style="background: #ffc107; color: #000; font-weight: bold;">🛠️ Panel Admin</a>
                                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                                <?php endif; ?>
                                <a href="#">Mi Perfil</a>
                                <a href="#">Mis Pedidos</a>
                                <a href="#" onclick="cerrarSesion()">🚪 Cerrar Sesión</a>
                            </div>
                        </div>

                        <!-- Carrito al lado del usuario -->
                        <div class="carrito-container">
                            <a href="#" id="carrito-toggle" class="cart-btn">
                                🛒 <span id="contador-carrito" class="cart-count"><?php echo $cantidadCarrito; ?></span>
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- USUARIO NO LOGUEADO -->
                    <div class="auth-container">
                        <a href="#" id="login-toggle" class="auth-link">Iniciar Sesión</a>

                        <!-- DROPDOWN DE AUTENTICACIÓN -->
                        <div id="auth-dropdown" class="auth-dropdown-oculto">
                            <!-- FORMULARIO DE LOGIN -->
                            <div id="login-form" class="auth-form">
                                <h3>Iniciar Sesión</h3>

                                <form id="form-login" method="post">
                                    <div class="form-group">
                                        <input type="email"
                                               id="login-email"
                                               name="email"
                                               placeholder="Correo electrónico"
                                               required
                                               autocomplete="email">
                                    </div>
                                    <div class="form-group">
                                        <input type="password"
                                               id="login-password"
                                               name="password"
                                               placeholder="Contraseña"
                                               required
                                               autocomplete="current-password">
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-container">
                                            <input type="checkbox" id="recordarme" name="recordarme" value="1">
                                            <span>Recordarme</span>
                                        </label>
                                    </div>
                                    <button type="submit" class="btn-auth">Iniciar Sesión</button>
                                </form>

                                <div class="auth-separator">
                                    <span>¿No tienes cuenta?</span>
                                    <a href="#" id="mostrar-registro">Regístrate aquí</a>
                                </div>
                            </div>

                            <!-- FORMULARIO DE REGISTRO -->
                            <div id="register-form" class="auth-form auth-form-oculto">
                                <h3>Crear Cuenta</h3>

                                <form id="form-registro" method="post">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <input type="text"
                                                   id="registro-nombre"
                                                   name="nombre"
                                                   placeholder="Nombre completo"
                                                   required
                                                   autocomplete="name">
                                        </div>
                                        <div class="form-group">
                                            <input type="email"
                                                   id="registro-email"
                                                   name="email"
                                                   placeholder="Correo electrónico"
                                                   required
                                                   autocomplete="email">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <input type="password"
                                               id="registro-password"
                                               name="password"
                                               placeholder="Contraseña"
                                               required
                                               autocomplete="new-password">
                                    </div>
                                    <div class="form-group">
                                        <input type="password"
                                               id="registro-confirm-password"
                                               name="confirm_password"
                                               placeholder="Confirmar contraseña"
                                               required
                                               autocomplete="new-password">
                                    </div>
                                    <button type="submit" class="btn-auth">Crear Cuenta</button>
                                </form>

                                <div class="auth-separator">
                                    <span>¿Ya tienes cuenta?</span>
                                    <a href="#" id="mostrar-login">Inicia sesión aquí</a>
                                </div>
                            </div>

                            <!-- FORMULARIO DE VERIFICACIÓN -->
                            <div id="verify-form" class="auth-form auth-form-oculto">
                                <h3>Verificar Email</h3>
                                <p>Te hemos enviado un código de verificación a tu email.</p>

                                <form id="form-verificacion" method="post">
                                    <div class="form-group">
                                        <input type="text"
                                               id="codigo-verificacion"
                                               name="codigo"
                                               placeholder="Código de verificación"
                                               required
                                               maxlength="6">
                                    </div>
                                    <button type="submit" class="btn-auth">Verificar</button>
                                </form>

                                <div class="auth-separator">
                                    <a href="#" id="reenviar-codigo">¿No recibiste el código? Reenviar</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Cart Preview (fuera del header para evitar estiramiento) -->
    <div id="vista-previa-carrito" class="vista-previa-oculta">
        <div class="carrito-header">
            <h3>Mi Carrito</h3>
            <span class="cerrar-vista-previa">&times;</span>
        </div>
        <div id="productos-vista-previa">
            <div class="carrito-loading">
                <p>Cargando carrito...</p>
            </div>
        </div>
        <div class="carrito-footer">
            <a href="vercarrito.php" class="btn-ver-carrito">Ver Carrito Completo</a>
        </div>
    </div>

    <!-- Navegación Secundaria con Categorías Dinámicas -->
    <nav class="nav-secondary">
        <div class="nav-content">
            <a href="home.php" class="nav-link">INICIO</a>
            <div class="dropdown">
                <a href="#" class="nav-link" class="dropbtn">CATEGORÍAS ▼</a>
                <div class="dropdown-content">
                    <div class="categorias-container">
                        <?php foreach ($categorias as $categoria => $subcategorias): ?>
                            <div class="columna">
                                <h4><?php echo htmlspecialchars($categoria); ?></h4>
                                <ul>
                                    <?php if (!empty($subcategorias)): ?>
                                        <?php foreach ($subcategorias as $sub): ?>
                                            <li>
                                                <a href="productos.php?categoria=<?php echo urlencode($categoria); ?>&subcategoria=<?php echo urlencode($sub); ?>">
                                                    <?php echo htmlspecialchars($sub); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>
                                            <a href="productos.php?categoria=<?php echo urlencode($categoria); ?>">
                                                Ver <?php echo htmlspecialchars($categoria); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <a href="#" class="nav-link">SOBRE NOSOTROS</a>
            <a href="#" class="nav-link">CONTACTO</a>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="home.php" class="breadcrumb-link">Inicio</a>
            </li>
            <li class="breadcrumb-separator">→</li>
            <li class="breadcrumb-item">
                <a href="#" class="breadcrumb-link">Arte</a>
            </li>
            <li class="breadcrumb-separator">→</li>
            <li class="breadcrumb-item"><?php echo $titulo_pagina; ?></li>
        </ol>
    </div>

    <!-- Header de Productos -->
    <div class="products-header">
        <div class="header-top">
            <h1 class="page-title"><?php echo $titulo_pagina; ?></h1>
            <span class="results-count"><?php echo count($productos_a_mostrar); ?> productos encontrados</span>
        </div>
        
        <div class="filters-bar">
            <div class="filter-group">
                <select class="filter-select" id="sort-select">
                    <option value="name">Ordenar por Nombre</option>
                    <option value="price-low">Precio: Menor a Mayor</option>
                    <option value="price-high">Precio: Mayor a Menor</option>
                    <option value="brand">Ordenar por Marca</option>
                </select>
            </div>
            
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Vista de cuadrícula">⊞</button>
                <button class="view-btn" data-view="list" title="Vista de lista">☰</button>
            </div>
        </div>
    </div>

    <!-- Contenedor de Productos -->
    <div class="products-container">
        <div class="products-grid" id="products-grid">
            <?php
            if (!empty($productos_a_mostrar)) {
                $contador = 0;
                foreach ($productos_a_mostrar as $row) {
                    $imgSrc = $row['ruta'] ? htmlspecialchars($row['ruta']) : "";
                    $hasImage = !empty($row['ruta']);
                    $badgeClass = '';
                    $badgeText = '';
                    if ($contador < 3) {
                        $badgeClass = 'badge-new';
                        $badgeText = 'Nuevo';
                    } elseif ($row['Precio'] < 5) {
                        $badgeClass = 'badge-sale';
                        $badgeText = 'Oferta';
                    }
                    ?>
                    <article class="product-card" 
                             data-name="<?php echo htmlspecialchars($row['NomProducto']); ?>" 
                             data-price="<?php echo $row['Precio']; ?>" 
                             data-brand="<?php echo htmlspecialchars($row['Marca']); ?>">
                        <div class="product-image-container <?php echo !$hasImage ? 'no-image' : ''; ?>">
                            <?php if ($hasImage): ?>
                                <img src="<?php echo $imgSrc; ?>" 
                                     alt="<?php echo htmlspecialchars($row['NomProducto']); ?>" 
                                     class="product-image" 
                                     loading="lazy" 
                                     onerror="this.style.display='none'; this.parentElement.classList.add('image-error');">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <div class="no-image-icon">📷</div>
                                    <div class="no-image-text">Sin imagen</div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($badgeClass)): ?>
                                <div class="product-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></div>
                            <?php endif; ?>
                            <div class="product-actions">
                                <button class="action-btn" title="Vista rápida">👁️</button>
                                <button class="action-btn" title="Agregar a favoritos">♡</button>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-brand"><?php echo htmlspecialchars($row['Marca']); ?></div>
                            <h3 class="product-title"><?php echo htmlspecialchars($row['NomProducto']); ?></h3>
                            <div class="product-price-container">
                                <span class="product-price">$<?php echo number_format($row['Precio'], 2); ?></span>
                                <span class="product-price-unit">c/u</span>
                            </div>
                            <button class="add-to-cart-btn" data-id="<?php echo $row['IdProducto']; ?>">Agregar al Carrito</button>
                        </div>
                    </article>
                    <?php
                    $contador++;
                }
            } else {
                echo '<p>No hay productos disponibles</p>';
            }
            ?>
        </div>

        <!-- Estado vacío (ejemplo, oculto por defecto) -->
        <div class="empty-state" style="display: none;" id="empty-state">
            <div class="empty-state-icon">🎨</div>
            <h2>No se encontraron productos</h2>
            <p>No hay productos disponibles que coincidan con tu búsqueda.</p>
            <div class="suggestions-grid">
                <a href="#" class="suggestion-link">Productos de Arte</a>
                <a href="#" class="suggestion-link">Artículos Escolares</a>
                <a href="#" class="suggestion-link">Productos de Oficina</a>
                <a href="#" class="suggestion-link">Ver todos</a>
            </div>
        </div>
    </div>

    <!-- Contenedor para notificaciones flotantes -->
    <div id="notificaciones-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000; pointer-events: none;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ===================================================
            // ELEMENTOS DEL DOM PARA AUTENTICACIÓN
            // ===================================================
            const loginToggle = document.getElementById('login-toggle');
            const authDropdown = document.getElementById('auth-dropdown');
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const verifyForm = document.getElementById('verify-form');
            const formLogin = document.getElementById('form-login');
            const formRegistro = document.getElementById('form-registro');
            const formVerificacion = document.getElementById('form-verificacion');
            const mostrarRegistro = document.getElementById('mostrar-registro');
            const mostrarLogin = document.getElementById('mostrar-login');
            const reenviarCodigo = document.getElementById('reenviar-codigo');

            let authOverlay = null;
            let dropdownAbierto = false;

            // ===================================================
            // FUNCIONES DE AUTENTICACIÓN
            // ===================================================

            function crearAuthOverlay() {
                if (!authOverlay) {
                    authOverlay = document.createElement('div');
                    authOverlay.className = 'auth-overlay';
                    authOverlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.3);
                        z-index: 999;
                        opacity: 0;
                        visibility: hidden;
                        transition: all 0.3s ease;
                    `;
                    authOverlay.addEventListener('click', cerrarDropdown);
                    document.body.appendChild(authOverlay);
                }
                return authOverlay;
            }

            function abrirDropdown() {
                if (authDropdown && !dropdownAbierto) {
                    authDropdown.classList.remove('auth-dropdown-oculto');
                    setTimeout(() => {
                        authDropdown.classList.add('mostrar');
                    }, 10);

                    const overlay = crearAuthOverlay();
                    overlay.style.opacity = '1';
                    overlay.style.visibility = 'visible';
                    dropdownAbierto = true;
                }
            }

            function cerrarDropdown() {
                if (authDropdown && dropdownAbierto) {
                    authDropdown.classList.remove('mostrar');
                    setTimeout(() => {
                        authDropdown.classList.add('auth-dropdown-oculto');
                    }, 300);

                    if (authOverlay) {
                        authOverlay.style.opacity = '0';
                        authOverlay.style.visibility = 'hidden';
                    }
                    dropdownAbierto = false;
                }
            }

            function mostrarFormulario(formulario) {
                // Ocultar todos los formularios
                if (loginForm) loginForm.classList.add('auth-form-oculto');
                if (registerForm) registerForm.classList.add('auth-form-oculto');
                if (verifyForm) verifyForm.classList.add('auth-form-oculto');

                // Mostrar el formulario seleccionado
                if (formulario) {
                    formulario.classList.remove('auth-form-oculto');
                }
            }

            function deshabilitarBoton(boton, texto = 'Procesando...') {
                if (boton) {
                    boton.disabled = true;
                    boton.textContent = texto;
                }
            }

            function habilitarBoton(boton, textoOriginal) {
                if (boton) {
                    boton.disabled = false;
                    boton.textContent = textoOriginal;
                }
            }

            function mostrarNotificacion(mensaje, tipo = 'success') {
                const notificacionesContainer = document.getElementById('notificaciones-container');
                if (!notificacionesContainer) return;

                const notificacion = document.createElement('div');
                notificacion.className = `toast ${tipo}`;
                notificacion.innerHTML = `
                    <div class="toast-header">
                        <span class="toast-title">${tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : 'Info'}</span>
                        <button class="toast-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                    </div>
                    <div class="toast-message">${mensaje}</div>
                `;

                notificacionesContainer.appendChild(notificacion);

                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    if (notificacion.parentElement) {
                        notificacion.remove();
                    }
                }, 5000);
            }

            function cerrarSesion() {
                fetch('logout_proceso.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cerrar_sesion=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        mostrarNotificacion('Sesión cerrada correctamente', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        mostrarNotificacion(data.mensaje || 'Error al cerrar sesión', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error de conexión', 'error');
                });
            }

            // ===================================================
            // EVENT LISTENERS PARA AUTENTICACIÓN
            // ===================================================

            if (loginToggle) {
                loginToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    abrirDropdown();
                });
            }

            if (mostrarRegistro) {
                mostrarRegistro.addEventListener('click', function(e) {
                    e.preventDefault();
                    mostrarFormulario(registerForm);
                });
            }

            if (mostrarLogin) {
                mostrarLogin.addEventListener('click', function(e) {
                    e.preventDefault();
                    mostrarFormulario(loginForm);
                });
            }

            // LOGIN
            if (formLogin) {
                formLogin.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    deshabilitarBoton(submitBtn, 'Iniciando sesión...');

                    fetch('login_proceso.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exito) {
                            mostrarNotificacion('¡Bienvenido!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            mostrarNotificacion(data.mensaje || 'Error en el login', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mostrarNotificacion('Error de conexión', 'error');
                    })
                    .finally(() => {
                        habilitarBoton(submitBtn, 'Iniciar Sesión');
                    });
                });
            }

            // REGISTRO
            if (formRegistro) {
                formRegistro.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    deshabilitarBoton(submitBtn, 'Creando cuenta...');

                    fetch('registro_proceso.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exito) {
                            mostrarNotificacion('¡Registro exitoso! Verifica tu email.', 'success');
                            mostrarFormulario(verifyForm);
                        } else {
                            mostrarNotificacion(data.mensaje || 'Error en el registro', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mostrarNotificacion('Error de conexión', 'error');
                    })
                    .finally(() => {
                        habilitarBoton(submitBtn, 'Crear Cuenta');
                    });
                });
            }

            // VERIFICACIÓN
            if (formVerificacion) {
                formVerificacion.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    deshabilitarBoton(submitBtn, 'Verificando...');

                    fetch('verificar_proceso.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exito) {
                            mostrarNotificacion('¡Email verificado! Ya puedes iniciar sesión.', 'success');
                            setTimeout(() => {
                                mostrarFormulario(loginForm);
                            }, 2000);
                        } else {
                            mostrarNotificacion(data.mensaje || 'Código inválido', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mostrarNotificacion('Error de conexión', 'error');
                    })
                    .finally(() => {
                        habilitarBoton(submitBtn, 'Verificar');
                    });
                });
            }

            // REENVIAR CÓDIGO
            if (reenviarCodigo) {
                reenviarCodigo.addEventListener('click', function(e) {
                    e.preventDefault();
                    mostrarNotificacion('Función de reenvío próximamente', 'info');
                });
            }

            // Cerrar dropdown con Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && dropdownAbierto) {
                    cerrarDropdown();
                }
            });

            // ===================================================
            // FUNCIONALIDAD EXISTENTE DEL CARRITO Y PRODUCTOS
            // ===================================================

            // Elementos del DOM
            const searchInput = document.getElementById('search-input');
            const searchBtn = document.getElementById('search-btn');
            const sortSelect = document.getElementById('sort-select');
            const viewBtns = document.querySelectorAll('.view-btn');
            const productsGrid = document.getElementById('products-grid');
            const productCards = document.querySelectorAll('.product-card');
            const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
            const emptyState = document.getElementById('empty-state');

            // Variables
            let currentView = 'grid';
            let cartCount = <?php echo $cantidadCarrito; ?>;
            let carritoAbierto = false;
            let carritoOverlay = null;

            // Elementos del carrito
            const carritoToggle = document.getElementById('carrito-toggle');
            const vistaPrevia = document.getElementById('vista-previa-carrito');
            const cerrarVistaPrevia = document.querySelector('.cerrar-vista-previa');
            const productosVistaPrevia = document.getElementById('productos-vista-previa');
            const contadorCarrito = document.getElementById('contador-carrito');

            // Funcionalidad de búsqueda
            function searchProducts() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                productCards.forEach(card => {
                    const name = card.dataset.name.toLowerCase();
                    const brand = card.dataset.brand.toLowerCase();
                    
                    if (name.includes(searchTerm) || brand.includes(searchTerm) || searchTerm === '') {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Actualizar contador de resultados
                const resultsCount = document.querySelector('.results-count');
                resultsCount.textContent = `${visibleCount} productos encontrados`;

                // Mostrar/ocultar estado vacío
                if (visibleCount === 0) {
                    productsGrid.style.display = 'none';
                    emptyState.style.display = 'block';
                } else {
                    productsGrid.style.display = 'grid';
                    emptyState.style.display = 'none';
                }
            }

            // Funcionalidad de ordenamiento
            function sortProducts() {
                const sortValue = sortSelect.value;
                const cardsArray = Array.from(productCards);

                cardsArray.sort((a, b) => {
                    switch(sortValue) {
                        case 'name':
                            return a.dataset.name.localeCompare(b.dataset.name);
                        case 'price-low':
                            return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                        case 'price-high':
                            return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                        case 'brand':
                            return a.dataset.brand.localeCompare(b.dataset.brand);
                        default:
                            return 0;
                    }
                });

                // Reordenar elementos en el DOM
                cardsArray.forEach(card => {
                    productsGrid.appendChild(card);
                });
            }

            // Cambio de vista (grid/lista)
            function changeView(view) {
                currentView = view;
                
                // Actualizar botones activos
                viewBtns.forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.view === view);
                });

                // Aplicar clases CSS
                if (view === 'list') {
                    productsGrid.classList.add('list-view');
                    productCards.forEach(card => card.classList.add('list-view'));
                } else {
                    productsGrid.classList.remove('list-view');
                    productCards.forEach(card => card.classList.remove('list-view'));
                }
            }

            // Funciones para la vista previa del carrito
            function cargarVistaPrevia() {
                if (!productosVistaPrevia) return;
                
                productosVistaPrevia.innerHTML = '<div class="carrito-loading"><p>Cargando carrito...</p></div>';
                
                fetch('vistapreviacarrito.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Error parsing JSON: ${e.message}. Response: ${text.substring(0, 200)}`);
                    }
                })
                .then(data => {
                    if (productosVistaPrevia) {
                        if (data.error) {
                            productosVistaPrevia.innerHTML = `
                                <div class="carrito-error">
                                    <p>Error: ${data.mensaje}</p>
                                </div>
                            `;
                            return;
                        }
                        if (data.productos && data.productos.length > 0) {
                            let html = '';
                            data.productos.forEach(producto => {
                                html += `
                                    <div class="producto-preview">
                                        <img src="${producto.imagen}" alt="${producto.nombre}" onerror="this.src='img/no-image.png'">
                                        <div class="producto-info">
                                            <h4>${producto.nombre}</h4>
                                            <p>Cantidad: ${producto.cantidad} - $${parseFloat(producto.precio).toFixed(2)}</p>
                                        </div>
                                    </div>
                                `;
                            });
                            html += `
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                    <strong>Total: $${parseFloat(data.total).toFixed(2)}</strong>
                                </div>
                            `;
                            productosVistaPrevia.innerHTML = html;
                        } else {
                            productosVistaPrevia.innerHTML = `
                                <div class="carrito-vacio">
                                    <p>Tu carrito está vacío</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Vista previa - Error completo:', error);
                    if (productosVistaPrevia) {
                        productosVistaPrevia.innerHTML = `
                            <div class="carrito-error">
                                <p>Error al cargar el carrito</p>
                            </div>
                        `;
                    }
                });
            }

            function mostrarVistaPrevia() {
                if (vistaPrevia && carritoToggle) {
                    // Calcular la posición del botón del carrito
                    const rect = carritoToggle.getBoundingClientRect();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    // Posicionar el cart preview arriba del botón
                    vistaPrevia.style.top = (rect.top + scrollTop - vistaPrevia.offsetHeight - 10) + 'px';
                    vistaPrevia.style.left = (rect.right - vistaPrevia.offsetWidth) + 'px';
                    
                    vistaPrevia.classList.remove('vista-previa-oculta');
                    setTimeout(() => vistaPrevia.classList.add('mostrar'), 10);
                    carritoAbierto = true;
                }
            }

            function ocultarVistaPrevia() {
                if (vistaPrevia) {
                    vistaPrevia.classList.remove('mostrar');
                    setTimeout(() => vistaPrevia.classList.add('vista-previa-oculta'), 300);
                    carritoAbierto = false;
                }
            }

            // Recalcular posición al redimensionar la ventana
            window.addEventListener('resize', function() {
                if (carritoAbierto && vistaPrevia && carritoToggle) {
                    const rect = carritoToggle.getBoundingClientRect();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    vistaPrevia.style.top = (rect.top + scrollTop - vistaPrevia.offsetHeight - 10) + 'px';
                    vistaPrevia.style.left = (rect.right - vistaPrevia.offsetWidth) + 'px';
                }
            });
            function addToCart(productId, btn) {
                // Deshabilitar botón temporalmente
                btn.disabled = true;
                btn.textContent = 'Agregando...';

                fetch('carrito_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${encodeURIComponent(productId)}&accion=agregar`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Error parsing JSON: ${e.message}. Response: ${text.substring(0, 200)}`);
                    }
                })
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Agregar al Carrito';

                    if (data.exito) {
                        cartCount = data.cantidad_total;
                        updateCartCount();
                        
                        // Feedback visual
                        btn.style.background = 'var(--success-color)';
                        btn.textContent = '¡Agregado!';
                        
                        setTimeout(() => {
                            btn.style.background = '';
                            btn.textContent = 'Agregar al Carrito';
                        }, 1500);

                        // Mostrar notificación
                        showNotification(`"${data.nombre || 'Producto'}" agregado al carrito`, 'success');
                        
                        // Actualizar vista previa si está abierta
                        if (carritoAbierto) {
                            setTimeout(() => cargarVistaPrevia(), 500);
                        }
                    } else {
                        showNotification('Error: ' + (data.mensaje || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.textContent = 'Agregar al Carrito';
                    showNotification('Error de conexión: ' + error.message, 'error');
                });
            }

            // Actualizar contador del carrito
            function updateCartCount() {
                const cartCountElement = document.querySelector('.cart-count');
                cartCountElement.textContent = cartCount;
                
                // Animación
                cartCountElement.style.animation = 'none';
                setTimeout(() => {
                    cartCountElement.style.animation = 'bounce 0.5s ease';
                }, 10);
            }

            // Mostrar notificación
            function showNotification(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? 'var(--success-color)' : 'var(--error-color)'};
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: var(--radius-md);
                    box-shadow: var(--shadow-lg);
                    z-index: 10000;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                `;
                notification.textContent = message;

                document.body.appendChild(notification);

                // Mostrar notificación
                setTimeout(() => {
                    notification.style.opacity = '1';
                    notification.style.transform = 'translateX(0)';
                }, 100);

                // Ocultar notificación
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }

            // Event Listeners
            searchInput.addEventListener('input', searchProducts);
            searchBtn.addEventListener('click', searchProducts);
            sortSelect.addEventListener('change', sortProducts);

            viewBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    changeView(btn.dataset.view);
                });
            });

            addToCartBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = btn.dataset.id;
                    addToCart(productId, btn);
                });
            });

            // Búsqueda con Enter
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchProducts();
                }
            });

            // Event listeners del carrito
            if (carritoToggle) {
                carritoToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!carritoAbierto) {
                        cargarVistaPrevia();
                        mostrarVistaPrevia();
                    } else {
                        ocultarVistaPrevia();
                    }
                });
            }

            if (cerrarVistaPrevia) {
                cerrarVistaPrevia.addEventListener('click', ocultarVistaPrevia);
            }

            // Cerrar con Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (carritoAbierto) {
                        ocultarVistaPrevia();
                    }
                }
            });

            // Cerrar al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (carritoAbierto && vistaPrevia && !vistaPrevia.contains(e.target) && !carritoToggle.contains(e.target)) {
                    ocultarVistaPrevia();
                }
            });

            // Inicialización
            console.log('Sistema de productos inicializado correctamente');
        });

        // CSS adicional para animaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 20%, 60%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                80% {
                    transform: translateY(-5px);
                }
            }

            .notification {
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .add-to-cart-btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
                transform: none !important;
            }

            /* Hover effects mejorados */
            .suggestion-link:hover {
                box-shadow: var(--shadow-md);
            }

            .action-btn:hover {
                box-shadow: var(--shadow-md);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>