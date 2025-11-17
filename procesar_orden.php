<?php
// 1. Iniciar la sesión
session_start();

// 2. ¡Guardia de Seguridad!
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, no puede procesar
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// 3. Verificar que los datos vengan por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Incluir conexión
    include 'conectar.php';

    // 5. Iniciar una transacción
    // Esto es CRUCIAL. O se inserta la orden Y sus items, o no se inserta NADA.
    $conn->begin_transaction();

    try {
        // 6. Recoger datos del formulario
        $solicitante_id = $_SESSION['user_id'];
        $solicitante_rol = $_SESSION['user_rol'];
        
        $nombre_orden = $_POST['nombre_orden'];
        $plazo_maximo = $_POST['plazo_maximo'];
        $tipo_compra = $_POST['tipo_compra'];
        $motivo_compra = $_POST['motivo_compra'];
        
        $presupuesto = $_POST['presupuesto'];
        $cuenta_presupuestaria = $_POST['cuenta_presupuestaria'];
        $subprog = $_POST['subprog'];
        $centro_costos = $_POST['centro_costos'];

        // Recoger totales (calculados por JS y enviados en campos hidden)
        $valor_neto = $_POST['valor_neto_hidden'];
        $iva = $_POST['iva_hidden'];
        $valor_total = $_POST['valor_total_hidden'];
        
        // Estado inicial
        if ($solicitante_rol === 'Director') {
            $estado = 'Pend. Firma Director';
        } else {
            // Para 'Profesional' o cualquier otro rol que cree
            $estado = 'Pend. Mi Firma';
        }

        // 7. --- INSERTAR EN LA TABLA 'Orden_Pedido' ---
        $sql_orden = "INSERT INTO Orden_Pedido 
                        (Solicitante_Id, Nombre_Orden, Fecha_Creacion, Tipo_Compra, Presupuesto, Subprog, Centro_Costos, 
                         Plazo_maximo, Iva, Valor_neto, Valor_total, Estado, Motivo_Compra, Cuenta_Presupuestaria)
                      VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_orden = $conn->prepare($sql_orden);
        $stmt_orden->bind_param("issssssdddsss", 
            $solicitante_id, $nombre_orden, $tipo_compra, $presupuesto, $subprog, $centro_costos, 
            $plazo_maximo, $iva, $valor_neto, $valor_total, $estado, $motivo_compra, $cuenta_presupuestaria);
        
        $stmt_orden->execute();
        
        // 8. ¡Obtener el ID de la orden que ACABAMOS de crear!
        $orden_id_nueva = $conn->insert_id;

        // 9. --- INSERTAR EN LA TABLA 'Orden_Item' ---
        
        // Recoger los arrays de items
        $item_cantidades = $_POST['item_cantidad'];
        $item_nombres = $_POST['item_nombre'];
        $item_unitarios = $_POST['item_v_unitario'];

        // Preparar la consulta para los items (solo una vez)
        $sql_item = "INSERT INTO Orden_Item 
                       (Orden_Id, Nombre_producto_servicio, Cantidad, Valor_Unitario, Valor_Total)
                     VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

        // Recorrer el array de items y ejecutarlos
        for ($i = 0; $i < count($item_cantidades); $i++) {
            $cantidad = (int)$item_cantidades[$i];
            $nombre = $item_nombres[$i];
            $v_unitario = (float)$item_unitarios[$i];
            
            // Recalcular el total en el servidor por seguridad
            $v_total_linea = $cantidad * $v_unitario; 
            
            $stmt_item->bind_param("isidd", 
                $orden_id_nueva, $nombre, $cantidad, $v_unitario, $v_total_linea);
            
            $stmt_item->execute();
        }

        // 10. ¡ÉXITO! Confirmar la transacción
        $conn->commit();
        
        $stmt_orden->close();
        $stmt_item->close();
        $conn->close();

        // 11. Redirigir al index con un mensaje de éxito
        header("Location: index.php?creacion=exito");
        exit;

    } catch (Exception $e) {
        // 12. ¡ERROR! Revertir todo
        $conn->rollback();
        $conn->close();
        
        // Mostrar un error (en un sistema real, esto se loguearía)
        die("Error al crear la orden: " . $e->getMessage());
    }

} else {
    // Si no es POST, redirigir
    header("Location: crear-orden.php");
    exit;
}
?>