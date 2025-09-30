<?php
session_start();
require_once 'conexion.php';
require_once 'auth.php';

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

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$subcategoria = isset($_GET['subcategoria']) ? $_GET['subcategoria'] : '';

$cantidadCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        $cantidadCarrito += $cantidad;
    }
}

function obtenerCategoriasConSubcategorias($conn) {
    $categorias = [];
    
    $sql_categorias = "
        SELECT Id, Nombre_Categoria 
        FROM Categoria 
        WHERE IdCategoriaPadre IS NULL 
        ORDER BY Nombre_Categoria
    ";
    
    $result_categorias = mysqli_query($conn, $sql_categorias);
    
    if ($result_categorias && mysqli_num_rows($result_categorias) > 0) {
        while ($categoria_padre = mysqli_fetch_assoc($result_categorias)) {
            $categoria_id = $categoria_padre['Id'];
            $categoria_nombre = $categoria_padre['Nombre_Categoria'];
            
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
            
            $categorias[$categoria_nombre] = $subcategorias;
        }
    } else {
        $categorias = [
            "ARTE" => ["Acrílico profesional", "Artículo para arte", "Acrílicos para tela"],
            "PAPELES Y DERIVADOS" => ["Bollo de lana", "Carboncillos", "Fieltro"],
            "ESCOLAR" => ["Lápiz graduado", "Lápiz 3 técnicas", "Laca plástica"],
            "OFICINA" => [],
            "INFANTIL" => ["Plumones profesionales", "Pinceles", "Prismacolor"]
        ];
    }
    
    return $categorias;
}

$categorias = obtenerCategoriasConSubcategorias($conn);

function obtenerProductosFiltrados($conn, $categoria, $subcategoria) {
    $sql = "";
    $productos = [];
    
    if (!empty($subcategoria)) {
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

$titulo_pagina = "Todos los Productos";
if (!empty($subcategoria)) {
    $titulo_pagina = htmlspecialchars($subcategoria);
} elseif (!empty($categoria)) {
    $titulo_pagina = htmlspecialchars($categoria);
}

if (empty($categoria) && empty($subcategoria)) {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- CSS UNIFICADO -->
    <link rel="stylesheet" href="estilopruebas.css">
    <link rel="stylesheet" href="estilocarrito.css">
    
    <title>Productos - Librería RL</title>
</head>
<body>

<!-- HEADER PRINCIPAL -->
<header class="header">
    <div class="header-top">
        <a href="home.php" class="logo">
            <button class="menu">☰</button>
            <span class="brand">Librería RL</span>
            <span class="star">★</span>
        </a>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="¿Qué estás buscando?" id="search-input">
            <button class="search-btn" id="search-btn">🔍</button>
        </div>

        <nav class="nav-links">
            <?php if ($usuarioLogueado): ?>
                <div class="usuario-logueado">
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

                    <div class="carrito-container">
                        <a href="#" id="carrito-toggle" class="carrito-link">
                            🛒 <span id="contador-carrito"><?php echo $cantidadCarrito; ?></span>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="auth-container">
                    <a href="#" id="login-toggle" class="auth-link">Iniciar Sesión</a>

                    <div id="auth-dropdown" class="auth-dropdown-oculto">
                        <div id="login-form" class="auth-form">
                            <h3>Iniciar Sesión</h3>
                            <form id="form-login" method="post">
                                <div class="form-group">
                                    <input type="email" id="login-email" name="email" placeholder="Correo electrónico" required autocomplete="email">
                                </div>
                                <div class="form-group">
                                    <input type="password" id="login-password" name="password" placeholder="Contraseña" required autocomplete="current-password">
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

                        <div id="register-form" class="auth-form auth-form-oculto">
                            <h3>Crear Cuenta</h3>
                            <form id="form-registro" method="post">
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="text" name="nombre" placeholder="Nombre" required autocomplete="given-name">
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="apellido" placeholder="Apellido" required autocomplete="family-name">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="email" name="correo" placeholder="Correo electrónico" required autocomplete="email">
                                </div>
                                <div class="form-group">
                                    <input type="text" name="usuario" placeholder="Nombre de usuario" required autocomplete="username">
                                </div>
                                <div class="form-group">
                                    <input type="tel" name="telefono" placeholder="Teléfono (opcional)" autocomplete="tel">
                                </div>
                                <div class="form-group">
                                    <input type="text" name="direccion" placeholder="Dirección (opcional)" autocomplete="street-address">
                                </div>
                                <div class="form-group">
                                    <input type="password" name="contrasena" placeholder="Contraseña" required minlength="6" autocomplete="new-password">
                                </div>
                                <div class="form-group">
                                    <input type="password" name="confirmar_contrasena" placeholder="Confirmar contraseña" required minlength="6" autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn-auth">Crear Cuenta</button>
                            </form>
                            <div class="auth-separator">
                                <span>¿Ya tienes cuenta?</span>
                                <a href="#" id="mostrar-login">Inicia sesión aquí</a>
                            </div>
                        </div>

                        <div id="verify-form" class="auth-form auth-form-oculto">
                            <h3>Verificar Email</h3>
                            <p>Te hemos enviado un código de verificación a tu email.</p>
                            <form id="form-verificacion" method="post">
                                <div class="form-group">
                                    <input type="text" id="codigo-verificacion" name="codigo" placeholder="Código de verificación" required maxlength="6">
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

<!-- VISTA PREVIA DEL CARRITO -->
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

<!-- NAVEGACIÓN SECUNDARIA -->
<nav class="nav-secondary">
    <div class="nav-content">
        <a href="home.php" class="nav-link">INICIO</a>
        <div class="dropdown">
            <a href="#" class="nav-link dropbtn">CATEGORÍAS ▼</a>
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

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
            <a href="home.php" class="breadcrumb-link">Inicio</a>
        </li>
        <li class="breadcrumb-separator">→</li>
        <li class="breadcrumb-item"><?php echo $titulo_pagina; ?></li>
    </ol>
</div>

<!-- HEADER DE PRODUCTOS -->
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

<!-- CONTENEDOR DE PRODUCTOS -->
<div class="products-container">
    <div class="productos" id="products-grid">
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
                <article class="producto" 
                         data-id="<?php echo $row['IdProducto']; ?>"
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
                        <button class="btn-agregar-carrito" data-id="<?php echo $row['IdProducto']; ?>">Agregar al Carrito</button>
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

    <!-- ESTADO VACÍO -->
    <div class="empty-state" style="display: none;" id="empty-state">
        <div class="empty-state-icon">🎨</div>
        <h2>No se encontraron productos</h2>
        <p>No hay productos disponibles que coincidan con tu búsqueda.</p>
        <div class="suggestions-grid">
            <a href="#" class="suggestion-link">Productos de Arte</a>
            <a href="#" class="suggestion-link">Artículos Escolares</a>
            <a href="#" class="suggestion-link">Productos de Oficina</a>
            <a href="home.php" class="suggestion-link">Ver todos</a>
        </div>
    </div>
</div>

<!-- PAGINACIÓN -->
<nav class="paginacion" aria-label="Navegación de páginas">
    <a href="#" aria-label="Página anterior">‹</a>
    <span class="actual" aria-current="page">1</span>
    <a href="#" aria-label="Página 2">2</a>
    <a href="#" aria-label="Página 3">3</a>
    <a href="#" aria-label="Siguiente página">›</a>
</nav>

<!-- CONTENEDOR DE NOTIFICACIONES -->
<div id="notificaciones-container"></div>

<script src="js/productos-unificado.js"></script>
</body>
</html>