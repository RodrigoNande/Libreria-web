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
    
    // 1. Obtener categorías principales (sin padre)
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
            
            // Obtener subcategorías que tienen esta categoría como padre
            $sql_subcategorias = "
                SELECT Id, Nombre_Categoria
                FROM Categoria 
                WHERE IdCategoriaPadre = ?
                ORDER BY Nombre_Categoria
            ";
            
            $stmt_sub = mysqli_prepare($conn, $sql_subcategorias);
            mysqli_stmt_bind_param($stmt_sub, "s", $categoria_id);
            mysqli_stmt_execute($stmt_sub);
            $result_subcategorias = mysqli_stmt_get_result($stmt_sub);
            
            $subcategorias = [];
            
            // Solo agregar subcategorías reales (no productos)
            if ($result_subcategorias && mysqli_num_rows($result_subcategorias) > 0) {
                while ($subcategoria = mysqli_fetch_assoc($result_subcategorias)) {
                    $subcategorias[] = $subcategoria['Nombre_Categoria'];
                }
            }
            // ✅ Si no hay subcategorías, simplemente deja el array vacío
            
            mysqli_stmt_close($stmt_sub);
            
            // Agregar al array de categorías
            $categorias[$categoria_nombre] = $subcategorias;
        }
    }
    if (!empty($subcategorias)) {
    $categorias[$categoria_nombre] = $subcategorias;
} else {
    // Categoría sin subcategorías - agregar enlace directo
    $categorias[$categoria_nombre] = ["Ver todos"];
}
    return $categorias;
}


$categorias = obtenerCategoriasConSubcategorias($conn);

