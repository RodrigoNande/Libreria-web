<?php
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]++;
    } else {
        $_SESSION['carrito'][$id] = 1;
    }
}
header('Location: home.php');
?>