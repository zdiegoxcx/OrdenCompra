<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("HTTP/1.1 403 Forbidden"); exit; }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'conectar.php';
    $conn->begin_transaction();

    try {
        // ... (Recogida de datos simples igual que antes) ...
        $solicitante_id = $_SESSION['user_id'];
        $solicitante_rol = $_SESSION['user_rol'];
        
        $nombre_orden = $_POST['nombre_orden'];
        $plazo_maximo = $_POST['plazo_maximo'];
        $tipo_compra = $_POST['tipo_compra'];
        $motivo_compra = $_POST['motivo_compra'];
        $presupuesto = $_POST['presupuesto'];
        $cuenta_presupuestaria = $_POST['cuenta_presupuestaria'];
        $subprog = $_POST['subprog'];
        $centro_costos = $_POST['centro_costos'];

        $valor_neto = $_POST['valor_neto_hidden'];
        $iva = $_POST['iva_hidden'];
        $valor_total = $_POST['valor_total_hidden'];
        
        $estado = ($solicitante_rol === 'Director') ? 'Pend. Firma Director' : 'Pend. Mi Firma';

        $id_licitacion = isset($_POST['id_licitacion_publica']) ? $_POST['id_licitacion_publica'] : null;

        // 1. Insertar Orden
        $sql_orden = "INSERT INTO Orden_Pedido (Solicitante_Id, Nombre_Orden, Fecha_Creacion, Tipo_Compra, Id_Licitacion, Presupuesto, Subprog, Centro_Costos, Plazo_maximo, Iva, Valor_neto, Valor_total, Estado, Motivo_Compra, Cuenta_Presupuestaria) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_orden = $conn->prepare($sql_orden);
        $stmt_orden->bind_param("isssssssdddsss", $solicitante_id, $nombre_orden, $tipo_compra, $id_licitacion, $presupuesto, $subprog, $centro_costos, $plazo_maximo, $iva, $valor_neto, $valor_total, $estado, $motivo_compra, $cuenta_presupuestaria);
        $stmt_orden->execute();
        $orden_id_nueva = $conn->insert_id; // ID generado

        // -----------------------------------------------------------------
        // 2. NUEVA LÓGICA DE ARCHIVOS (Múltiples archivos por tipo con límite)
        // -----------------------------------------------------------------

        $uploadDir = 'uploads/'; // Asegúrate de que esta carpeta exista y tenga permisos

        /**
         * Función para procesar subidas múltiples con límite
         * @param string $inputName Nombre del input en el formulario (sin los corchetes)
         * @param string $tipoDoc   Nombre para la BD (Cotizacion, Memorando, etc.)
         * @param int    $ordenId   ID de la orden
         * @param object $conn      Conexión a BD
         * @param string $dir       Directorio de destino
         * @param int    $maxFiles  Límite de archivos permitidos
         */
        function subirArchivosMultiples($inputName, $tipoDoc, $ordenId, $conn, $dir, $maxFiles = 3) {
            // Verificar si existe el input y si tiene archivos
            if (isset($_FILES[$inputName]) && is_array($_FILES[$inputName]['name'])) {
                
                $files = $_FILES[$inputName];
                $totalSubidos = count($files['name']);
                
                // El bucle se ejecutará máximo $maxFiles veces
                $limite = min($totalSubidos, $maxFiles);

                for ($i = 0; $i < $limite; $i++) {
                    // Verificar errores individuales (0 = UPLOAD_ERR_OK)
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        
                        $nombreOriginal = basename($files['name'][$i]);
                        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                        
                        // Nombre único: orden_ID_TIPO_indice_timestamp.ext
                        $nombreGuardado = "orden_{$ordenId}_{$tipoDoc}_{$i}_" . time() . "." . $ext;
                        $rutaDestino = $dir . $nombreGuardado;

                        if (move_uploaded_file($files['tmp_name'][$i], $rutaDestino)) {
                            // Guardar en Base de Datos
                            $sql_archivo = "INSERT INTO Orden_Archivos (Orden_Id, Nombre_Archivo, Nombre_Original, Tipo_Documento, Ruta_Archivo) VALUES (?, ?, ?, ?, ?)";
                            $stmt_arch = $conn->prepare($sql_archivo);
                            $stmt_arch->bind_param("issss", $ordenId, $nombreGuardado, $nombreOriginal, $tipoDoc, $rutaDestino);
                            $stmt_arch->execute();
                            $stmt_arch->close();
                        }
                    }
                }
            }
        }

        // A. Archivos de Trato Directo (Ahora soportan múltiples)
        if ($tipo_compra === 'Trato Directo') {
            // Llama a la función pasándole el nombre del input (sin [])
            subirArchivosMultiples('cotizacion_file', 'Cotizacion', $orden_id_nueva, $conn, $uploadDir, 3);
            subirArchivosMultiples('memorando_file', 'Memorando', $orden_id_nueva, $conn, $uploadDir, 3);
            subirArchivosMultiples('decreto_file', 'Decreto', $orden_id_nueva, $conn, $uploadDir, 3);
        }

        // B. Archivos Adicionales (También limitado a 3)
        subirArchivosMultiples('archivos_adicionales', 'Adicional', $orden_id_nueva, $conn, $uploadDir, 3);
        
        // -----------------------------------------------------------------
        // 3. Insertar Items (CON LA NUEVA COLUMNA CODIGO)
        // -----------------------------------------------------------------
        
        $item_cantidades = $_POST['item_cantidad'];
        $item_nombres = $_POST['item_nombre'];
        $item_unitarios = $_POST['item_v_unitario'];
        
        // NUEVO: Capturar array de códigos
        // Si no se envió (porque estaba oculto), PHP puede dar null, lo manejamos.
        $item_codigos = isset($_POST['item_codigo']) ? $_POST['item_codigo'] : array_fill(0, count($item_cantidades), null);

        // Actualizamos el INSERT para incluir Codigo_Producto
        $sql_item = "INSERT INTO Orden_Item (Orden_Id, Nombre_producto_servicio, Codigo_Producto, Cantidad, Valor_Unitario, Valor_Total) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

        for ($i = 0; $i < count($item_cantidades); $i++) {
            $cantidad = (int)$item_cantidades[$i];
            $nombre = $item_nombres[$i];
            $codigo = isset($item_codigos[$i]) ? $item_codigos[$i] : ''; // Codigo o vacío
            $v_unitario = (float)$item_unitarios[$i];
            $v_total_linea = $cantidad * $v_unitario; 
            
            // bind_param: Orden_Id(i), Nombre(s), Codigo(s), Cant(i), Unit(d), Total(d)
            $stmt_item->bind_param("issidd", $orden_id_nueva, $nombre, $codigo, $cantidad, $v_unitario, $v_total_linea);
            $stmt_item->execute();
        }

        $conn->commit();
        $stmt_orden->close();
        $stmt_item->close();
        $conn->close();

        header("Location: index.php?creacion=exito");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        die("Error al crear la orden: " . $e->getMessage());
    }
} else {
    header("Location: crear-orden.php");
    exit;
}
?>