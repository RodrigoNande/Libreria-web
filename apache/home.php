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

    <!-- CSS mejorado (reemplaza el link si tienes uno anterior) -->
    <link rel="stylesheet" href="estilo1.css">
    <link rel="stylesheet" href="estilocarrito.css">

   
    
  
    <title>Librería RL - Inicio</title>
</head>
<body>
<!-- Pantalla de carga -->


<header class="header">
    <!-- HEADER UNIFICADO - Solo una sección -->
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
        
        <!-- REEMPLAZAR la sección <nav class="nav-links"> en home.php con este código -->

<nav class="nav-links">
    <?php if ($usuarioLogueado): ?>
        <!-- USUARIO LOGUEADO - Mostrar perfil y carrito -->
        <div class="usuario-logueado">
            <div class="dropdown-usuario">
                <a href="#" class="usuario-info">
                    👤 Hola, <?php echo htmlspecialchars($usuarioActual['Nombre'] ?? ($usuarioActual['usuario'] ?? 'Usuario')); ?>
                </a>
                <div class="dropdown-usuario-content">
                    <a href="#">Mi Perfil</a>
                    <a href="#">Mis Pedidos</a>
                    <a href="#" onclick="cerrarSesion()">Cerrar Sesión</a>
                </div>
            </div>
        </div>
        
        <!-- CARRITO (solo visible cuando está logueado) -->
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
                    <div class="carrito-loading">
                        <p>Cargando carrito...</p>
                    </div>
                </div>
                <div class="carrito-footer">
                    <a href="vercarrito.php" class="btn-ver-carrito">Ver Carrito Completo</a>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- USUARIO NO LOGUEADO - Solo mostrar iniciar sesión -->
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
                
            </div>
        </div>
        
        <!-- CARRITO PARA INVITADOS (opcional - puedes comentar esta sección si no quieres carrito para invitados) -->
        <!-- 
        <div class="carrito-container">
            <a href="#" id="carrito-toggle-invitado" class="carrito-link">
                🛒 <span id="contador-carrito-invitado">0</span>
            </a>
        </div>
        -->
        
    <?php endif; ?>
