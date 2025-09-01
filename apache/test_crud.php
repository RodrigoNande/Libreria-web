<?php
// test_crud.php - Script de prueba para operaciones CRUD
session_start();
require_once 'conexion.php';

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

echo "<h1>🧪 Pruebas CRUD - Base de Datos</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { color: #007bff; background: #cce7ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .test-section { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .test-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    .test-btn:hover { background: #0056b3; }
    .delete-btn { background: #dc3545; }
    .delete-btn:hover { background: #c82333; }
    .success-btn { background: #28a745; }
    .success-btn:hover { background: #218838; }
    form { margin: 10px 0; }
    input, select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
</style>";

// Función para contar productos
function contarProductos($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM articulo");
    return $result->fetch_assoc()['total'];
}

// Función para obtener categorías
function obtenerCategorias($conn) {
    $result = $conn->query("SELECT Nombre_Categoria FROM Categoria WHERE IdCategoriaPadre IS NULL ORDER BY Nombre_Categoria");
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row['Nombre_Categoria'];
    }
    return $categorias;
}

$categorias = obtenerCategorias($conn);
$productos_antes = contarProductos($conn);

echo "<div class='info'>📊 Estado actual: <strong>$productos_antes productos</strong> en la base de datos</div>";

// Procesar acciones de prueba
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['test_accion'] ?? '';

    switch ($accion) {
        case 'insertar':
            $nombre = trim($_POST['test_nombre'] ?? '');
            $marca = trim($_POST['test_marca'] ?? '');
            $tipo = trim($_POST['test_tipo'] ?? '');
            $precio = floatval($_POST['test_precio'] ?? 0);
            $stock = intval($_POST['test_stock'] ?? 0);

            if (!empty($nombre) && !empty($marca) && !empty($tipo) && $precio > 0) {
                $idProducto = 'TEST_' . strtoupper(bin2hex(random_bytes(4))) . '_' . time();

                $stmt = $conn->prepare("INSERT INTO articulo (IdProducto, NomProducto, Marca, TipoProducto, Precio, Precio_Unitario, Stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssddi", $idProducto, $nombre, $marca, $tipo, $precio, $precio, $stock);

                if ($stmt->execute()) {
                    echo "<div class='success'>✅ PRODUCTO INSERTADO EXITOSAMENTE<br>";
                    echo "ID: $idProducto<br>";
                    echo "Nombre: $nombre<br>";
                    echo "Marca: $marca<br>";
                    echo "Categoría: $tipo<br>";
                    echo "Precio: $$precio<br>";
                    echo "Stock: $stock</div>";
                } else {
                    echo "<div class='error'>❌ Error al insertar: " . $conn->error . "</div>";
                }
            } else {
                echo "<div class='error'>❌ Complete todos los campos obligatorios</div>";
            }
            break;

        case 'actualizar':
            $idProducto = trim($_POST['test_id_update'] ?? '');
            $nuevoNombre = trim($_POST['test_nuevo_nombre'] ?? '');

            if (!empty($idProducto) && !empty($nuevoNombre)) {
                $stmt = $conn->prepare("UPDATE articulo SET NomProducto = ? WHERE IdProducto = ?");
                $stmt->bind_param("ss", $nuevoNombre, $idProducto);

                if ($stmt->execute()) {
                    echo "<div class='success'>✅ PRODUCTO ACTUALIZADO EXITOSAMENTE<br>";
                    echo "ID: $idProducto<br>";
                    echo "Nuevo nombre: $nuevoNombre</div>";
                } else {
                    echo "<div class='error'>❌ Error al actualizar: " . $conn->error . "</div>";
                }
            } else {
                echo "<div class='error'>❌ Complete ID y nuevo nombre</div>";
            }
            break;

        case 'eliminar':
            $idProducto = trim($_POST['test_id_delete'] ?? '');

            if (!empty($idProducto)) {
                // Eliminar imagen primero
                $stmt = $conn->prepare("DELETE FROM img WHERE idProd = ?");
                $stmt->bind_param("s", $idProducto);
                $stmt->execute();

                // Eliminar producto
                $stmt = $conn->prepare("DELETE FROM articulo WHERE IdProducto = ?");
                $stmt->bind_param("s", $idProducto);

                if ($stmt->execute()) {
                    echo "<div class='success'>✅ PRODUCTO ELIMINADO EXITOSAMENTE<br>";
                    echo "ID eliminado: $idProducto</div>";
                } else {
                    echo "<div class='error'>❌ Error al eliminar: " . $conn->error . "</div>";
                }
            } else {
                echo "<div class='error'>❌ Ingrese un ID válido</div>";
            }
            break;
    }

    // Actualizar contador después de la operación
    $productos_despues = contarProductos($conn);
    echo "<div class='info'>📊 Después de la operación: <strong>$productos_despues productos</strong> en la base de datos</div>";
}
?>

<div class="test-section">
    <h2>➕ Insertar Producto de Prueba</h2>
    <form method="POST">
        <input type="hidden" name="test_accion" value="insertar">
        <input type="text" name="test_nombre" placeholder="Nombre del producto" required>
        <input type="text" name="test_marca" placeholder="Marca" required>
        <select name="test_tipo" required>
            <option value="">Seleccionar categoría</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo htmlspecialchars($categoria); ?>"><?php echo htmlspecialchars($categoria); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="test_precio" placeholder="Precio" step="0.01" min="0" required>
        <input type="number" name="test_stock" placeholder="Stock" min="0" required>
        <button type="submit" class="test-btn success-btn">📦 Insertar Producto</button>
    </form>
</div>

<div class="test-section">
    <h2>✏️ Actualizar Producto</h2>
    <form method="POST">
        <input type="hidden" name="test_accion" value="actualizar">
        <input type="text" name="test_id_update" placeholder="ID del producto" required>
        <input type="text" name="test_nuevo_nombre" placeholder="Nuevo nombre" required>
        <button type="submit" class="test-btn">🔄 Actualizar Nombre</button>
    </form>
</div>

<div class="test-section">
    <h2>🗑️ Eliminar Producto</h2>
    <form method="POST">
        <input type="hidden" name="test_accion" value="eliminar">
        <input type="text" name="test_id_delete" placeholder="ID del producto a eliminar" required>
        <button type="submit" class="test-btn delete-btn">🗑️ Eliminar Producto</button>
    </form>
</div>

<div class="test-section">
    <h2>📋 Productos Recientes</h2>
    <?php
    $result = $conn->query("SELECT IdProducto, NomProducto, Marca, TipoProducto FROM articulo ORDER BY IdProducto DESC LIMIT 10");
    if ($result && $result->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f2f2f2;'><th style='border: 1px solid #ddd; padding: 8px;'>ID</th><th style='border: 1px solid #ddd; padding: 8px;'>Nombre</th><th style='border: 1px solid #ddd; padding: 8px;'>Marca</th><th style='border: 1px solid #ddd; padding: 8px;'>Categoría</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['IdProducto']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['NomProducto']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['Marca']) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['TipoProducto']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>No hay productos en la base de datos</div>";
    }
    ?>
</div>

<div style="margin: 20px 0;">
    <a href="admin_productos.php" class="test-btn">🏠 Volver al Panel</a>
    <a href="verificar_db.php" class="test-btn">🔍 Ver Estado General</a>
</div>

<?php $conn->close(); ?>
