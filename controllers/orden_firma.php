<?php
// ARCHIVO: controllers/orden_firma.php

/**
 * ESTE SCRIPT ES UNA API (Backend):
 * No muestra HTML. Recibe datos JSON, procesa la lógica y devuelve JSON.
 * Es consumido por 'js/firma-logic.js'.
 */

// 1. Configuración Inicial
// Indicamos al navegador que la respuesta será siempre en formato JSON
header('Content-Type: application/json'); 
session_start();

// 2. Seguridad: Control de Acceso
// Si el usuario no está logueado, detenemos la ejecución inmediatamente.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Código HTTP 403 Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso denegado: Usuario no autenticado.']);
    exit;
}

// 3. Conexión a la Base de Datos
// CAMBIO DE RUTA: Al estar en 'controllers', subimos un nivel (../) para entrar a 'config'
include '../config/db.php';

// 4. Recepción de Datos (Payload JSON)
// A diferencia de los formularios tradicionales ($_POST), fetch() envía el cuerpo en crudo.
// Leemos el flujo de entrada 'php://input' y lo decodificamos.
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

// Validamos que lleguen datos
if (!$data) {
    http_response_code(400); // Código HTTP 400 Bad Request
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos válidos.']);
    exit;
}

// 5. Variables de Sesión y Datos Recibidos
$user_id_actual       = $_SESSION['user_id'];
$user_rol_actual      = $_SESSION['user_rol'];
$user_depto_id_actual = $_SESSION['user_depto_id'];

// Uso del operador de fusión de null (??) para evitar errores si falta algún campo
$orden_id        = $data['orden_id'] ?? 0;
$accion          = $data['accion'] ?? '';       // 'firmar' o 'rechazar'
$token_ingresado = $data['token'] ?? '';        // Solo si firma
$motivo_rechazo  = $data['motivo'] ?? '';       // Solo si rechaza

// 6. Iniciar Transacción (ACID)
// Esto es CRÍTICO. Asegura que o se guarda todo (log + actualización) o no se guarda nada.
$conn->begin_transaction();

try {
    // -----------------------------------------------------------------------
    // PASO A: Obtener información actual de la orden para validar permisos
    // -----------------------------------------------------------------------
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
        throw new Exception("La orden solicitada no existe.");
    }
    
    $orden = $resultado_orden->fetch_assoc();
    $stmt_orden_info->close();

    $orden_estado_actual        = $orden['Estado'];
    $orden_solicitante_id       = $orden['Solicitante_Id'];
    $orden_solicitante_depto_id = $orden['Solicitante_Depto_Id'];

    // -----------------------------------------------------------------------
    // PASO B: Matriz de Autorización (Lógica de Negocio)
    // -----------------------------------------------------------------------
    // Definimos quién tiene permiso para actuar según el estado actual de la orden
    
    $puede_actuar = false;
    $nuevo_estado = '';

    // CASO 1: El Profesional (Solicitante) firma su propia orden para enviarla al Director
    if ($orden_estado_actual === 'Pend. Mi Firma' && $user_id_actual === $orden_solicitante_id) {
        $puede_actuar = true;
        $nuevo_estado = 'Pend. Firma Director'; 
    } 
    // CASO 2: El Director firma las órdenes de su departamento
    elseif ($orden_estado_actual === 'Pend. Firma Director' && $user_rol_actual === 'Director' && $user_depto_id_actual === $orden_solicitante_depto_id) {
        $puede_actuar = true;
        $nuevo_estado = 'Pend. Firma Alcalde'; 
    } 
    // CASO 3: El Alcalde firma la aprobación final
    elseif ($orden_estado_actual === 'Pend. Firma Alcalde' && $user_rol_actual === 'Alcalde') { 
        $puede_actuar = true;
        $nuevo_estado = 'Aprobado'; 
    }

    if (!$puede_actuar) {
        throw new Exception("No tiene permisos para realizar esta acción en el estado actual de la orden.");
    }

    // -----------------------------------------------------------------------
    // PASO C: Procesar la Acción Específica
    // -----------------------------------------------------------------------
    
    // Preparamos la sentencia SQL para el LOG de auditoría (quién hizo qué y cuándo)
    $sql_log = "INSERT INTO Firmas_Orden (Usuario_Id, Orden_Id, Fecha_Firma, Decision) VALUES (?, ?, NOW(), ?)";
    $stmt_log = $conn->prepare($sql_log);
    
    if ($accion === 'firmar') {
        // --- LÓGICA DE FIRMA ---
        
        // C.1: Validar el Token de Seguridad del usuario actual
        $sql_token = "SELECT Token FROM Usuario WHERE Id = ?";
        $stmt_token = $conn->prepare($sql_token);
        $stmt_token->bind_param("i", $user_id_actual);
        $stmt_token->execute();
        $res_token = $stmt_token->get_result();
        $token_bd = $res_token->fetch_assoc()['Token'];
        $stmt_token->close();

        // Comparación estricta del token
        if ($token_bd !== $token_ingresado) {
            // Importante: Aunque falle la lógica, debemos hacer rollback por seguridad
            $conn->rollback(); 
            echo json_encode(['success' => false, 'message' => 'Token de firma incorrecto. Intente nuevamente.']);
            exit;
        }

        // C.2: Token Válido -> Actualizar Estado de la Orden
        $sql_update = "UPDATE Orden_Pedido SET Estado = ? WHERE Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nuevo_estado, $orden_id);
        $stmt_update->execute();
        $stmt_update->close();

        // C.3: Registrar en el Log (Decision = 1 Aprobado)
        $decision_log = 1; 
        $stmt_log->bind_param("iii", $user_id_actual, $orden_id, $decision_log);
        
        $message = "Orden firmada y aprobada correctamente.";

    } elseif ($accion === 'rechazar') {
        // --- LÓGICA DE RECHAZO ---
        
        if (empty($motivo_rechazo)) {
            throw new Exception("Debe ingresar un motivo para rechazar la orden.");
        }

        // C.1: Marcar orden como rechazada y guardar el motivo
        $sql_update = "UPDATE Orden_Pedido SET Estado = 'Rechazada', Motivo_Rechazo = ? WHERE Id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $motivo_rechazo, $orden_id);
        $stmt_update->execute();
        $stmt_update->close();

        // C.2: Registrar en el Log (Decision = 0 Rechazado)
        $decision_log = 0; 
        $stmt_log->bind_param("iii", $user_id_actual, $orden_id, $decision_log);
        
        $message = "La orden ha sido rechazada.";
    }

    // Ejecutar la inserción en el log
    $stmt_log->execute();
    $stmt_log->close();

    // 7. Commit: Confirmar todos los cambios en la BD
    $conn->commit();
    
    // Responder éxito al Frontend
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // 8. Rollback: Si algo falló, deshacer cualquier cambio pendiente
    $conn->rollback();
    
    // Enviar error al cliente (Error 500 Interno)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Cerrar conexión
$conn->close();
?>