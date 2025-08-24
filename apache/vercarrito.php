<?php
session_start();
require_once 'conexion.php';

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $producto_id = intval($_POST['producto_id'] ?? 0);
    $nueva_cantidad = intval($_POST['cantidad'] ?? 0);
    
    if ($producto_id > 0) {
        switch ($accion) {
            case 'actualizar':
                if ($nueva_cantidad > 0) {
                    $_SESSION['carrito'][$producto_id] = $nueva_cantidad;
                } else {
                    unset($_SESSION['carrito'][$producto_id]);
                }
                break;
                
            case 'eliminar':
                unset($_SESSION['carrito'][$producto_id]);
                break;
                
            case 'incrementar':
                $_SESSION['carrito'][$producto_id] = ($_SESSION['carrito'][$producto_id] ?? 0) + 1;
                break;
                
            case 'decrementar':
                if (($_SESSION['carrito'][$producto_id] ?? 0) > 1) {
                    $_SESSION['carrito'][$producto_id]--;
                } else {
                    unset($_SESSION['carrito'][$producto_id]);
                }
                break;
        }
    }
    
    // Redireccionar para evitar reenvío del formulario
    header('Location: vercarrito.php');
    exit;
}

$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras - Librería RL</title>
    <link rel="stylesheet" href="estilocarrito.css">
    <link rel="stylesheet" href="carrito-mejorado.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="carrito-header-page">
            <h1>🛒 Tu Carrito de Compras</h1>
            <a href="home.php" class="btn-continuar">← Continuar Comprando</a>
        </header>

        <?php if (empty($carrito)): ?>
            <div class="carrito-vacio-page">
                <div class="icono-carrito-vacio">🛒</div>
                <h2>Tu carrito está vacío</h2>
                <p>¡Agrega algunos productos increíbles a tu carrito!</p>
                <a href="home.php" class="btn-primario">Explorar Productos</a>
            </div>
        <?php else: ?>
            <?php
            $ids = implode(',', array_keys($carrito));
            $sql = "SELECT a.IdProducto, a.NomProducto, a.Precio, a.Precio_Unitario, a.Marca, i.ruta
                    FROM articulo a
                    LEFT JOIN img i ON a.IdProducto = i.idProd
                    WHERE a.IdProducto IN ($ids)";
            $result = $conn->query($sql);
            
            $productos = [];
            while ($row = $result->fetch_assoc()) {
                $productos[$row['IdProducto']] = $row;
            }
            
            // Limpiar carrito de productos que ya no existen
            $carrito_limpio = [];
            foreach ($carrito as $id => $cantidad) {
                if (isset($productos[$id])) {
                    $carrito_limpio[$id] = $cantidad;
                }
            }
            
            // Actualizar sesión con carrito limpio
            if (count($carrito_limpio) !== count($carrito)) {
                $_SESSION['carrito'] = $carrito_limpio;
                $carrito = $carrito_limpio;
            }
            
            $total = 0;
            $cantidad_items = 0;
            ?>
            
            <div class="carrito-contenido">
                <div class="productos-carrito">
                    <?php foreach ($carrito as $id => $cantidad): 
                        // Verificar que el producto existe antes de procesarlo
                        if (!isset($productos[$id])) {
                            continue; // Saltar productos que no existen
                        }
                        
                        $producto = $productos[$id];
                        $subtotal = $producto['Precio'] * $cantidad;
                        $total += $subtotal;
                        $cantidad_items += $cantidad;
                        $imgSrc = !empty($producto['ruta']) ? htmlspecialchars($producto['ruta']) : "img/no-image.png";
                    ?>
                    <div class="item-carrito" data-producto-id="<?php echo $id; ?>">
                        <div class="item-imagen">
                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($producto['NomProducto']); ?>">
                        </div>
                        
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($producto['NomProducto'] ?? 'Producto sin nombre'); ?></h3>
                            <p class="item-marca">Marca: <?php echo htmlspecialchars($producto['Marca'] ?? 'Sin marca'); ?></p>
                            <p class="item-precio">$<?php echo number_format((float)($producto['Precio'] ?? 0), 2); ?> c/u</p>
                        </div>
                        
                        <div class="item-cantidad">
                            <label>Cantidad:</label>
                            <div class="cantidad-controles">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="decrementar">
                                    <input type="hidden" name="producto_id" value="<?php echo $id; ?>">
                                    <button type="submit" class="btn-cantidad">-</button>
                                </form>
                                
                                <input type="number" value="<?php echo $cantidad; ?>" min="1" max="99" 
                                       class="input-cantidad" data-producto-id="<?php echo $id; ?>">
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="incrementar">
                                    <input type="hidden" name="producto_id" value="<?php echo $id; ?>">
                                    <button type="submit" class="btn-cantidad">+</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="item-subtotal">
                            <p class="subtotal-label">Subtotal:</p>
                            <p class="subtotal-precio">$<?php echo number_format($subtotal, 2); ?></p>
                        </div>
                        
                        <div class="item-acciones">
                            <form method="POST" onsubmit="return confirm('¿Eliminar este producto?')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="producto_id" value="<?php echo $id; ?>">
                                <button type="submit" class="btn-eliminar">🗑️</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="resumen-carrito">
                    <div class="resumen-header">
                        <h3>Resumen del Pedido</h3>
                    </div>
                    
                    <div class="resumen-detalles">
                        <div class="detalle-linea">
                            <span>Productos (<?php echo $cantidad_items; ?> items)</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="detalle-linea">
                            <span>Envío</span>
                            <span class="envio-gratis">
                                <?php if ($total >= 100): ?>
                                    GRATIS
                                <?php else: ?>
                                    $15.00
                                    <?php $total += 15; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($total < 100): ?>
                        <div class="envio-progreso">
                            <p>Agrega $<?php echo number_format(100 - ($total - 15), 2); ?> más para envío gratuito</p>
                            <div class="barra-progreso">
                                <div class="barra-relleno" style="width: <?php echo min(100, (($total - 15) / 100) * 100); ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detalle-total">
                            <span>Total</span>
                            <span class="total-precio">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="botones-checkout">
                            <button class="btn-checkout">Proceder al Pago</button>
                            <button class="btn-guardar-despues">Guardar para después</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Actualizar cantidad automáticamente
    document.querySelectorAll('.input-cantidad').forEach(input => {
        input.addEventListener('change', function() {
            const productoId = this.dataset.productoId;
            const cantidad = parseInt(this.value);
            
            if (cantidad > 0) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="producto_id" value="${productoId}">
                    <input type="hidden" name="cantidad" value="${cantidad}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Animación para botones
    document.querySelectorAll('.btn-cantidad, .btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
        });
    });
    </script>
</body>
</html>