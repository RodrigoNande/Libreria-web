<?php
// setup_admin.php - Configuración inicial de administrador
// Este script solo funciona una vez y se auto-desactiva

session_start();
require_once 'conexion.php';

// Verificar si ya existe un admin
$check_query = "SELECT COUNT(*) as count FROM usuarios WHERE Rol = 'admin'";
$check_result = $conn->query($check_query);
$admin_exists = $check_result->fetch_assoc()['count'] > 0;

// Si ya existe un admin, redirigir
if ($admin_exists) {
    header('Location: home.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? 'Administrador');
    $apellido = trim($_POST['apellido'] ?? 'Sistema');
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar = $_POST['confirmar_contrasena'] ?? '';
    
    // Validaciones
    if (empty($correo) || empty($usuario) || empty($contrasena)) {
        $mensaje = 'Correo, usuario y contraseña son obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($contrasena !== $confirmar) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($contrasena) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El formato del correo no es válido';
        $tipo_mensaje = 'error';
    } else {
        // Crear administrador
        $id_admin = 'USR_ADMIN_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
        $hash_password = password_hash($contrasena, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO usuarios (
                IdUsuario, Nombre, Apellido, Correo, Usuario, Contrasena, 
                Rol, activo, email_verificado, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, 'admin', 1, 1, NOW())
        ");
        
        $stmt->bind_param("ssssss", $id_admin, $nombre, $apellido, $correo, $usuario, $hash_password);
        
        if ($stmt->execute()) {
            $mensaje = '¡Administrador creado exitosamente! Ya puedes iniciar sesión.';
            $tipo_mensaje = 'exito';
            
            // Auto-login del admin recién creado
            $_SESSION['usuario_id'] = $id_admin;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $correo;
            $_SESSION['usuario_rol'] = 'admin';
            $_SESSION['login_time'] = time();
            
            // Redirigir al panel de admin después de 3 segundos
            header("Refresh: 3; url=admin_productos.php");
        } else {
            $mensaje = 'Error al crear el administrador: ' . $stmt->error;
            $tipo_mensaje = 'error';
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Inicial - Librería RL</title>
    <link rel="stylesheet" href="estilopruebas.css">
    <style>
        .setup-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .setup-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4834d4;
        }
        
        .btn-setup {
            width: 100%;
            background: linear-gradient(135deg, #4834d4, #686de0);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-setup:hover {
            transform: translateY(-2px);
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
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
        
        .security-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>⚙️ Configuración Inicial</h1>
            <p>Crea tu cuenta de administrador para gestionar la librería</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($tipo_mensaje !== 'exito'): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="Administrador" required>
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" value="Sistema" required>
            </div>
            
            <div class="form-group">
                <label for="correo">Correo Electrónico *</label>
                <input type="email" id="correo" name="correo" placeholder="admin@libreriarlg.com" required>
            </div>
            
            <div class="form-group">
                <label for="usuario">Nombre de Usuario *</label>
                <input type="text" id="usuario" name="usuario" placeholder="admin" required>
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña *</label>
                <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 6 caracteres" required>
            </div>
            
            <div class="form-group">
                <label for="confirmar_contrasena">Confirmar Contraseña *</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
            </div>
            
            <button type="submit" class="btn-setup">Crear Administrador</button>
        </form>
        
        <div class="security-note">
            <strong>🔒 Nota de Seguridad:</strong>
            Este formulario solo aparece cuando no existe ningún administrador en el sistema. Una vez creado, este enlace se desactivará automáticamente.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>