<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'EncargadoAdquision') {
    header("Location: index.php");
    exit;
}

include 'conectar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orden_id = intval($_POST['orden_id']);
    $proveedor = $_POST['proveedor_nombre'];
    
    if ($orden_id > 0 && !empty($proveedor)) {
        $conn->begin_transaction();
        
        try {
            // 1. Insertar en Gestion_Compra
            $sql_gestion = "INSERT INTO Gestion_Compra (Orden_Id, Fecha_Gestion, Proveedor_Contactado, Estado_gestion) VALUES (?, NOW(), ?, 'En Proceso')";
            $stmt = $conn->prepare($sql_gestion);
            $stmt->bind_param("is", $orden_id, $proveedor);
            $stmt->execute();
            $stmt->close();
            
            // 2. Actualizar estado de la Orden
            $sql_update = "UPDATE Orden_Pedido SET Estado = 'En Espera' WHERE Id = ?";
            $stmt2 = $conn->prepare($sql_update);
            $stmt2->bind_param("i", $orden_id);
            $stmt2->execute();
            $stmt2->close();
            
            $conn->commit();
            header("Location: ver_orden.php?id=$orden_id&msg=gestion_iniciada");
            
        } catch (Exception $e) {
            $conn->rollback();
            die("Error al procesar: " . $e->getMessage());
        }
    } else {
        die("Datos inválidos.");
    }
}
$conn->close();
?>