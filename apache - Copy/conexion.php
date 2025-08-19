<?php
// Datos de conexión a la base de datos
$servidor = "mysql";  // Cambia si tu servidor es diferente
$usuario = "app_user";  // Reemplaza con tu usuario de MySQL
$contrasena = "app_pass";  // Reemplaza con tu contraseña
$baseDatos = "app_bd";  // Reemplaza con el nombre de tu base de datos
$puerto = 3060;
// Crear conexión
$conexion = new mysqli($servidor, $usuario, $contrasena, $baseDatos, $puerto);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configurar charset a utf8
$conexion->set_charset("utf8");
?>

