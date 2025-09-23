<?php
session_start();
require_once 'conexion.php';
require_once 'auth.php';

if (isset($_GET['debug'])) {
    echo "<pre style='background: #f0f0f0; padding: 10px; margin: 10px;'>";
    echo "=== DEBUG SESIÓN ===\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Usuario logueado (función): " . (estaLogueado() ? 'SÍ' : 'NO') . "\n";
    echo "\nDatos de sesión:\n";
    print_r($_SESSION);
    echo "\nCookies:\n";
    print_r($_COOKIE);
    echo "</pre>";
}

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

    <!-- Fuentes mejoradas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet">

    <!-- Meta tags mejorados -->
    <meta name="theme-color" content="#120049">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- CSS mejorado -->
    <link rel="stylesheet" href="estilopruebas.css">
    <link rel="stylesheet" href="estilocarrito.css">

    <style>
        /* Estilos para el contador del carrito */
        .cart-btn {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            padding: 0.5rem 1rem !important;
            border-radius: 50px !important;
            text-decoration: none !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(10px) !important;
        }

        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.15) !important;
            transform: translateY(-2px) !important;
        }

        .cart-count {
            background: var(--secondary-color) !important;
            color: var(--primary-color) !important;
            padding: 0.125rem 0.5rem !important;
            border-radius: 50px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            min-width: 20px !important;
            text-align: center !important;
            display: inline-block !important;
        }

        /* Animación para el contador del carrito */
        @keyframes bounceIn {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Asegurar que los estilos del contador tengan máxima prioridad */
        #carrito-toggle .cart-count {
            background: var(--secondary-color) !important;
            color: var(--primary-color) !important;
            padding: 0.125rem 0.5rem !important;
            border-radius: 50px !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            min-width: 20px !important;
            text-align: center !important;
            display: inline-block !important;
            line-height: 1 !important;
        }
    </style>

    <title>Librería RL - Inicio</title>
</head>
<body>

<header class="header">
    <!-- HEADER UNIFICADO -->
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
                <!-- USUARIO LOGUEADO - Todo en una sola línea -->
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
                            </div>                        <!-- FORMULARIO DE REGISTRO -->
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

                        <!-- FORMULARIO DE VERIFICACIÓN -->
                        <div id="verify-form" class="auth-form auth-form-oculto">
                            <h3>Verificar Email</h3>
                            <p>Te enviamos un código de 6 dígitos a tu correo electrónico.</p>
                            <p><strong>Código temporal para desarrollo: <span id="codigo-temp">------</span></strong></p>
                            
                            <form id="form-verificacion" method="post">
                                <div class="form-group">
                                    <input type="text" 
                                           id="codigo-verificacion" 
                                           name="codigo" 
                                           placeholder="Código de 6 dígitos" 
                                           maxlength="6" 
                                           required
                                           pattern="[0-9]{6}"
                                           autocomplete="one-time-code">
                                </div>
                                <input type="hidden" id="email-verificar" name="email">
                                <button type="submit" class="btn-auth">Verificar</button>
                            </form>
                            
                            <div class="auth-separator">
                                <a href="#" id="reenviar-codigo">¿No recibiste el código? Reenviar</a>
                            </div>
                        </div>
                        </div> <!-- Cierre de auth-form-container -->
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

    <!-- Navegación Secundaria con Categorías Dinámicas -->
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
            <li class="breadcrumb-item">Productos</li>
        </ol>
    </div>

    <!-- Header de Productos -->
    <div class="products-header">
        <div class="header-top">
            <h1 class="page-title">Nuestros Productos</h1>
            <span class="results-count"><?php echo $result->num_rows ?? 0; ?> productos encontrados</span>
        </div>
    </div>

<main class="productos" role="main" aria-label="Lista de productos">
    <?php
    $sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $contador = 0;
        while ($row = $result->fetch_assoc()) {
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
                    <button class="add-to-cart-btn" data-id="<?php echo $row['IdProducto']; ?>">Agregar al Carrito</button>
                </div>
            </article>
            <?php
            $contador++;
        }
    } else {
        echo "<p>No hay productos disponibles</p>";
    }
    ?>

    <!-- Estado vacío (oculto por defecto) -->
    <div class="empty-state" id="empty-state" style="display: none;">
        <div class="empty-state-icon">🔍</div>
        <h2>No se encontraron productos</h2>
        <p>No hay productos que coincidan con tu búsqueda.</p>
        <div class="suggestions-grid">
            <a href="productos.php?categoria=ARTE" class="suggestion-link">Productos de Arte</a>
            <a href="productos.php?categoria=ESCOLAR" class="suggestion-link">Artículos Escolares</a>
            <a href="productos.php?categoria=OFICINA" class="suggestion-link">Productos de Oficina</a>
            <a href="home.php" class="suggestion-link">Ver todos los productos</a>
        </div>
    </div>
