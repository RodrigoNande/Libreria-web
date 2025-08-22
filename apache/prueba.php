<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    require_once 'conexion.php';
  
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <link rel="stylesheet" href="estilo1.css">

    <title>Librería RL</title>
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
        <a href="formularioproducto.php?id=<?php echo $row['IdProducto']; ?>" class="producto">
            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($row['NomProducto']); ?>">
            <h3><?php echo htmlspecialchars($row['NomProducto']); ?></h3>
            <p>Precio: <strong>$<?php echo number_format($row['Precio'], 2); ?></strong></p>
            <p>Marca: <?php echo htmlspecialchars($row['Marca']); ?></p>
            <p>Precio Unitario: $<?php echo number_format($row['Precio_Unitario'], 2); ?></p>
        </a>
        <?php
    }
} else {
    echo "<p>No hay productos disponibles</p>";
}
?>



    <a href="formularioproducto.php?id=2" class="producto">
        <img src="img/cuaderno2.jpg" alt="cuaderno triple mario bros">
        <h3>Caja de Cuadernos</h3>
        <p>Precio: <strong>$57.87</strong></p>
        <p>Marca: Forger</p>
        <p>Precio Unitario: $1.75</p>
    </a>
</main>

</body>
</html>