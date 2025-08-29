<?php
// login_proceso.php
session_start();
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'mensaje' => '', 'es_admin' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recordarme = isset($_POST['recordarme']) && $_POST['recordarme'] === '1';
    
    if (empty($email) || empty($password)) {
        $response['mensaje'] = 'Email y contraseña son requeridos';
    } else {
        $resultado = loginUsuario($email, $password, $recordarme);
        $response = $resultado;
    }
} else {
    $response['mensaje'] = 'Método no permitido';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>