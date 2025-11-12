<?php
// 1. Iniciar la sesión (SIEMPRE al principio)
session_start();

// 2. ¡Guardia de Seguridad!
// Si no existe la variable de sesión 'user_id', significa que no está logueado
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login
    header("Location: login.php");
    exit; // Detener la ejecución del script
}

// 3. Si pasó la guardia, incluir la conexión
include 'conectar.php';

// 4. Obtener los datos del usuario desde la sesión
$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];
$user_rol = $_SESSION['user_rol'];
$user_depto_id = $_SESSION['user_depto_id'];

// 5. --- ¡AQUÍ ESTÁ LA LÓGICA DE ROLES! ---
// Preparar la consulta base
$sql_base = "SELECT op.Id, op.Nombre_Orden, op.Fecha_Creacion, op.Valor_total, op.Estado 
             FROM Orden_Pedido op";
$params = []; // Array para los parámetros de la consulta preparada
$types = ""; // String para los tipos de parámetros (i = integer, s = string)

if ($user_rol === 'Director') {
    // Lógica del Director:
    // Ver órdenes de su depto que esperan firma ('Pendiente Aprobación') O las suyas propias.
    
    $sql_join = " JOIN Usuario u ON op.Solicitante_Id = u.Id";
    $sql_where = " WHERE (u.Departamento_Id = ? AND op.Estado = 'Pendiente Aprobación') OR (op.Solicitante_Id = ?)";
    
    $sql = $sql_base . $sql_join . $sql_where . " ORDER BY op.Id DESC";
    
    $params = [$user_depto_id, $user_id];
    $types = "ii"; // Dos enteros (department_id, user_id)

} else {
    // Lógica del Solicitante (Rol 'Profesional' o cualquier otro)
    // Ver SÓLO sus propias órdenes
    $sql_where = " WHERE op.Solicitante_Id = ?";
    $sql = $sql_base . $sql_where . " ORDER BY op.Id DESC";
    
    $params = [$user_id];
    $types = "i"; // Un entero (user_id)
}

// 6. Ejecutar la consulta dinámica
$stmt = $conn->prepare($sql);
if ($stmt === FALSE) {
    die("Error al preparar la consulta: " . $conn->error);
}

// bind_param necesita referencias, usamos ...$params
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// (Tu función getStatusClass sigue igual)
function getStatusClass($estado) {
    switch (strtolower($estado)) {
        case 'aprobado': return 'status-aprobado';
        case 'pendiente aprobación':
        case 'pend. director': return 'status-pendiente-firma';
        case 'pend. alcalde': return 'status-pendiente';
        case 'pendiente mi firma': return 'status-borrador';
        default: return 'status-borrador';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Adquisiciones - Inicio</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <div class="app-container">
        
        <header class="app-header">
            <h1>Plataforma de Adquisiciones</h1>
            <span>
                Usuario: <strong><?php echo htmlspecialchars($user_nombre); ?></strong> (<?php echo htmlspecialchars($user_rol); ?>)
                &nbsp; | &nbsp;
                <a href="logout.php" style="color: white; text-decoration: underline;">Cerrar Sesión</a>
            </span>
        </header>

        <main class="app-content">
            <div id="panel-view">
                <div class="panel-header">
                    <h2>Mis Órdenes de Pedido</h2>
                    <a href="crear-orden.html" class="btn btn-primary">➕ Crear Nueva Orden</a>
                </div>
                
                <table id="ordenes-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre de la Compra</th>
                            <th>Fecha Creación</th>
                            <th>Total ($)</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultado->num_rows > 0) {
                            while($fila = $resultado->fetch_assoc()) {
                                $statusClass = getStatusClass($fila["Estado"]);
                                $totalFormateado = number_format($fila["Valor_total"], 0, ',', '.');
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($fila["Id"]) . "</td>";
                                echo "<td>" . htmlspecialchars($fila["Nombre_Orden"]) . "</td>";
                                echo "<td>" . htmlspecialchars($fila["Fecha_Creacion"]) . "</td>";
                                echo "<td>" . htmlspecialchars($totalFormateado) . "</td>";
                                echo "<td><span class='status " . $statusClass . "'>" . htmlspecialchars($fila["Estado"]) . "</span></td>";
                                echo "<td><a href='ver_orden.php?id=" . $fila["Id"] . "' class='btn btn-secondary' style='padding: 5px 10px;'>Ver</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No se encontraron órdenes de pedido para mostrar.</td></tr>";
                        }
                        
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>