<?php
// ver_orden.php

// 1. Iniciar la sesión
session_start();

// 2. Seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 3. Conexión a BD
include 'config/db.php';

// 4. Obtener y validar el ID
$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orden_id === 0) {
    die("Error: No se proporcionó un ID de orden válido.");
}

// 5. Consulta principal (ADAPTADA A FUNCIONARIOS_MUNI)
$sql_orden = "
    SELECT 
        op.*, 
        CONCAT(f.NOMBRE, ' ', f.APELLIDO) AS Nombre_Solicitante,
        f.CORREO AS Email_Solicitante,
        f.FONO AS Fono_Solicitante,
        f.DEPTO AS Nombre_Departamento,
        lic.Nombre_Orden AS Nombre_Licitacion_Origen
    FROM Orden_Pedido op
    LEFT JOIN FUNCIONARIOS_MUNI f ON op.Solicitante_Id = f.ID
    LEFT JOIN Orden_Pedido lic ON op.Id_Licitacion = lic.Id
    WHERE op.Id = ?
";

$stmt_orden = $conn->prepare($sql_orden);
$stmt_orden->bind_param("i", $orden_id);
$stmt_orden->execute();
$resultado_orden = $stmt_orden->get_result();

if ($resultado_orden->num_rows === 0) {
    die("Error: No se encontró la orden con ID " . $orden_id);
}
$orden = $resultado_orden->fetch_assoc();

// 6. Consulta de Ítems
$sql_items = "SELECT * FROM Orden_Item WHERE Orden_Id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $orden_id);
$stmt_items->execute();
$resultado_items = $stmt_items->get_result();

// 7. Consulta Archivos
$sql_archivos = "SELECT * FROM Orden_Archivos WHERE Orden_Id = ?";
$stmt_files = $conn->prepare($sql_archivos);
$stmt_files->bind_param("i", $orden_id);
$stmt_files->execute();
$res_files = $stmt_files->get_result();
$archivos = [];
while($f = $res_files->fetch_assoc()) { $archivos[] = $f; }

// 8. LÓGICA DE VISUALIZACIÓN DE FIRMA (CORREGIDA PARA DIRECTOR GLOBAL)
$user_id_actual = $_SESSION['user_id'];
$user_rol_actual = strtoupper($_SESSION['user_rol']); // Normalizamos a Mayúsculas por seguridad
$user_depto_actual = $_SESSION['user_depto']; 

$orden_estado = $orden['Estado'];
$orden_solicitante_id = $orden['Solicitante_Id'];

$mostrar_firma_box = false;

// Caso A: Funcionario firma su propia orden
if ($orden_estado === 'Pend. Mi Firma' && $user_id_actual == $orden_solicitante_id) {
    $mostrar_firma_box = true;
}
// Caso B: Director firma CUALQUIER orden pendiente de director (GLOBAL)
// CORRECCIÓN: Quitamos la validación de departamento
elseif ($orden_estado === 'Pend. Firma Director' && $user_rol_actual === 'DIRECTOR') {
    $mostrar_firma_box = true;
}
// Caso C: Alcalde firma final
elseif ($orden_estado === 'Pend. Firma Alcalde' && $user_rol_actual === 'ALCALDE') {
    $mostrar_firma_box = true;
}

// 9. LÓGICA TIPO COMPRA
$tipo_compra = $orden['Tipo_Compra'];
$isModoPresupuesto = false;
$isConvenioMarco = false;

if (in_array($tipo_compra, ['Compra Ágil', 'Licitación Pública', 'Licitación Privada'])) {
    $isModoPresupuesto = true;
} elseif ($tipo_compra === 'Convenio Marco') {
    $isConvenioMarco = true;
}

