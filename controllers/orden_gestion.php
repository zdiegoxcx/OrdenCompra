<?php
// controllers/orden_gestion.php

// 1. Iniciar sesión y control de acceso
session_start();

// Validar que el usuario esté logueado Y tenga el rol 'EncargadoAdquision'
// Si no cumple, lo devolvemos al inicio
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'EncargadoAdquision') {
    // CAMBIO: Salir de 'controllers' para ir al index en la raíz
    header("Location: ../index.php");
    exit;
}

// 2. Incluir conexión a la base de datos
// CAMBIO: Subimos un nivel (../) para encontrar la carpeta config
include '../config/db.php';

// 3. Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir y sanitizar datos básicos
    $orden_id = intval($_POST['orden_id']);
    $proveedor = $_POST['proveedor_nombre'];
    
    // Validar que los datos sean correctos
    if ($orden_id > 0 && !empty($proveedor)) {
        
        // Iniciar transacción (Todo o nada)
        $conn->begin_transaction();
        
        try {
            // ---------------------------------------------------------------
            // PASO A: Registrar la gestión en el historial
            // ---------------------------------------------------------------
            $sql_gestion = "INSERT INTO Gestion_Compra (Orden_Id, Fecha_Gestion, Proveedor_Contactado, Estado_gestion) VALUES (?, NOW(), ?, 'En Proceso')";
            $stmt = $conn->prepare($sql_gestion);
            $stmt->bind_param("is", $orden_id, $proveedor);
            $stmt->execute();
            $stmt->close();
            
            // ---------------------------------------------------------------
            // PASO B: Actualizar el estado de la Orden Principal
            // ---------------------------------------------------------------
            // La orden pasa a 'En Espera' para activar el contador de tiempo (cron)
            $sql_update = "UPDATE Orden_Pedido SET Estado = 'En Espera' WHERE Id = ?";
            $stmt2 = $conn->prepare($sql_update);
            $stmt2->bind_param("i", $orden_id);
            $stmt2->execute();
            $stmt2->close();
            
            // Confirmar cambios
            $conn->commit();
            
            // 4. Redirección Exitosa
            // CAMBIO: Salir de 'controllers' para ir a ver_orden.php en la raíz
            header("Location: ../ver_orden.php?id=$orden_id&msg=gestion_iniciada");
            exit;
            
        } catch (Exception $e) {
            // Si hay error, deshacer cambios
            $conn->rollback();
            die("Error al procesar la gestión: " . $e->getMessage());
        }
    } else {
        die("Datos inválidos: Falta ID de orden o nombre del proveedor.");
    }
}

// Cerrar conexión (buenas prácticas)
$conn->close();
?>