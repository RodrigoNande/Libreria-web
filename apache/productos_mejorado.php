<?php
session_start();
require_once 'conexion.php';
require_once 'auth.php';
require_once 'paginacion_helper.php';

// Obtener datos del usuario
$usuarioLogueado = estaLogueado();
$esAdmin = estaLogueado() && esAdmin();
$usuarioActual = $usuarioLogueado ? obtenerUsuarioActual() : null;

// Contar carrito
$cantidadCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        $cantidadCarrito += $cantidad;
    }
}

// === PARÁMETROS DE BÚSQUEDA Y FILTRADO ===
$categoria = isset($_GET['categoria']) ? sanitizar_entrada($_GET['categoria']) : '';
$subcategoria = isset($_GET['subcategoria']) ? sanitizar_entrada($_GET['subcategoria']) : '';
$busqueda = isset($_GET['buscar']) ? sanitizar_entrada($_GET['buscar']) : '';
$ordenar = isset($_GET['ordenar']) ? sanitizar_entrada($_GET['ordenar']) : 'reciente';

// Configuración de paginación
$itemsPorPagina = 12;

// === CONSTRUIR CONSULTA ===
$condiciones = [];
$params = [];
$types = '';

// Búsqueda por texto
if (!empty($busqueda)) {
    $condiciones[] = "(a.NomProducto LIKE ? OR a.Marca LIKE ? OR a.TipoProducto LIKE ?)";
    $busquedaParam = "%$busqueda%";
    $params[] = $busquedaParam;
    $params[] = $busquedaParam;
    $params[] = $busquedaParam;
    $types .= 'sss';
}

// Filtro por categoría
if (!empty($categoria)) {
    $condiciones[] = "a.TipoProducto = ?";
    $params[] = $categoria;
    $types .= 's';
}

// Filtro por subcategoría
if (!empty($subcategoria)) {
    $condiciones[] = "a.NomProducto LIKE ?";
    $params[] = "%$subcategoria%";
    $types .= 's';
}

// Construir WHERE
$whereClause = !empty($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

// Ordenamiento
$orderClause = match($ordenar) {
    'precio_asc' => 'ORDER BY a.Precio ASC',
    'precio_desc' => 'ORDER BY a.Precio DESC',
    'nombre_asc' => 'ORDER BY a.NomProducto ASC',
    'nombre_desc' => 'ORDER BY a.NomProducto DESC',
    default => 'ORDER BY a.IdProducto DESC'
};

// === CONTAR TOTAL DE PRODUCTOS ===
$sqlCount = "SELECT COUNT(*) as total FROM articulo a $whereClause";
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalItems = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

// === CREAR PAGINACIÓN ===
$paginacion = crear_paginacion($totalItems, $itemsPorPagina);

// === OBTENER PRODUCTOS ===
$sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, a.Stock, i.ruta
        FROM articulo a
        LEFT JOIN img i ON a.IdProducto = i.idProd
        $whereClause
        $orderClause
        " . $paginacion->obtenerClausulaSQL();

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}
$stmt->close();

// === OBTENER CATEGORÍAS PARA EL MENÚ ===
function obtenerCategoriasMenu($conn) {
    $categorias = [];
    $sql = "SELECT DISTINCT TipoProducto FROM articulo ORDER BY TipoProducto";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row['TipoProducto'];
        }
    }
    
    return $categorias;
}