// Lógica de gestión
$mostrar_gestion_box = ($user_rol_actual === 'ADQUISICIONES' && $orden['Estado'] === 'Aprobado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Orden N° <?php echo $orden['Id']; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .file-link {
            display: inline-block; margin-right: 10px; margin-bottom: 5px;
            padding: 5px 10px; background-color: #f8f9fa; border: 1px solid #ddd;
            border-radius: 4px; text-decoration: none; color: #007bff;
        }
        .file-link:hover { background-color: #e2e6ea; text-decoration: underline; }
    </style>
</head>
<body>

    <div id="modal-overlay"></div>
    <div id="modal-rechazo" class="modal">
        <h2>Motivo del Rechazo</h2>
        <p>Por favor, describa brevemente por qué se rechaza esta orden.</p>
        <textarea id="motivo-rechazo-textarea" rows="5"></textarea>
        <div class="modal-actions">
            <button id="btn-cancelar-rechazo" class="btn btn-secondary">Cancelar</button>
            <button id="btn-enviar-rechazo" class="btn btn-danger">Confirmar Rechazo</button>
        </div>
    </div>

    <div class="app-container">
        <header class="app-header">
            <h1>Plataforma de Adquisiciones</h1>
            <span>
                Usuario: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['user_rol']); ?>)
                &nbsp; | &nbsp;
                <a href="controllers/auth_logout.php" style="color: white; text-decoration: underline;">Cerrar Sesión</a>
            </span>
        </header>

        <main class="app-content">
            <div id="form-view">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h2>Detalle Orden de Pedido N°: <?php echo $orden['Id']; ?></h2>
                    <a href="index.php" class="btn btn-secondary">Volver</a>
                </div>

                <fieldset>
                    <legend>1. Datos del Solicitante</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>N°</label>
                            <input type="text" value="<?php echo $orden['Id']; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Fecha Creación</label>
                            <input type="text" value="<?php echo date("d/m/Y H:i", strtotime($orden['Fecha_Creacion'])); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Prof. Responsable</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Nombre_Solicitante']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Depto. Solicitante</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Nombre_Departamento']); ?>" disabled>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>2. Datos Generales de la Orden</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre de la Compra</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Nombre_Orden']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Plazo Máximo de Entrega</label>
                            <input type="date" value="<?php echo htmlspecialchars($orden['Plazo_maximo']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Compra</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Tipo_Compra']); ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Motivo de la Compra</label>
                        <textarea disabled><?php echo htmlspecialchars($orden['Motivo_Compra']); ?></textarea>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>3. Imputación Presupuestaria</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Presupuesto</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Presupuesto']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Cuenta Presupuestaria</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Cuenta_Presupuestaria']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Subprograma</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Subprog']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Centro de Costo</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Centro_Costos']); ?>" disabled>
                        </div>
                    </div>
                </fieldset>

                <?php if (!empty($orden['Id_Licitacion'])): ?>
                <fieldset>
                    <legend>3.6. ID de Licitación</legend>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Licitación Asociada</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Id_Licitacion'] . " - " . $orden['Nombre_Licitacion_Origen']); ?>" disabled>
                        </div>
                    </div>
                </fieldset>
                <?php endif; ?>

                <fieldset>
                    <legend>3.5 Documentos Adjuntos</legend>
                    <div class="form-group full-width">
                    <?php 
                    if (count($archivos) > 0) {
                        foreach($archivos as $arch) {
                            $nombre = htmlspecialchars($arch['Nombre_Original']);
                            $tipo = htmlspecialchars($arch['Tipo_Documento']);
                            $ruta = htmlspecialchars($arch['Ruta_Archivo']);
                            echo "<div style='margin-bottom: 5px;'><strong>$tipo:</strong> <a href='$ruta' target='_blank' class='file-link'>📄 $nombre</a></div>";
                        }
                    } else {
                        echo "<p style='color: #666;'>No hay documentos adjuntos.</p>";
                    }
                    ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>4. Detalle de Productos/Servicios</legend>
                    <table id="items-table-view"> 
                        <thead>
                            <tr>
                                <th style="width: 10%;">Cant.</th>
                                <?php if ($isConvenioMarco): ?><th style="background-color: #e3f2fd;">ID Producto</th><?php endif; ?>
                                <th>Producto o Servicio</th>
                                <?php if (!$isModoPresupuesto): ?>
                                    <th class="col-v-unitario" style="width: 20%;">V. Unitario ($)</th>
                                    <th class="col-total-linea" style="width: 20%;">Total Línea ($)</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($resultado_items->num_rows > 0) {
                                while($item = $resultado_items->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $item['Cantidad'] . "</td>";
                                    if ($isConvenioMarco) echo "<td style='background-color: #f1f8ff;'>" . htmlspecialchars($item['Codigo_Producto']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['Nombre_producto_servicio']) . "</td>";
                                    if (!$isModoPresupuesto) {
                                        echo "<td>$ " . number_format($item['Valor_Unitario'], 0, ',', '.') . "</td>";
                                        echo "<td>$ " . number_format($item['Valor_Total'], 0, ',', '.') . "</td>";
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>Sin ítems registrados.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </fieldset>

                <fieldset>
                    <legend>5. Totales</legend>
                    <div class="totals-grid">
                        <?php if ($isModoPresupuesto): ?>
                            <label>Presupuesto:</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled>
                            <label>IVA:</label>
                            <input type="text" value="Incluido (si aplica)" disabled>
                            <label>Total (Presupuesto):</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled style="font-weight: bold; font-size: 1.1em; color: var(--primary-color);">
                        <?php else: ?>
                            <label>Valor Neto:</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_neto'], 0, ',', '.'); ?>" disabled>
                            <label>IVA:</label>
                            <input type="text" value="$ <?php echo number_format($orden['Iva'], 0, ',', '.'); ?>" disabled>
                            <label>Valor Total:</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled style="font-weight: bold; font-size: 1.1em; color: var(--primary-color);">
                        <?php endif; ?>
                    </div>
                </fieldset>
                
                <?php if ($mostrar_gestion_box): ?>
                <div style="margin-top: 20px; border: 1px solid #ccc; padding: 20px; border-radius: 8px;">
                    <h3>Gestionar Compra</h3>
                    <form action="controllers/orden_gestion.php" method="POST">
                        <input type="hidden" name="orden_id" value="<?php echo $orden['Id']; ?>">
                        <div class="form-group full-width">
                            <label>Proveedor Contactado:</label>
                            <input type="text" name="proveedor_nombre" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Gestión</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($mostrar_firma_box): ?>
                <div id="firma-view" style="background-color: #e3f2fd; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #90caf9;">
                    <fieldset id="fieldset-firma-accion" data-orden-id="<?php echo $orden['Id']; ?>" style="border: none; background: transparent; padding: 0; margin: 0;">
                        <legend style="color: #0d47a1;">6. Proceso de Firma Digital</legend>
                        <p style="margin-bottom: 15px; color: #555;">Para aprobar esta orden, ingrese los <strong>primeros 6 dígitos de su RUT</strong> como token de seguridad.</p>
                        
                        <div class="form-group full-width">
                            <label for="token-input" style="font-weight: bold;">Token de Firma (6 dígitos del RUT):</label>
                            <input type="password" id="token-input" placeholder="Ej: 123456" maxlength="6" style="max-width: 200px; padding: 10px; border: 2px solid #2196f3;">
                        </div>
                        
                        <div class="form-actions" style="justify-content: flex-start; gap: 15px; margin-top: 15px;">
                            <button id="btn-firmar" class="btn btn-success" style="padding: 10px 20px;">✅ Firmar y Aprobar</button>
                            <button id="btn-rechazar" class="btn btn-danger" style="padding: 10px 20px;">❌ Rechazar Orden</button>
                        </div>
                    </fieldset>
                </div>
                <?php else: ?>
                    <fieldset>
                        <legend>6. Estado de la Orden</legend>
                        <div class="form-group full-width">
                            <label>Estado Actual</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Estado']); ?>" disabled style="font-weight: bold;">
                        </div>
                        <?php if ($orden['Estado'] === 'Rechazada'): ?>
                            <div class="form-group full-width">
                                <label style="color: red;">Motivo del Rechazo</label>
                                <textarea disabled style="border-color: red;"><?php echo htmlspecialchars($orden['Motivo_Rechazo']); ?></textarea>
                            </div>
                        <?php endif; ?>
                    </fieldset>
                    
                    <div class="form-actions">
                        <a href="generar_pdf.php?id=<?php echo $orden['Id']; ?>" target="_blank" class="btn btn-primary">Descargar PDF</a>
                    </div>
                <?php endif; ?>
    
            </div>
        </main>
    </div>
    
    <script src="assets/js/firma-logic.js"></script>

    <?php
    $stmt_orden->close();
    $stmt_items->close(); 
    $stmt_files->close();
    $conn->close();
    ?>
</body>
</html>