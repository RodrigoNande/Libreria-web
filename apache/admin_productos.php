<?php
// admin_productos.php - Panel de gestión de productos para administradores
session_start();
require_once 'conexion.php';
require_once 'auth.php';

// Verificar que el usuario esté logueado y sea admin
if (!estaLogueado() || !esAdmin()) {
    header('Location: home.php');
    exit;
}

$usuarioActual = obtenerUsuarioActual();

// Función para obtener todos los productos con sus categorías
function obtenerProductos($conn) {
    $sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, a.Stock, i.ruta
            FROM articulo a
            LEFT JOIN img i ON a.IdProducto = i.idProd
            ORDER BY a.NomProducto";
    
    $result = $conn->query($sql);
    $productos = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
    }
    
    return $productos;
}

// Función para obtener categorías (sin filtrar por padre)
function obtenerCategorias($conn) {
    $sql = "SELECT Id, Nombre_Categoria, Descripcion, IdCategoriaPadre 
            FROM Categoria 
            ORDER BY Nombre_Categoria";
    
    $result = $conn->query($sql);
    $categorias = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    
    return $categorias;
}

// Función para asignar producto a categoría
function asignarProductoCategoria($conn, $idProducto, $idCategoria) {
    // Primero verificar si ya existe la relación
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM producto_categoria WHERE Id_Producto = ? AND Id_Categoria = ?");
    $checkStmt->bind_param("ss", $idProducto, $idCategoria);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->fetch_assoc()['count'] > 0;
    $checkStmt->close();
    
    if (!$exists) {
        $stmt = $conn->prepare("INSERT INTO producto_categoria (Id_Producto, Id_Categoria) VALUES (?, ?)");
        $stmt->bind_param("ss", $idProducto, $idCategoria);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    return true;
}

// Procesar acciones POST
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'agregar':
            $nombre = trim($_POST['nombre'] ?? '');
            $marca = trim($_POST['marca'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
            $stock = intval($_POST['stock'] ?? 0);
            $idCategoria = trim($_POST['id_categoria'] ?? '');
            
            if (empty($nombre) || empty($marca) || empty($tipo) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                // Generar ID único
                $idProducto = 'PROD_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
                
                $stmt = $conn->prepare("INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssddi", $idProducto, $nombre, $marca, $tipo, $precio, $precio_unitario, $stock);
                
                if ($stmt->execute()) {
                    // Asignar categoría si se seleccionó
                    if (!empty($idCategoria)) {
                        asignarProductoCategoria($conn, $idProducto, $idCategoria);
                    }
                    
                    $mensaje = 'Producto agregado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al agregar el producto';
                    $tipoMensaje = 'error';
                }
                $stmt->close();
            }
            break;
            
        case 'eliminar':
            $idProducto = $_POST['id_producto'] ?? '';
            
            if (!empty($idProducto)) {
                try {
                    // Iniciar transacción para asegurar que todo se elimine correctamente
                    $conn->begin_transaction();
                    
                    // 1. Eliminar relaciones en producto_categoria primero (CRÍTICO)
                    $stmt = $conn->prepare("DELETE FROM producto_categoria WHERE Id_Producto = ?");
                    $stmt->bind_param("s", $idProducto);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 2. Eliminar imágenes asociadas
                    $stmt = $conn->prepare("DELETE FROM img WHERE idProd = ?");
                    $stmt->bind_param("s", $idProducto);
                    $stmt->execute();
                    $stmt->close();
                    
                    // 3. Finalmente eliminar el producto
                    $stmt = $conn->prepare("DELETE FROM articulo WHERE IdProducto = ?");
                    $stmt->bind_param("s", $idProducto);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Confirmar transacción
                    $conn->commit();
                    
                    $mensaje = 'Producto eliminado exitosamente';
                    $tipoMensaje = 'exito';
                    
                } catch (Exception $e) {
                    // Revertir cambios en caso de error
                    $conn->rollback();
                    $mensaje = 'Error al eliminar el producto: ' . $e->getMessage();
                    $tipoMensaje = 'error';
                }
            }
            break;
            
        case 'modificar':
            $idProducto = $_POST['id_producto'] ?? '';
            $nombre = trim($_POST['nombre'] ?? '');
            $marca = trim($_POST['marca'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $precio = floatval($_POST['precio'] ?? 0);
            $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
            $stock = intval($_POST['stock'] ?? 0);
            $idCategoria = trim($_POST['id_categoria'] ?? '');
            
            if (empty($idProducto) || empty($nombre) || empty($marca) || empty($tipo) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE articulo SET NomProducto = ?, Marca = ?, TipoProducto = ?, Precio = ?, Precio_Unitario = ?, Stock = ? WHERE IdProducto = ?");
                $stmt->bind_param("sssddis", $nombre, $marca, $tipo, $precio, $precio_unitario, $stock, $idProducto);
                
                if ($stmt->execute()) {
                    // Actualizar categoría si se seleccionó
                    if (!empty($idCategoria)) {
                        // Eliminar relaciones anteriores
                        $delStmt = $conn->prepare("DELETE FROM producto_categoria WHERE Id_Producto = ?");
                        $delStmt->bind_param("s", $idProducto);
                        $delStmt->execute();
                        $delStmt->close();
                        
                        // Crear nueva relación
                        asignarProductoCategoria($conn, $idProducto, $idCategoria);
                    }
                    
                    $mensaje = 'Producto modificado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al modificar el producto';
                    $tipoMensaje = 'error';
                }
                $stmt->close();
            }
            break;
    }
}

$productos = obtenerProductos($conn);
$categorias = obtenerCategorias($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Productos</title>
    <link rel="stylesheet" href="estilo1.css?v=1.1">
    
</head>
<body>
    <div class="admin-container">
        <div class="admin-panel">
            <header class="admin-header">
                <div class="admin-title">
                    <h1>Panel de Administración - Productos</h1>
                </div>
                <a href="home.php" class="btn-volver">Volver a la Tienda</a>
            </header>

            <main class="admin-content">
                <section class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo count($productos); ?></h3>
                        <p>Productos Totales</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count($categorias); ?></h3>
                        <p>Categorías</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo array_sum(array_column($productos, 'Stock')); ?></h3>
                        <p>Unidades en Stock</p>
                    </div>
                </section>

                <?php if ($mensaje): ?>
                    <div class="mensaje <?php echo $tipoMensaje; ?>">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <section class="formulario-producto">
                    <h3>Agregar Nuevo Producto</h3>
                    <form method="POST">
                        <input type="hidden" name="accion" value="agregar">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto *</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="marca">Marca *</label>
                                <input type="text" id="marca" name="marca" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo">Tipo de Producto *</label>
                                <input type="text" id="tipo" name="tipo" required>
                            </div>
                            <div class="form-group">
                                <label for="id_categoria">Categoría</label>
                                <select id="id_categoria" name="id_categoria">
                                    <option value="">Seleccionar categoría...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['Id']); ?>">
                                            <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                                            <?php if (!empty($categoria['Descripcion'])): ?>
                                                - <?php echo htmlspecialchars(substr($categoria['Descripcion'], 0, 30)); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="precio">Precio de Venta *</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="precio_unitario">Precio Unitario</label>
                                <input type="number" id="precio_unitario" name="precio_unitario" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock Inicial</label>
                                <input type="number" id="stock" name="stock" min="0" value="0">
                            </div>
                        </div>

                        <button type="submit" class="btn-agregar">Agregar Producto</button>
                    </form>
                </section>

                <section class="productos-section">
                    <h2>Productos Existentes (<?php echo count($productos); ?>)</h2>
                    <div class="productos-grid">
                        <?php foreach ($productos as $producto): ?>
                            <article class="producto-admin">
                                <div class="product-image-container">
                                    <?php if (!empty($producto['ruta'])): ?>
                                        <img src="<?php echo $producto['ruta']; ?>" alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>">
                                    <?php else: ?>
                                        <p>Sin imagen</p>
                                    <?php endif; ?>
                                </div>

                                <div class="producto-content">
                                    <h4><?php echo htmlspecialchars($producto['NomProducto']); ?></h4>
                                    <p><strong>Marca:</strong> <?php echo htmlspecialchars($producto['Marca']); ?></p>
                                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($producto['TipoProducto']); ?></p>
                                    <p><strong>Precio:</strong> $<?php echo number_format($producto['Precio'], 2); ?></p>
                                    <p><strong>Stock:</strong> <?php echo $producto['Stock']; ?> unidades</p>

                                    <div class="producto-acciones">
                                        <button class="btn-admin btn-modificar"
                                                onclick="abrirModalModificar('<?php echo $producto['IdProducto']; ?>',
                                                                             '<?php echo htmlspecialchars($producto['NomProducto']); ?>',
                                                                             '<?php echo htmlspecialchars($producto['Marca']); ?>',
                                                                             '<?php echo htmlspecialchars($producto['TipoProducto']); ?>',
                                                                             <?php echo $producto['Precio']; ?>,
                                                                             <?php echo $producto['Precio_Unitario']; ?>,
                                                                             <?php echo $producto['Stock']; ?>)">
                                            Modificar
                                        </button>

                                        <button class="btn-admin btn-eliminar"
                                                onclick="confirmarEliminar('<?php echo $producto['IdProducto']; ?>', '<?php echo htmlspecialchars($producto['NomProducto']); ?>')">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
    
    <div id="modalModificar" class="modal">
        <div class="modal-content">
            <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
            <h3>Modificar Producto</h3>

            <form method="POST">
                <input type="hidden" name="accion" value="modificar">
                <input type="hidden" id="mod_id_producto" name="id_producto">

                <div class="form-group">
                    <label for="mod_nombre">Nombre del Producto *</label>
                    <input type="text" id="mod_nombre" name="nombre" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mod_marca">Marca *</label>
                        <input type="text" id="mod_marca" name="marca" required>
                    </div>
                    <div class="form-group">
                        <label for="mod_tipo">Tipo *</label>
                        <input type="text" id="mod_tipo" name="tipo" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mod_id_categoria">Categoría</label>
                    <select id="mod_id_categoria" name="id_categoria">
                        <option value="">Seleccionar categoría...</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria['Id']); ?>">
                                <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mod_precio">Precio *</label>
                        <input type="number" id="mod_precio" name="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="mod_precio_unitario">Precio Unitario</label>
                        <input type="number" id="mod_precio_unitario" name="precio_unitario" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="mod_stock">Stock</label>
                        <input type="number" id="mod_stock" name="stock" min="0">
                    </div>
                </div>

                <button type="submit" class="btn-agregar">Guardar Cambios</button>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalModificar(id, nombre, marca, tipo, precio, precioUnitario, stock) {
            document.getElementById('mod_id_producto').value = id;
            document.getElementById('mod_nombre').value = nombre;
            document.getElementById('mod_marca').value = marca;
            document.getElementById('mod_tipo').value = tipo;
            document.getElementById('mod_precio').value = precio;
            document.getElementById('mod_precio_unitario').value = precioUnitario;
            document.getElementById('mod_stock').value = stock;
            document.getElementById('modalModificar').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalModificar').style.display = 'none';
        }

        function confirmarEliminar(id, nombre) {
            if (confirm('¿Estás seguro de eliminar "' + nombre + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_producto" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalModificar');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>