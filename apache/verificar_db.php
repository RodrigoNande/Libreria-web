<?php
// verificar_db.php - Script para verificar operaciones en la base de datos
session_start();
require_once 'conexion.php';

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

echo "<h1>🔍 Verificación de Base de Datos</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: #007bff; background: #cce7ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; background: white; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f2f2f2; }
    .test-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    .test-btn:hover { background: #0056b3; }
</style>";

// Función para contar productos
function contarProductos($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM articulo");
    return $result->fetch_assoc()['total'];
}

// Función para obtener último producto
function obtenerUltimoProducto($conn) {
    $result = $conn->query("SELECT * FROM articulo ORDER BY IdProducto DESC LIMIT 1");
    return $result->fetch_assoc();
}

$productos_antes = contarProductos($conn);

echo "<div class='info'>✅ Conexión a la base de datos: EXITOSA</div>";
echo "<div class='info'>📊 Productos actuales en BD: <strong>$productos_antes</strong></div>";

// Mostrar productos recientes
echo "<h2>📦 Últimos 5 productos en la base de datos:</h2>";
$result = $conn->query("SELECT IdProducto, NomProducto, Marca, TipoProducto, Precio, Stock FROM articulo ORDER BY IdProducto DESC LIMIT 5");

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Categoría</th><th>Precio</th><th>Stock</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['IdProducto']) . "</td>";
        echo "<td>" . htmlspecialchars($row['NomProducto']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Marca']) . "</td>";
        echo "<td>" . htmlspecialchars($row['TipoProducto']) . "</td>";
        echo "<td>$" . number_format($row['Precio'], 2) . "</td>";
        echo "<td>" . $row['Stock'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No se encontraron productos en la base de datos</div>";
}

// Verificar tablas
echo "<h2>🗂️ Verificación de tablas:</h2>";
$tablas = ['articulo', 'img', 'Categoria'];
foreach ($tablas as $tabla) {
    $result = $conn->query("SHOW TABLES LIKE '$tabla'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✅ Tabla '$tabla' existe</div>";
    } else {
        echo "<div class='error'>❌ Tabla '$tabla' NO existe</div>";
    }
}

// Verificar categorías
echo "<h2>🏷️ Categorías disponibles:</h2>";
$result = $conn->query("SELECT Nombre_Categoria FROM Categoria WHERE IdCategoriaPadre IS NULL ORDER BY Nombre_Categoria");
if ($result && $result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['Nombre_Categoria']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='error'>❌ No se encontraron categorías principales</div>";
}

echo "<hr>";
echo "<h2>🧪 Pruebas de Operaciones:</h2>";
echo "<p><strong>Para verificar que las operaciones funcionan:</strong></p>";
echo "<ol>";
echo "<li>Ve al panel de administración (<code>admin_productos.php</code>)</li>";
echo "<li>Agrega un producto de prueba</li>";
echo "<li>Modifica ese producto</li>";
echo "<li>Elimina el producto</li>";
echo "<li>Regresa aquí para ver si los cambios se reflejan</li>";
echo "</ol>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='admin_productos.php' class='test-btn'>🏠 Ir al Panel de Administración</a>";
echo "<a href='verificar_db.php' class='test-btn'>🔄 Actualizar Verificación</a>";
echo "</div>";

$conn->close();
?></content>
<parameter name="filePath">c:\Users\josia\OneDrive\Documents\YigoProyect\Libreria-web\apache\verificar_db.php
