<?php
// Datos de conexión
$servername = "localhost";
$username = "root";
$password = ""; // Por defecto en XAMPP es vacía
$dbname = "ordencompra2"; // Cambia esto al nombre de tu base de datos

// 1. Crear la conexión (Estilo Orientado a Objetos)
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. Verificar la conexión
if ($conn->connect_error) {
    // Si hay un error, muestra cuál es y detiene el script
    die("Conexión fallida: " . $conn->connect_error);
}

// 3. (Opcional) Establecer el charset a UTF-8 para evitar problemas con tildes y ñ
$conn->set_charset("utf8mb4");

?>