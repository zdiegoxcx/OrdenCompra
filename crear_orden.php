<?php
// crear_orden.php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

include 'config/db.php';
$user_id = $_SESSION['user_id'];

// Lógica original de usuario
$sql_user = "SELECT NOMBRE, APELLIDO, CORREO, FONO, DEPTO, ADQUISICIONES FROM FUNCIONARIOS_MUNI WHERE ID = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$resultado_user = $stmt_user->get_result();
$usuario = $resultado_user->fetch_assoc();

if (!$usuario) { 
    session_destroy(); 
    header("Location: login.php?error=user_not_found"); 
    exit; 
}

$fecha_hoy = date("d/m/Y");
$nombre_usuario = htmlspecialchars($usuario['NOMBRE'] . " " . $usuario['APELLIDO']);
$depto_usuario = htmlspecialchars($usuario['DEPTO']); 
$user_rol = $_SESSION['user_rol']; 

// Licitaciones
$sql_licitaciones = "SELECT Id, Nombre_Orden FROM Orden_Pedido WHERE Tipo_Compra = 'Licitación Pública' ORDER BY Id DESC";
$res_licitaciones = $conn->query($sql_licitaciones);
$stmt_user->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Orden - Gestión de Adquisiciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/forms-pro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Clases necesarias para la lógica del JS original */
        .col-id-producto { display: none; }
        .iva-control-wrapper { display: flex; align-items: center; gap: 8px; }
        .file-list ul { list-style: none; padding: 0; margin-top: 5px; }
        .file-list li { background:#f8f9fa; border:1px solid #ddd; padding:5px; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center; border-radius:4px; font-size:0.85em; }
    </style>
</head>
<body>
<div class="app-container">
    <header class="main-header">
        <div class="header-left">
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
            <h1>Nueva Orden de Pedido</h1>
        </div>
        <div class="user-info">
            <div class="user-text">
                <div class="name"><?php echo $nombre_usuario; ?></div>
                <div class="role"><?php echo htmlspecialchars($user_rol); ?> | <?php echo $depto_usuario; ?></div>
            </div>
            <a href="controllers/auth_logout.php" class="logout-icon"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="form-wrapper">
        <form id="form-crear-orden" action="controllers/orden_crear.php" method="POST" enctype="multipart/form-data" class="content-card shadow-sm">
            
            <div class="form-section-header">
                <div class="section-icon"><i class="fas fa-user-tie"></i></div>
                <div>
                    <h2>1. Datos del Solicitante</h2>
                    <p>Información del profesional responsable</p>
                </div>
            </div>
            <div class="form-body">
                <div class="form-grid">
                    <div class="form-group"><label>N°</label><input type="text" value="(Automático)" disabled></div>
                    <div class="form-group"><label>Fecha Creación</label><input type="text" value="<?php echo $fecha_hoy; ?>" disabled></div>
                    <div class="form-group"><label>Prof. Responsable</label><input type="text" value="<?php echo $nombre_usuario; ?>" disabled></div>
                    <div class="form-group"><label>Depto. Solicitante</label><input type="text" value="<?php echo $depto_usuario; ?>" disabled></div>
                </div>
            </div>

            <div class="form-section-header no-border-top">
                <div class="section-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <h2>2. Datos Generales</h2>
                    <p>Defina el propósito y modalidad</p>
                </div>
            </div>
            <div class="form-body">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="nombre-orden">Nombre de la Compra <span class="required">*</span></label>
                        <input type="text" id="nombre-orden" name="nombre_orden" maxlength="100" required placeholder="Ej: Servicio de mantención...">
                    </div>
                    <div class="form-group">
                        <label for="plazo-max">Plazo Máximo Entrega <span class="required">*</span></label>
                        <input type="date" id="plazo-max" name="plazo_maximo" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo-compra">Tipo de Compra <span class="required">*</span></label>
                        <select id="tipo-compra" name="tipo_compra" required>
                            <option value="">Seleccione...</option>
                            <option value="Convenio Marco">Convenio Marco</option> 
                            <option value="Compra Ágil">Compra Ágil</option>
                            <option value="Trato Directo">Trato Directo</option>
                            <option value="Licitación Pública">Licitación Pública</option>
                            <option value="Licitación Privada">Licitación Privada</option>
                            <option value="Suministro">Suministro</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="motivo-compra">Motivo de la Compra <span class="required">*</span></label>
                        <textarea id="motivo-compra" name="motivo_compra" required placeholder="Describa la necesidad..."></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section-header no-border-top">
                <div class="section-icon"><i class="fas fa-calculator"></i></div>
                <div>
                    <h2>3. Imputación Presupuestaria</h2>
                    <p>Asignación de fondos</p>
                </div>
            </div>
            <div class="form-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="presupuesto">Presupuesto <span class="required">*</span></label>
                        <input type="number" id="presupuesto" name="presupuesto" required onkeydown="if(['e', 'E', '.', '-', '+'].includes(event.key)) event.preventDefault();">
                    </div>
                    <div class="form-group">
                        <label for="cuenta_presupuestaria">Cuenta Presupuestaria <span class="required">*</span></label>
                        <input type="text" id="cuenta_presupuestaria" name="cuenta_presupuestaria">
                    </div>
                    <div class="form-group">
                        <label for="subprog">Subprograma <span class="required">*</span></label>
                        <select id="subprog" name="subprog" required>
                            <option value="">Seleccione...</option>
                            <?php for($i=1;$i<=6;$i++) echo "<option value='Subprog $i'>$i</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cc">Centro de Costo</label>
                        <input type="text" id="cc" name="centro_costos" maxlength="6" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>
            </div>

            <div id="fieldset-licitacion-publica" style="display: none; padding: 0 30px 20px;">
                <label style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">ID Licitación Pública *</label>
                <select id="id_licitacion_publica" name="id_licitacion_publica" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                    <option value="">Seleccione...</option>
                    <?php while($row = $res_licitaciones->fetch_assoc()) echo "<option value='".$row['Id']."'>ID: ".$row['Id']." - ".htmlspecialchars($row['Nombre_Orden'])."</option>"; ?>
                </select>
            </div>

            <div id="fieldset-trato-directo" style="display: none;">
                <div class="form-section-header no-border-top">
                    <div class="section-icon"><i class="fas fa-folder-open"></i></div>
                    <div>
                        <h2>Documentos Trato Directo</h2>
                        <p>Documentación obligatoria</p>
                    </div>
                </div>
                <div class="form-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>1° Cotización (Máx 3) *</label>
                            <input type="file" id="cotizacion_file" name="cotizacion_file[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div id="lista-cotizacion" class="file-list"></div>
                        </div>
                        <div class="form-group">
                            <label>2° Memorando (Máx 3) *</label>
                            <input type="file" id="memorando_file" name="memorando_file[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div id="lista-memorando" class="file-list"></div>
                        </div>
                        <div class="form-group">
                            <label>3° Decreto (Máx 3) *</label>
                            <input type="file" id="decreto_file" name="decreto_file[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div id="lista-decreto" class="file-list"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section-header no-border-top">
                <div class="section-icon"><i class="fas fa-boxes"></i></div>
                <div>
                    <h2>4. Detalle de Productos</h2>
                    <p>Ítems y valores unitarios</p>
                </div>
            </div>
            <div class="table-container-form" style="padding: 20px 30px;">
                <table id="items-table" class="modern-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Cant.</th>
                            <th class="col-id-producto">ID</th>
                            <th>Descripción</th>
                            <th class="col-v-unitario" style="width: 150px;">Unitario ($)</th>
                            <th class="col-total-linea" style="width: 150px;">Total ($)</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        <tr>
                            <td><input type="number" name="item_cantidad[]" class="input-calc" value="1" min="1" required></td>
                            <td class="col-id-producto"><input type="text" name="item_codigo[]" placeholder="ID CM"></td>
                            <td><input type="text" name="item_nombre[]" required></td>
                            <td class="col-v-unitario"><input type="number" name="item_v_unitario[]" class="input-v-unitario input-calc" value="0" min="0" required></td>
                            <td class="col-total-linea"><span class="total-linea">0</span></td>
                            <td><button type="button" class="btn-delete-item"><i class="fas fa-trash-alt"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:15px;"><button type="button" class="btn-add-item"><i class="fas fa-plus"></i> Agregar Ítem</button></div>
            </div>

            <div class="form-section-header no-border-top">
                <div class="section-icon"><i class="fas fa-paperclip"></i></div>
                <div>
                    <h2>5. Archivos Adicionales</h2>
                    <p>Otros documentos de respaldo (Opcional)</p>
                </div>
            </div>
            <div class="form-body">
                <div class="form-group full-width">
                    <label>Adjuntar documentos (Máx 3)</label>
                    <input type="file" id="archivos_adicionales" name="archivos_adicionales[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                    <div id="lista-adicionales" class="file-list"></div>
                </div>
            </div>

            <div class="totals-section">
                <div class="totals-card">
                    <div class="total-row">
                        <label id="label-valor-neto">Valor Neto:</label>
                        <input id="input-valor-neto" type="text" value="0" disabled>
                    </div>
                    <div class="total-row align-center">
                        <div class="iva-control-wrapper">
                            <label id="label-iva">IVA (19%):</label>
                            <div class="iva-control">
                                <input type="checkbox" id="aplica_iva" checked>
                                <label for="aplica_iva" style="margin:0; font-weight:400; font-size:12px;">Aplicar</label>
                            </div>
                        </div>
                        <input id="input-iva" type="text" value="0" disabled>
                    </div>
                    <div class="total-row main-total">
                        <label id="label-valor-total">VALOR TOTAL:</label>
                        <input id="input-valor-total" type="text" value="0" disabled>
                    </div>
                </div>
            </div>

            <input type="hidden" name="valor_neto_hidden" id="valor_neto_hidden">
            <input type="hidden" name="iva_hidden" id="iva_hidden">
            <input type="hidden" name="valor_total_hidden" id="valor_total_hidden">

            <div class="form-footer">
                <a href="index.php" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-submit">Enviar a Aprobación</button>
            </div>
        </form>
    </main>
</div>
<script src="assets/js/crear.js"></script>
</body>
</html>