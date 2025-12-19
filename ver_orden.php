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

// 5. Consulta principal
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

// 8. LÓGICA DE VISUALIZACIÓN DE FIRMA
$user_id_actual = $_SESSION['user_id'];
$user_rol_actual = strtoupper($_SESSION['user_rol']); 
$user_depto_actual = $_SESSION['user_depto']; 

$orden_estado = $orden['Estado'];
$orden_solicitante_id = $orden['Solicitante_Id'];

$mostrar_firma_box = false;

// Caso A: Funcionario firma su propia orden
if ($orden_estado === 'Pend. Mi Firma' && $user_id_actual == $orden_solicitante_id) {
    $mostrar_firma_box = true;
}
// Caso B: Director firma (GLOBAL)
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
    <title>Ver Orden N° <?php echo $orden['Id']; ?> - Gestión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/forms-pro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Regla global para evitar desbordamientos */
        * { box-sizing: border-box; }

        /* Estilos específicos para visualización (read-only) */
        .read-only-input {
            background-color: #f8fafc;
            color: #475569;
            border-color: #e2e8f0;
            cursor: not-allowed;
            font-weight: 500;
        }
        
        /* Links de Archivos */
        .file-card {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
            text-decoration: none;
            color: #334155;
            transition: all 0.2s;
        }
        .file-card:hover {
            border-color: var(--primary);
            background: #f0f7ff;
            transform: translateX(2px);
        }
        .file-icon { margin-right: 10px; color: var(--primary); font-size: 1.2rem; }
        .file-type { font-size: 0.75rem; color: #64748b; text-transform: uppercase; margin-right: 8px; font-weight: 700; }

        /* Estilo para el input del Token */
        .token-input {
            font-size: 1.1rem;
            letter-spacing: 3px;
            text-align: left;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            width: 150px;
            transition: all 0.3s;
        }
        .token-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
            outline: none;
        }

        /* --- MODAL DE RECHAZO CORREGIDO --- */
        #modal-overlay {
            display: none; 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 1000; backdrop-filter: blur(3px);
            
            /* FLEXBOX PARA CENTRADO PERFECTO */
            align-items: center;
            justify-content: center;
        }
        
        #modal-rechazo {
            /* Ya no usa position absolute/fixed porque está dentro del flex */
            position: relative;
            background: white; border-radius: 14px; width: 95%; max-width: 550px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); z-index: 1001;
            overflow: hidden;
            display: block; /* Siempre visible dentro del overlay cuando el overlay es visible */
        }

        /* Clases que activa JS */
        #modal-overlay.modal-show { display: flex !important; }
        
        /* Encabezado del Modal */
        .modal-header {
            background-color: #fef2f2;
            padding: 15px 25px;
            border-bottom: 1px solid #fee2e2;
            display: flex; align-items: center; gap: 10px;
        }
        .modal-header h2 { margin: 0; color: #991b1b; font-size: 1.1rem; font-weight: 700; }
        
        .modal-body { padding: 25px; }
        
        .modal-footer {
            padding: 15px 25px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex; justify-content: flex-end; gap: 12px;
        }

        /* Textarea Corregido */
        #motivo-rechazo-textarea { 
            width: 100%; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            padding: 12px; 
            margin-top: 10px; 
            font-family: inherit;
            resize: none; 
            height: 120px;
            box-sizing: border-box; /* IMPORTANTE PARA EVITAR DESBORDE */
        }
        #motivo-rechazo-textarea:focus { border-color: #ef4444; outline: none; }

        /* --- BOTONES NUEVOS --- */
        .btn-cancel-styled {
            background: white; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; 
            cursor: pointer; color: #475569; font-weight: 600; transition: all 0.2s;
        }
        .btn-cancel-styled:hover { background: #f1f5f9; border-color: #94a3b8; }

        /* Botón ROJO Bonito (Para Modal y para Acción Principal) */
        .btn-danger-styled {
            background: linear-gradient(to bottom, #ef4444, #dc2626);
            border: none;
            color: white;
            padding: 9px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.25);
            transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 8px; justify-content: center;
        }
        .btn-danger-styled:hover {
            background: linear-gradient(to bottom, #dc2626, #b91c1c);
            box-shadow: 0 6px 10px rgba(239, 68, 68, 0.3);
            transform: translateY(-1px);
        }

        /* Utilidad de firma compacta */
        .signature-row {
            display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap;
        }
    </style>
</head>
<body>

    <div id="modal-overlay">
        <div id="modal-rechazo" onclick="event.stopPropagation()">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                <h2>Confirmar Rechazo</h2>
            </div>
            <div class="modal-body">
                <p style="color: #475569; font-size: 0.95rem; margin-top: 0; line-height: 1.5;">
                    Está a punto de rechazar esta solicitud. Es necesario ingresar un motivo para que el solicitante pueda corregirlo.
                </p>
                <label style="display: block; margin-top: 15px; font-weight: 600; font-size: 0.9rem; color: #334155;">Motivo del rechazo:</label>
                <textarea id="motivo-rechazo-textarea" placeholder="Ej: Falta adjuntar la cotización actualizada..."></textarea>
            </div>
            <div class="modal-footer">
                <button id="btn-cancelar-rechazo" class="btn-cancel-styled">Cancelar</button>
                <button id="btn-enviar-rechazo" class="btn-danger-styled">
                    <i class="fas fa-times-circle"></i> Confirmar Rechazo
                </button>
            </div>
        </div>
    </div>

    <div class="app-container">
        <header class="main-header">
            <div class="header-left">
                <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
                <h1>Orden N° <?php echo $orden['Id']; ?></h1>
            </div>
            <div class="user-info">
                <div class="user-text">
                    <div class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    <div class="role"><?php echo htmlspecialchars($_SESSION['user_rol']); ?></div>
                </div>
                <a href="controllers/auth_logout.php" class="logout-icon"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </header>

        <main class="form-wrapper">
            
            <div class="content-card shadow-sm">
                <div class="form-section-header">
                    <div class="section-icon"><i class="fas fa-user-check"></i></div>
                    <div>
                        <h2>1. Datos del Solicitante</h2>
                        <p>Información del origen de la solicitud</p>
                    </div>
                </div>
                <div class="form-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Folio</label>
                            <input type="text" class="read-only-input" value="#<?php echo $orden['Id']; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Solicitud</label>
                            <input type="text" class="read-only-input" value="<?php echo date("d/m/Y H:i", strtotime($orden['Fecha_Creacion'])); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Prof. Responsable</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Nombre_Solicitante']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Departamento</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Nombre_Departamento']); ?>" disabled>
                        </div>
                    </div>
                </div>

                <div class="form-section-header no-border-top">
                    <div class="section-icon"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <h2>2. Detalles de la Compra</h2>
                        <p>Información general y plazos</p>
                    </div>
                </div>
                <div class="form-body">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Nombre de la Compra</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Nombre_Orden']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Plazo Máximo</label>
                            <input type="text" class="read-only-input" value="<?php echo date("d/m/Y", strtotime($orden['Plazo_maximo'])); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Tipo De Compra</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Tipo_Compra']); ?>" disabled>
                        </div>
                        <div class="form-group full-width">
                            <label>Motivo de la Compra</label>
                            <textarea class="read-only-input" disabled rows="3" style="resize: none;"><?php echo htmlspecialchars($orden['Motivo_Compra']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section-header no-border-top">
                    <div class="section-icon"><i class="fas fa-coins"></i></div>
                    <div>
                        <h2>3. Imputación Presupuestaria</h2>
                    </div>
                </div>
                <div class="form-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Presupuesto</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Presupuesto']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Cuenta Presupuestaria</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Cuenta_Presupuestaria']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Subprograma</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Subprog']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Centro de Costo</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Centro_Costos']); ?>" disabled>
                        </div>
                        <?php if (!empty($orden['Id_Licitacion'])): ?>
                        <div class="form-group full-width">
                            <label>Licitación Asociada</label>
                            <input type="text" class="read-only-input" value="<?php echo htmlspecialchars($orden['Id_Licitacion'] . " - " . $orden['Nombre_Licitacion_Origen']); ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-section-header no-border-top">
                    <div class="section-icon"><i class="fas fa-paperclip"></i></div>
                    <div>
                        <h2>Documentos Adjuntos</h2>
                    </div>
                </div>
                <div class="form-body">
                    <?php 
                    if (count($archivos) > 0) {
                        foreach($archivos as $arch) {
                            $nombre = htmlspecialchars($arch['Nombre_Original']);
                            $tipo = htmlspecialchars($arch['Tipo_Documento']);
                            $ruta = htmlspecialchars($arch['Ruta_Archivo']);
                            echo "
                            <a href='$ruta' target='_blank' class='file-card'>
                                <i class='fas fa-file-alt file-icon'></i>
                                <div>
                                    <span class='file-type'>$tipo</span>
                                    <span class='file-name'>$nombre</span>
                                </div>
                            </a>";
                        }
                    } else {
                        echo "<p style='color: #94a3b8; font-style: italic; padding: 10px;'>No hay documentos adjuntos en esta orden.</p>";
                    }
                    ?>
                </div>

                <div class="form-section-header no-border-top">
                    <div class="section-icon"><i class="fas fa-boxes"></i></div>
                    <div>
                        <h2>Detalle de Productos</h2>
                    </div>
                </div>
                <div class="table-container-form">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Cant.</th>
                                <?php if ($isConvenioMarco): ?><th>ID Producto</th><?php endif; ?>
                                <th>Descripción</th>
                                <?php if (!$isModoPresupuesto): ?>
                                    <th style="width: 150px; text-align: right;">Unitario</th>
                                    <th style="width: 150px; text-align: right;">Total</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($resultado_items->num_rows > 0) {
                                while($item = $resultado_items->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><strong>" . $item['Cantidad'] . "</strong></td>";
                                    if ($isConvenioMarco) echo "<td><span style='background:#f1f5f9; padding:4px 8px; border-radius:4px; font-size:0.85rem;'>" . htmlspecialchars($item['Codigo_Producto']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($item['Nombre_producto_servicio']) . "</td>";
                                    if (!$isModoPresupuesto) {
                                        echo "<td style='text-align: right;'>$ " . number_format($item['Valor_Unitario'], 0, ',', '.') . "</td>";
                                        echo "<td style='text-align: right; font-weight:600;'>$ " . number_format($item['Valor_Total'], 0, ',', '.') . "</td>";
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align:center; padding:20px; color:#64748b;'>Sin ítems registrados.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="totals-section">
                    <div class="totals-card">
                        <?php if ($isModoPresupuesto): ?>
                            <div class="total-row main-total">
                                <label>PRESUPUESTO:</label>
                                <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled>
                            </div>
                        <?php else: ?>
                            <div class="total-row">
                                <label>Valor Neto:</label>
                                <input type="text" value="$ <?php echo number_format($orden['Valor_neto'], 0, ',', '.'); ?>" disabled>
                            </div>
                            <div class="total-row">
                                <label>IVA:</label>
                                <input type="text" value="$ <?php echo number_format($orden['Iva'], 0, ',', '.'); ?>" disabled>
                            </div>
                            <div class="total-row main-total">
                                <label>VALOR TOTAL:</label>
                                <input type="text" value="$ <?php echo number_format($orden['Valor_total'], 0, ',', '.'); ?>" disabled>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div> <?php if ($mostrar_gestion_box): ?>
            <div class="content-card shadow-sm" style="margin-top: 25px; border-left: 5px solid #0f62fe;">
                <div class="form-section-header">
                    <div class="section-icon" style="background: #e0f2fe; color: #0043ce;"><i class="fas fa-briefcase"></i></div>
                    <div>
                        <h2>Gestión de Compra</h2>
                        <p>Área exclusiva para el departamento de Adquisiciones</p>
                    </div>
                </div>
                <div class="form-body">
                    <form action="controllers/orden_gestion.php" method="POST">
                        <input type="hidden" name="orden_id" value="<?php echo $orden['Id']; ?>">
                        <div class="form-group full-width">
                            <label>Proveedor Contactado</label>
                            <input type="text" name="proveedor_nombre" required placeholder="Nombre del proveedor asignado">
                        </div>
                        <div style="text-align: right; margin-top: 15px;">
                            <button type="submit" class="btn-submit">Guardar Gestión</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_firma_box): ?>
            <div class="content-card shadow-sm" style="margin-top: 20px; border-left: 5px solid #16a34a;">
                <div class="form-section-header" style="padding-bottom: 5px;">
                    <div class="section-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fas fa-pen-nib"></i>
                    </div>
                    <div>
                        <h2>Firma Digital Requerida</h2>
                        <p style="margin-bottom: 0;">Autorización pendiente</p>
                    </div>
                </div>
                
                <div class="form-body" style="padding-top: 15px;">
                    <fieldset id="fieldset-firma-accion" data-orden-id="<?php echo $orden['Id']; ?>" style="border: none; padding: 0; margin: 0;">
                        
                        <div class="signature-row">
                            
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display: block; font-weight: 600; color: #334155; margin-bottom: 8px; font-size: 0.9rem;">
                                    Token de Seguridad
                                </label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="password" id="token-input" class="token-input" placeholder="• • • • • •" maxlength="6">
                                    <span style="font-size: 0.8rem; color: #64748b; font-style: italic;">(6 dígitos RUT)</span>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px;">
                                <button id="btn-rechazar" class="btn-danger-styled">
                                    <i class="fas fa-times-circle"></i> Rechazar
                                </button>
                                <button id="btn-firmar" class="btn-submit" style="background: #16a34a; border: none; color: white; cursor: pointer; box-shadow: 0 4px 6px rgba(22, 163, 74, 0.2); padding: 9px 20px;">
                                    <i class="fas fa-check-circle"></i> Firmar y Aprobar
                                </button>
                            </div>

                        </div>

                    </fieldset>
                </div>
            </div>
            
            <?php else: ?>
                <div class="content-card shadow-sm" style="margin-top: 25px; padding: 25px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 5px;">Estado Actual</span>
                        <span style="font-size: 1.2rem; font-weight: 700; color: var(--primary);">
                            <?php echo htmlspecialchars($orden['Estado']); ?>
                        </span>
                        <?php if ($orden['Estado'] === 'Rechazada'): ?>
                            <div style="margin-top: 10px; padding: 10px; background: #fef2f2; border-left: 3px solid #ef4444; color: #b91c1c; font-size: 0.9rem;">
                                <strong>Motivo:</strong> <?php echo htmlspecialchars($orden['Motivo_Rechazo']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="generar_pdf.php?id=<?php echo $orden['Id']; ?>" target="_blank" class="btn-submit" style="background: #334155;">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                        </a>
                    </div>
                </div>
            <?php endif; ?>

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