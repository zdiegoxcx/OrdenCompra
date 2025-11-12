<?php
// Iniciar la sesión para poder manejar mensajes de error
session_start();

// Si el usuario YA está logueado, redirigirlo al index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Revisar si hay un mensaje de error desde el procesador
$error_msg = '';
if (isset($_GET['error'])) {
    $error_msg = "Email o contraseña incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Estilos simples para el login */
        body { background-color: #f0f2f5; }
        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .login-container h1 {
            text-align: center;
            color: #004a99;
            margin-bottom: 25px;
        }
        .login-container form .form-group {
            margin-bottom: 15px;
        }
        .login-container form .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 16px;
        }
        .error-msg {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Plataforma de Adquisiciones</h1>
        
        <?php if ($error_msg): ?>
            <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="procesar_login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>
            <button type="submit" class="btn btn-primary">Ingresar</button>
        </form>
    </div>
</body>
</html>