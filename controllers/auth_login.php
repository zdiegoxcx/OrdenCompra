<?php
// controllers/auth_login.php
session_start();
require_once '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtenemos el RUT y le quitamos espacios
    $rut = trim($_POST['rut']); 

    // Preparamos la consulta para buscar solo por RUT
    // Si cambiaste el nombre de la columna ADQUISICIONES a FUNCIONARIO en la BD, cámbialo aquí también.
    $sql = "SELECT ID, NOMBRE, APELLIDO, DEPTO, ADQUISICIONES 
            FROM FUNCIONARIOS_MUNI 
            WHERE RUT = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // --- USUARIO ENCONTRADO ---
        $user = $result->fetch_assoc();

        // Guardamos variables de sesión
        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['user_rut'] = $rut;
        $_SESSION['user_name'] = $user['NOMBRE'] . " " . $user['APELLIDO'];
        $_SESSION['user_depto'] = $user['DEPTO'];
        
        // Rol del usuario (Jefe, Administrativo, etc.)
        // Si el campo es nulo, asignamos 'FUNCIONARIO' por defecto
        $_SESSION['user_rol'] = !empty($user['ADQUISICIONES']) ? $user['ADQUISICIONES'] : 'FUNCIONARIO';

        // Redirigir al panel principal
        header("Location: ../index.php");
        exit;
        
    } else {
        // RUT no encontrado en la base de datos
        header("Location: ../login.php?error=1");
        exit;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../login.php");
    exit;
}
?>