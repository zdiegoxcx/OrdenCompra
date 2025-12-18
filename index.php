<?php
// ARCHIVO: index.php (Ubicación: Raíz del proyecto)

// 1. Iniciar la sesión
session_start();

// Control de Acceso: Si no hay sesión, al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Conexión a Base de Datos
include 'config/db.php';

// 3. Lógica Automática: Actualizar estados expirados
// Verifica si hay órdenes "En Espera" que ya vencieron
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

// Datos del usuario actual (Adaptados a la nueva estructura de sesión)
$user_id = $_SESSION['user_id'];
// Aseguramos compatibilidad si en login usaste 'user_name' o 'user_nombre'
$user_nombre = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['user_nombre']) ? $_SESSION['user_nombre'] : 'Usuario');
$user_rol = $_SESSION['user_rol'];     // Viene de la columna ADQUISICIONES
$user_depto = $_SESSION['user_depto']; // Ahora es el NOMBRE del departamento (String)

// --- CONFIGURACIÓN DE FILTROS (Recibidos por GET) ---
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


// --- CONSTRUCCIÓN DINÁMICA DE LA CONSULTA SQL ---
$sql_joins = ""; 
$sql_where = "";
$params = [];
$types = "";

// 1. Obtener y limpiar el rol de la sesión
// trim() elimina espacios accidentales y strtoupper() lo hace mayúscula
$rol_limpio = isset($_SESSION['user_rol']) ? strtoupper(trim($_SESSION['user_rol'])) : 'FUNCIONARIO';

// (Opcional) Descomenta la siguiente línea para ver qué rol detecta el sistema si sigue fallando:
// die("Rol detectado: [" . $rol_limpio . "]");

if ($rol_limpio === 'DIRECTOR') {
    // Director: Ve lo pendiente de su firma (Global) O lo suyo propio
    $sql_where = " WHERE (op.Estado = 'Pend. Firma Director' OR op.Solicitante_Id = ?)";
    $params = [$user_id];
    $types = "i";

} else if ($rol_limpio === 'ALCALDE') {
    // Alcalde: Ve lo pendiente de su firma (Global) O lo suyo propio
    $sql_where = " WHERE (op.Estado = 'Pend. Firma Alcalde' OR op.Solicitante_Id = ?)";
    $params = [$user_id];
    $types = "i";

} else if ($rol_limpio === 'ADQUISICIONES') {
    // Adquisiciones: Ve lo aprobado/en espera (Global) O lo suyo propio
    $sql_where = " WHERE (op.Estado IN ('Aprobado', 'En Espera', 'Sin respuesta del vendedor') OR op.Solicitante_Id = ?)";
    $params = [$user_id];
    $types = "i";

} else {
    // Funcionario normal (Rol por defecto): Solo ve sus propias solicitudes
    $sql_where = " WHERE (op.Solicitante_Id = ?)";
    $params = [$user_id];
    $types = "i";
}

// B. Aplicar Filtros de Búsqueda (Se suman con AND a la condición base)

// B1. Búsqueda por Texto (ID o Nombre)
if (!empty($busqueda)) {
    $sql_where .= " AND (op.Id LIKE ? OR op.Nombre_Orden LIKE ?)";
    $termino = "%" . $busqueda . "%";
    array_push($params, $termino, $termino);
    $types .= "ss";
}

// B2. Filtro por Estado Exacto
if (!empty($filtro_estado)) {
    $sql_where .= " AND op.Estado = ?";
    array_push($params, $filtro_estado);
    $types .= "s";
}

// B3. Filtro por Tipo de Compra
if (!empty($filtro_tipo)) {
    $sql_where .= " AND op.Tipo_Compra = ?";
    array_push($params, $filtro_tipo);
    $types .= "s";
}

// B4. Filtro por Rango de Fechas
if (!empty($fecha_inicio)) {
    $sql_where .= " AND DATE(op.Fecha_Creacion) >= ?";
    array_push($params, $fecha_inicio);
    $types .= "s";
}
if (!empty($fecha_fin)) {
    $sql_where .= " AND DATE(op.Fecha_Creacion) <= ?";
    array_push($params, $fecha_fin);
    $types .= "s";
}

// 4. Ejecutar Consultas

