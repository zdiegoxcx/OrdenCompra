<?php
// controllers/auth_login.php

// 1. Iniciar la sesión (SIEMPRE al principio)
session_start();

// 2. Incluir la conexión
// CAMBIO: Salimos de 'controllers' (../) y entramos a 'config'
include '../config/db.php';

// 3. Verificar que los datos vengan por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $contrasena_ingresada = $_POST['contrasena'];

    // 4. Preparar la consulta (evita Inyección SQL)
    $sql = "SELECT Id, Nombre, Contrasenha, Rol, Departamento_Id FROM Usuario WHERE Email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // 5. Verificar si se encontró al usuario
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // 6. ¡Verificación!
        if ($contrasena_ingresada === $usuario['Contrasenha']) {
            
            // ¡Contraseña correcta!
            // 7. Regenerar ID de sesión por seguridad
            session_regenerate_id(true);

            // 8. Guardar datos clave en la SESIÓN
            $_SESSION['user_id'] = $usuario['Id'];
            $_SESSION['user_nombre'] = $usuario['Nombre'];
            $_SESSION['user_rol'] = $usuario['Rol'];
            $_SESSION['user_depto_id'] = $usuario['Departamento_Id'];

            // 9. Redirigir al panel principal (index.php)
            // CAMBIO: Salimos de 'controllers' para ir al index en la raíz
            header("Location: ../index.php");
            exit;

        }
    }

    // 10. Si algo falla (usuario no existe o pass incorrecta), redirigir de vuelta
    // CAMBIO: Salimos de 'controllers' para ir a login en la raíz
    header("Location: ../login.php?error=1");
    exit;

} else {
    // Si alguien intenta acceder a este archivo directamente
    // CAMBIO: Salimos de 'controllers' para ir a login en la raíz
    header("Location: ../login.php");
    exit;
}
?>