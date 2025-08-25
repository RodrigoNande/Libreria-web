<?php
$host = "mysql";    // porque expones el puerto al host
$port = 3306;           
$user = "app_user";     
$pass = "app_pass";     
$db   = "app_db";       

$conn = new mysqli($host, $user, $pass, $db, $port);
?>