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
    <link rel="stylesheet" href="estilo1.css">
    <link rel="stylesheet" href="estilocarrito.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>Librería RL</title>
</head>
<body>
   <header class="header">
     <!--<a href="?debug=1" style="background: red; color: white; padding: 5px; position: absolute; top: 0; right: 0; z-index: 9999;">DEBUG</a>-->
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
                    <!-- Botón que funciona con tu carrito.php original 
                    <a href="carrito.php?id=<?php echo $row['IdProducto']; ?>" 
                       class="btn-agregar-carrito-original">
                        Agregar al Carrito (Original)-->
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
<script>
function actualizarContadorCarrito(cantidad) {
    const contadorCarrito = document.getElementById('contador-carrito');
    if (contadorCarrito) {
        contadorCarrito.textContent = cantidad;
        // Agregar animación
        contadorCarrito.style.animation = 'none';
        setTimeout(() => {
            contadorCarrito.style.animation = 'pulse 0.5s ease-in-out';
        }, 10);
    }
}

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
            console.log('Vista previa - Response status:', response.status);
            console.log('Vista previa - Content-Type:', response.headers.get('Content-Type'));
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            console.log('Vista previa - Raw response:', text);
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Vista previa - JSON parse error:', e);
                throw new Error(`Error parsing JSON: ${e.message}. Response: ${text.substring(0, 200)}`);
            }
        })
        .then(data => {
            console.log('Vista previa - Parsed data:', data);
            
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
            console.error('Vista previa - Error completo:', error);
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
                console.error('Datos del producto incompletos:', {
                    id: productoId,
                    nombre: productoNombre,
                    precio: productoPrecio
                });
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
                console.log('Agregar carrito - Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text();
            })
            .then(text => {
                console.log('Agregar carrito - Raw response:', text);
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Agregar carrito - JSON parse error:', e);
                    throw new Error(`Error parsing JSON: ${e.message}. Response: ${text.substring(0, 200)}`);
                }
            })
            .then(data => {
                console.log('Agregar carrito - Data received:', data);
                
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
                    
                    // Mostrar modal o notificación simple
                    mostrarNotificacion(`${productoNombre} agregado al carrito`);
                } else {
                    alert('Error: ' + (data.mensaje || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Agregar carrito - Error completo:', error);
                
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

// JavaScript para autenticación - agregar después del script existente en home.php

// Variables para autenticación
const loginToggle = document.getElementById('login-toggle');
const authDropdown = document.getElementById('auth-dropdown');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const verifyForm = document.getElementById('verify-form');
const mostrarRegistro = document.getElementById('mostrar-registro');
const mostrarLogin = document.getElementById('mostrar-login');

let authAbierto = false;

// ==========================================
// FUNCIONALIDAD DEL DROPDOWN DE AUTH
// ==========================================

if (loginToggle) {
    loginToggle.addEventListener('click', function(e) {
        e.preventDefault();
        if (!authAbierto) {
            mostrarAuthDropdown();
        } else {
            ocultarAuthDropdown();
        }
    });
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(e) {
    if (authAbierto && !e.target.closest('.auth-container')) {
        ocultarAuthDropdown();
    }
});

function mostrarAuthDropdown() {
    if (authDropdown) {
        authDropdown.classList.remove('auth-dropdown-oculto');
        setTimeout(() => authDropdown.classList.add('mostrar'), 10);
        authAbierto = true;
    }
}

function ocultarAuthDropdown() {
    if (authDropdown) {
        authDropdown.classList.remove('mostrar');
        setTimeout(() => authDropdown.classList.add('auth-dropdown-oculto'), 300);
        authAbierto = false;
    }
}

// ==========================================
// NAVEGACIÓN ENTRE FORMULARIOS
// ==========================================

if (mostrarRegistro) {
    mostrarRegistro.addEventListener('click', function(e) {
        e.preventDefault();
        mostrarFormulario('register');
    });
}

if (mostrarLogin) {
    mostrarLogin.addEventListener('click', function(e) {
        e.preventDefault();
        mostrarFormulario('login');
    });
}

function mostrarFormulario(tipo) {
    // Ocultar todos los formularios
    if (loginForm) loginForm.classList.add('auth-form-oculto');
    if (registerForm) registerForm.classList.add('auth-form-oculto');
    if (verifyForm) verifyForm.classList.add('auth-form-oculto');
    
    // Mostrar el formulario solicitado
    switch (tipo) {
        case 'login':
            if (loginForm) loginForm.classList.remove('auth-form-oculto');
            break;
        case 'register':
            if (registerForm) registerForm.classList.remove('auth-form-oculto');
            break;
        case 'verify':
            if (verifyForm) verifyForm.classList.remove('auth-form-oculto');
            break;
    }
}

// ==========================================
// PROCESAMIENTO DE FORMULARIOS
// ==========================================

// Login
if (document.getElementById('form-login')) {
    document.getElementById('form-login').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const email = formData.get('email');
        const password = formData.get('password');
        const recordarme = formData.get('recordarme') ? '1' : '0';
        
        if (!email || !password) {
            mostrarMensaje('Por favor completa todos los campos', 'error');
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Iniciando sesión...';
        submitBtn.disabled = true;
        
        fetch('login_proceso.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&recordarme=${recordarme}`
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (data.exito) {
                mostrarMensaje(data.mensaje, 'exito');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                mostrarMensaje(data.mensaje, 'error');
            }
        })
        .catch(error => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            mostrarMensaje('Error de conexión: ' + error.message, 'error');
        });
    });
}

// Registro
if (document.getElementById('form-registro')) {
    document.getElementById('form-registro').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const password = formData.get('contrasena');
        const confirmPassword = formData.get('confirmar_contrasena');
        
        // Validar contraseñas
        if (password !== confirmPassword) {
            mostrarMensaje('Las contraseñas no coinciden', 'error');
            return;
        }
        
        if (password.length < 6) {
            mostrarMensaje('La contraseña debe tener al menos 6 caracteres', 'error');
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Creando cuenta...';
        submitBtn.disabled = true;
        
        fetch('registro_proceso.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (data.exito) {
                mostrarMensaje(data.mensaje, 'exito');
                
                // Mostrar código temporal (solo para desarrollo)
                if (data.codigo) {
                    document.getElementById('codigo-temp').textContent = data.codigo;
                }
                
                // Configurar email para verificación
                document.getElementById('email-verificar').value = formData.get('correo');
                
                // Cambiar a formulario de verificación
                setTimeout(() => {
                    mostrarFormulario('verify');
                }, 1500);
            } else {
                mostrarMensaje(data.mensaje, 'error');
            }
        })
        .catch(error => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            mostrarMensaje('Error de conexión: ' + error.message, 'error');
        });
    });
}

