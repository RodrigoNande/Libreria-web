<?php
session_start();
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $response['mensaje'] = 'Email es requerido';
    } else {
        $resultado = reenviarCodigoVerificacion($email);
        $response = $resultado;
    }
} else {
    $response['mensaje'] = 'Método no permitido';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>