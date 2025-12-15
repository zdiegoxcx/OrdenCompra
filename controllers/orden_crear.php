<?php
// controllers/orden_crear.php

// 1. Iniciar la sesión para verificar autenticación
session_start();

// Si el usuario no ha iniciado sesión, denegar el acceso (403 Forbidden)
if (!isset($_SESSION['user_id'])) { 
    header("HTTP/1.1 403 Forbidden"); 
    exit; 
}

// 2. Verificar que la solicitud sea POST (envío de formulario)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CAMBIO: Incluir la conexión a la base de datos (subiendo un nivel ../)
    include '../config/db.php';
    
    // Iniciar transacción para asegurar que la orden y sus items se guarden juntos o ninguno
    $conn->begin_transaction();

    try {
        // --- RECOPILACIÓN DE DATOS DEL FORMULARIO Y SESIÓN ---
        
        $solicitante_id = $_SESSION['user_id'];
        $solicitante_rol = $_SESSION['user_rol'];
        
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
        
        // Determinar el estado inicial según el rol del solicitante
        // Si es Director, pasa directo a firma de Alcalde (o estado superior). Si no, firma su jefe (Director).
        // NOTA: Ajusta esta lógica según tu flujo exacto. Aquí asumo: 
        // Director crea -> 'Pend. Firma Director' (quizás auto-aprobada?) o 'Pend. Firma Alcalde'
        // Profesional crea -> 'Pend. Mi Firma' (para que él mismo firme primero)
        $estado = ($solicitante_rol === 'Director') ? 'Pend. Firma Director' : 'Pend. Mi Firma';

        // ID de licitación (opcional, solo si aplica)
        $id_licitacion = isset($_POST['id_licitacion_publica']) ? $_POST['id_licitacion_publica'] : null;

        // --- 1. INSERTAR LA CABECERA DE LA ORDEN ---
        
        $sql_orden = "INSERT INTO Orden_Pedido (Solicitante_Id, Nombre_Orden, Fecha_Creacion, Tipo_Compra, Id_Licitacion, Presupuesto, Subprog, Centro_Costos, Plazo_maximo, Iva, Valor_neto, Valor_total, Estado, Motivo_Compra, Cuenta_Presupuestaria) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_orden = $conn->prepare($sql_orden);
        // "isssssssdddsss" define los tipos de datos: i=integer, s=string, d=double
        $stmt_orden->bind_param("isssssssdddsss", $solicitante_id, $nombre_orden, $tipo_compra, $id_licitacion, $presupuesto, $subprog, $centro_costos, $plazo_maximo, $iva, $valor_neto, $valor_total, $estado, $motivo_compra, $cuenta_presupuestaria);
        
        $stmt_orden->execute();
        
        // Obtener el ID de la orden recién creada para usarlo en items y archivos
        $orden_id_nueva = $conn->insert_id; 

        // -----------------------------------------------------------------
        // 2. LÓGICA DE ARCHIVOS (Múltiples archivos por tipo con límite)
        // -----------------------------------------------------------------

        // CAMBIO: Definir directorio de subida (subiendo un nivel desde controllers)
        // La carpeta 'uploads' está en la raíz, así que es '../uploads/'
        $uploadDir = '../uploads/'; 

        /**
         * Función auxiliar para procesar la subida de múltiples archivos
         * Se encarga de mover el archivo y guardar el registro en la BD
         */
        function subirArchivosMultiples($inputName, $tipoDoc, $ordenId, $conn, $dir, $maxFiles = 3) {
            // Verificar si el input existe y contiene archivos
            if (isset($_FILES[$inputName]) && is_array($_FILES[$inputName]['name'])) {
                
                $files = $_FILES[$inputName];
                $totalSubidos = count($files['name']);
                
                // Limitar la cantidad de archivos a procesar
                $limite = min($totalSubidos, $maxFiles);

                for ($i = 0; $i < $limite; $i++) {
                    // Verificar que no hubo error en la subida (código 0)
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        
                        $nombreOriginal = basename($files['name'][$i]);
                        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                        
                        // Generar nombre único para evitar colisiones: orden_ID_TIPO_indice_timestamp.ext
                        $nombreGuardado = "orden_{$ordenId}_{$tipoDoc}_{$i}_" . time() . "." . $ext;
                        $rutaDestino = $dir . $nombreGuardado;

                        // Mover archivo de temp a destino final
                        if (move_uploaded_file($files['tmp_name'][$i], $rutaDestino)) {
                            
                            // Guardar referencia en Base de Datos (Tabla Orden_Archivos)
                            // CAMBIO: Guardamos la ruta relativa para usarla desde la web ("uploads/nombre.ext")
                            // Quitamos el "../" para que al mostrar en HTML sea válido desde la raíz
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

        // A. Procesar archivos específicos de Trato Directo
        if ($tipo_compra === 'Trato Directo') {
            subirArchivosMultiples('cotizacion_file', 'Cotizacion', $orden_id_nueva, $conn, $uploadDir, 3);
            subirArchivosMultiples('memorando_file', 'Memorando', $orden_id_nueva, $conn, $uploadDir, 3);
            subirArchivosMultiples('decreto_file', 'Decreto', $orden_id_nueva, $conn, $uploadDir, 3);
        }

        // B. Procesar archivos adicionales generales
        subirArchivosMultiples('archivos_adicionales', 'Adicional', $orden_id_nueva, $conn, $uploadDir, 3);
        
        // -----------------------------------------------------------------
        // 3. INSERTAR ÍTEMS (Detalle de productos/servicios)
        // -----------------------------------------------------------------
        
        // Recoger arrays de datos de los items
        $item_cantidades = $_POST['item_cantidad'];
        $item_nombres = $_POST['item_nombre'];
        $item_unitarios = $_POST['item_v_unitario'];
        
        // Recoger códigos de producto (Convenio Marco). Si no existen, llenar con null.
        $item_codigos = isset($_POST['item_codigo']) ? $_POST['item_codigo'] : array_fill(0, count($item_cantidades), null);

        // Preparar sentencia SQL para insertar ítems
        $sql_item = "INSERT INTO Orden_Item (Orden_Id, Nombre_producto_servicio, Codigo_Producto, Cantidad, Valor_Unitario, Valor_Total) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

        // Iterar sobre cada ítem recibido
        for ($i = 0; $i < count($item_cantidades); $i++) {
            $cantidad = (int)$item_cantidades[$i];
            $nombre = $item_nombres[$i];
            $codigo = isset($item_codigos[$i]) ? $item_codigos[$i] : ''; 
            $v_unitario = (float)$item_unitarios[$i];
            $v_total_linea = $cantidad * $v_unitario; 
            
            // Vincular parámetros y ejecutar inserción por cada fila
            $stmt_item->bind_param("issidd", $orden_id_nueva, $nombre, $codigo, $cantidad, $v_unitario, $v_total_linea);
            $stmt_item->execute();
        }

        // --- FINALIZACIÓN EXITOSA ---
        
        // Confirmar transacción (guardar todo en BD)
        $conn->commit();
        
        // Cerrar sentencias y conexión
        $stmt_orden->close();
        $stmt_item->close();
        $conn->close();

        // Redirigir al usuario al dashboard con mensaje de éxito
        // CAMBIO: Salir de 'controllers' para ir a index.php en raíz
        header("Location: ../index.php?creacion=exito");
        exit;

    } catch (Exception $e) {
        // --- MANEJO DE ERRORES ---
        
        // Si algo falla, revertir transacción (no guardar nada)
        $conn->rollback();
        $conn->close();
        // Mostrar mensaje de error y detener ejecución
        die("Error al crear la orden: " . $e->getMessage());
    }
} else {
    // Si intentan entrar directo a este archivo sin POST, redirigir al formulario
    // CAMBIO: Salir de 'controllers' para ir a crear_orden.php en raíz
    header("Location: ../crear_orden.php");
    exit;
}
?>