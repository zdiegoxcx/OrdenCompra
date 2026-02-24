<?php
// controllers/auth_login.php
session_start();
require_once '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtenemos el RUT y le quitamos espacios
    $rut = trim($_POST['rut']); 

    // Preparamos la consulta para buscar solo por RUT
    // Si cambiaste el nombre de la columna ADQUISICIONES a FUNCIONARIO en la BD, cámbialo aquí también.
    // Preparamos la consulta para buscar solo por RUT (Agregamos TIPO_CONTRATO)
    $sql = "SELECT ID, NOMBRE, APELLIDO, DEPTO, ADQUISICIONES, TIPO_CONTRATO 
            FROM FUNCIONARIOS_MUNI 
            WHERE RUT = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // NUEVO: Validar que no sea un trabajador a Honorarios
        $tipo_contrato = strtoupper(trim($user['TIPO_CONTRATO']));
        if ($tipo_contrato === 'HONORARIO' || $tipo_contrato === 'HONORARIOS') {
            // Redirigir de vuelta al login con un código de error específico
            header("Location: ../login.php?error=3");
            exit;
        }

        // Guardamos variables de sesión
        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['user_rut'] = $rut;
        $_SESSION['user_name'] = $user['NOMBRE'] . " " . $user['APELLIDO'];
        $_SESSION['user_depto'] = $user['DEPTO'];
        
        // Rol del usuario (Jefe, Administrativo, etc.)
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