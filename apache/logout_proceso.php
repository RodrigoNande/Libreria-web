<?php
// logout_proceso.php
session_start();
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logout();
    $response['exito'] = true;
    $response['mensaje'] = 'Sesión cerrada correctamente';
} else {
    $response['mensaje'] = 'Método no permitido';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>