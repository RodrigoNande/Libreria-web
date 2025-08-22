<?php
require_once 'conexion.php';

// Validar y obtener el id del producto
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consulta para obtener el producto y su imagen
$sql = "SELECT a.IdProducto, a.NomProducto, a.Marca, a.TipoProducto, a.Precio, a.Precio_Unitario, i.ruta
        FROM articulo a
        LEFT JOIN img i ON a.IdProducto = i.idProd
        WHERE a.IdProducto = $id
        LIMIT 1";
$result = $conn->query($sql);
$producto = $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo2.css">
    <title>Formulario de Compra</title>
</head>
<body>
    <header class="header">
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
            <a href="#">Mis listas</a>
            <a href="#">Mis pedidos</a>
            <a href="#">Mi Cuenta</a>               
            <a href="#">$0</a>
        </nav>
    </header>
 <nav class="menu-secundario">
        <a href="#">INICIO</a>
        <a href="#">SOBRE NOSOTROS</a>
        <a href="#">CATEGORÍA ▼</a>
        <a href="#">CONTACTO</a>
        <a href="#">TIENDA</a>
    </nav>
    <h1>Formulario de Compra</h1>

    <?php if ($producto): ?>
    <div class="producto">
        <img src="<?php echo $producto['ruta'] ? htmlspecialchars($producto['ruta']) : 'img/no-image.png'; ?>" alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>">
        <div class="detalle-producto">
            <h2><?php echo htmlspecialchars($producto['NomProducto']); ?></h2>
            <div class="marca">Marca: <?php echo htmlspecialchars($producto['Marca']); ?></div>
            <div class="precio">Precio: $<?php echo number_format($producto['Precio'], 2); ?></div>
            <div class="precio">Precio Unitario: $<?php echo number_format($producto['Precio_Unitario'], 2); ?></div>
            
            <div>
                <br><br><button class="btn-carrito">Añadir Al Carrito</button>
                <button class="btn-comprar">Comprar Ahora</button>
            </div>
        </div>
    </div>
    <?php else: ?>
        <p>Producto no encontrado.</p>
    <?php endif; ?>

</body>
</html>

