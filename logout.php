<?php
session_start(); // 1. Unirse a la sesión existente

// 2. Limpiar todas las variables de sesión
$_SESSION = array();

// 3. Destruir la sesión del servidor
session_destroy();

// 4. Redirigir al formulario de login
header("Location: login.php");
exit;
?>