// FUNCIÓN CORREGIDA PARA OBTENER PRODUCTOS FILTRADOS
// =============================================
function obtenerProductosFiltrados($conn, $categoria, $subcategoria) {
    $productos = [];
    
    if (!empty($subcategoria)) {
        // Buscar por subcategoría específica
        // Puede ser una subcategoría real O un nombre de producto
        
        // Primero: intentar como subcategoría
        $sql = "
            SELECT DISTINCT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, 
                   a.Precio, a.Precio_Unitario, a.Stock, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            INNER JOIN producto_categoria pc ON a.IdProducto = pc.Id_Producto
            INNER JOIN Categoria c ON pc.Id_Categoria = c.Id
            WHERE c.Nombre_Categoria = ?
            ORDER BY a.NomProducto
        ";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $subcategoria);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $productos[] = $row;
            }
        } else {
            // Si no encontró nada, buscar como nombre de producto
            $sql_producto = "
                SELECT DISTINCT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, 
                       a.Precio, a.Precio_Unitario, a.Stock, i.ruta
                FROM articulo a
                LEFT JOIN img i ON a.IdProducto = i.idProd
                WHERE a.NomProducto LIKE ? OR a.TipoProducto LIKE ?
                ORDER BY a.NomProducto
            ";
            
            $busqueda = "%{$subcategoria}%";
            $stmt2 = mysqli_prepare($conn, $sql_producto);
            mysqli_stmt_bind_param($stmt2, "ss", $busqueda, $busqueda);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            
            if ($result2 && mysqli_num_rows($result2) > 0) {
                while ($row = mysqli_fetch_assoc($result2)) {
                    $productos[] = $row;
                }
            }
            mysqli_stmt_close($stmt2);
        }
        mysqli_stmt_close($stmt);
        
    } elseif (!empty($categoria)) {
        // Buscar por categoría principal
        // Obtener todas las subcategorías de esta categoría padre
        $sql = "
            SELECT DISTINCT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, 
                   a.Precio, a.Precio_Unitario, a.Stock, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            INNER JOIN producto_categoria pc ON a.IdProducto = pc.Id_Producto
            INNER JOIN Categoria c ON pc.Id_Categoria = c.Id
            LEFT JOIN Categoria cp ON c.IdCategoriaPadre = cp.Id
            WHERE (c.Nombre_Categoria = ? OR cp.Nombre_Categoria = ? OR c.Id = ? OR cp.Id = ?)
            ORDER BY a.NomProducto
        ";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $categoria, $categoria, $categoria, $categoria);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $productos[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
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
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Fuentes mejoradas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet">

    <!-- Meta tags mejorados -->
    <meta name="theme-color" content="#120049">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">


    <link rel="stylesheet" href="estilopruebas.css">
    
    <title>Productos - Librería RL</title>
</head>
</head>
<body>
    <script src="js/validaciones.js"></script>
<script src="js/paginacion.js"></script>
    <!-- Header Principal -->
    
<!-- HEADER CORREGIDO - Reemplaza desde <header> hasta </nav> (navegación secundaria) -->

<header class="header">
    <!-- HEADER PRINCIPAL -->
    <div class="header-top">
        <div class="logo">
            <button class="menu">☰</button>
            <span class="brand">Librería RL</span>
            <span class="star">★</span>
        </div>
        
        <div class="search-container">
            <input type="text" placeholder="¿Qué estás buscando?" id="search-input">
            <button class="search-button" id="search-btn">🔍</button>
        </div>
        
        <!-- NAVEGACIÓN CORREGIDA -->
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
                            <a href="#">👤 Mi Perfil</a>
                            <a href="vercarrito.php">📦 Mis Pedidos</a>
                            <a href="#" onclick="cerrarSesion()">🚪 Cerrar Sesión</a>
                        </div>
                    </div>
                    
                    <!-- Carrito al lado del usuario -->
                    <div class="carrito-container">
                        <a href="#" id="carrito-toggle" class="cart-btn">
                            🛒 <span id="contador-carrito" class="cart-count"><?php echo $cantidadCarrito; ?></span>
                        </a>
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
                    </div>
                </div>
                
            <?php else: ?>
                <!-- USUARIO NO LOGUEADO -->
                <div class="auth-container">
                    <a href="#" id="login-toggle" class="auth-link">Iniciar Sesión</a>
                    
                    <!-- DROPDOWN DE AUTENTICACIÓN -->
                    <div id="auth-dropdown" class="auth-dropdown-oculto">
                        <div class="auth-form-container">
                            <button class="auth-close" onclick="cerrarDropdown()">&times;</button>
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
                                               name="nombre" 
                                               placeholder="Nombre" 
                                               required
                                               autocomplete="given-name">
                                    </div>
                                    <div class="form-group">
                                        <input type="text" 
                                               name="apellido" 
                                               placeholder="Apellido" 
                                               required
                                               autocomplete="family-name">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="email" 
                                           name="correo" 
                                           placeholder="Correo electrónico" 
                                           required
                                           autocomplete="email">
                                </div>
                                <div class="form-group">
                                    <input type="text" 
                                           name="usuario" 
                                           placeholder="Nombre de usuario" 
                                           required
                                           autocomplete="username">
                                </div>
                                <div class="form-group">
                                    <input type="tel" 
                                           name="telefono" 
                                           placeholder="Teléfono (opcional)"
                                           autocomplete="tel">
                                </div>
                                <div class="form-group">
                                    <input type="text" 
                                           name="direccion" 
                                           placeholder="Dirección (opcional)"
                                           autocomplete="street-address">
                                </div>
                                <div class="form-group">
                                    <input type="password" 
                                           name="contrasena" 
                                           placeholder="Contraseña" 
                                           required 
                                           minlength="6"
                                           autocomplete="new-password">
                                </div>
                                <div class="form-group">
                                    <input type="password" 
                                           name="confirmar_contrasena" 
                                           placeholder="Confirmar contraseña" 
                                           required 
                                           minlength="6"
                                           autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn-auth">Crear Cuenta</button>
                            </form>
                            
                            <div class="auth-separator">
                                <span>¿Ya tienes cuenta?</span>
                                <a href="#" id="mostrar-login">Inicia sesión aquí</a>
                            </div>
                        </div>

                          
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

    <!-- Cart Preview (fuera del header para evitar estiramiento) 
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
            -->
    <!-- Navegación Secundaria con Categorías Dinámicas -->
   <!-- NAVEGACIÓN SECUNDARIA CON CATEGORÍAS -->
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
                            // Cerrar el modal antes de recargar
                            cerrarDropdown();
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
        const loginValidator = new FormValidator('form-login', {
    realTimeValidation: true,
    scrollToError: true
});

loginValidator.addRules(CommonValidators.login);

// Para registro
const registerValidator = new FormValidator('form-registro');
registerValidator.addRules(CommonValidators.register);

// Para productos (admin)
const productoValidator = new FormValidator('form-producto');
productoValidator.addRules(CommonValidators.producto);
    </script>
</body>
</html>