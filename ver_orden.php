<?php
// ver_orden.php (En la raíz del proyecto)

// 1. Iniciar la sesión
session_start();

// 2. Seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 3. Conexión a BD (Desde raíz a config)
include 'config/db.php';

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
        d.Nombre AS Nombre_Departamento,
        lic.Nombre_Orden AS Nombre_Licitacion_Origen
    FROM Orden_Pedido op
    LEFT JOIN Usuario u ON op.Solicitante_Id = u.Id
    LEFT JOIN Departamento d ON u.Departamento_Id = d.Id
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

// 6. Consulta de los Ítems
$sql_items = "SELECT * FROM Orden_Item WHERE Orden_Id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $orden_id);
$stmt_items->execute();
$resultado_items = $stmt_items->get_result();

// 7. Consulta Archivos Adjuntos
$sql_archivos = "SELECT * FROM Orden_Archivos WHERE Orden_Id = ?";
$stmt_files = $conn->prepare($sql_archivos);
$stmt_files->bind_param("i", $orden_id);
$stmt_files->execute();
$res_files = $stmt_files->get_result();

$archivos = [];
while($f = $res_files->fetch_assoc()) {
    $archivos[] = $f;
}

// 8. LÓGICA DE VISUALIZACIÓN DE FIRMA
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

// 9. LÓGICA DE VISUALIZACIÓN DE TIPO DE COMPRA
$tipo_compra = $orden['Tipo_Compra'];
$isModoPresupuesto = false;
$isLicitacion = false;
$isConvenioMarco = false;

switch ($tipo_compra) {
    case 'Compra Ágil':
    case 'Licitación Pública':
    case 'Licitación Privada':
        $isModoPresupuesto = true;
        break;
    case 'Convenio Marco':
        $isConvenioMarco = true;
        break;
}

if ($tipo_compra === 'Licitación Pública' || $tipo_compra === 'Suministro') {
    $isLicitacion = true;
}

$mostrar_gestion_box = false;
if ($user_rol_actual === 'EncargadoAdquision' && $orden['Estado'] === 'Aprobado') {
    $mostrar_gestion_box = true;
}
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
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        .file-link:hover {
            background-color: #e2e6ea;
            text-decoration: underline;
        }
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
                Usuario: <strong><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong> (<?php echo htmlspecialchars($_SESSION['user_rol']); ?>)
                &nbsp; | &nbsp;
                <a href="controllers/auth_logout.php" style="color: white; text-decoration: underline;">Cerrar Sesión</a>
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

                <fieldset id="fieldset-licitacion-publica">
                    <legend>3.6. ID de Licitación</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Licitación Asociada</label>
                            <?php 
                                $valor_mostrar = 'N/A';
                                if (!empty($orden['Id_Licitacion'])) {
                                    $id_lic = $orden['Id_Licitacion'];
                                    $nom_lic = !empty($orden['Nombre_Licitacion_Origen']) ? $orden['Nombre_Licitacion_Origen'] : '(Nombre no encontrado)';
                                    $valor_mostrar = "id: $id_lic - $nom_lic";
                                }
                            ?>
                            <input type="text" value="<?php echo htmlspecialchars($valor_mostrar); ?>" disabled>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>3.5 Documentos Adjuntos</legend>
                    <div class="form-group full-width">
                    <?php 
                    if (count($archivos) > 0) {
                        foreach($archivos as $arch) {
                            $nombre_mostrar = htmlspecialchars($arch['Nombre_Original']);
                            $tipo_doc = htmlspecialchars($arch['Tipo_Documento']);
                            $ruta = htmlspecialchars($arch['Ruta_Archivo']);
                            
                            // Nota: La ruta guardada en BD es relativa ("uploads/archivo.pdf"), funciona bien desde la raíz.
                            echo "<div style='margin-bottom: 10px;'>";
                            echo "<strong>$tipo_doc:</strong> ";
                            echo "<a href='$ruta' target='_blank' class='file-link'>📄 $nombre_mostrar</a>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p style='color: #666;'>No hay documentos adjuntos para esta orden.</p>";
                    }
                    ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>4. Detalle de Productos/Servicios</legend>
                    <table id="items-table-view"> 
                        <thead>
                            <tr>
                                <th style="width: 10%;">Cantidad</th>
                                <?php if ($isConvenioMarco): ?>
                                    <th style="background-color: #e3f2fd;">ID Producto</th>
                                <?php endif; ?>
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
                                    echo "<td>" . htmlspecialchars($item['Cantidad']) . "</td>";
                                    
                                    if ($isConvenioMarco) {
                                        $codigo = !empty($item['Codigo_Producto']) ? $item['Codigo_Producto'] : '-';
                                        echo "<td style='background-color: #f1f8ff;'>" . htmlspecialchars($codigo) . "</td>";
                                    }

                                    echo "<td>" . htmlspecialchars($item['Nombre_producto_servicio']) . "</td>";
                                    
                                    if (!$isModoPresupuesto) {
                                        echo "<td>" . number_format($item['Valor_Unitario'], 0, ',', '.') . "</td>";
                                        echo "<td>" . number_format($item['Valor_Total'], 0, ',', '.') . "</td>";
                                    }
                                    
                                    echo "</tr>";
                                }
                            } else {
                                $colspan = 4;
                                if ($isConvenioMarco) $colspan++;
                                if ($isModoPresupuesto) $colspan -= 2;
                                echo "<tr><td colspan='$colspan'>No se encontraron ítems para esta orden.</td></tr>";
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
                
                <?php if ($mostrar_gestion_box): ?>
                <div id="gestion-view" style="margin-top: 20px; border-top: 2px solid #004a99; padding-top: 20px;">
                    <form action="controllers/orden_gestion.php" method="POST">
                        <fieldset>
                            <legend>Gestionar Compra (Encargado)</legend>
                            <input type="hidden" name="orden_id" value="<?php echo $orden['Id']; ?>">
                            
                            <p>Al iniciar la gestión, la orden pasará a estado <strong>"En Espera"</strong>. Si no se recibe respuesta en 24 horas, el sistema la marcará como expirada.</p>

                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Proveedor Contactado <span style="color: red;">*</span></label>
                                    <input type="text" name="proveedor_nombre" required placeholder="Nombre del proveedor o empresa...">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">💾 Guardar e Iniciar Espera</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
                <?php endif; ?>

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
                        <a href="generar_pdf.php?id=<?php echo $orden['Id']; ?>" target="_blank" class="btn btn-primary" style="background-color: #dc3545; border-color: #dc3545;">
                            Descargar PDF
                        </a>
                        <a href="index.php" class="btn btn-secondary">Volver</a>
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