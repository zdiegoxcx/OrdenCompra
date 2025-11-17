<?php
// 1. Iniciar la sesión (SIEMPRE al principio)
session_start();

// 2. ¡Guardia de Seguridad!
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 3. Incluir conexión
include 'conectar.php';

// 4. Obtener y validar el ID de la URL
$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orden_id === 0) {
    die("Error: No se proporcionó un ID de orden válido.");
}

// 5. Consulta principal (Datos de la Orden + Datos del Solicitante)
$sql_orden = "
    SELECT 
        op.*, 
        u.Nombre AS Nombre_Solicitante, 
        u.Email AS Email_Solicitante,
        u.Telefono AS Fono_Solicitante,
        u.Departamento_Id AS Solicitante_Depto_Id,
        d.Nombre AS Nombre_Departamento
        /* , op.Id_Licitacion -- (Estos campos aún no se guardan)
        , op.Archivo_Cotizacion 
        , op.Archivo_Memorando 
        , op.Archivo_Decreto 
        */
    FROM Orden_Pedido op
    LEFT JOIN Usuario u ON op.Solicitante_Id = u.Id
    LEFT JOIN Departamento d ON u.Departamento_Id = d.Id
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

// 6. Consulta de los Ítems
$sql_items = "SELECT * FROM Orden_Item WHERE Orden_Id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $orden_id);
$stmt_items->execute();
$resultado_items = $stmt_items->get_result();

// 7. --- LÓGICA DE VISUALIZACIÓN DE FIRMA ---
$user_id_actual = $_SESSION['user_id'];
$user_rol_actual = $_SESSION['user_rol'];
$user_depto_id_actual = $_SESSION['user_depto_id'];
$orden_estado = $orden['Estado'];
$orden_solicitante_id = $orden['Solicitante_Id'];
$orden_solicitante_depto_id = $orden['Solicitante_Depto_Id'];
$mostrar_firma_box = false;
if ($orden_estado === 'Pend. Mi Firma' && $user_id_actual === $orden_solicitante_id) {
    $mostrar_firma_box = true;
}
elseif ($orden_estado === 'Pend. Firma Director' && $user_rol_actual === 'Director' && $user_depto_id_actual === $orden_solicitante_depto_id) {
    $mostrar_firma_box = true;
}
elseif ($orden_estado === 'Pend. Firma Alcalde' && $user_rol_actual === 'Alcalde') {
    $mostrar_firma_box = true;
}

// 8. --- LÓGICA DE VISUALIZACIÓN DE TIPO DE COMPRA ---
$tipo_compra = $orden['Tipo_Compra'];
$isModoPresupuesto = false;
$isTratoDirecto = false;
$isLicitacion = false;

switch ($tipo_compra) {
    case 'Compra Ágil':
    case 'Licitación Pública':
    case 'Licitación Privada':
        $isModoPresupuesto = true;
        break;
    case 'Trato Directo':
        $isTratoDirecto = true;
        break;
}
if ($tipo_compra === 'Licitación Pública' || $tipo_compra === 'Suministro') {
    $isLicitacion = true;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Orden N° <?php echo $orden['Id']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
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
                Usuario: <strong><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong> (<?php echo htmlspecialchars($_SESSION['user_rol']); ?>)
                &nbsp; | &nbsp;
                <a href="logout.php" style="color: white; text-decoration: underline;">Cerrar Sesión</a>
            </span>
        </header>

        <main class="app-content">
            <div id="form-view">
                <h2>Detalle Orden de Pedido N°: <?php echo $orden['Id']; ?></h2>

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
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Email_Solicitante']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Fono_Solicitante']); ?>" disabled>
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

                
                <?php if ($isLicitacion): ?>
                <fieldset id="fieldset-licitacion-publica">
                    <legend>3.6. ID de Licitación</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>ID Licitacion Publica</label>
                            <input type="text" value="<?php echo htmlspecialchars(isset($orden['Id_Licitacion']) ? $orden['Id_Licitacion'] : 'N/A'); ?>" disabled>
                        </div>
                    </div>
                </fieldset>
                <?php endif; ?>


                <?php if ($isTratoDirecto): ?>
                <fieldset id="fieldset-trato-directo">
                    <legend>3.5. Documentos Requeridos (Trato Directo)</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>1° Cotización</label>
                            <a href="/uploads/<?php echo htmlspecialchars(isset($orden['Archivo_Cotizacion']) ? $orden['Archivo_Cotizacion'] : ''); ?>" target="_blank">Ver Archivo</a>
                        </div>
                        <div class="form-group">
                            <label>2° Memorando</label>
                            <a href="/uploads/<?php echo htmlspecialchars(isset($orden['Archivo_Memorando']) ? $orden['Archivo_Memorando'] : ''); ?>" target="_blank">Ver Archivo</a>
                        </div>
                        <div class="form-group">
                            <label>3° Decreto Autoriza Trato Directo</label>
                            <a href="/uploads/<?php echo htmlspecialchars(isset($orden['Archivo_Decreto']) ? $orden['Archivo_Decreto'] : ''); ?>" target="_blank">Ver Archivo</a>
                        </div>
                    </div>
                </fieldset>
                <?php endif; ?>


                <fieldset>
                    <legend>4. Detalle de Productos/Servicios</legend>
                    <table id="items-table-view"> 
                        <thead>
                            <tr>
                                <th style="width: 10%;">Cantidad</th>
                                <th>Producto o Servicio</th>
                                
                                <th class="col-v-unitario" style="width: 20%; <?php if ($isModoPresupuesto) echo 'display: none;'; ?>">V. Unitario ($)</th>
                                <th class="col-total-linea" style="width: 20%; <?php if ($isModoPresupuesto) echo 'display: none;'; ?>">Total Línea ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($resultado_items->num_rows > 0) {
                                while($item = $resultado_items->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($item['Cantidad']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['Nombre_producto_servicio']) . "</td>";
                                    
                                    // Ocultar TD si es modo presupuesto
                                    echo "<td class" . ($isModoPresupuesto ? 'display: none;' : '') . "'>" . number_format($item['Valor_Unitario'], 0, ',', '.') . "</td>";
                                    echo "<td class" . ($isModoPresupuesto ? 'display: none;' : '') . "'>" . number_format($item['Valor_Total'], 0, ',', '.') . "</td>";
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No se encontraron ítems para esta orden.</td></tr>";
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
                            <input type="text" value="N/A" disabled>

                            <label>Total (Presupuesto):</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled style="font-weight: bold; font-size: 1.1em;">

                        <?php else: ?>
                            <label>Valor Neto:</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_neto'], 0, ',', '.'); ?>" disabled>
                            
                            <label>IVA (19%):</label>
                            <input type="text" value="$ <?php echo number_format($orden['Iva'], 0, ',', '.'); ?>" disabled>

                            <label>Valor Total:</label>
                            <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled style="font-weight: bold; font-size: 1.1em;">
                        <?php endif; ?>

                    </div>
                </fieldset>
                
                
                <?php if ($mostrar_firma_box): ?>
                <div id="firma-view">
                    <fieldset id="fieldset-firma-accion" data-orden-id="<?php echo $orden['Id']; ?>">
                        <legend>6. Proceso de Firma Digital</legend>
                        <div class="form-group full-width">
                            <label for="token-input">Ingrese su Token de Firma (6 dígitos)</label>
                            <input type="password" id="token-input" placeholder="********" maxlength="6">
                        </div>
                        
                        <div class="form-actions">
                            <button id="btn-rechazar" class="btn btn-danger">Rechazar Orden</button>
                            <button id="btn-firmar" class="btn btn-success">Firmar y Aprobar</button>
                        </div>
                    </fieldset>
    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Volver</a>
                    </div>
                </div>
                <?php else: ?>
                    <fieldset>
                        <legend>6. Estado de la Orden</legend>
                        <div class="form-group full-width">
                            <label>Estado Actual</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Estado']); ?>" disabled>
                        </div>
                        <?php if ($orden['Estado'] === 'Rechazada' && !empty($orden['Motivo_Rechazo'])): ?>
                            <div class="form-group full-width">
                                <label>Motivo del Rechazo</label>
                                <textarea disabled><?php echo htmlspecialchars($orden['Motivo_Rechazo']); ?></textarea>
                            </div>
                        <?php endif; ?>
                    </fieldset>
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Volver</a>
                    </div>
                <?php endif; ?>
    
            </div>
        </main>
    </div>
    
    <script src="js/firma-logic.js"></script>

    <?php
    // 8. Cerramos todas las conexiones
    $stmt_orden->close();
    $stmt_items->close(); 
    $conn->close();
    ?>
</body>
</html>