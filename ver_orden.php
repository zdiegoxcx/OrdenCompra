<?php


// 1. Iniciar la sesión (SIEMPRE al principio)
session_start();

// 2. ¡Guardia de Seguridad!
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;

}
// 1. Incluir conexión
include 'conectar.php';

// 2. Obtener y validar el ID de la URL
// Usamos intval() por seguridad, para prevenir Inyección SQL
$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orden_id === 0) {
    die("Error: No se proporcionó un ID de orden válido.");
}

// 3. Consulta principal (Datos de la Orden + Datos del Solicitante)
// Usamos JOIN para traer el nombre del solicitante y su departamento
$sql_orden = "
    SELECT 
        op.*, 
        u.Nombre AS Nombre_Solicitante, 
        d.Nombre AS Nombre_Departamento
    FROM Orden_Pedido op
    LEFT JOIN Usuario u ON op.Solicitante_Id = u.Id
    LEFT JOIN Departamento d ON u.Departamento_Id = d.Id
    WHERE op.Id = ?
";

// 4. Preparar y ejecutar la consulta de la orden (¡Más seguro!)
$stmt_orden = $conn->prepare($sql_orden);
$stmt_orden->bind_param("i", $orden_id);
$stmt_orden->execute();
$resultado_orden = $stmt_orden->get_result();

if ($resultado_orden->num_rows === 0) {
    die("Error: No se encontró la orden con ID " . $orden_id);
}
// Guardamos los datos de la orden en un array
$orden = $resultado_orden->fetch_assoc();

// 5. Consulta de los Ítems de la orden
$sql_items = "SELECT * FROM Orden_Item WHERE Orden_Id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $orden_id);
$stmt_items->execute();
$resultado_items = $stmt_items->get_result();

// Nota: No cerramos la conexión aquí, lo haremos al final del HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Orden <?php echo $orden['Id']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <div class="app-container">
        
        <header class="app-header">
            <h1>Plataforma de Adquisiciones</h1>
            <span>Usuario: <?php echo htmlspecialchars($orden['Nombre_Solicitante']); ?> (Solicitante)</span>
        </header>

        <main class="app-content">

            <div id="firma-view">
                <h2>Detalle de Orden de Pedido N° <?php echo $orden['Id']; ?></h2>

                <fieldset disabled> <legend>1. Datos del Solicitante</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>N° Requerimiento</label>
                            <input type="text" value="<?php echo $orden['Id']; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Fecha Creación</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Fecha_Creacion']); ?>" disabled>
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

                <fieldset disabled>
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
                            <select disabled>
                                <option><?php echo htmlspecialchars($orden['Tipo_Compra']); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label>Motivo de la Compra</label>
                        <textarea disabled><?php echo htmlspecialchars($orden['Motivo_Compra']); ?></textarea>
                    </div>
                </fieldset>

                <fieldset disabled>
                    <legend>3. Imputación Presupuestaria</legend>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Presupuesto ($)</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Presupuesto']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Subprograma</label>
                            <select disabled>
                                <option><?php echo htmlspecialchars($orden['Subprog']); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Centro de Costo</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Centro_Costos']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Cuenta Presupuestaria</label>
                            <input type="text" value="<?php echo htmlspecialchars($orden['Cuenta_Presupuestaria']); ?>" disabled>
                        </div>
                    </div>
                </fieldset>

                <fieldset disabled>
                    <legend>4. Detalle de Productos/Servicios</legend>
                    <table id="items-table-firma">
                        <thead>
                            <tr>
                                <th>Cantidad</th>
                                <th>Producto o Servicio</th>
                                <th>V. Unitario ($)</th>
                                <th>Total Línea ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // 6. Recorremos los ítems y los pintamos en la tabla
                            if ($resultado_items->num_rows > 0) {
                                while($item = $resultado_items->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><input type='number' value='" . htmlspecialchars($item['Cantidad']) . "' disabled></td>";
                                    echo "<td><input type='text' value='" . htmlspecialchars($item['Nombre_producto_servicio']) . "' disabled></td>";
                                    echo "<td><input type='number' class='input-v-unitario' value='" . htmlspecialchars($item['Valor_Unitario']) . "' disabled></td>";
                                    echo "<td><span class='total-linea'>" . htmlspecialchars($item['Valor_Total']) . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Esta orden no tiene ítems.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </fieldset>

                <fieldset disabled>
                    <legend>5. Totales</legend>
                    <div class="totals-grid">
                        <label>Valor Neto:</label>
                        <input type="text" value="<?php echo htmlspecialchars($orden['Valor_neto']); ?>" disabled>
                        
                        <label>IVA (19%):</label>
                        <input type="text" value="<?php echo htmlspecialchars($orden['Iva']); ?>" disabled>

                        <label>Valor Total:</label>
                        <input type="text" value="<?php echo htmlspecialchars($orden['Valor_total']); ?>" disabled style="font-weight: bold; font-size: 1.1em;">
                    </div>
                </fieldset>
                
                <div id="firma-view">
                    <fieldset>
                        <legend>6. Proceso de Firma Digital</legend>
                        <div class="form-group full-width">
                            <label for="token-input">Ingrese su Token de Firma</label>
                            <input type="password" id="token-input" placeholder="********">
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
    
            </div>
        </main>
    </div>
    

    <div id="modal-overlay" class="modal-overlay"></div>
    <div id="modal-rechazo" class="modal">
        <h3>Motivo del Rechazo</h3>
        <p>Por favor, detalle por qué se rechaza esta orden.</p>
        <div class="form-group full-width">
            <textarea id="motivo-rechazo-textarea" rows="5" placeholder="Escriba aquí el motivo..."></textarea>
        </div>
        <div class="modal-actions">
            <button id="btn-cancelar-rechazo" class="btn btn-secondary">Cancelar</button>
            <button id="btn-enviar-rechazo" class="btn btn-danger">Enviar Rechazo</button>
        </div>
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