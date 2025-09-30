<?php
/**
 * Componente de Header Unificado
 * Usar: include 'componentes/header.php';
 * 
 * Variables requeridas (deben estar definidas antes del include):
 * - $usuarioLogueado (bool)
 * - $esAdmin (bool)
 * - $usuarioActual (array|null)
 * - $cantidadCarrito (int)
 * - $categorias (array) - opcional
 */

// Asegurar que las variables existen
$usuarioLogueado = $usuarioLogueado ?? false;
$esAdmin = $esAdmin ?? false;
$usuarioActual = $usuarioActual ?? null;
$cantidadCarrito = $cantidadCarrito ?? 0;
$categorias = $categorias ?? [];
?>

<header class="header">
    <!-- HEADER TOP -->
    <div class="header-top">
        <!-- Logo -->
        <div class="logo">
            <a href="home.php" style="color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                <span class="brand">Librería RL</span>
                <span class="star">★</span>
            </a>
        </div>
        
        <!-- Búsqueda -->
        <div class="search-container">
            <form action="productos_mejorado.php" method="GET" id="search-form">
                <input type="text" 
                       name="buscar" 
                       id="search-input" 
                       placeholder="¿Qué estás buscando?"
                       value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>"
                       autocomplete="off">
                <button type="submit" class="search-button">🔍</button>
            </form>
        </div>
        
        <!-- Navegación -->
        <nav class="nav-links">
            <?php if ($usuarioLogueado): ?>
                <!-- Usuario logueado -->
                <div class="usuario-logueado">
                    <!-- Dropdown usuario -->
                    <div class="dropdown-usuario">
                        <a href="#" class="usuario-info">
                            👤 Hola, <?php echo htmlspecialchars($usuarioActual['Nombre'] ?? ($usuarioActual['usuario'] ?? 'Usuario')); ?>
                            <?php if ($esAdmin): ?>
                                <span style="color: #ffc107; font-weight: bold; margin-left: 4px;">[ADMIN]</span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-usuario-content">
                            <?php if ($esAdmin): ?>
                                <a href="admin_productos.php" style="background: #ffc107; color: #000; font-weight: bold;">
                                    🛠️ Panel Admin
                                </a>
                            <?php endif; ?>
                            <a href="perfil.php">👤 Mi Perfil</a>
                            <a href="mis_pedidos.php">📦 Mis Pedidos</a>
                            <a href="#" onclick="cerrarSesion(); return false;">🚪 Cerrar Sesión</a>
                        </div>
                    </div>
                    
                    <!-- Carrito -->
                    <div class="carrito-container">
                        <a href="#" id="carrito-toggle" class="cart-btn">
                            🛒 <span id="contador-carrito" class="cart-count"><?php echo $cantidadCarrito; ?></span>
                        </a>
                        <!-- Vista previa del carrito (manejada por JavaScript) -->
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
                <!-- Usuario no logueado -->
                <div class="auth-container">
                    <a href="#" id="login-toggle" class="auth-link">Iniciar Sesión</a>
                    
                    <!-- Dropdown de autenticación (manejado por JavaScript en home.php) -->
                    <div id="auth-dropdown" class="auth-dropdown-oculto">
                        <!-- Contenido del formulario de login/registro -->
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- NAVEGACIÓN SECUNDARIA -->
<nav class="nav-secondary">
    <div class="nav-content">
        <a href="home.php" class="nav-link">INICIO</a>
        
        <?php if (!empty($categorias)): ?>
        <!-- Dropdown de categorías -->
        <div class="dropdown">
            <a href="#" class="nav-link dropbtn">CATEGORÍAS ▼</a>
            <div class="dropdown-content">
                <div class="categorias-container">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="columna">
                            <h4><?php echo htmlspecialchars($categoria); ?></h4>
                            <ul>
                                <li>
                                    <a href="productos_mejorado.php?categoria=<?php echo urlencode($categoria); ?>">
                                        Ver <?php echo htmlspecialchars($categoria); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <a href="productos_mejorado.php" class="nav-link">CATEGORÍAS</a>
        <?php endif; ?>
        
        <a href="sobre_nosotros.php" class="nav-link">SOBRE NOSOTROS</a>
        <a href="contacto.php" class="nav-link">CONTACTO</a>
    </div>
</nav>

<script>
// Función global para cerrar sesión
function cerrarSesion() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {