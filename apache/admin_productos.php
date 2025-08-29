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

// Función para obtener todos los productos
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
            
            if (empty($nombre) || empty($marca) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                // Generar ID único
                $idProducto = 'PROD_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
                
                $stmt = $conn->prepare("INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssddi", $idProducto, $nombre, $marca, $tipo, $precio, $precio_unitario, $stock);
                
                if ($stmt->execute()) {
                    $mensaje = 'Producto agregado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al agregar el producto';
                    $tipoMensaje = 'error';
                }
            }
            break;
            
        case 'eliminar':
            $idProducto = $_POST['id_producto'] ?? '';
            
            if (!empty($idProducto)) {
                // Eliminar imagen primero
                $stmt = $conn->prepare("DELETE FROM img WHERE idProd = ?");
                $stmt->bind_param("s", $idProducto);
                $stmt->execute();
                
                // Eliminar producto
                $stmt = $conn->prepare("DELETE FROM articulo WHERE IdProducto = ?");
                $stmt->bind_param("s", $idProducto);
                
                if ($stmt->execute()) {
                    $mensaje = 'Producto eliminado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al eliminar el producto';
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
            
            if (empty($idProducto) || empty($nombre) || empty($marca) || $precio <= 0) {
                $mensaje = 'Por favor complete todos los campos obligatorios';
                $tipoMensaje = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE articulo SET NomProducto = ?, Marca = ?, TipoProducto = ?, Precio = ?, Precio_Unitario = ?, Stock = ? WHERE IdProducto = ?");
                $stmt->bind_param("sssddis", $nombre, $marca, $tipo, $precio, $precio_unitario, $stock, $idProducto);
                
                if ($stmt->execute()) {
                    $mensaje = 'Producto modificado exitosamente';
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = 'Error al modificar el producto';
                    $tipoMensaje = 'error';
                }
            }
            break;
    }
}

$productos = obtenerProductos($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Productos</title>
    <link rel="stylesheet" href="estilopruebas.css">
    <style>
        .admin-panel {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .btn-volver {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-volver:hover {
            background: #5a6268;
        }
        
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .producto-admin {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .producto-admin img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .producto-admin h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .producto-info {
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .producto-acciones {
            display: flex;
            gap: 10px;
        }
        
        .btn-admin {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-modificar {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-modificar:hover {
            background: #e0a800;
        }
        
        .btn-eliminar {
            background: #dc3545;
            color: white;
        }
        
        .btn-eliminar:hover {
            background: #c82333;
        }
        
        .formulario-producto {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn-agregar {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-agregar:hover {
            background: #218838;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .mensaje.exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .cerrar-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #aaa;
        }
        
        .cerrar-modal:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="admin-panel">
        <div class="admin-header">
            <h1>Panel de Administración - Productos</h1>
            <div>
                <span>Bienvenido, <?php echo htmlspecialchars($usuarioActual['Nombre'] ?? 'Admin'); ?></span>
                <a href="home.php" class="btn-volver">Volver a la Tienda</a>
            </div>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipoMensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario para agregar producto -->
        <div class="formulario-producto">
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
                        <label for="tipo">Tipo de Producto</label>
                        <input type="text" id="tipo" name="tipo">
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="precio_unitario">Precio Unitario</label>
                        <input type="number" id="precio_unitario" name="precio_unitario" step="0.01" min="0">
                    </div>
                </div>
                
                <button type="submit" class="btn-agregar">Agregar Producto</button>
            </form>
        </div>
        
        <!-- Lista de productos -->
        <h3>Productos Existentes (<?php echo count($productos); ?>)</h3>
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-admin">
                    <img src="<?php echo $producto['ruta'] ?: 'img/no-image.png'; ?>" 
                         alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>"
                         onerror="this.src='img/no-image.png'">
                    
                    <h4><?php echo htmlspecialchars($producto['NomProducto']); ?></h4>
                    
                    <div class="producto-info">
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars($producto['Marca']); ?></p>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($producto['TipoProducto']); ?></p>
                        <p><strong>Precio:</strong> $<?php echo number_format($producto['Precio'], 2); ?></p>
                        <p><strong>Stock:</strong> <?php echo $producto['Stock']; ?></p>
                    </div>
                    
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
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Modal para modificar producto -->
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
                        <label for="mod_tipo">Tipo de Producto</label>
                        <input type="text" id="mod_tipo" name="tipo">
                    </div>
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
                </div>
                
                <div class="form-group">
                    <label for="mod_stock">Stock</label>
                    <input type="number" id="mod_stock" name="stock" min="0">
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
            if (confirm('¿Estás seguro de que quieres eliminar el producto "' + nombre + '"? Esta acción no se puede deshacer.')) {
                // Crear un formulario temporal para enviar la eliminación
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
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalModificar');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>