$categorias = obtenerCategoriasMenu($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Productos de Librería RL - Encuentra todo lo que necesitas">
    <title><?php echo !empty($busqueda) ? "Búsqueda: $busqueda" : "Productos"; ?> - Librería RL</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Estilos unificados -->
    <link rel="stylesheet" href="estilos_unificados.css">
    <link rel="stylesheet" href="estilocarrito.css">
</head>
<body>
    <!-- HEADER -->
    <?php include 'componentes/header.php'; ?>
    
    <!-- BREADCRUMB -->
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <ol class="breadcrumb-list">
                <li><a href="home.php">Inicio</a></li>
                <li>›</li>
                <?php if (!empty($categoria)): ?>
                    <li><a href="productos_mejorado.php">Productos</a></li>
                    <li>›</li>
                    <li><?php echo escapar_html($categoria); ?></li>
                <?php else: ?>
                    <li>Productos</li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
    
    <!-- HEADER DE PRODUCTOS -->
    <div class="container">
        <div class="products-header mt-2 mb-2">
            <h1 class="page-title">
                <?php 
                if (!empty($busqueda)) {
                    echo 'Resultados para: "' . escapar_html($busqueda) . '"';
                } elseif (!empty($categoria)) {
                    echo escapar_html($categoria);
                } else {
                    echo 'Nuestros Productos';
                }
                ?>
            </h1>
            <p class="results-count"><?php echo $totalItems; ?> productos encontrados</p>
        </div>
        
        <!-- FILTROS Y ORDENAMIENTO -->
        <div class="filters-bar mb-2">
            <div class="filter-group">
                <label for="ordenar">Ordenar por:</label>
                <select id="ordenar" name="ordenar" onchange="aplicarOrden(this.value)">
                    <option value="reciente" <?php echo $ordenar === 'reciente' ? 'selected' : ''; ?>>Más reciente</option>
                    <option value="precio_asc" <?php echo $ordenar === 'precio_asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                    <option value="precio_desc" <?php echo $ordenar === 'precio_desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                    <option value="nombre_asc" <?php echo $ordenar === 'nombre_asc' ? 'selected' : ''; ?>>Nombre: A-Z</option>
                    <option value="nombre_desc" <?php echo $ordenar === 'nombre_desc' ? 'selected' : ''; ?>>Nombre: Z-A</option>
                </select>
            </div>
            
            <?php if (!empty($busqueda) || !empty($categoria)): ?>
            <button class="btn btn-secondary" onclick="limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- PRODUCTOS -->
    <main class="container">
        <?php if (count($productos) > 0): ?>
            <div class="productos">
                <?php foreach ($productos as $index => $producto): 
                    $hasImage = !empty($producto['ruta']);
                    $imgSrc = $hasImage ? escapar_html($producto['ruta']) : '';
                    
                    // Determinar badge
                    $badgeClass = '';
                    $badgeText = '';
                    if ($index < 3) {
                        $badgeClass = 'badge-new';
                        $badgeText = 'Nuevo';
                    } elseif ($producto['Precio'] < 5) {
                        $badgeClass = 'badge-sale';
                        $badgeText = 'Oferta';
                    }
                ?>
                    <article class="product-card" 
                             data-id="<?php echo escapar_html($producto['IdProducto']); ?>" 
                             data-name="<?php echo escapar_html($producto['NomProducto']); ?>" 
                             data-price="<?php echo $producto['Precio']; ?>" 
                             data-brand="<?php echo escapar_html($producto['Marca']); ?>">
                        
                        <div class="product-image-container <?php echo !$hasImage ? 'no-image' : ''; ?>">
                            <?php if ($hasImage): ?>
                                <img src="<?php echo $imgSrc; ?>" 
                                     alt="<?php echo escapar_html($producto['NomProducto']); ?>" 
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
                        </div>
                        
                        <div class="product-info">
                            <div class="product-brand"><?php echo escapar_html($producto['Marca']); ?></div>
                            <h3 class="product-title"><?php echo escapar_html($producto['NomProducto']); ?></h3>
                            
                            <div class="product-price-container">
                                <span class="product-price">$<?php echo number_format($producto['Precio'], 2); ?></span>
                                <span class="product-price-unit">c/u</span>
                            </div>
                            
                            <button class="add-to-cart-btn" data-id="<?php echo escapar_html($producto['IdProducto']); ?>">
                                Agregar al Carrito
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- PAGINACIÓN -->
            <div class="mt-3 mb-3">
                <?php echo $paginacion->renderizar(); ?>
            </div>
            
        <?php else: ?>
            <!-- ESTADO VACÍO -->
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h2>No se encontraron productos</h2>
                <p>No hay productos que coincidan con tu búsqueda.</p>
                <div class="suggestions-grid mt-2">
                    <a href="productos_mejorado.php" class="btn btn-primary">Ver todos los productos</a>
                    <a href="home.php" class="btn btn-secondary">Volver al inicio</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- SCRIPTS -->
    <script src="validaciones.js"></script>
    <script src="paginacion.js"></script>
    <script>
        // Aplicar orden
        function aplicarOrden(orden) {
            const url = new URL(window.location.href);
            url.searchParams.set('ordenar', orden);
            window.location.href = url.toString();
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'productos_mejorado.php';
        }
        
        // Agregar al carrito
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productoId = this.dataset.id;
                const card = this.closest('.product-card');
                const nombre = card.dataset.name;
                
                agregarAlCarrito(productoId, nombre, this);
            });
        });
        
        function agregarAlCarrito(id, nombre, boton) {
            const originalText = boton.textContent;
            boton.textContent = 'Agregando...';
            boton.disabled = true;
            
            fetch('carrito_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${encodeURIComponent(id)}&accion=agregar`
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    // Actualizar contador
                    const contador = document.getElementById('contador-carrito');
                    if (contador) {
                        contador.textContent = data.cantidad_total;
                    }
                    
                    // Feedback visual
                    boton.textContent = '¡Agregado!';
                    boton.style.background = '#27ae60';
                    
                    setTimeout(() => {
                        boton.textContent = originalText;
                        boton.style.background = '';
                        boton.disabled = false;
                    }, 2000);
                    
                    // Notificación
                    mostrarNotificacion(`"${nombre}" agregado al carrito`, 'exito');
                } else {
                    throw new Error(data.mensaje || 'Error al agregar');
                }
            })
            .catch(error => {
                boton.textContent = originalText;
                boton.disabled = false;
                mostrarNotificacion(error.message, 'error');
            });
        }
        
        // Sistema de notificaciones
        function mostrarNotificacion(mensaje, tipo = 'exito') {
            const container = document.getElementById('notificaciones-container') || crearContainerNotificaciones();
            
            const notif = document.createElement('div');
            notif.className = `notificacion ${tipo}`;
            notif.textContent = mensaje;
            
            container.appendChild(notif);
            
            setTimeout(() => notif.classList.add('show'), 10);
            setTimeout(() => {
                notif.classList.remove('show');
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }
        
        function crearContainerNotificaciones() {
            const container = document.createElement('div');
            container.id = 'notificaciones-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
            `;
            document.body.appendChild(container);
            return container;
        }
    </script>
    
    <style>
        .notificacion {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .notificacion.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notificacion.exito {
            border-left: 4px solid #27ae60;
        }
        
        .notificacion.error {
            border-left: 4px solid #e74c3c;
        }
    </style>
</body>
</html>