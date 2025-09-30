<?php
session_start();
require_once 'conexion.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'correo' => trim($_POST['correo'] ?? ''),
        'usuario' => trim($_POST['usuario'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'contrasena' => $_POST['contrasena'] ?? ''
    ];
    
    $resultado = registrarUsuario($datos);
    $response = $resultado;
} else {
    $response['mensaje'] = 'Método no permitido';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>