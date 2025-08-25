<?php
// verificar_proceso.php
session_start();
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    
    if (empty($email) || empty($codigo)) {
        $response['mensaje'] = 'Email y código son requeridos';
    } else {
        $resultado = verificarEmail($email, $codigo);
        $response = $resultado;
    }
} else {
    $response['mensaje'] = 'Método no permitido';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>