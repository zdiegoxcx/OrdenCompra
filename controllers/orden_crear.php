<?php
// controllers/orden_crear.php

// 1. Iniciar la sesión para verificar autenticación
session_start();

// Si el usuario no ha iniciado sesión, denegar el acceso
if (!isset($_SESSION['user_id'])) { 
    header("HTTP/1.1 403 Forbidden"); 
    exit; 
}

// 2. Verificar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Conexión a la base de datos
    include '../config/db.php';
    
    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // --- RECOPILACIÓN DE DATOS DEL FORMULARIO Y SESIÓN ---
        
        $solicitante_id = $_SESSION['user_id'];
        $solicitante_rol = $_SESSION['user_rol']; // Viene de columna ADQUISICIONES
        
        // Datos generales de la orden
        $nombre_orden = $_POST['nombre_orden'];
        $plazo_maximo = $_POST['plazo_maximo'];
        $tipo_compra = $_POST['tipo_compra'];
        $motivo_compra = $_POST['motivo_compra'];
        
        // Datos de imputación presupuestaria
        $presupuesto = $_POST['presupuesto'];
        $cuenta_presupuestaria = $_POST['cuenta_presupuestaria'];
        $subprog = $_POST['subprog'];
        $centro_costos = $_POST['centro_costos'];

        // Totales calculados (vienen ocultos desde el frontend)
        $valor_neto = $_POST['valor_neto_hidden'];
        $iva = $_POST['iva_hidden'];
        $valor_total = $_POST['valor_total_hidden'];
        
        // Lógica especial para tipos de compra que usan presupuesto total
        if ($tipo_compra === 'Compra Ágil' || $tipo_compra === 'Licitación Pública' || $tipo_compra === 'Licitación Privada') {
            $presupuesto_num = (float)$presupuesto;
            $valor_neto = $presupuesto_num;
            $valor_total = $presupuesto_num;
            $iva = 0;
        }

        // Determinar el estado inicial según el rol del solicitante
        // Se mantiene tu lógica original
        $estado = ($solicitante_rol === 'Director') ? 'Pend. Firma Director' : 'Pend. Mi Firma';

        // ID de licitación (opcional)
        $id_licitacion = isset($_POST['id_licitacion_publica']) ? $_POST['id_licitacion_publica'] : null;

        // --- 1. INSERTAR LA CABECERA DE LA ORDEN ---
        
        $sql_orden = "INSERT INTO Orden_Pedido (Solicitante_Id, Nombre_Orden, Fecha_Creacion, Tipo_Compra, Id_Licitacion, Presupuesto, Subprog, Centro_Costos, Plazo_maximo, Iva, Valor_neto, Valor_total, Estado, Motivo_Compra, Cuenta_Presupuestaria) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_orden = $conn->prepare($sql_orden);
        // "isssssssdddsss" -> is integer, s string, d double
        $stmt_orden->bind_param("isssssssdddsss", $solicitante_id, $nombre_orden, $tipo_compra, $id_licitacion, $presupuesto, $subprog, $centro_costos, $plazo_maximo, $iva, $valor_neto, $valor_total, $estado, $motivo_compra, $cuenta_presupuestaria);
        
        $stmt_orden->execute();
        
        // Obtener el ID de la orden recién creada
        $orden_id_nueva = $conn->insert_id; 

        // -----------------------------------------------------------------
        // 2. LÓGICA DE ARCHIVOS (Tu función original)
        // -----------------------------------------------------------------
        $uploadDir = '../uploads/'; 
        // --- AGREGAR ESTO: Crear carpeta si no existe ---
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        // ------------------------------------------------
        function subirArchivosMultiples($inputName, $tipoDoc, $ordenId, $conn, $dir, $maxFiles = 3) {
            if (isset($_FILES[$inputName]) && is_array($_FILES[$inputName]['name'])) {
                $files = $_FILES[$inputName];
                $totalSubidos = count($files['name']);
                $limite = min($totalSubidos, $maxFiles);

                for ($i = 0; $i < $limite; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $nombreOriginal = basename($files['name'][$i]);
                        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                        $nombreGuardado = "orden_{$ordenId}_{$tipoDoc}_{$i}_" . time() . "." . $ext;
                        $rutaDestino = $dir . $nombreGuardado;

                        if (move_uploaded_file($files['tmp_name'][$i], $rutaDestino)) {
                            // Guardamos ruta relativa "uploads/..."
                            $rutaWeb = 'uploads/' . $nombreGuardado;
                            $sql_archivo = "INSERT INTO Orden_Archivos (Orden_Id, Nombre_Archivo, Nombre_Original, Tipo_Documento, Ruta_Archivo) VALUES (?, ?, ?, ?, ?)";
                            $stmt_arch = $conn->prepare($sql_archivo);
                            $stmt_arch->bind_param("issss", $ordenId, $nombreGuardado, $nombreOriginal, $tipoDoc, $rutaWeb);
                            $stmt_arch->execute();
                            $stmt_arch->close();
                        }
                    }
                }
            }
        }

        // Procesar archivos Trato Directo
        if ($tipo_compra === 'Trato Directo') {
            subirArchivosMultiples('cotizacion_file', 'Cotizacion', $orden_id_nueva, $conn, $uploadDir, 3);
            subirArchivosMultiples('memorando_file', 'Memorando', $orden_id_nueva, $conn, $uploadDir, 3);
            subirArchivosMultiples('decreto_file', 'Decreto', $orden_id_nueva, $conn, $uploadDir, 3);
        }

        // Procesar archivos adicionales
        subirArchivosMultiples('archivos_adicionales', 'Adicional', $orden_id_nueva, $conn, $uploadDir, 3);
        
        // -----------------------------------------------------------------
        // 3. INSERTAR ÍTEMS
        // -----------------------------------------------------------------
        
        $item_cantidades = $_POST['item_cantidad'];
        $item_nombres = $_POST['item_nombre'];
        $item_unitarios = $_POST['item_v_unitario'];
        $item_codigos = isset($_POST['item_codigo']) ? $_POST['item_codigo'] : array_fill(0, count($item_cantidades), null);

        $sql_item = "INSERT INTO Orden_Item (Orden_Id, Nombre_producto_servicio, Codigo_Producto, Cantidad, Valor_Unitario, Valor_Total) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

        for ($i = 0; $i < count($item_cantidades); $i++) {
            $cantidad = (int)$item_cantidades[$i];
            $nombre = $item_nombres[$i];
            $codigo = isset($item_codigos[$i]) ? $item_codigos[$i] : ''; 
            $v_unitario = (float)$item_unitarios[$i];
            $v_total_linea = $cantidad * $v_unitario; 
            
            $stmt_item->bind_param("issidd", $orden_id_nueva, $nombre, $codigo, $cantidad, $v_unitario, $v_total_linea);
            $stmt_item->execute();
        }

        // Confirmar transacción
        $conn->commit();
        
        $stmt_orden->close();
        $stmt_item->close();
        $conn->close();

        header("Location: ../index.php?creacion=exito");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        die("Error al crear la orden: " . $e->getMessage());
    }
} else {
    header("Location: ../crear_orden.php");
    exit;
}
?>