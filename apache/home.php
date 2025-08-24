<?php
session_start();
require_once 'conexion.php';

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo1.css">
    <link rel="stylesheet" href="estilocarrito.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Librería RL</title>
</head>
<body>
    <header class="header">
        <div class="logo">
            <button class="menu">☰</button>
            <span class="brand">Librería RL</span>
            <span class="star">★</span>
        </div>
        <div class="search-container">
            <input type="text" placeholder="¿Qué estás buscando?">
            <button class="search-button">🔍</button>
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
        <a href="#">INICIO</a>
        <div class="dropdown">
            <a href="#" class="dropbtn">CATEGORÍA ▼</a>
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
        <a href="#">SOBRE NOSOTROS</a>
        <a href="#">CONTACTO</a>
        <a href="#">TIENDA</a>
    </nav>

    <main class="productos">
        <?php
        $sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, i.ruta
                FROM articulo a
                LEFT JOIN img i ON a.IdProducto = i.idProd";
        $result = $conn->query($sql);
         
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $imgSrc = $row['ruta'] ? htmlspecialchars($row['ruta']) : "img/no-image.png";
                ?>
                <div class="producto">
                    <a href="formularioproducto.php?id=<?php echo $row['IdProducto']; ?>">
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($row['NomProducto']); ?>">
                        <h3><?php echo htmlspecialchars($row['NomProducto']); ?></h3>
                        <p>Precio: <strong>$<?php echo number_format($row['Precio'], 2); ?></strong></p>
                        <p>Marca: <?php echo htmlspecialchars($row['Marca']); ?></p>
                        <p>Precio Unitario: $<?php echo number_format($row['Precio_Unitario'], 2); ?></p>
                    </a>
                    <!-- Botón que funciona con tu carrito.php original -->
                    <a href="carrito.php?id=<?php echo $row['IdProducto']; ?>" 
                       class="btn-agregar-carrito-original">
                        Agregar al Carrito (Original)
                    </a>
                    <!-- Botón con AJAX mejorado -->
                    <button class="btn-agregar-carrito" 
                            data-id="<?php echo $row['IdProducto']; ?>"
                            data-nombre="<?php echo htmlspecialchars($row['NomProducto']); ?>"
                            data-precio="<?php echo $row['Precio']; ?>">
                        Agregar al Carrito 
                    </button>
                </div>
                <?php
            }
        } else {
            echo "<p>No hay productos disponibles</p>";
        }
        ?>
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

    <script src="carrito.js"></script>
</body>
</html>