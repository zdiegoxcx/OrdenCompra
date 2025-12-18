<?php
// login.php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Funcionarios - Gestión Adquisiciones</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: var(--bg-body);
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-card h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-muted); }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover { background-color: var(--primary-hover); }
        .error-msg { 
            background-color: #fee2e2; 
            color: #b91c1c; 
            padding: 10px; 
            border-radius: 6px;
            margin-bottom: 15px; 
            font-size: 0.9rem; 
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Bienvenido</h2>
    <p style="color: #666; margin-bottom: 25px;">Ingrese su RUT para acceder</p>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">
            <?php 
                if($_GET['error'] == '1') echo "El RUT ingresado no se encuentra en el sistema.";
                if($_GET['error'] == '2') echo "Debe iniciar sesión para continuar.";
            ?>
        </div>
    <?php endif; ?>

    <form action="controllers/auth_login.php" method="POST">
        <div class="form-group">
            <label for="rut">RUT Funcionario</label>
            <input type="text" id="rut" name="rut" placeholder="Ej: 12345678-9" required autocomplete="off" autofocus>
        </div>

        <button type="submit" class="btn-login">Ingresar</button>
    </form>
</div>

</body>
</html>