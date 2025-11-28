<?php
// Este script verifica si ha pasado más de 1 día desde la gestión
include 'conectar.php';

// SQL: Actualizar a 'Sin respuesta...' si el estado es 'En Espera' 
// Y la fecha en Gestion_Compra es mayor a 24 horas (INTERVAL 1 DAY)
$sql = "
    UPDATE Orden_Pedido op
    JOIN Gestion_Compra gc ON op.Id = gc.Orden_Id
    SET op.Estado = 'Sin respuesta del vendedor'
    WHERE op.Estado = 'En Espera'
    AND gc.Fecha_Gestion < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
";

if ($conn->query($sql) === TRUE) {
    echo "Actualización de vencimientos completada. Filas afectadas: " . $conn->affected_rows;
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>