<?php
// 1. Iniciar la sesión
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'conectar.php';

// Actualizar estados expirados
$conn->query("
    UPDATE Orden_Pedido op
    INNER JOIN (
        SELECT Orden_Id, MAX(Fecha_Gestion) as Ultima_Gestion 
        FROM Gestion_Compra 
        GROUP BY Orden_Id
    ) gc ON op.Id = gc.Orden_Id
    SET op.Estado = 'Sin respuesta del vendedor'
    WHERE op.Estado = 'En Espera'
    AND gc.Ultima_Gestion < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
");

$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];
$user_rol = $_SESSION['user_rol'];
$user_depto_id = $_SESSION['user_depto_id'];

// --- CONFIGURACIÓN DE FILTROS ---
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// --- CONFIGURACIÓN DE PAGINACIÓN ---
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// --- CONSTRUCCIÓN DINÁMICA DE LA CONSULTA ---
$sql_joins = ""; 
$sql_where = "";
$params = [];
$types = "";

// 1. Lógica Base por Rol
if ($user_rol === 'Director') {
    $sql_joins = " JOIN Usuario u ON op.Solicitante_Id = u.Id";
    $sql_where = " WHERE ((u.Departamento_Id = ? AND op.Estado = 'Pend. Firma Director') OR (op.Solicitante_Id = ?))";
    $params = [$user_depto_id, $user_id];
    $types = "ii";
} else if ($user_rol === 'Alcalde') {
    $sql_where = " WHERE (op.Estado = 'Pend. Firma Alcalde' OR op.Solicitante_Id = ?)";
    $params = [$user_id];
    $types = "i";
} else if ($user_rol === 'EncargadoAdquision') {
    $sql_where = " WHERE (op.Solicitante_Id = ? OR op.Estado IN ('Aprobado', 'En Espera', 'Sin respuesta del vendedor'))";
    $params = [$user_id];
    $types = "i";
} else {
    $sql_where = " WHERE (op.Solicitante_Id = ?)";
    $params = [$user_id];
    $types = "i";
}

// 2. Aplicar Filtros Adicionales

// A. Búsqueda Texto (ID o Nombre)
if (!empty($busqueda)) {
    $sql_where .= " AND (op.Id LIKE ? OR op.Nombre_Orden LIKE ?)";
    $termino = "%" . $busqueda . "%";
    array_push($params, $termino, $termino);
    $types .= "ss";
}

// B. Filtro Estado
if (!empty($filtro_estado)) {
    $sql_where .= " AND op.Estado = ?";
    array_push($params, $filtro_estado);
    $types .= "s";
}

// C. Filtro Tipo Compra
if (!empty($filtro_tipo)) {
    $sql_where .= " AND op.Tipo_Compra = ?";
    array_push($params, $filtro_tipo);
    $types .= "s";
}

// D. Filtro Fecha Inicio
if (!empty($fecha_inicio)) {
    $sql_where .= " AND DATE(op.Fecha_Creacion) >= ?";
    array_push($params, $fecha_inicio);
    $types .= "s";
}

// E. Filtro Fecha Fin
if (!empty($fecha_fin)) {
    $sql_where .= " AND DATE(op.Fecha_Creacion) <= ?";
    array_push($params, $fecha_fin);
    $types .= "s";
}

// 3. CONSULTA 1: Contar Total (Para Paginación)
$sql_count = "SELECT COUNT(*) as total FROM Orden_Pedido op" . $sql_joins . $sql_where;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_paginas = ceil($total_registros / $registros_por_pagina);

// 4. CONSULTA 2: Obtener Datos
$sql_data = "SELECT op.Id, op.Nombre_Orden, op.Fecha_Creacion, op.Valor_total, op.Estado 
             FROM Orden_Pedido op" . $sql_joins . $sql_where . " 
             ORDER BY op.Id DESC LIMIT ? OFFSET ?";

array_push($params, $registros_por_pagina, $offset);
$types .= "ii";

$stmt = $conn->prepare($sql_data);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();

// Función auxiliar para mantener los filtros en la paginación
function getPaginationUrl($page) {
    $queryParams = $_GET;
    $queryParams['pagina'] = $page;
    return '?' . http_build_query($queryParams);
}

