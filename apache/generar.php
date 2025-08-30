<?php
// generar_hash.php - Genera hash para admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Contraseña: " . $password . "\n";
echo "Hash generado: " . $hash . "\n";
echo "\n";
echo "Para actualizar en tu BD:\n";
echo "UPDATE usuarios SET Contrasena = '$hash' WHERE Correo = 'hernandezrodri83@gmail.com';\n";
echo "\n";
echo "Verificación:\n";
if (password_verify($password, $hash)) {
    echo "✓ Hash verificado correctamente\n";
} else {
    echo "✗ Error en el hash\n";
}
?>