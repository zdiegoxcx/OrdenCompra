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

// 4. Obtener datos de la SESIÓN
$user_id = $_SESSION['user_id'];

// 5. Consultar la BD para obtener MÁS datos del usuario (email, fono, depto)
$sql_user = "
    SELECT 
        u.Nombre AS NombreUsuario, 
        u.Email, 
        u.Telefono, 
        d.Nombre AS NombreDepartamento
    FROM Usuario u
    LEFT JOIN Departamento d ON u.Departamento_Id = d.Id
    WHERE u.Id = ?
";

$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$resultado_user = $stmt_user->get_result();
$usuario = $resultado_user->fetch_assoc();

if (!$usuario) {
    // Si por alguna razón no se encuentra el usuario
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit;
}

// 6. Preparar variables para el formulario
$fecha_hoy = date("d/m/Y"); // Fecha de hoy
$nombre_usuario = htmlspecialchars($usuario['NombreUsuario']);
$depto_usuario = htmlspecialchars($usuario['NombreDepartamento']);
$email_usuario = htmlspecialchars($usuario['Email']);
$fono_usuario = htmlspecialchars($usuario['Telefono']);

$user_rol = $_SESSION['user_rol'];


$stmt_user->close();
// Dejamos la conexión $conn abierta para el script
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Orden</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <div class="app-container">
        
        <header class="app-header">
            <h1>Plataforma de Adquisiciones</h1>
            <span>Usuario: <strong> <?php echo $nombre_usuario; ?> </strong>(<?php echo htmlspecialchars($user_rol); ?>)
        </span>
        </header>

        <main class="app-content">
        <form id="form-crear-orden" action="procesar_orden.php" method="POST" enctype="multipart/form-data">

                <div id="form-view">
                    <h2>Formulario de Creación de Orden de Pedido</h2>

                    <fieldset>
                        <legend>1. Datos del Solicitante</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>N°</label>
                                <input type="text" value="(Automático)" disabled>
                            </div>
                            <div class="form-group">
                                <label>Fecha Creación</label>
                                <input type="text" value="<?php echo $fecha_hoy; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Prof. Responsable</label>
                                <input type="text" value="<?php echo $nombre_usuario; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Depto. Solicitante</label>
                                <input type="text" value="<?php echo $depto_usuario; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" value="<?php echo $email_usuario; ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" value="<?php echo $fono_usuario; ?>" disabled>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>2. Datos Generales de la Orden</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre-orden">Nombre de la Compra <span style="color: red;">*</span></label>
                                <input type="text" id="nombre-orden" name="nombre_orden"  required>
                            </div>
                            <div class="form-group">
                                <label for="plazo-max">Plazo Máximo de Entrega <span style="color: red;">*</span></label>
                                <input type="date" id="plazo-max" name="plazo_maximo" required>
                            </div>
                            <div class="form-group">
                                <label for="tipo-compra">Tipo de Compra<span style="color: red;">*</span></label>
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
                        </div>
                        <div class="form-group full-width">
                            <label for="motivo-compra">Motivo de la Compra <span style="color: red;">*</span></label>
                            <textarea id="motivo-compra" name="motivo_compra" placeholder="Justifique la necesidad de esta compra..." required></textarea>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>3. Imputación Presupuestaria</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="presupuesto">Presupuesto  <span style="color: red;">*</span></label>
                                <input type="text" id="presupuesto" name="presupuesto" required>
                            </div>
                            <div class="form-group">
                                <label for="cuenta_presupuestaria">Cuenta Presupuestaria <span style="color: red;">*</span></label>
                                <input type="text" id="cuenta_presupuestaria" name="cuenta_presupuestaria" >
                            </div>
                            <div class="form-group">
                                <label for="subprog">Subprograma <span style="color: red;">*</span></label>
                                <select id="subprog" name="subprog" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Subprog 1"> 1</option>
                                    <option value="Subprog 2"> 2</option>
                                    <option value="Subprog 3"> 3</option>
                                    <option value="Subprog 4"> 4</option>
                                    <option value="Subprog 5"> 5</option>
                                    <option value="Subprog 6"> 6</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cc">Centro de Costo</label>
                                <input type="text" id="cc" name="centro_costos" placeholder="000000">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset id="fieldset-licitacion-publica" style="display: none;">
                        <legend>3.6. Ingresar el ID de la Licitacion Publica correspondiente</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="id_licitacion_publica">ID Licitacion Publica <span style="color: red;">*</span></label>
                                <select id="id_licitacion_publica" name="id_licitacion_publica" required>
                                    <option value="">Seleccione...</option>
                                    <option value="ID-001">ID-001</option>
                                    <option value="ID-002">ID-002</option>
                                    <option value="ID-003">ID-003</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>



                    <fieldset id="fieldset-trato-directo" style="display: none;">
                        <legend>3.5. Documentos Requeridos (Trato Directo)</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cotizacion_file">1° Cotización <span style="color: red;">*</span></label>
                                <input type="file" id="cotizacion_file" name="cotizacion_file" accept=".pdf,.doc,.docx,.jpg,.png,.xls,.xlsx">
                            </div>
                            <div class="form-group">
                                <label for="memorando_file">2° Memorando <span style="color: red;">*</span></label>
                                <input type="file" id="memorando_file" name="memorando_file" accept=".pdf,.doc,.docx,.jpg,.png,.xls,.xlsx">
                            </div>
                            <div class="form-group">
                                <label for="decreto_file">3° Decreto Autoriza Trato Directo <span style="color: red;">*</span></label>
                                <input type="file" id="decreto_file" name="decreto_file" accept=".pdf,.doc,.docx,.jpg,.png,.xls,.xlsx">
                            </div>
                        </div>
                        <p style="font-size: 0.9em; color: #555;">(Se deben adjuntar los 3 documentos para continuar)</p>
                    </fieldset>


                
                    <fieldset>
                        <legend>4. Detalle de Productos/Servicios</legend>
                        <table id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Cantidad <span style="color: red;">*</span></th>
                                    <th>Producto o Servicio <span style="color: red;">*</span></th>
                                    <th class="col-v-unitario" style="width: 20%;">V. Unitario ($) <span style="color: red;">*</span></th>
                                    <th class="col-total-linea" style="width: 20%;">Total Línea ($) </th>
                                    <th style="width: 5%;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="number" name="item_cantidad[]" class="input-calc" value="1" min="1" required></td>
                                    <td><input type="text" name="item_nombre[]" required></td>
                                    <td class="col-v-unitario"><input type="number" name="item_v_unitario[]" class="input-v-unitario input-calc" value="0" min="0" required></td>
                                    <td class="col-total-linea"><span class="total-linea">0</span></td>
                                    <td><button type="button" class="accion-btn btn-delete-item">🗑️</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-add-item">➕ Agregar Ítem</button>
                    </fieldset>


                    <fieldset>
                        <legend>4.5. Archivos Adicionales (Opcional)</legend>
                        <div class="form-group full-width">
                            <label for="archivos_adicionales">Adjuntar otros documentos </label>
                            
                            <input type="file" id="archivos_adicionales" name="archivos_adicionales[]" multiple accept=".pdf,.doc,.docx,.jpg,.png,.xls,.xlsx">
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>5. Totales (Cálculo Automático)</legend>
                        <div class="totals-grid">
 
                            <label id="label-valor-neto">Valor Neto:</label>
                            <input id="input-valor-neto" type="text" value="0" disabled>
                            
                            <label id="label-iva">IVA (19%):</label>
                            <input id="input-iva" type="text" value="0" disabled>

                            <label id="label-valor-total">Valor Total:</label>
                            <input id="input-valor-total" type="text" value="0" disabled style="font-weight: bold; font-size: 1.1em;">

                            <input type="hidden" name="valor_neto_hidden" id="valor_neto_hidden">
                            <input type="hidden" name="iva_hidden" id="iva_hidden">
                            <input type="hidden" name="valor_total_hidden" id="valor_total_hidden">
                        </div>
                    </fieldset>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-danger">Cancelar</a>
                        <button type="submit" class="btn btn-success">➡️ Enviar a Aprobación</button>
                    </div>
                

            </form>
        </main>
    </div>

    <script src="js/crear.js"></script>

</body>
</html>