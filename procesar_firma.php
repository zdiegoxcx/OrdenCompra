<?php
// 1. Iniciar la sesión (SIEMPRE al principio)
session_start();

// 2. ¡Guardia de Seguridad!
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}
// 3. Incluir conexión
include 'conectar.php';

// 4. Leer los datos JSON enviados desde fetch()
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Datos no recibidos.']);
    exit;
}

// 5. Recoger datos de la sesión y del JSON
$user_id_actual = $_SESSION['user_id'];
$user_rol_actual = $_SESSION['user_rol'];
$user_depto_id_actual = $_SESSION['user_depto_id'];

$orden_id = $data['orden_id'] ?? 0;
$accion = $data['accion'] ?? '';
$token_ingresado = $data['token'] ?? '';
$motivo_rechazo = $data['motivo'] ?? '';

// --- Iniciar Transacción ---
$conn->begin_transaction();

try {
    // 6. Obtener la orden y los datos del solicitante
    $sql_orden = "
        SELECT 
            op.Estado, 
            op.Solicitante_Id,
            u.Departamento_Id AS Solicitante_Depto_Id
        FROM Orden_Pedido op
        JOIN Usuario u ON op.Solicitante_Id = u.Id
        WHERE op.Id = ?
    ";
    $stmt_orden_info = $conn->prepare($sql_orden);
    $stmt_orden_info->bind_param("i", $orden_id);
    $stmt_orden_info->execute();
    $resultado_orden = $stmt_orden_info->get_result();

    if ($resultado_orden->num_rows === 0) {
        throw new Exception("Orden no encontrada.");
    }
    $orden = $resultado_orden->fetch_assoc();
    $stmt_orden_info->close();

    $orden_estado_actual = $orden['Estado'];
    $orden_solicitante_id = $orden['Solicitante_Id'];
    $orden_solicitante_depto_id = $orden['Solicitante_Depto_Id'];

    // 7. --- LÓGICA DE AUTORIZACIÓN ---
    // ¿Quién puede firmar y en qué estado?
    $puede_actuar = false;
    $nuevo_estado = '';

    if ($orden_estado_actual === 'Pend. Mi Firma' && $user_id_actual === $orden_solicitante_id) {
        $puede_actuar = true;
        $nuevo_estado = 'Pend. Firma Director'; // Siguiente paso
    } 
    elseif ($orden_estado_actual === 'Pend. Firma Director' && $user_rol_actual === 'Director' && $user_depto_id_actual === $orden_solicitante_depto_id) {
        $puede_actuar = true;
        $nuevo_estado = 'Pend. Firma Alcalde'; // Siguiente paso
    } 
    elseif ($orden_estado_actual === 'Pend. Firma Alcalde' && $user_rol_actual === 'Alcalde') { // Asumiendo que el rol es 'Alcalde'
        $puede_actuar = true;
        $nuevo_estado = 'Aprobado'; // Paso final
    }

    if (!$puede_actuar) {
        throw new Exception("No tiene permisos para realizar esta acción en este estado.");
    }

    // 8. --- PROCESAR LA ACCIÓN (FIRMAR O RECHAZAR) ---
    
    // Preparar el INSERT para la tabla de logs (Firmas_Orden)
    $sql_log = "INSERT INTO Firmas_Orden (Usuario_Id, Orden_Id, Fecha_Firma, Decision) VALUES (?, ?, NOW(), ?)";
    $stmt_log = $conn->prepare($sql_log);
    
    if ($accion === 'firmar') {
        // 8a. Acción: FIRMAR
        
        // Validar el token del usuario
        $sql_token = "SELECT Token FROM Usuario WHERE Id = ?";
        $stmt_token = $conn->prepare($sql_token);
        $stmt_token->bind_param("i", $user_id_actual);
        $stmt_token->execute();
        $token_bd = $stmt_token->get_result()->fetch_assoc()['Token'];
        $stmt_token->close();

        if ($token_bd !== $token_ingresado) {
            // Usamos una excepción para detenernos, pero no revertimos la TX
            $conn->rollback(); // Revertimos porque fue un intento fallido
            echo json_encode(['success' => false, 'message' => 'Token de firma incorrecto.']);
            exit;
        }

        // Token correcto: Actualizar la orden al siguiente estado
        $sql_update = "UPDATE Orden_Pedido SET Estado = ? WHERE Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nuevo_estado, $orden_id);
        $stmt_update->execute();
        $stmt_update->close();

        // Registrar la aprobación (Decision = 1)
        $decision_log = 1; // 1 = Aprobado
        $stmt_log->bind_param("iii", $user_id_actual, $orden_id, $decision_log);
        
        $message = "Orden aprobada y enviada al siguiente nivel.";

    } elseif ($accion === 'rechazar') {
        // 8b. Acción: RECHAZAR
        if (empty($motivo_rechazo)) {
            throw new Exception("El motivo de rechazo es obligatorio.");
        }

        // Actualizar la orden a 'Rechazada' y guardar el motivo
        $sql_update = "UPDATE Orden_Pedido SET Estado = 'Rechazada', Motivo_Rechazo = ? WHERE Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $motivo_rechazo, $orden_id);
        $stmt_update->execute();
        $stmt_update->close();

        // Registrar el rechazo (Decision = 0)
        $decision_log = 0; // 0 = Rechazado
        $stmt_log->bind_param("iii", $user_id_actual, $orden_id, $decision_log);
        
        $message = "Orden rechazada correctamente.";
    }

    // Ejecutar el log
    $stmt_log->execute();
    $stmt_log->close();

    // 9. ¡ÉXITO! Confirmar la transacción
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // 10. ¡ERROR! Revertir todo
    $conn->rollback();
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>