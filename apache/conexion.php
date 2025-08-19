<?php

$servidor = "mysql";  
$usuario = "app_user";  
$contrasena = "app_pass";  
$baseDatos = "app_bd"; 
$puerto = 3060;

$conexion = new mysqli($servidor, $usuario, $contrasena, $baseDatos, $puerto);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


$conexion->set_charset("utf8");
?>

