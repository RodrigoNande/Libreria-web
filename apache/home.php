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

// CORREGIR: Obtener datos ¿del usuario actual de la forma correcta
$usuarioActual = null;
if ($usuarioLogueado) {
    // Opción 1: Usar la función que ya tienes
    $usuarioActual = obtenerUsuarioActual();
    
    // Opción 2: O directamente desde sesión si prefieres
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

    <!-- Fuentes mejoradas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet">


    <!-- Meta tags mejorados -->
    <meta name="theme-color" content="#120049">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- CSS mejorado (reemplaza el link si tienes uno anterior) -->
    <link rel="stylesheet" href="estilo1.css">
    <link rel="stylesheet" href="estilocarrito.css">

    <!-- Estilos de carga inicial y mejoras visuales -->
    <style>
    .loading-screen {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex; align-items: center; justify-content: center;
        z-index: 10000; transition: opacity 0.5s ease;
    }
    .loading-spinner {
        width: 50px; height: 50px;
        border: 4px solid rgba(255,255,255,0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    .loading-text {
        color: white; font-size: 18px; font-weight: 600; margin-top: 20px;
    }
    /* Breadcrumbs y mejoras visuales */
    .breadcrumbs { padding: 20px 30px; max-width: 1600px; margin: 0 auto; }
    .breadcrumbs ul { display: flex; list-style: none; padding: 0; margin: 0; flex-wrap: wrap; }
    .breadcrumbs li { display: flex; align-items: center; color: var(--text-light); font-size: 14px; }
    .breadcrumbs li:not(:last-child)::after { content: '→'; margin: 0 10px; color: var(--secondary-color); }
    .breadcrumbs a { color: var(--text-medium); text-decoration: none; transition: var(--transition-fast); }
    .breadcrumbs a:hover { color: var(--primary-color); }
    .animate { animation: fadeInUp 0.6s ease-out; }
    .producto.animate { animation: fadeInUp 0.6s ease-out; }
    .quick-view {
        position: absolute; top: 15px; right: 15px;
        background: rgba(255,255,255,0.9); border: none;
        width: 40px; height: 40px; border-radius: 50%;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        opacity: 0; transform: scale(0.8); transition: all 0.3s ease; z-index: 10;
    }
    .producto:hover .quick-view { opacity: 1; transform: scale(1); }
    .quick-view:hover { background: var(--secondary-color); color: white; }
    </style>
    <!-- ...existing head code... -->
</head>
<body>
<!-- Pantalla de carga -->
<div class="loading-screen" id="loading-screen">
    <div style="text-align: center;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Cargando Librería RL...</div>
    </div>
</div>

<header class="header">
    <!-- Primera fila: Logo, búsqueda y usuario -->
    <div class="header-top">
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
            <?php if ($usuarioLogueado): ?>
                <!-- Usuario logueado -->
                <div class="usuario-logueado">
                    <div class="dropdown-usuario">
                        <a href="#" class="usuario-info">
                            👤 Hola, <?php echo htmlspecialchars($usuarioActual['Nombre'] ?? ($usuarioActual['usuario'] ?? 'Usuario')); ?>
                        </a>
                        <div class="dropdown-usuario-content">
                            <a href="#">Mi Perfil</a>
                            <a href="#" onclick="cerrarSesion()">Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
                
                <!-- Carrito (solo visible si está logueado) -->
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
            <?php else: ?>
                <!-- Usuario no logueado -->
                <div class="auth-container">
                  <!--  <a href="#" id="login-toggle" class="auth-link">Iniciar Sesión</a> -->
                    
                    
                    <div id="auth-dropdown" class="auth-dropdown-oculto">
                         <!-- Formulario de Login -->
                    <div id="login-form" class="auth-form">
                        <h3>Iniciar Sesión</h3>
                        <form id="form-login">
                            <div class="form-group">
                                <input type="email" id="login-email" name="email" placeholder="Correo electrónico" required>
                            </div>
                            <div class="form-group">
                                <input type="password" id="login-password" name="password" placeholder="Contraseña" required>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" id="recordarme" name="recordarme">
                                    <span class="checkmark"></span>
                                    Recordarme
                                </label>
                            </div>
                            <button type="submit" class="btn-auth">Iniciar Sesión</button>
                        </form>
                        <div class="auth-separator">
                            <span>¿No tienes cuenta?</span>
                            <a href="#" id="mostrar-registro">Regístrate aquí</a>
                        </div>
                    </div>

                    <!-- Formulario de Registro -->
                    <div id="register-form" class="auth-form auth-form-oculto">
                        <h3>Crear Cuenta</h3>
                        <form id="form-registro">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="nombre" placeholder="Nombre" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="apellido" placeholder="Apellido" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="email" name="correo" placeholder="Correo electrónico" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="usuario" placeholder="Nombre de usuario" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="telefono" placeholder="Teléfono (opcional)">
                            </div>
                            <div class="form-group">
                                <input type="text" name="direccion" placeholder="Dirección (opcional)">
                            </div>
                            <div class="form-group">
                                <input type="password" name="contrasena" placeholder="Contraseña" required minlength="6">
                            </div>
                            <div class="form-group">
                                <input type="password" name="confirmar_contrasena" placeholder="Confirmar contraseña" required minlength="6">
                            </div>
                            <button type="submit" class="btn-auth">Crear Cuenta</button>
                        </form>
                        <div class="auth-separator">
                            <span>¿Ya tienes cuenta?</span>
                            <a href="#" id="mostrar-login">Inicia sesión aquí</a>
                        </div>
                    </div>

                    <!-- Formulario de Verificación -->
                    <div id="verify-form" class="auth-form auth-form-oculto">
                        <h3>Verificar Email</h3>
                        <p>Te enviamos un código de 6 dígitos a tu correo.</p>
                        <p><strong>Código temporal: <span id="codigo-temp"></span></strong></p>
                        <form id="form-verificacion">
                            <div class="form-group">
                                <input type="text" id="codigo-verificacion" name="codigo" placeholder="Código de 6 dígitos" maxlength="6" required>
                            </div>
                            <input type="hidden" id="email-verificar" name="email">
                            <button type="submit" class="btn-auth">Verificar</button>
                        </form>
                        <div class="auth-separator">
                            <a href="#" id="reenviar-codigo">Reenviar código</a>
                        </div>
                    </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
    <nav class="nav-links">
         <?php if ($usuarioLogueado): ?>
            <!-- Usuario logueado -->
            <div class="usuario-logueado">
                <div class="dropdown-usuario">
                    <a href="#" class="usuario-info">
                        👤 Hola, <?php echo htmlspecialchars($usuarioActual['Nombre'] ?? ($usuarioActual['usuario'] ?? 'Usuario')); ?>
                    </a>
                    <div class="dropdown-usuario-content">
                        <a href="#">Mi Perfil</a>
                        <a href="#" onclick="cerrarSesion()">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
            
            <!-- Carrito (solo visible si está logueado) -->
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
        <?php else: ?>
            <!-- Usuario no logueado -->
            <div class="auth-container">
                <a href="#" id="login-toggle" class="auth-link">Iniciar Sesión</a>
                
                <!-- Dropdown de autenticación -->
                <div id="auth-dropdown" class="auth-dropdown-oculto">
                    <!-- Formulario de Login -->
                    <div id="login-form" class="auth-form">
                        <h3>Iniciar Sesión</h3>
                        <form id="form-login">
                            <div class="form-group">
                                <input type="email" id="login-email" name="email" placeholder="Correo electrónico" required>
                            </div>
                            <div class="form-group">
                                <input type="password" id="login-password" name="password" placeholder="Contraseña" required>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" id="recordarme" name="recordarme">
                                    <span class="checkmark"></span>
                                    Recordarme
                                </label>
                            </div>
                            <button type="submit" class="btn-auth">Iniciar Sesión</button>
                        </form>
                        <div class="auth-separator">
                            <span>¿No tienes cuenta?</span>
                            <a href="#" id="mostrar-registro">Regístrate aquí</a>
                        </div>
                    </div>

                    <!-- Formulario de Registro -->
                    <div id="register-form" class="auth-form auth-form-oculto">
                        <h3>Crear Cuenta</h3>
                        <form id="form-registro">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="nombre" placeholder="Nombre" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="apellido" placeholder="Apellido" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="email" name="correo" placeholder="Correo electrónico" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="usuario" placeholder="Nombre de usuario" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="telefono" placeholder="Teléfono (opcional)">
                            </div>
                            <div class="form-group">
                                <input type="text" name="direccion" placeholder="Dirección (opcional)">
                            </div>
                            <div class="form-group">
                                <input type="password" name="contrasena" placeholder="Contraseña" required minlength="6">
                            </div>
                            <div class="form-group">
                                <input type="password" name="confirmar_contrasena" placeholder="Confirmar contraseña" required minlength="6">
                            </div>
                            <button type="submit" class="btn-auth">Crear Cuenta</button>
                        </form>
                        <div class="auth-separator">
                            <span>¿Ya tienes cuenta?</span>
                            <a href="#" id="mostrar-login">Inicia sesión aquí</a>
                        </div>
                    </div>

                    <!-- Formulario de Verificación -->
                    <div id="verify-form" class="auth-form auth-form-oculto">
                        <h3>Verificar Email</h3>
                        <p>Te enviamos un código de 6 dígitos a tu correo.</p>
                        <p><strong>Código temporal: <span id="codigo-temp"></span></strong></p>
                        <form id="form-verificacion">
                            <div class="form-group">
                                <input type="text" id="codigo-verificacion" name="codigo" placeholder="Código de 6 dígitos" maxlength="6" required>
                            </div>
                            <input type="hidden" id="email-verificar" name="email">
                            <button type="submit" class="btn-auth">Verificar</button>
                        </form>
                        <div class="auth-separator">
                            <a href="#" id="reenviar-codigo">Reenviar código</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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

<!-- Breadcrumbs -->
<nav class="breadcrumbs" aria-label="Ruta de navegación">
    <ul>
        <li><a href="/">Inicio</a></li>
        <li>Productos</li>
    </ul>
</nav>

<main class="productos" role="main" aria-label="Lista de productos">
    <?php
    $sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $contador = 0;
        while ($row = $result->fetch_assoc()) {
            $imgSrc = $row['ruta'] ? htmlspecialchars($row['ruta']) : "img/no-image.png";
            $clases_especiales = '';
            if ($contador < 3) $clases_especiales .= ' nuevo';
            if ($row['Precio'] < 5) $clases_especiales .= ' oferta';
            if ($contador % 4 == 0) $clases_especiales .= ' popular';
            ?>
            <article class="producto<?php echo $clases_especiales; ?>"
                     data-id="<?php echo $row['IdProducto']; ?>"
                     data-nombre="<?php echo htmlspecialchars($row['NomProducto']); ?>"
                     data-precio="<?php echo $row['Precio']; ?>"
                     data-marca="<?php echo htmlspecialchars($row['Marca']); ?>">
                <!-- Vista rápida -->
                <button class="quick-view tooltip" data-tooltip="Vista rápida" aria-label="Vista rápida del producto">👁️</button>
                <a href="formularioproducto.php?id=<?php echo $row['IdProducto']; ?>"
                   aria-label="Ver detalles de <?php echo htmlspecialchars($row['NomProducto']); ?>">
                    <img src="<?php echo $imgSrc; ?>"
                         alt="<?php echo htmlspecialchars($row['NomProducto']); ?>"
                         loading="lazy"
                         onerror="this.src='img/no-image.png'">
                    <h3><?php echo htmlspecialchars($row['NomProducto']); ?></h3>
                    <p>Precio: <strong>$<?php echo number_format($row['Precio'], 2); ?></strong></p>
                    <p>Marca: <?php echo htmlspecialchars($row['Marca']); ?></p>
                    <p>Precio Unitario: $<?php echo number_format($row['Precio_Unitario'], 2); ?></p>
                </a>
                <button class="btn-agregar-carrito tooltip"
                        data-id="<?php echo $row['IdProducto']; ?>"
                        data-nombre="<?php echo htmlspecialchars($row['NomProducto']); ?>"
                        data-precio="<?php echo $row['Precio']; ?>"
                        data-tooltip="Agregar al carrito"
                        aria-label="Agregar <?php echo htmlspecialchars($row['NomProducto']); ?> al carrito">
                    <span>Agregar al Carrito</span>
                </button>
            </article>
            <?php
            $contador++;
        }
    } else {
        echo "<p>No hay productos disponibles</p>";
    }
    ?>
</main>

<!-- Paginación ejemplo -->
<nav class="paginacion" aria-label="Navegación de páginas">
    <a href="#" aria-label="Página anterior">‹</a>
    <span class="actual" aria-current="page">1</span>
    <a href="#" aria-label="Página 2">2</a>
    <a href="#" aria-label="Página 3">3</a>
    <a href="#" aria-label="Siguiente página">›</a>
</nav>

<!-- Modal mejorado para confirmación -->
<div class="modal-overlay" id="modal-confirmacion" role="dialog" aria-labelledby="modal-title" aria-modal="true">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title" class="modal-title">¡Producto agregado!</h3>
            <button class="modal-close" aria-label="Cerrar modal">&times;</button>
        </div>
        <div class="modal-body">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 48px; color: var(--success-color);">✅</div>
            </div>
            <p id="mensaje-producto" style="text-align: center;"></p>
        </div>
        <div class="modal-footer">
            <button id="continuar-comprando" class="btn-secondary">Continuar Comprando</button>
            <a href="vercarrito.php" class="btn-primary">Ver Carrito</a>
        </div>
    </div>
</div>

<script>
// Pantalla de carga
window.addEventListener('load', function() {
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.style.opacity = '0';
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500);
        }, 1000);
    }
});

