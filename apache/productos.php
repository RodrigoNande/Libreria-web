<?php
session_start();
require_once 'conexion.php';

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
    </style>
</head>
<body>
    <!-- Header Principal -->
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo">
                <span>Librería RL</span>
                <span class="star">★</span>
            </a>
            
            <div class="search-container">
                <input type="text" class="search-input" placeholder="¿Qué estás buscando?" id="search-input">
                <button class="search-btn" id="search-btn">🔍</button>
            </div>
            
            <div class="header-actions">
                <a href="#" class="cart-btn">
                    🛒 <span class="cart-count">3</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Navegación Secundaria -->
    <nav class="nav-secondary">
        <div class="nav-content">
            <a href="#" class="nav-link">Inicio</a>
            <a href="#" class="nav-link">Categorías</a>
            <a href="#" class="nav-link">Ofertas</a>
            <a href="#" class="nav-link">Sobre Nosotros</a>
            <a href="#" class="nav-link">Contacto</a>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <ol class="breadcrumb-list">
            <li class="breadcrumb-item">
                <a href="#" class="breadcrumb-link">Inicio</a>
            </li>
            <li class="breadcrumb-separator">→</li>
            <li class="breadcrumb-item">
                <a href="#" class="breadcrumb-link">Arte</a>
            </li>
            <li class="breadcrumb-separator">→</li>
            <li class="breadcrumb-item">Acrílico Profesional</li>
        </ol>
    </div>

    <!-- Header de Productos -->
    <div class="products-header">
        <div class="header-top">
            <h1 class="page-title">Acrílico Profesional</h1>
            <span class="results-count">24 productos encontrados</span>
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
            <!-- Productos de ejemplo -->
            <article class="product-card" data-name="Acrílico Profesional Windsor & Newton" data-price="25.99" data-brand="Windsor & Newton">
                <div class="product-image-container">
                    <img src="https://images.unsplash.com/photo-1541961017774-22349e4a1262?w=300&h=300&fit=crop&crop=center" alt="Acrílico Profesional Windsor & Newton" class="product-image">
                    <div class="product-badge badge-new">Nuevo</div>
                    <div class="product-actions">
                        <button class="action-btn" title="Vista rápida">👁️</button>
                        <button class="action-btn" title="Agregar a favoritos">♡</button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">Windsor & Newton</div>
                    <h3 class="product-title">Acrílico Profesional Serie 1 - Set de 12 Colores</h3>
                    <div class="product-price-container">
                        <span class="product-price">$25.99</span>
                        <span class="product-price-unit">c/u</span>
                    </div>
                    <button class="add-to-cart-btn" data-id="1">Agregar al Carrito</button>
                </div>
            </article>

            <article class="product-card" data-name="Pincel Profesional Escoda" data-price="18.50" data-brand="Escoda">
                <div class="product-image-container">
                    <img src="https://images.unsplash.com/photo-1572729943322-7d2a5c8c8b1a?w=300&h=300&fit=crop&crop=center" alt="Pincel Profesional Escoda" class="product-image">
                    <div class="product-badge badge-sale">Oferta</div>
                    <div class="product-actions">
                        <button class="action-btn" title="Vista rápida">👁️</button>
                        <button class="action-btn" title="Agregar a favoritos">♡</button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">Escoda</div>
                    <h3 class="product-title">Pincel Profesional Serie Versatil - Redondo N°8</h3>
                    <div class="product-price-container">
                        <span class="product-price">$18.50</span>
                        <span class="product-price-unit">c/u</span>
                    </div>
                    <button class="add-to-cart-btn" data-id="2">Agregar al Carrito</button>
                </div>
            </article>

            <article class="product-card" data-name="Lienzo Preparado Premium" data-price="32.00" data-brand="Arteza">
                <div class="product-image-container">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=300&h=300&fit=crop&crop=center" alt="Lienzo Preparado Premium" class="product-image">
                    <div class="product-actions">
                        <button class="action-btn" title="Vista rápida">👁️</button>
                        <button class="action-btn" title="Agregar a favoritos">♡</button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">Arteza</div>
                    <h3 class="product-title">Lienzo Preparado Premium 40x50cm - Pack de 5</h3>
                    <div class="product-price-container">
                        <span class="product-price">$32.00</span>
                        <span class="product-price-unit">pack</span>
                    </div>
                    <button class="add-to-cart-btn" data-id="3">Agregar al Carrito</button>
                </div>
            </article>

            <article class="product-card" data-name="Paleta de Mezcla Profesional" data-price="12.75" data-brand="Arteza">
                <div class="product-image-container">
                    <img src="https://images.unsplash.com/photo-1541961017774-22349e4a1262?w=300&h=300&fit=crop&crop=center" alt="Paleta de Mezcla" class="product-image">
                    <div class="product-actions">
                        <button class="action-btn" title="Vista rápida">👁️</button>
                        <button class="action-btn" title="Agregar a favoritos">♡</button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">Arteza</div>
                    <h3 class="product-title">Paleta de Mezcla Profesional - Vidrio Templado</h3>
                    <div class="product-price-container">
                        <span class="product-price">$12.75</span>
                        <span class="product-price-unit">c/u</span>
                    </div>
                    <button class="add-to-cart-btn" data-id="4">Agregar al Carrito</button>
                </div>
            </article>

            <article class="product-card" data-name="Médium Acrílico Profesional" data-price="15.99" data-brand="Golden">
                <div class="product-image-container">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=300&h=300&fit=crop&crop=center" alt="Médium Acrílico" class="product-image">
                    <div class="product-actions">
                        <button class="action-btn" title="Vista rápida">👁️</button>
                        <button class="action-btn" title="Agregar a favoritos">♡</button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">Golden</div>
                    <h3 class="product-title">Médium Acrílico Profesional - Gel Brillante</h3>
                    <div class="product-price-container">
                        <span class="product-price">$15.99</span>
                        <span class="product-price-unit">c/u</span>
                    </div>
                    <button class="add-to-cart-btn" data-id="5">Agregar al Carrito</button>
                </div>
            </article>

            <article class="product-card" data-name="Papel Acuarela Profesional" data-price="22.50" data-brand="Canson">
                <div class="product-image-container">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop&crop=center" alt="Papel Acuarela" class="product-image">
                    <div class="product-badge badge-new">Nuevo</div>
                    <div class="product-actions">
                        <button class="action-btn" title="Vista rápida">👁️</button>
                        <button class="action-btn" title="Agregar a favoritos">♡</button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">Canson</div>
                    <h3 class="product-title">Papel Acuarela Profesional 300g - Block A4</h3>
                    <div class="product-price-container">
                        <span class="product-price">$22.50</span>
                        <span class="product-price-unit">block</span>
                    </div>
                    <button class="add-to-cart-btn" data-id="6">Agregar al Carrito</button>
                </div>
            </article>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
            let cartCount = 3;

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

            // Agregar al carrito
            function addToCart(productId, btn) {
                // Deshabilitar botón temporalmente
                btn.disabled = true;
                btn.textContent = 'Agregando...';

                // Simular llamada AJAX
                setTimeout(() => {
                    cartCount++;
                    updateCartCount();
                    
                    // Feedback visual
                    btn.style.background = 'var(--success-color)';
                    btn.textContent = '¡Agregado!';
                    
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.style.background = '';
                        btn.textContent = 'Agregar al Carrito';
                    }, 1500);

                    // Mostrar notificación
                    showNotification('Producto agregado al carrito', 'success');
                }, 800);
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