// CONSULTA 1: Contar Total (Para calcular número de páginas)
$sql_count = "SELECT COUNT(*) as total FROM Orden_Pedido op" . $sql_joins . $sql_where;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_paginas = ceil($total_registros / $registros_por_pagina);

// CONSULTA 2: Obtener Datos Paginados
$sql_data = "SELECT op.Id, op.Nombre_Orden, op.Fecha_Creacion, op.Valor_total, op.Estado 
             FROM Orden_Pedido op" . $sql_joins . $sql_where . " 
             ORDER BY op.Id DESC LIMIT ? OFFSET ?";

// Añadir parámetros de límite y offset a la lista de parámetros
array_push($params, $registros_por_pagina, $offset);
$types .= "ii";

$stmt = $conn->prepare($sql_data);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();

// --- Helpers para la Vista ---

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
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="app-container">
        
        <header class="app-header">
            <div style="display: flex; gap: 30px; align-items: center;">
                <h1>Gestión Adquisiciones</h1>
                <a href="crear_orden.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Nueva Orden
                </a>
            </div>

            <div style="text-align: right;">
                <span style="display:block; font-weight:bold; color: var(--text-main);">
                    <?php echo htmlspecialchars($user_nombre); ?>
                </span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">
                    <?php echo htmlspecialchars($user_rol); ?> | <?php echo htmlspecialchars($user_depto); ?>
                </span>
                <div style="margin-top: 5px;">
                    <a href="controllers/auth_logout.php" style="font-size: 0.85rem; color: var(--danger-color);">Cerrar Sesión</a>
                </div>
            </div>
        </header>

        <main class="app-content">
            <div id="panel-view">
                
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

                        <div class="filter-row" style="margin-top: 15px;">
                            <div class="filter-group grow-1">
                                <label>Tipo de Compra:</label>
                                <select name="filtro_tipo">
                                    <option value="">Todos</option>
                                    <option value="Convenio Marco" <?php if($filtro_tipo=='Convenio Marco') echo 'selected'; ?>>Convenio Marco</option>
                                    <option value="Compra Ágil" <?php if($filtro_tipo=='Compra Ágil') echo 'selected'; ?>>Compra Ágil</option>
                                    <option value="Trato Directo" <?php if($filtro_tipo=='Trato Directo') echo 'selected'; ?>>Trato Directo</option>
                                    <option value="Licitación Pública" <?php if($filtro_tipo=='Licitación Pública') echo 'selected'; ?>>Licitación Pública</option>
                                    <option value="Licitación Privada" <?php if($filtro_tipo=='Licitación Privada') echo 'selected'; ?>>Licitación Privada</option>
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
                            <div class="filter-actions" style="align-self: flex-end;">
                                <button type="submit" class="btn btn-secondary">Filtrar</button>
                                <a href="index.php" class="btn btn-danger" style="text-decoration:none; padding: 10px 15px;">Limpiar</a>
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
                                echo "<td>#" . htmlspecialchars($fila["Id"]) . "</td>";
                                echo "<td>" . htmlspecialchars($fila["Nombre_Orden"]) . "</td>";
                                echo "<td>" . date("d/m/Y", strtotime($fila["Fecha_Creacion"])) . "</td>";
                                echo "<td>$ " . htmlspecialchars($totalFormateado) . "</td>";
                                echo "<td><span class='status " . $statusClass . "'>" . htmlspecialchars($fila["Estado"]) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color: var(--text-muted);'>No se encontraron órdenes con esos criterios.</td></tr>";
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

                    <?php 
                    $rango = 2; 
                    for ($i = 1; $i <= $total_paginas; $i++) {
                        if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)) {
                            $clase_activa = ($i == $pagina_actual) ? 'active' : '';
                            echo '<a href="' . getPaginationUrl($i) . '" class="' . $clase_activa . '">' . $i . '</a>';
                        }
                        elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1) {
                            echo '<span style="padding: 8px 12px; color: #999;">...</span>';
                        }
                    }
                    ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="<?php echo getPaginationUrl($pagina_actual + 1); ?>">Siguiente &raquo;</a>
                    <?php endif; ?>
                    
                </div>
                
                <div style="text-align: center; margin-top: 15px; color: var(--text-muted); font-size: 0.9rem;">
                    Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> (Total: <?php echo $total_registros; ?> registros)
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>