// Mejorar el contador del carrito
function actualizarContadorCarrito(cantidad) {
    const contadorCarrito = document.getElementById('contador-carrito');
    if (contadorCarrito) {
        contadorCarrito.textContent = cantidad;
        contadorCarrito.style.animation = 'none';
        setTimeout(() => {
            contadorCarrito.style.animation = 'bounceIn 0.5s ease-out';
        }, 10);
    }
}

// ==========================
// VISTA PREVIA DEL CARRITO Y AGREGAR AL CARRITO MEJORADO
// ==========================
document.addEventListener('DOMContentLoaded', function() {
    // Variables para vista previa del carrito
    const carritoToggle = document.getElementById('carrito-toggle');
    const vistaPrevia = document.getElementById('vista-previa-carrito');
    const cerrarVistaPrevia = document.querySelector('.cerrar-vista-previa');
    let vistaAbbierta = false;

    // Funcionalidad de vista previa del carrito
    if (carritoToggle) {
        carritoToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (!vistaAbbierta) {
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

    // Cerrar vista previa al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (vistaAbbierta && !e.target.closest('.carrito-container')) {
            ocultarVistaPrevia();
        }
    });

    function mostrarVistaPrevia() {
        if (vistaPrevia) {
            vistaPrevia.classList.remove('vista-previa-oculta');
            setTimeout(() => vistaPrevia.classList.add('mostrar'), 10);
            vistaAbbierta = true;
        }
    }

    function ocultarVistaPrevia() {
        if (vistaPrevia) {
            vistaPrevia.classList.remove('mostrar');
            setTimeout(() => vistaPrevia.classList.add('vista-previa-oculta'), 300);
            vistaAbbierta = false;
        }
    }

    function cargarVistaPrevia() {
        const contenedor = document.getElementById('productos-vista-previa');
        // Mostrar loading
        if (contenedor) {
            contenedor.innerHTML = `
                <div class="carrito-loading">
                    <p>Cargando carrito...</p>
                </div>
            `;
        }

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
            if (contenedor) {
                if (data.error) {
                    contenedor.innerHTML = `
                        <div class="carrito-error">
                            <p>Error: ${data.mensaje}</p>
                            <small>Revisa la consola para más detalles</small>
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
                    contenedor.innerHTML = html;
                } else {
                    contenedor.innerHTML = `
                        <div class="carrito-vacio">
                            <p>Tu carrito está vacío</p>
                            <small>¡Agrega algunos productos!</small>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            if (contenedor) {
                contenedor.innerHTML = `
                    <div class="carrito-error">
                        <p>Error al cargar el carrito</p>
                        <small>${error.message}</small>
                        <br><small>Revisa la consola para más detalles</small>
                    </div>
                `;
            }
        });
    }

    // Funcionalidad de agregar al carrito (mejorada)
    const botonesAgregar = document.querySelectorAll('.btn-agregar-carrito');
    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            const productoId = this.getAttribute('data-id');
            const productoNombre = this.getAttribute('data-nombre');
            const productoPrecio = this.getAttribute('data-precio');
            if (!productoId || !productoNombre || !productoPrecio) {
                alert('Error: Datos del producto incompletos');
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
                // Restaurar botón
                this.innerHTML = botonOriginal;
                this.classList.remove('agregando');
                this.disabled = false;

                if (data.exito) {
                    // Actualizar contador
                    actualizarContadorCarrito(data.cantidad_total);
                    // Mostrar confirmación visual
                    this.style.background = '#26d45c';
                    this.innerHTML = '¡Agregado!';
                    setTimeout(() => {
                        this.style.background = '';
                        this.innerHTML = botonOriginal;
                    }, 1500);
                    // Mostrar notificación simple
                    mostrarNotificacion(`${productoNombre} agregado al carrito`);
                } else {
                    alert('Error: ' + (data.mensaje || 'Error desconocido'));
                }
            })
            .catch(error => {
                // Restaurar botón
                this.innerHTML = botonOriginal;
                this.classList.remove('agregando');
                this.disabled = false;
                alert('Error de conexión: ' + error.message + '\nRevisa la consola para más detalles.');
            });
        });
    });

    // Función para mostrar notificación simple
    function mostrarNotificacion(mensaje) {
        // Crear notificación temporal
        const notificacion = document.createElement('div');
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #26d45c;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        notificacion.textContent = mensaje;
        document.body.appendChild(notificacion);
        // Animar entrada
        setTimeout(() => {
            notificacion.style.transform = 'translateX(0)';
        }, 100);
        // Remover después de 3 segundos
        setTimeout(() => {
            notificacion.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 3000);
    }

    // Cerrar vista previa con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && vistaAbbierta) {
            ocultarVistaPrevia();
        }
    });

    // Efectos hover en productos (opcional)
    const productos = document.querySelectorAll('.producto');
    productos.forEach(producto => {
        producto.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        producto.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Mejorar el sistema de autenticación (login.php)
document.addEventListener('DOMContentLoaded', function() {
    const formularioLogin = document.getElementById('formulario-login');
    const loginToggle = document.getElementById('login-toggle');
    const authDropdown = document.getElementById('auth-dropdown');

    if (loginToggle) {
        loginToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (authDropdown.classList.contains('auth-dropdown-oculto')) {
                authDropdown.classList.remove('auth-dropdown-oculto');
                authDropdown.classList.add('auth-dropdown-visible');
                setTimeout(() => {
                    authDropdown.classList.add('mostrar');
                }, 10);
            } else {
                authDropdown.classList.remove('mostrar');
                setTimeout(() => {
                    authDropdown.classList.add('auth-dropdown-oculto');
                }, 300);
            }
        });
    }

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.auth-container')) {
            authDropdown.classList.remove('mostrar');
            setTimeout(() => {
                authDropdown.classList.add('auth-dropdown-oculto');
            }, 300);
        }
    });

    // Manejo del formulario de login
    if (formularioLogin) {
        formularioLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formularioLogin);
            const datos = Object.fromEntries(formData);

            // Validación simple
            if (!datos.usuario || !datos.password) {
                return mostrarMensaje('Por favor, completa todos los campos', 'error');
            }

            // Enviar datos por AJAX
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    // Login exitoso
                    location.reload();
                } else {
                    mostrarMensaje(data.mensaje || 'Error desconocido', 'error');
                }
            })
            .catch(error => {
                console.error('Error en el login:', error);
                mostrarMensaje('Error de conexión. Inténtalo de nuevo más tarde.', 'error');
            });
        });
    }

    function mostrarMensaje(mensaje, tipo) {
        const contenedor = document.createElement('div');
        contenedor.className = `mensaje ${tipo}`;
        contenedor.textContent = mensaje;
        document.body.appendChild(contenedor);

        setTimeout(() => {
            contenedor.classList.add('mostrar');
        }, 100);

        setTimeout(() => {
            contenedor.classList.remove('mostrar');
            setTimeout(() => {
                document.body.removeChild(contenedor);
            }, 300);
        }, 3000);
    }
});

// ...resto de tu código JS (tooltips, autenticación, etc.)...
</script>
<!-- ...resto de tu código existente (autenticación, carrito, etc.)... -->
</body>
</html>

