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
    <!-- Encabezado principal -->
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

    <!-- Menú secundario -->
    <nav class="menu-secundario">
        <a href="#">INICIO</a>
        <a href="#">SOBRE NOSOTROS</a>
        <a href="#">CATEGORÍA ▼</a>
        <a href="#">CONTACTO</a>
        <a href="#">TIENDA</a>
    </nav>

    <!-- Productos -->
   <main class="productos">
    <?php    
    $sql = "SELECT ruta FROM img WHERE idProd = 80";
    $result = $conn->query($sql);
    $imgSrc = "";
    if ($result && $row = $result->fetch_assoc()) {
        $imgSrc = $row['ruta'];
    }
?>
<a href="formularioproducto.php?id=1" class="producto">
    <img src="<?php echo "Ruta obtenida: " . htmlspecialchars($imgSrc); ?>" alt="caja de cuadernos">
    <h3>Caja de Cuadernos</h3>
    <p>Precio: <strong>$45.18</strong></p>
    <p>Marca: Primavera</p>
    <p>Precio Unitario: $1.25</p>
</a>


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




