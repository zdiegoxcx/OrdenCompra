<?php
// controllers/orden_firma.php
header('Content-Type: application/json'); 
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

include '../config/db.php';

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$user_id_actual  = $_SESSION['user_id'];
// CORRECCIÓN: Convertir a mayúsculas para coincidir con la lógica de ver_orden.php
$user_rol_actual = strtoupper($_SESSION['user_rol']);   
$user_depto_actual = $_SESSION['user_depto'];

$orden_id = $data['orden_id'] ?? 0;
$accion   = $data['accion'] ?? '';
$token_in = $data['token'] ?? '';
$motivo   = $data['motivo'] ?? '';

$conn->begin_transaction();

try {
    // 1. Obtener datos de la orden
    $sql = "SELECT op.Estado, op.Solicitante_Id, f.DEPTO 
            FROM Orden_Pedido op 
            JOIN FUNCIONARIOS_MUNI f ON op.Solicitante_Id = f.ID 
            WHERE op.Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) throw new Exception("Orden no encontrada.");
    $orden = $res->fetch_assoc();
    $stmt->close();

    $estado_actual = $orden['Estado'];
    $solicitante_id = $orden['Solicitante_Id'];

    // 2. MATRIZ DE PERMISOS (Lógica de Roles)
    $puede_actuar = false;
    $nuevo_estado = '';

    // A: Funcionario firma su propia orden
    if ($estado_actual === 'Pend. Mi Firma' && $user_id_actual == $solicitante_id) {
        $puede_actuar = true;
        $nuevo_estado = 'Pend. Firma Director';
    }
    // B: Director firma CUALQUIER orden pendiente de firma de director (Global)
    elseif ($estado_actual === 'Pend. Firma Director' && $user_rol_actual === 'DIRECTOR') {
        $puede_actuar = true;
        $nuevo_estado = 'Pend. Firma Alcalde';
    }
    // C: Alcalde aprueba todo
    elseif ($estado_actual === 'Pend. Firma Alcalde' && $user_rol_actual === 'ALCALDE') {
        $puede_actuar = true;
        $nuevo_estado = 'Aprobado';
    }

    if (!$puede_actuar && $accion === 'firmar') {
        throw new Exception("No tiene permisos para firmar en esta etapa. Rol actual: $user_rol_actual, Estado Orden: $estado_actual");
    }

    // 3. PROCESAR ACCIÓN
    $sql_log = "INSERT INTO Firmas_Orden (Usuario_Id, Orden_Id, Fecha_Firma, Decision) VALUES (?, ?, NOW(), ?)";
    $stmt_log = $conn->prepare($sql_log);

    if ($accion === 'firmar') {
        // --- VALIDACIÓN DE TOKEN (Primeros 6 dígitos del RUT) ---
        
        // 1. Obtener RUT real del usuario
        $sql_auth = "SELECT RUT FROM FUNCIONARIOS_MUNI WHERE ID = ?";
        $stmt_auth = $conn->prepare($sql_auth);
        $stmt_auth->bind_param("i", $user_id_actual);
        $stmt_auth->execute();
        $res_auth = $stmt_auth->get_result();
        $rut_bd = $res_auth->fetch_assoc()['RUT'];
        
        // 2. Limpiar RUT (quitar puntos, guion, k) y dejar solo números
        $rut_limpio = preg_replace('/[^0-9]/', '', $rut_bd);
        
        // 3. Extraer primeros 6 dígitos
        $token_valido = substr($rut_limpio, 0, 6);
        
        // 4. Comparar (Token ingresado también debe limpiarse por si acaso)
        $token_in_clean = preg_replace('/[^0-9]/', '', $token_in);

        if ($token_in_clean !== $token_valido) {
            throw new Exception("Token incorrecto. Ingrese los primeros 6 números de su RUT.");
        }

        // --- SI ES CORRECTO, ACTUALIZAR ORDEN ---
        $conn->query("UPDATE Orden_Pedido SET Estado = '$nuevo_estado' WHERE Id = $orden_id");
        
        // Registrar Log Aprobado (1)
        $decision = 1;
        $stmt_log->bind_param("iii", $user_id_actual, $orden_id, $decision);
        $msg = "Orden firmada exitosamente.";

    } elseif ($accion === 'rechazar') {
        if (empty($motivo)) throw new Exception("Debe indicar un motivo.");
        
        $sql_upd = "UPDATE Orden_Pedido SET Estado = 'Rechazada', Motivo_Rechazo = ? WHERE Id = ?";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("si", $motivo, $orden_id);
        $stmt_upd->execute();

        // Registrar Log Rechazado (0)
        $decision = 0;
        $stmt_log->bind_param("iii", $user_id_actual, $orden_id, $decision);
        $msg = "Orden rechazada.";
    }

    $stmt_log->execute();
    $conn->commit();

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>