// Verificación
if (document.getElementById('form-verificacion')) {
    document.getElementById('form-verificacion').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const codigo = formData.get('codigo');
        const email = formData.get('email');
        
        if (!codigo || codigo.length !== 6) {
            mostrarMensaje('Ingresa un código de 6 dígitos', 'error');
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Verificando...';
        submitBtn.disabled = true;
        
        fetch('verificar_proceso.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}&codigo=${encodeURIComponent(codigo)}`
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (data.exito) {
                mostrarMensaje(data.mensaje, 'exito');
                setTimeout(() => {
                    mostrarFormulario('login');
                    mostrarMensaje('Ahora puedes iniciar sesión', 'exito');
                }, 1500);
            } else {
                mostrarMensaje(data.mensaje, 'error');
            }
        })
        .catch(error => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            mostrarMensaje('Error de conexión: ' + error.message, 'error');
        });
    });
}

// Auto-format código de verificación (solo números, máximo 6)
if (document.getElementById('codigo-verificacion')) {
    document.getElementById('codigo-verificacion').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').substring(0, 6);
    });
}

// ==========================================
// FUNCIONES DE SOPORTE
// ==========================================

function mostrarMensaje(mensaje, tipo) {
    // Remover mensaje anterior si existe
    const mensajeAnterior = document.querySelector('.mensaje');
    if (mensajeAnterior) {
        mensajeAnterior.remove();
    }
    
    // Crear nuevo mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `mensaje ${tipo}`;
    mensajeDiv.textContent = mensaje;
    
    // Insertar en el formulario activo
    const formularioActivo = document.querySelector('.auth-form:not(.auth-form-oculto)');
    if (formularioActivo) {
        formularioActivo.insertBefore(mensajeDiv, formularioActivo.firstChild);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (mensajeDiv.parentNode) {
                mensajeDiv.remove();
            }
        }, 5000);
    }
}

function cerrarSesion() {
    if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
        fetch('logout_proceso.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                window.location.reload();
            } else {
                alert('Error al cerrar sesión');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Recargar de todas formas
            window.location.reload();
        });
    }
}
</script>
</body>
</html>