</nav>
    </div>
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
// ===================================================
// SISTEMA DE AUTENTICACIÓN CORREGIDO Y MEJORADO
// ===================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // === ELEMENTOS DEL DOM ===
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
    
    let authOverlay = null;
    let dropdownAbierto = false;
    
    // === FUNCIONES DE UTILIDAD ===
    function crearOverlay() {
        if (!authOverlay) {
            authOverlay = document.createElement('div');
            authOverlay.className = 'auth-overlay';
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
            
            const overlay = crearOverlay();
            overlay.classList.add('activo');
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
                authOverlay.classList.remove('activo');
            }
            dropdownAbierto = false;
        }
    }
    
    function mostrarFormulario(formularioMostrar) {
        // Ocultar todos los formularios
        const formularios = [loginForm, registerForm, verifyForm];
        formularios.forEach(form => {
            if (form) form.classList.add('auth-form-oculto');
        });
        
        // Mostrar el formulario solicitado
        if (formularioMostrar) {
            formularioMostrar.classList.remove('auth-form-oculto');
        }
    }
    
    function mostrarMensaje(mensaje, tipo = 'info', duracion = 5000) {
        // Remover mensajes anteriores
        const mensajesAnteriores = document.querySelectorAll('.mensaje-temporal');
        mensajesAnteriores.forEach(msg => msg.remove());
        
        // Crear nuevo mensaje
        const contenedor = document.createElement('div');
        contenedor.className = `mensaje mensaje-temporal ${tipo}`;
        contenedor.innerHTML = `
            <span>${mensaje}</span>
            <button type="button" style="float: right; background: none; border: none; color: inherit; cursor: pointer; font-weight: bold; margin-left: 10px;">&times;</button>
        `;
        
        // Agregar al dropdown
        if (authDropdown) {
            const formularioActivo = authDropdown.querySelector('.auth-form:not(.auth-form-oculto)');
            if (formularioActivo) {
                formularioActivo.insertBefore(contenedor, formularioActivo.firstChild);
            }
        }
        
        // Funcionalidad del botón cerrar
        const btnCerrar = contenedor.querySelector('button');
        btnCerrar.addEventListener('click', () => contenedor.remove());
        
        // Auto-eliminar después del tiempo especificado
        setTimeout(() => {
            if (contenedor.parentNode) {
                contenedor.remove();
            }
        }, duracion);
    }
    
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
    
    // === EVENT LISTENERS ===
    
    // Toggle del dropdown de autenticación
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
    
    // Cambiar entre formularios
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
    
    // === MANEJO DEL FORMULARIO DE LOGIN ===
    if (formLogin) {
        formLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validación básica
            const email = formData.get('email');
            const password = formData.get('password');
            
            if (!email || !password) {
                mostrarMensaje('Por favor, completa todos los campos', 'error');
                return;
            }
            
            if (!email.includes('@')) {
                mostrarMensaje('Por favor, ingresa un email válido', 'error');
                return;
            }
            
            deshabilitarBoton(submitBtn, 'Iniciando sesión...');
            
            fetch('login_proceso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Response text:', text);
                    throw new Error('Error en la respuesta del servidor');
                }
                
                if (data.exito) {
                    mostrarMensaje('¡Inicio de sesión exitoso! Recargando página...', 'exito');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarMensaje(data.mensaje || 'Error desconocido', 'error');
                    habilitarBoton(submitBtn);
                }
            })
            .catch(error => {
                console.error('Error en el login:', error);
                mostrarMensaje('Error de conexión. Inténtalo de nuevo.', 'error');
                habilitarBoton(submitBtn);
            });
        });
    }
    
    // === MANEJO DEL FORMULARIO DE REGISTRO ===
    if (formRegistro) {
        formRegistro.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validaciones
            const datos = Object.fromEntries(formData);
            
            if (!datos.nombre || !datos.apellido || !datos.correo || !datos.usuario || !datos.contrasena) {
                mostrarMensaje('Por favor, completa todos los campos obligatorios', 'error');
                return;
            }
            
            if (!datos.correo.includes('@')) {
                mostrarMensaje('Por favor, ingresa un email válido', 'error');
                return;
            }
            
            if (datos.contrasena.length < 6) {
                mostrarMensaje('La contraseña debe tener al menos 6 caracteres', 'error');
                return;
            }
            
            if (datos.contrasena !== datos.confirmar_contrasena) {
                mostrarMensaje('Las contraseñas no coinciden', 'error');
                return;
            }
            
            deshabilitarBoton(submitBtn, 'Creando cuenta...');
            
            fetch('registro_proceso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Response text:', text);
                    throw new Error('Error en la respuesta del servidor');
                }
                
                if (data.exito) {
                    // Mostrar código temporal si está disponible
                    if (data.codigo) {
                        const codigoTemp = document.getElementById('codigo-temp');
                        if (codigoTemp) {
                            codigoTemp.textContent = data.codigo;
                        }
                    }
                    
                    // Configurar email para verificación
                    const emailVerificar = document.getElementById('email-verificar');
                    if (emailVerificar) {
                        emailVerificar.value = datos.correo;
                    }
                    
                    mostrarMensaje('¡Cuenta creada! Ahora verifica tu email.', 'exito');
                    setTimeout(() => {
                        mostrarFormulario(verifyForm);
                    }, 2000);
                } else {
                    mostrarMensaje(data.mensaje || 'Error desconocido', 'error');
                    habilitarBoton(submitBtn);
                }
            })
            .catch(error => {
                console.error('Error en el registro:', error);
                mostrarMensaje('Error de conexión. Inténtalo de nuevo.', 'error');
                habilitarBoton(submitBtn);
            });
        });
    }
    
    // === MANEJO DEL FORMULARIO DE VERIFICACIÓN ===
    if (formVerificacion) {
        formVerificacion.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            const codigo = formData.get('codigo');
            const email = formData.get('email');
            
            if (!codigo || !email) {
                mostrarMensaje('Código y email son requeridos', 'error');
                return;
            }
            
            if (codigo.length !== 6 || !/^\d{6}$/.test(codigo)) {
                mostrarMensaje('El código debe tener 6 dígitos', 'error');
                return;
            }
            
            deshabilitarBoton(submitBtn, 'Verificando...');
            
            fetch('verificar_proceso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Response text:', text);
                    throw new Error('Error en la respuesta del servidor');
                }
                
                if (data.exito) {
                    mostrarMensaje('¡Email verificado correctamente! Ya puedes iniciar sesión.', 'exito');
                    setTimeout(() => {
                        mostrarFormulario(loginForm);
                    }, 2000);
                } else {
                    mostrarMensaje(data.mensaje || 'Código inválido', 'error');
                    habilitarBoton(submitBtn);
                }
            })
            .catch(error => {
                console.error('Error en la verificación:', error);
                mostrarMensaje('Error de conexión. Inténtalo de nuevo.', 'error');
                habilitarBoton(submitBtn);
            });
        });
    }
    
    // === CERRAR DROPDOWN CON ESCAPE ===
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && dropdownAbierto) {
            cerrarDropdown();
        }
    });
    
    // === PREVENIR CIERRE AL HACER CLIC DENTRO DEL DROPDOWN ===
    if (authDropdown) {
        authDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
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
            // Intentar cerrar sesión de forma manual como fallback
            window.location.href = 'logout_proceso.php';
        });
    }
}
</script>
</body>
</html>