</main>

<nav class="paginacion" aria-label="Navegación de páginas">
    <a href="#" aria-label="Página anterior">‹</a>
    <span class="actual" aria-current="page">1</span>
    <a href="#" aria-label="Página 2">2</a>
    <a href="#" aria-label="Página 3">3</a>
    <a href="#" aria-label="Siguiente página">›</a>
</nav>

<!-- Contenedor para notificaciones flotantes -->
<div id="notificaciones-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000; pointer-events: none;"></div>

<script>
// ===================================================
// SISTEMA DE AUTENTICACIÓN Y CARRITO MEJORADO
// ===================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // === ELEMENTOS DEL DOM ===
    // Autenticación
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
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');

    // Productos
    const productCards = document.querySelectorAll('.product-card');
    const productsGrid = document.querySelector('.products-grid');
    const emptyState = document.getElementById('empty-state');
    const resultsCount = document.querySelector('.results-count');

    // Carrito
    const botonesAgregar = document.querySelectorAll('.add-to-cart-btn');
    const contadorCarrito = document.getElementById('contador-carrito');
    const carritoToggle = document.getElementById('carrito-toggle');
    const vistaPrevia = document.getElementById('vista-previa-carrito');
    const cerrarVistaPrevia = document.querySelector('.cerrar-vista-previa');
    const productosVistaPrevia = document.getElementById('productos-vista-previa');
    
    let authOverlay = null;
    let dropdownAbierto = false;
    let carritoAbierto = false;
    let carritoOverlay = null;
    
    // ===================================================
    // SISTEMA DE AUTENTICACIÓN (MEJORADO)
    // ===================================================
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
            
            // Mostrar formulario de login por defecto
            mostrarFormulario(loginForm);
        }
    }
    
    function cerrarDropdown() {
        console.log('Ejecutando cerrarDropdown, dropdownAbierto:', dropdownAbierto);
        if (authDropdown) {
            authDropdown.classList.remove('mostrar');
            setTimeout(() => {
                authDropdown.classList.add('auth-dropdown-oculto');
                console.log('Dropdown cerrado');
            }, 300);

            if (authOverlay) {
                authOverlay.style.opacity = '0';
                authOverlay.style.visibility = 'hidden';
            }
            dropdownAbierto = false;
        } else {
            console.log('authDropdown no encontrado');
        }
    }
    
    function mostrarFormulario(formularioMostrar) {
        const formularios = [loginForm, registerForm, verifyForm];
        formularios.forEach(form => {
            if (form) {
                form.classList.add('auth-form-oculto');
                form.classList.remove('active');
            }
        });
        
        if (formularioMostrar) {
            formularioMostrar.classList.remove('auth-form-oculto');
            formularioMostrar.classList.add('active');
        }
    }
    
    // FUNCIÓN DE NOTIFICACIONES MEJORADA
    function mostrarNotificacion(mensaje, tipo = 'exito', duracion = 4000) {
        const contenedor = document.getElementById('notificaciones-container');
        if (!contenedor) return;
        
        // Crear notificación
        const notif = document.createElement('div');
        notif.className = `notificacion-flotante ${tipo}`;
        notif.style.cssText = `
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            border-left: 4px solid ${tipo === 'exito' ? '#2ed573' : tipo === 'error' ? '#ff4757' : '#4834d4'};
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            pointer-events: auto;
            max-width: 350px;
            position: relative;
            overflow: hidden;
        `;
        
        // Icono según tipo
        let icono = '✅';
        if (tipo === 'error') icono = '❌';
        else if (tipo === 'info') icono = 'ℹ️';
        else if (tipo === 'warning') icono = '⚠️';
        
        notif.innerHTML = `
            <div class="notif-icono" style="font-size: 24px; flex-shrink: 0;">${icono}</div>
            <div class="notif-contenido" style="flex: 1; min-width: 0;">
                <div class="notif-mensaje" style="font-weight: 500; color: #333; margin-bottom: 2px;">${mensaje}</div>
                <div class="notif-tiempo" style="font-size: 12px; color: #666;">Ahora</div>
            </div>
            <button class="notif-cerrar" style="
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                color: #999;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s ease;
                flex-shrink: 0;
            ">&times;</button>
            <div class="notif-progreso" style="
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: ${tipo === 'exito' ? '#2ed573' : tipo === 'error' ? '#ff4757' : '#4834d4'};
                width: 100%;
                transform-origin: left;
                animation: progreso ${duracion}ms linear forwards;
            "></div>
        `;
        
        // Agregar al contenedor
        contenedor.appendChild(notif);
        
        // Animación de entrada
        setTimeout(() => {
            notif.style.transform = 'translateX(0)';
            notif.style.opacity = '1';
        }, 100);
        
        // Botón de cerrar
        const btnCerrar = notif.querySelector('.notif-cerrar');
        btnCerrar.addEventListener('click', () => {
            cerrarNotificacion(notif);
        });
        
        // Hover para pausar progreso
        notif.addEventListener('mouseenter', () => {
            const progreso = notif.querySelector('.notif-progreso');
            progreso.style.animationPlayState = 'paused';
        });
        
        notif.addEventListener('mouseleave', () => {
            const progreso = notif.querySelector('.notif-progreso');
            progreso.style.animationPlayState = 'running';
        });
        
        // Auto-cerrar
        setTimeout(() => {
            if (notif.parentNode) {
                cerrarNotificacion(notif);
            }
        }, duracion);
        
        function cerrarNotificacion(elemento) {
            elemento.style.transform = 'translateX(100%)';
            elemento.style.opacity = '0';
            setTimeout(() => {
                if (elemento.parentNode) {
                    elemento.parentNode.removeChild(elemento);
                }
            }, 400);
        }
    }
    
    // NUEVAS FUNCIONES DE UTILIDAD
    function deshabilitarBoton(boton, texto = 'Procesando...') {
        if (boton) {
            boton.disabled = true;
            boton.classList.add('cargando');
            boton.dataset.textoOriginal = boton.textContent;
            boton.textContent = texto;
        }
    }
    
    function habilitarBoton(boton) {
        if (boton) {
            boton.disabled = false;
            boton.classList.remove('cargando');
            if (boton.dataset.textoOriginal) {
                boton.textContent = boton.dataset.textoOriginal;
            }
        }
    }
    
    // Event listeners de autenticación
    if (loginToggle) {
        loginToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (dropdownAbierto) {
                cerrarDropdown();
            } else {
                abrirDropdown();
                mostrarFormulario(loginForm);
            }
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
    
    // Hacer funciones globales para el botón de cerrar
    window.cerrarDropdown = cerrarDropdown;
    window.abrirDropdown = abrirDropdown;
    window.mostrarFormulario = mostrarFormulario;
    
    // Formularios de autenticación
    if (formLogin) {
        formLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            const email = formData.get('email');
            const password = formData.get('password');
            
            if (!email || !password) {
                mostrarNotificacion('Por favor, completa todos los campos', 'error');
                return;
            }
            
            if (!email.includes('@')) {
                mostrarNotificacion('Por favor, ingresa un email válido', 'error');
                return;
            }
            
            deshabilitarBoton(submitBtn, 'Iniciando sesión...');
            
            fetch('login_proceso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.exito) {
                    console.log('Login exitoso, procediendo a cerrar dropdown...');
                    if (data.es_admin) {
                        mostrarNotificacion('¡Bienvenido Administrador! Acceso completo al sistema', 'exito');
                    } else {
                        mostrarNotificacion('¡Login exitoso! Recargando página...', 'exito');
                    }
                    // Cerrar dropdown inmediatamente
                    cerrarDropdown();
                    setTimeout(() => {
                        console.log('Recargando página ahora...');
                        window.location.reload();
                    }, 500); // Reducido aún más
                } else {
                    console.log('Login fallido:', data.mensaje);

                    // Caso especial: email no verificado
                    if (data.requiere_verificacion && data.email) {
                        mostrarNotificacion('Debes verificar tu email antes de iniciar sesión', 'warning');

                        // Cambiar al formulario de verificación
                        document.getElementById('email-verificar').value = data.email;
                        mostrarFormulario(verifyForm);

                        // Ofrecer reenviar código automáticamente
                        setTimeout(() => {
                            if (confirm('¿Quieres que te reenviemos el código de verificación?')) {
                                document.getElementById('reenviar-codigo').click();
                            }
                        }, 1000);
                    } else {
                        mostrarNotificacion(data.mensaje || 'Error en el login', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión', 'error');
            })
            .finally(() => {
                habilitarBoton(submitBtn);
            });
        });
    }
    
    if (formRegistro) {
        formRegistro.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const password = formData.get('contrasena');
            const confirmPassword = formData.get('confirmar_contrasena');
            
            if (password !== confirmPassword) {
                mostrarNotificacion('Las contraseñas no coinciden', 'error');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            deshabilitarBoton(submitBtn, 'Registrando...');
            
            fetch('registro_proceso_debug.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    document.getElementById('email-verificar').value = formData.get('correo');

                    // Solo mostrar código temporal en desarrollo
                    if (data.codigo && document.getElementById('codigo-temp')) {
                        document.getElementById('codigo-temp').textContent = data.codigo;
                    } else {
                        // En producción, ocultar el elemento del código temporal
                        const codigoTempElement = document.getElementById('codigo-temp');
                        if (codigoTempElement) {
                            codigoTempElement.parentElement.style.display = 'none';
                        }
                    }

                    mostrarFormulario(verifyForm);

                    if (data.email_enviado) {
                        mostrarNotificacion('¡Registro exitoso! Te enviamos un código de verificación a tu email.', 'exito');
                    } else {
                        mostrarNotificacion('¡Registro exitoso! Usa el código mostrado para verificar.', 'info');
                    }
                } else {
                    mostrarNotificacion(data.mensaje || 'Error en el registro', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión', 'error');
            })
            .finally(() => {
                habilitarBoton(submitBtn);
            });
        });
    }
    
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
                    mostrarNotificacion('¡Email verificado! Ya puedes iniciar sesión.', 'exito');
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
                habilitarBoton(submitBtn);
            });
        });
    }
    
    // ===================================================
    // SISTEMA DE CARRITO (MEJORADO)
    // ===================================================
    
    function actualizarContador(nuevaCantidad) {
        if (contadorCarrito) {
            contadorCarrito.textContent = nuevaCantidad;
            contadorCarrito.style.animation = 'none';
            setTimeout(() => {
                contadorCarrito.style.animation = 'bounceIn 0.5s ease-out';
            }, 10);
        }
    }
    
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
                        const hasImage = producto.imagen && producto.imagen !== 'img/no-image.png';
                        html += `
                            <div class="producto-preview">
                                ${hasImage ? 
                                    `<img src="${producto.imagen}" alt="${producto.nombre}" onerror="this.style.display='none'; this.parentElement.classList.add('image-error');">` :
                                    `<div class="no-image-preview">
                                        <div class="no-image-icon">📷</div>
                                    </div>`
                                }
                                <div class="producto-info">
                                    <h4>${producto.nombre}</h4>
                                    <p>Cantidad: ${producto.cantidad} - $${parseFloat(producto.precio).toFixed(2)}</p>
                                </div>
                            </div>
                        `;
                    });
                    html += `
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <strong>Total: ${parseFloat(data.total).toFixed(2)}</strong>
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
    
    // Manejar botones agregar al carrito
    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Obtener datos del producto desde el elemento padre (product-card)
            const productCard = this.closest('.product-card');
            const productoId = productCard.getAttribute('data-id') || productCard.getAttribute('data-name');
            const productoNombre = productCard.getAttribute('data-name');
            const productoPrecio = productCard.getAttribute('data-price');
            
            if (!productoId || !productoNombre || !productoPrecio) {
                mostrarNotificacion('Error: Datos del producto incompletos', 'error');
                return;
            }
            
            const botonOriginal = this.innerHTML;
            this.innerHTML = 'Agregando...';
            this.classList.add('agregando');
            this.disabled = true;

            fetch('carrito_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${encodeURIComponent(productoId)}&accion=agregar`
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
                this.innerHTML = botonOriginal;
                this.classList.remove('agregando');
                this.disabled = false;

                if (data.exito) {
                    actualizarContador(data.cantidad_total);
                    this.style.background = '#26d45c';
                    this.innerHTML = '¡Agregado!';
                    setTimeout(() => {
                        this.style.background = '';
                        this.innerHTML = botonOriginal;
                    }, 1500);
                    
                    mostrarNotificacion(`"${productoNombre}" agregado al carrito`, 'exito');
                    
                    if (carritoAbierto) {
                        setTimeout(() => cargarVistaPrevia(), 500);
                    }
                } else {
                    mostrarNotificacion('Error: ' + (data.mensaje || 'Error desconocido'), 'error');
                }
            })
            .catch(error => {
                this.innerHTML = botonOriginal;
                this.classList.remove('agregando');
                this.disabled = false;
                mostrarNotificacion('Error de conexión: ' + error.message, 'error');
            });
        });
    });
    
    // Toggle vista previa del carrito
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
    
    function mostrarVistaPrevia() {
        if (vistaPrevia) {
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
    
    // Cerrar vista previa
    if (cerrarVistaPrevia) {
        cerrarVistaPrevia.addEventListener('click', ocultarVistaPrevia);
    }
    
    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (carritoAbierto) {
                ocultarVistaPrevia();
            } else if (dropdownAbierto) {
                cerrarDropdown();
            }
        }
    });
    
    // Prevenir cierre al hacer clic dentro
    if (vistaPrevia) {
        vistaPrevia.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    if (authDropdown) {
        authDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Cargar contador inicial
    cargarVistaPrevia();
    
    // Event listeners para búsqueda
    if (searchInput) {
        searchInput.addEventListener('input', searchProducts);
    }
    
    if (searchBtn) {
        searchBtn.addEventListener('click', searchProducts);
    }
    
    // Búsqueda con Enter
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
    
    console.log('Sistemas de autenticación, carrito y búsqueda inicializados correctamente');
});