function getStatusClass($estado) {
    switch (strtolower($estado)) {
        case 'aprobado': return 'status-aprobado';
        case 'pendiente mi firma': return 'status-borrador';
        case 'pend. mi firma': return 'status-borrador';
        case 'pend. firma director': return 'status-pendiente-firma';
        case 'pend. firma alcalde': return 'status-pendiente';
        case 'rechazada': return 'status-rechazado';
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
                    <a href="crear-orden.php" class="btn btn-primary">Crear Nueva Orden</a>
                </div>

                <div class="search-container">
                    <form action="index.php" method="GET" class="filter-form">
                        
                        <div class="filter-row">
                            <div class="filter-group grow-2">
                                <label>Búsqueda General:</label>
                                <input type="text" name="busqueda" placeholder="ID o Nombre..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            <div class="filter-group">
                                <label>Desde:</label>
                                <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                            </div>
                            <div class="filter-group">
                                <label>Hasta:</label>
                                <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                            </div>
                        </div>

                        <div class="filter-row">
                            <div class="filter-group grow-1">
                                <label>Tipo de Compra:</label>
                                <select name="filtro_tipo">
                                    <option value="">Todos</option>
                                    <option value="Convenio Marco" <?php if($filtro_tipo=='Convenio Marco') echo 'selected'; ?>>Convenio Marco</option>
                                    <option value="Compra Ágil" <?php if($filtro_tipo=='Compra Ágil') echo 'selected'; ?>>Compra Ágil</option>
                                    <option value="Trato Directo" <?php if($filtro_tipo=='Trato Directo') echo 'selected'; ?>>Trato Directo</option>
                                    <option value="Licitación Pública" <?php if($filtro_tipo=='Licitación Pública') echo 'selected'; ?>>Licitación Pública</option>
                                    <option value="Suministro" <?php if($filtro_tipo=='Suministro') echo 'selected'; ?>>Suministro</option>
                                </select>
                            </div>
                            <div class="filter-group grow-1">
                                <label>Estado:</label>
                                <select name="filtro_estado">
                                    <option value="">Todos</option>
                                    <option value="Pend. Mi Firma" <?php if($filtro_estado=='Pend. Mi Firma') echo 'selected'; ?>>Pend. Mi Firma</option>
                                    <option value="Pend. Firma Director" <?php if($filtro_estado=='Pend. Firma Director') echo 'selected'; ?>>Pend. Firma Director</option>
                                    <option value="Pend. Firma Alcalde" <?php if($filtro_estado=='Pend. Firma Alcalde') echo 'selected'; ?>>Pend. Firma Alcalde</option>
                                    <option value="Aprobado" <?php if($filtro_estado=='Aprobado') echo 'selected'; ?>>Aprobado</option>
                                    <option value="Rechazada" <?php if($filtro_estado=='Rechazada') echo 'selected'; ?>>Rechazada</option>
                                    <option value="En Espera" <?php if($filtro_estado=='En Espera') echo 'selected'; ?>>En Espera</option>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-secondary">Filtrar</button>
                                <a href="index.php" class="btn btn-danger" style="text-decoration:none;">Limpiar</a>
                            </div>
                        </div>

                    </form>
                </div>
                
                <table id="ordenes-table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre de la Compra</th>
                            <th>Fecha Creación</th>
                            <th>Total ($)</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultado->num_rows > 0) {
                            while($fila = $resultado->fetch_assoc()) {
                                $statusClass = getStatusClass($fila["Estado"]);
                                $totalFormateado = number_format($fila["Valor_total"], 0, ',', '.');
                                
                                echo "<tr class='clickable-row' onclick=\"window.location.href='ver_orden.php?id=" . $fila["Id"] . "'\">";
                                echo "<td>" . htmlspecialchars($fila["Id"]) . "</td>";
                                echo "<td>" . htmlspecialchars($fila["Nombre_Orden"]) . "</td>";
                                echo "<td>" . date("d/m/Y", strtotime($fila["Fecha_Creacion"])) . "</td>";
                                echo "<td>$ " . htmlspecialchars($totalFormateado) . "</td>";
                                echo "<td><span class='status " . $statusClass . "'>" . htmlspecialchars($fila["Estado"]) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding: 20px;'>No se encontraron órdenes con esos criterios.</td></tr>";
                        }
                        
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="<?php echo getPaginationUrl($pagina_actual - 1); ?>">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="<?php echo getPaginationUrl($i); ?>" 
                           class="<?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="<?php echo getPaginationUrl($pagina_actual + 1); ?>">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 10px; color: #666;">
                    Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> (Total: <?php echo $total_registros; ?> registros)
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>