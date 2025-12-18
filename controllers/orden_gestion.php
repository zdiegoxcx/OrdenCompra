<?php
// controllers/orden_gestion.php

// 1. Iniciar sesión y seguridad
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Validación de ROL (Nuevo cambio solicitado)
// Solo el perfil "ADQUISICIONES" puede acceder a este script
if ($_SESSION['user_rol'] !== 'ADQUISICIONES') {
    die("Acceso denegado: Usted no tiene permisos de Adquisiciones.");
}

// 3. Conexión a BD
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $orden_id = isset($_POST['orden_id']) ? intval($_POST['orden_id']) : 0;
    $proveedor = isset($_POST['proveedor_nombre']) ? trim($_POST['proveedor_nombre']) : '';

    if ($orden_id === 0 || empty($proveedor)) {
        die("Error: Faltan datos necesarios (ID Orden o Proveedor).");
    }

    // Iniciar transacción por seguridad
    $conn->begin_transaction();

    try {
        // 4. Insertar registro en Gestion_Compra
        $sql_gestion = "INSERT INTO Gestion_Compra (Orden_Id, Fecha_Gestion, Proveedor_Contactado, Estado_gestion) 
                        VALUES (?, NOW(), ?, 'En Espera')";
        
        $stmt = $conn->prepare($sql_gestion);
        $stmt->bind_param("is", $orden_id, $proveedor);
        $stmt->execute();
        $stmt->close();

        // 5. Actualizar estado de la Orden a 'En Espera'
        // Esto indica que Adquisiciones ya la tomó y está esperando cotización/respuesta
        $sql_update = "UPDATE Orden_Pedido SET Estado = 'En Espera' WHERE Id = ?";
        $stmt_upd = $conn->prepare($sql_update);
        $stmt_upd->bind_param("i", $orden_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        // Confirmar cambios
        $conn->commit();

        // Redireccionar
        header("Location: ../ver_orden.php?id=" . $orden_id . "&msg=gestion_iniciada");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Error al guardar la gestión: " . $e->getMessage());
    }
} else {
    // Si intentan entrar directo
    header("Location: ../index.php");
    exit;
}
?>