// === FUNCIÓN PARA CERRAR SESIÓN (GLOBAL) ===
function cerrarSesion() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        fetch('logout_proceso.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                window.location.reload();
            } else {
                alert('Error al cerrar sesión: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'logout_proceso.php';
        });
    }
}

// === CSS PARA LAS NOTIFICACIONES ===
const styleNotificaciones = document.createElement('style');
styleNotificaciones.textContent = `
    @keyframes progreso {
        0% { transform: scaleX(1); }
        100% { transform: scaleX(0); }
    }
    
    .notif-cerrar:hover {
        background: rgba(0,0,0,0.1) !important;
        color: #666 !important;
    }
    
    .notif-progreso {
        animation: progreso linear forwards;
    }
    
    /* Animación de bounce para el contador */
    @keyframes bounceIn {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(styleNotificaciones);

// ===================================================
// SISTEMA DE DROPDOWN DE CATEGORÍAS (MEJORADO)
// ===================================================

document.addEventListener('DOMContentLoaded', function() {
    const dropdown = document.querySelector('.dropdown');
    const dropdownContent = document.querySelector('.dropdown-content');
    
    if (dropdown && dropdownContent) {
        let timeoutId;
        
        // Función para mostrar el dropdown
        function mostrarDropdown() {
            clearTimeout(timeoutId);
            dropdownContent.style.display = 'block';
            dropdownContent.style.opacity = '1';
            dropdownContent.style.transform = 'translateX(-50%) translateY(0)';
        }
        
        // Función para ocultar el dropdown
        function ocultarDropdown() {
            timeoutId = setTimeout(() => {
                dropdownContent.style.opacity = '0';
                dropdownContent.style.transform = 'translateX(-50%) translateY(-10px)';
                setTimeout(() => {
                    dropdownContent.style.display = 'none';
                }, 300);
            }, 150); // Pequeño delay para evitar flickering
        }
        
        // Eventos del dropdown
        dropdown.addEventListener('mouseenter', mostrarDropdown);
        dropdown.addEventListener('mouseleave', ocultarDropdown);
        
        // Mantener visible cuando el mouse está sobre el contenido
        dropdownContent.addEventListener('mouseenter', mostrarDropdown);
        dropdownContent.addEventListener('mouseleave', ocultarDropdown);
        
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                ocultarDropdown();
            }
        });
        
        // Prevenir que el click en el dropdown lo cierre
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>
</body>
</html>