<?php
// ARCHIVO: cron/vencimientos.php

/**
 * SCRIPT DE AUTOMATIZACIÓN (CRON JOB)
 * -----------------------------------------------------------------------
 * Este script está diseñado para ejecutarse automáticamente (ej. cada hora)
 * en el servidor. Su función es verificar órdenes que están "En Espera"
 * y marcarlas como vencidas si el proveedor no ha respondido en el tiempo límite.
 */

// 1. Incluir la configuración de la base de datos
// CAMBIO: Subimos un nivel (../) para encontrar la carpeta config
include '../config/db.php';

// 2. Definir y Ejecutar la Lógica de Vencimiento
try {
    // La consulta hace lo siguiente:
    // 1. UPDATE Orden_Pedido: Vamos a actualizar la tabla de órdenes.
    // 2. JOIN Gestion_Compra: Unimos con la tabla de gestión para ver cuándo se contactó al proveedor.
    // 3. SET op.Estado: Cambiamos el estado a 'Sin respuesta del vendedor'.
    // 4. WHERE op.Estado = 'En Espera': Solo afectamos órdenes que están esperando respuesta.
    // 5. AND gc.Fecha_Gestion < DATE_SUB(...): Filtramos las que son más antiguas que el tiempo límite.
    
    // NOTA: Actualmente configurado a '10 MINUTE' para pruebas rápidas. 
    // Para producción, cambiar '10 MINUTE' por '1 DAY' o el plazo real.
    $sql = "
        UPDATE Orden_Pedido op
        JOIN Gestion_Compra gc ON op.Id = gc.Orden_Id
        SET op.Estado = 'Sin respuesta del vendedor'
        WHERE op.Estado = 'En Espera'
        AND gc.Fecha_Gestion < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ";

    // Ejecutar la consulta
    if ($conn->query($sql) === TRUE) {
        // Si todo sale bien, mostramos cuántas órdenes fueron actualizadas
        $filas_afectadas = $conn->affected_rows;
        
        if ($filas_afectadas > 0) {
            echo "Cron ejecutado exitosamente: Se marcaron $filas_afectadas orden(es) como vencidas.";
        } else {
            echo "Cron ejecutado: No se encontraron órdenes vencidas para actualizar.";
        }
        
    } else {
        // Error en la sintaxis SQL
        throw new Exception("Error en la consulta SQL: " . $conn->error);
    }

} catch (Exception $e) {
    // Capturar errores de conexión o ejecución
    echo "Error Crítico: " . $e->getMessage();
}

// 3. Cerrar la conexión para liberar recursos
$conn->close();
?>