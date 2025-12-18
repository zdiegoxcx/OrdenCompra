<?php
// index.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

// 1. CONFIGURACIÓN DE PAGINACIÓN (Única declaración)
$registros_por_pagina = 5; 
$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 2. Lógica Automática: Actualizar estados expirados
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
$user_nombre = $_SESSION['user_name'] ?? $_SESSION['user_nombre'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'];
$user_depto = $_SESSION['user_depto'];

// 3. Captura de Filtros
$busqueda = trim($_GET['busqueda'] ?? '');
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tipo = $_GET['filtro_tipo'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// 4. Construcción de Consulta SQL
$rol_limpio = strtoupper(trim($user_rol));
$sql_where = " WHERE 1=1";
$params = [];
$types = "";

if ($rol_limpio === 'DIRECTOR') {
    $sql_where .= " AND (op.Estado = 'Pend. Firma Director' OR op.Solicitante_Id = ?)";
} else if ($rol_limpio === 'ALCALDE') {
    $sql_where .= " AND (op.Estado = 'Pend. Firma Alcalde' OR op.Solicitante_Id = ?)";
} else if ($rol_limpio === 'ADQUISICIONES') {
    $sql_where .= " AND (op.Estado IN ('Aprobado', 'En Espera', 'Sin respuesta del vendedor') OR op.Solicitante_Id = ?)";
} else {
    $sql_where .= " AND op.Solicitante_Id = ?";
}
$params[] = $user_id; $types .= "i";

if (!empty($busqueda)) {
    $sql_where .= " AND (op.Id LIKE ? OR op.Nombre_Orden LIKE ?)";
    $term = "%$busqueda%"; array_push($params, $term, $term); $types .= "ss";
}
if (!empty($filtro_estado)) { $sql_where .= " AND op.Estado = ?"; $params[] = $filtro_estado; $types .= "s"; }
if (!empty($filtro_tipo)) { $sql_where .= " AND op.Tipo_Compra = ?"; $params[] = $filtro_tipo; $types .= "s"; }
if (!empty($fecha_inicio)) { $sql_where .= " AND DATE(op.Fecha_Creacion) >= ?"; $params[] = $fecha_inicio; $types .= "s"; }
if (!empty($fecha_fin)) { $sql_where .= " AND DATE(op.Fecha_Creacion) <= ?"; $params[] = $fecha_fin; $types .= "s"; }

// 5. Ejecución de consultas (Conteo Total)
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM Orden_Pedido op $sql_where");
if (!empty($params)) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 6. Obtención de datos paginados
$sql_data = "SELECT op.Id, op.Nombre_Orden, op.Fecha_Creacion, op.Valor_total, op.Estado 
             FROM Orden_Pedido op $sql_where 
             ORDER BY op.Id DESC LIMIT ? OFFSET ?";
$params_data = array_merge($params, [$registros_por_pagina, $offset]);
$stmt = $conn->prepare($sql_data);
$stmt->bind_param($types . "ii", ...$params_data);
$stmt->execute();
$resultado = $stmt->get_result();

function getStatusClass($estado) {
    switch (strtolower($estado)) {
        case 'aprobado': return 'status-aprobado';
        case 'pend. firma director': return 'status-pendiente-firma';
        case 'pend. firma alcalde': return 'status-pendiente';
        case 'rechazada': return 'status-rechazado';
        case 'sin respuesta del vendedor': return 'status-vencido';
        case 'en espera': return 'status-espera';
        default: return 'status-borrador';
    }
}

function getPaginationUrl($page) {
    $queryParams = $_GET;
    $queryParams['pagina'] = $page;
    return '?' . http_build_query($queryParams);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plataforma de Adquisiciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="app-container">
    <header class="main-header">
        <div class="header-left">
            <h1>Gestión de Adquisiciones</h1>
            <a href="crear_orden.php" class="btn-new"><i class="fas fa-plus"></i> Crear Nueva Orden</a>
        </div>
        <div class="user-info">
            <div class="user-text">
                <div class="name"><?= htmlspecialchars($user_nombre) ?></div>
                <div class="role"><?= htmlspecialchars($user_rol) ?> | <?= htmlspecialchars($user_depto) ?></div>
            </div>
            <a href="controllers/auth_logout.php" class="logout-icon"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <div class="content-card search-section">
        <form action="index.php" method="GET" class="filter-grid">
            <div class="filter-group-wide">
                <label>Búsqueda por ID o Nombre</label>
                <input type="text" name="busqueda" placeholder="Ej: 1024 o Compra de Resmas..." value="<?= htmlspecialchars($busqueda) ?>">
            </div>

            <div class="filter-group">
                <label>Tipo de Compra</label>
                <select name="filtro_tipo">
                    <option value="">Todos</option>
                    <option value="Convenio Marco" <?= $filtro_tipo=='Convenio Marco'?'selected':'' ?>>Convenio Marco</option>
                    <option value="Compra Ágil" <?= $filtro_tipo=='Compra Ágil'?'selected':'' ?>>Compra Ágil</option>
                    <option value="Trato Directo" <?= $filtro_tipo=='Trato Directo'?'selected':'' ?>>Trato Directo</option>
                    <option value="Licitación Pública" <?= $filtro_tipo=='Licitación Pública'?'selected':'' ?>>Licitación Pública</option>
                    <option value="Licitación Privada" <?= $filtro_tipo=='Licitación Privada'?'selected':'' ?>>Licitación Privada</option>
                    <option value="Suministro" <?= $filtro_tipo=='Suministro'?'selected':'' ?>>Suministro</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Estado</label>
                <select name="filtro_estado">
                    <option value="">Todos</option>
                    <option value="Pend. Mi Firma" <?= $filtro_estado=='Pend. Mi Firma'?'selected':'' ?>>Pend. Mi Firma</option>
                    <option value="Pend. Firma Director" <?= $filtro_estado=='Pend. Firma Director'?'selected':'' ?>>Pend. Firma Director</option>
                    <option value="Pend. Firma Alcalde" <?= $filtro_estado=='Pend. Firma Alcalde'?'selected':'' ?>>Pend. Firma Alcalde</option>
                    <option value="Aprobado" <?= $filtro_estado=='Aprobado'?'selected':'' ?>>Aprobado</option>
                    <option value="Rechazada" <?= $filtro_estado=='Rechazada'?'selected':'' ?>>Rechazada</option>
                    <option value="En Espera" <?= $filtro_estado=='En Espera'?'selected':'' ?>>En Espera</option>
                    <option value="Sin respuesta del vendedor" <?= $filtro_estado=='Sin respuesta del vendedor'?'selected':'' ?>>Sin respuesta del vendedor</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Fecha Desde</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>

            <div class="filter-group">
                <label>Fecha Hasta</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter">Aplicar Filtros</button>
                <a href="index.php" class="btn-clear">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="content-card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="col-id">N°</th>
                        <th class="col-name">Nombre de la Compra</th>
                        <th class="col-date">Fecha Creación</th>
                        <th class="col-total">Total ($)</th>
                        <th class="col-status">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($resultado->num_rows > 0): ?>
                        <?php while($fila = $resultado->fetch_assoc()): ?>
                        <tr class="clickable-row" onclick="location.href='ver_orden.php?id=<?= $fila['Id'] ?>'">
                            <td class="col-id">#<?= $fila['Id'] ?></td>
                            <td class="col-name"><?= htmlspecialchars($fila['Nombre_Orden']) ?></td>
                            <td class="col-date"><?= date("d/m/Y", strtotime($fila['Fecha_Creacion'])) ?></td>
                            <td class="col-total">$ <?= number_format($fila['Valor_total'], 0, ',', '.') ?></td>
                            <td class="col-status">
                                <span class="badge <?= getStatusClass($fila['Estado']) ?>">
                                    <?= htmlspecialchars($fila['Estado']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 40px;">No se encontraron órdenes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Página <?= $pagina_actual ?> de <?= $total_paginas ?> (<?= $total_registros ?> totales)
            </div>
            <div class="pagination-controls">
                <?php if ($pagina_actual > 1): ?>
                    <a href="<?= getPaginationUrl(1) ?>" class="page-link" title="Primera"><i class="fas fa-angle-double-left"></i></a>
                    <a href="<?= getPaginationUrl($pagina_actual - 1) ?>" class="page-link">Anterior</a>
                <?php endif; ?>

                <?php 
                $rango = 2; 
                for ($i = 1; $i <= $total_paginas; $i++):
                    if ($i == 1 || $i == $total_paginas || ($i >= $pagina_actual - $rango && $i <= $pagina_actual + $rango)):
                        $activeClass = ($i == $pagina_actual) ? 'active' : '';
                ?>
                    <a href="<?= getPaginationUrl($i) ?>" class="page-link <?= $activeClass ?>"><?= $i ?></a>
                <?php 
                    elseif ($i == $pagina_actual - $rango - 1 || $i == $pagina_actual + $rango + 1):
                        echo '<span class="page-dots">...</span>';
                    endif;
                endfor; 
                ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="<?= getPaginationUrl($pagina_actual + 1) ?>" class="page-link">Siguiente</a>
                    <a href="<?= getPaginationUrl($total_paginas) ?>" class="page-link" title="Última"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>