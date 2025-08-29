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
    <link rel="stylesheet" href="estiloproductos.css">
    <link rel="stylesheet" href="carrito-mejorado.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title><?php echo $titulo_pagina; ?> - Librería RL</title>
</head>
<body>
    <header class="header">
        <div class="logo">
            <button class="menu">☰</button>
            <span class="brand">Librería RL</span>
            <span class="star">★</span>
        </div>
        <div class="search-container">
            <input type="text" placeholder="¿Qué estás buscando?" id="buscador-productos">
            <button class="search-button" onclick="buscarProductos()">🔍</button>
        </div>
        <nav class="nav-links">
            <a href="#">Mis listas</a>
            <a href="#">Mis pedidos</a>
            <a href="#">Mi Cuenta</a>
            <!-- Carrito mejorado con vista previa -->
            <div class="carrito-container">
                <a href="#" id="carrito-toggle" class="carrito-link">
                    🛒 <span id="contador-carrito"><?php echo $cantidadCarrito; ?></span>
                </a>
                <div id="vista-previa-carrito" class="vista-previa-oculta">
                    <div class="carrito-header">
                        <h3>Mi Carrito</h3>
                        <span class="cerrar-vista-previa">&times;</span>
                    </div>
                    <div id="productos-vista-previa">
                        <!-- Contenido cargado dinámicamente -->
                    </div>
                    <div class="carrito-footer">
                        <a href="vercarrito.php" class="btn-ver-carrito">Ver Carrito Completo</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <nav class="menu-secundario">
        <a href="home.php">INICIO</a>
        <div class="dropdown">
            <a href="#" class="dropbtn">CATEGORÍA ▼</a>
            <div class="dropdown-content">
                <div class="categorias-container">
                    <?php foreach ($categorias as $cat => $subcats): ?>
                        <div class="columna">
                            <h4><?php echo htmlspecialchars($cat); ?></h4>
                            <ul>
                                <?php if (!empty($subcats)): ?>
                                    <?php foreach ($subcats as $sub): ?>
                                        <li>
                                            <a href="productos.php?categoria=<?php echo urlencode($cat); ?>&subcategoria=<?php echo urlencode($sub); ?>">
                                                <?php echo htmlspecialchars($sub); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>
                                        <a href="productos.php?categoria=<?php echo urlencode($cat); ?>">
                                            Ver <?php echo htmlspecialchars($cat); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <a href="#">SOBRE NOSOTROS</a>
        <a href="#">CONTACTO</a>
        <a href="#">TIENDA</a>
    </nav>

    <!-- Breadcrumb / Navegación -->
    <div class="breadcrumb">
        <a href="home.php">Inicio</a>
        <?php if (!empty($categoria)): ?>
            <span> > </span>
            <a href="productos.php?categoria=<?php echo urlencode($categoria); ?>">
                <?php echo htmlspecialchars($categoria); ?>
            </a>
        <?php endif; ?>
        <?php if (!empty($subcategoria)): ?>
            <span> > </span>
            <span class="actual"><?php echo htmlspecialchars($subcategoria); ?></span>
        <?php endif; ?>
    </div>

    <!-- Título y filtros -->
    <div class="productos-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <div class="filtros">
            <select id="ordenar-por" onchange="ordenarProductos()">
                <option value="nombre">Ordenar por Nombre</option>
                <option value="precio-menor">Precio: Menor a Mayor</option>
                <option value="precio-mayor">Precio: Mayor a Menor</option>
                <option value="marca">Ordenar por Marca</option>
            </select>
            <span class="contador-resultados">
                <?php echo count($productos_filtrados); ?> productos encontrados
            </span>
        </div>
    </div>

    <main class="productos" id="contenedor-productos">
        <?php if (!empty($productos_filtrados)): ?>
            <?php foreach ($productos_filtrados as $producto): 
                $imgSrc = !empty($producto['ruta']) ? htmlspecialchars($producto['ruta']) : "img/no-image.png";
            ?>
                <div class="producto" data-nombre="<?php echo htmlspecialchars($producto['NomProducto']); ?>" 
                     data-precio="<?php echo $producto['Precio']; ?>" 
                     data-marca="<?php echo htmlspecialchars($producto['Marca']); ?>">
                    <a href="formularioproducto.php?id=<?php echo $producto['IdProducto']; ?>">
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>">
                        <h3><?php echo htmlspecialchars($producto['NomProducto']); ?></h3>
                        <p>Precio: <strong>$<?php echo number_format($producto['Precio'], 2); ?></strong></p>
                        <p>Marca: <?php echo htmlspecialchars($producto['Marca']); ?></p>
                        <p>Precio Unitario: $<?php echo number_format($producto['Precio_Unitario'], 2); ?></p>
                    </a>
                    <!-- Botón que funciona con tu carrito.php original -->
                    <a href="carrito.php?id=<?php echo $producto['IdProducto']; ?>" 
                       class="btn-agregar-carrito-original">
                        Agregar al Carrito
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-productos">
                <h2>😞 No se encontraron productos</h2>
                <p>No hay productos disponibles para "<?php echo $titulo_pagina; ?>"</p>
                <div class="sugerencias">
                    <h3>¿Qué tal si pruebas con:</h3>
                    <ul>
                        <li><a href="productos.php?categoria=ARTE">Productos de Arte</a></li>
                        <li><a href="productos.php?categoria=ESCOLAR">Artículos Escolares</a></li>
                        <li><a href="productos.php?categoria=OFICINA">Productos de Oficina</a></li>
                        <li><a href="home.php">Ver todos los productos</a></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal de confirmación -->
    <div id="modal-confirmacion" class="modal-oculto">
        <div class="modal-contenido">
            <span class="cerrar-modal">&times;</span>
            <div class="modal-icono">✅</div>
            <h3>¡Producto agregado!</h3>
            <p id="mensaje-producto"></p>
            <div class="modal-botones">
                <button id="continuar-comprando" class="btn-secundario">Continuar Comprando</button>
                <a href="vercarrito.php" class="btn-primario">Ver Carrito</a>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div id="overlay" class="overlay-oculto"></div>

    <script src="carrito-mejorado.js"></script>
    <script>
    // Funcionalidad de búsqueda
    function buscarProductos() {
        const termino = document.getElementById('buscador-productos').value.toLowerCase();
        const productos = document.querySelectorAll('.producto');
        let encontrados = 0;
        
        productos.forEach(producto => {
            const nombre = producto.dataset.nombre.toLowerCase();
            const marca = producto.dataset.marca.toLowerCase();
            
            if (nombre.includes(termino) || marca.includes(termino)) {
                producto.style.display = 'block';
                encontrados++;
            } else {
                producto.style.display = 'none';
            }
        });
        
        document.querySelector('.contador-resultados').textContent = 
            `${encontrados} productos encontrados`;
    }

    // Funcionalidad de ordenamiento
    function ordenarProductos() {
        const criterio = document.getElementById('ordenar-por').value;
        const contenedor = document.getElementById('contenedor-productos');
        const productos = Array.from(contenedor.querySelectorAll('.producto'));
        
        productos.sort((a, b) => {
            switch(criterio) {
                case 'nombre':
                    return a.dataset.nombre.localeCompare(b.dataset.nombre);
                case 'precio-menor':
                    return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
                case 'precio-mayor':
                    return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
                case 'marca':
                    return a.dataset.marca.localeCompare(b.dataset.marca);
                default:
                    return 0;
            }
        });
        
        // Reordenar elementos en el DOM
        productos.forEach(producto => {
            contenedor.appendChild(producto);
        });
    }

    // Búsqueda en tiempo real
    document.getElementById('buscador-productos').addEventListener('input', buscarProductos);
    </script>
</body>
</html>