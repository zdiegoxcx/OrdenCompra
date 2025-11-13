document.addEventListener('DOMContentLoaded', () => {

    // --- SELECCIÓN DE ELEMENTOS (DE AMBAS PARTES) ---
    
    // Parte 1: Tabla de ítems
    const tablaItemsBody = document.querySelector("#items-table tbody");
    const btnAgregarItem = document.querySelector(".btn-add-item");
    const itemsTable = document.getElementById('items-table'); // Usado por ambas

    // Parte 2: Tipo de Compra y Presupuesto
    const tipoCompraSelect = document.getElementById('tipo-compra');
    const presupuestoInput = document.getElementById('presupuesto');

    // Parte 3: Sección de Totales (Usada por ambas lógicas)
    const labelValorNeto = document.getElementById('label-valor-neto');
    const inputValorNeto = document.getElementById('input-valor-neto');
    const labelIva = document.getElementById('label-iva');
    const inputIva = document.getElementById('input-iva');
    const labelValorTotal = document.getElementById('label-valor-total');
    const inputValorTotal = document.getElementById('input-valor-total');

    // Inputs Ocultos (¡Muy importantes para PHP!)
    const valorNetoHidden = document.getElementById("valor_neto_hidden");
    const ivaHidden = document.getElementById("iva_hidden");
    const valorTotalHidden = document.getElementById("valor_total_hidden");

    // --- NUEVOS ELEMENTOS (Trato Directo) ---
    const fieldsetTratoDirecto = document.getElementById('fieldset-trato-directo');
    const inputCotizacion = document.getElementById('cotizacion_file');
    const inputMemorando = document.getElementById('memorando_file');
    const inputDecreto = document.getElementById('decreto_file');

    
    // --- NUEVOS ELEMENTOS (Licitación Pública) ---
    const fieldsetLicitacion = document.getElementById('fieldset-licitacion-publica');
    const inputLicitacion = document.getElementById('id_licitacion_publica');


    // --- FUNCIÓN 1: CALCULAR TOTALES (DESDE LOS ÍTEMS) ---
    function calcularTotales() {
        let valorNetoTotal = 0;
        
        tablaItemsBody.querySelectorAll("tr").forEach(fila => {
            const inputCantidad = fila.querySelector(".input-calc");
            const inputValorUnitario = fila.querySelector(".input-v-unitario");
            const spanTotalLinea = fila.querySelector(".total-linea");

            const cantidad = parseFloat(inputCantidad.value) || 0;
            const valorUnitario = parseFloat(inputValorUnitario.value) || 0;
            
            const totalLinea = cantidad * valorUnitario;
            
            spanTotalLinea.textContent = totalLinea.toLocaleString('es-CL');
            valorNetoTotal += totalLinea;
        });

        const iva = Math.round(valorNetoTotal * 0.19);
        const valorTotal = valorNetoTotal + iva;

        // Actualizar campos 'disabled' (para ver)
        inputValorNeto.value = valorNetoTotal.toLocaleString('es-CL');
        inputIva.value = iva.toLocaleString('es-CL');
        inputValorTotal.value = valorTotal.toLocaleString('es-CL');

        // Actualizar campos 'hidden' (para enviar)
        valorNetoHidden.value = valorNetoTotal;
        ivaHidden.value = iva;
        valorTotalHidden.value = valorTotal;
    }

    // --- FUNCIÓN 2: MANEJAR LÓGICA DE TIPO DE COMPRA ---
    function manejarTipoCompra() {
        const tipoSeleccionado = tipoCompraSelect.value;
        const valorPresupuestoNum = parseFloat(presupuestoInput.value) || 0;
        const valorPresupuestoStr = valorPresupuestoNum.toLocaleString('es-CL');

        // **FIX IMPORTANTE**: Seleccionar columnas *dentro* de la función
        // para que afecte también a las filas nuevas.
        const colsUnitario = itemsTable.querySelectorAll('.col-v-unitario');
        const colsTotalLinea = itemsTable.querySelectorAll('.col-total-linea');


        if (tipoSeleccionado === 'Suministro') {
            fieldsetLicitacion.style.display = 'block'; // Mostrar
            inputLicitacion.required = true;            // Hacer obligatorio
        } else {
            fieldsetLicitacion.style.display = 'none';  // Ocultar
            inputLicitacion.required = false;           // No hacer obligatorio
        }

        if (tipoSeleccionado === 'Trato Directo') {
            fieldsetTratoDirecto.style.display = 'block'; // Mostrar
            inputCotizacion.required = true;
            inputMemorando.required = true;
            inputDecreto.required = true;
        } else {
            fieldsetTratoDirecto.style.display = 'none'; // Ocultar
            inputCotizacion.required = false;
            inputMemorando.required = false;
            inputDecreto.required = false;
        }


        if (tipoSeleccionado === 'Compra Ágil' || tipoSeleccionado === 'Licitación Pública' || tipoSeleccionado === 'Licitación Privada') {
            // Lógica para COMPRA ÁGIL / LICITACIÓN
            
            // 1. Ocultar columnas
            colsUnitario.forEach(col => col.style.display = 'none');
            colsTotalLinea.forEach(col => col.style.display = 'none');

            // 2. Modificar sección de Totales
            labelValorNeto.textContent = 'Presupuesto disponible:';
            inputValorNeto.value = valorPresupuestoStr;
            labelIva.style.display = 'none';
            inputIva.style.display = 'none';
            labelValorTotal.textContent = 'Total :';
            inputValorTotal.value = valorPresupuestoStr;

            // 3. Actualizar hiddens para modo Presupuesto
            valorNetoHidden.value = valorPresupuestoNum;
            ivaHidden.value = 0; // No hay IVA calculado
            valorTotalHidden.value = valorPresupuestoNum;

        } else {
            // Lógica para OTRA COMPRA (Resetear)

            // 1. Mostrar columnas
            colsUnitario.forEach(col => col.style.display = '');
            colsTotalLinea.forEach(col => col.style.display = '');

            // 2. Resetear sección de Totales
            labelValorNeto.textContent = 'Valor Neto:';
            labelIva.style.display = '';
            labelValorTotal.textContent = 'Valor Total:';
            
            // 3. Recalcular desde los ítems
            calcularTotales();
        }
    }

    // --- FUNCIÓN 3: AGREGAR FILA DE ÍTEM ---
    function agregarFila() {
        const nuevaFila = document.createElement('tr');
        nuevaFila.innerHTML = `
            <td><input type="number" name="item_cantidad[]" class="input-calc" value="1" min="1" required></td>
            <td><input type="text" name="item_nombre[]" required></td>
            <td class="col-v-unitario"><input type="number" name="item_v_unitario[]" class="input-v-unitario input-calc" value="0" min="0" required></td>
            <td class="col-total-linea"><span class="total-linea">0</span></td>
            <td><button type="button" class="accion-btn btn-delete-item">🗑️</button></td>
        `;

        // **FIX IMPORTANTE**: Ocultar las columnas en la fila nueva
        // si estamos en modo "Compra Ágil".
        const tipoActual = tipoCompraSelect.value;
        if (tipoActual === 'Compra Ágil' || tipoActual === 'Licitación Pública' || tipoActual === 'Licitación Privada') {
            nuevaFila.querySelector('.col-v-unitario').style.display = 'none';
            nuevaFila.querySelector('.col-total-linea').style.display = 'none';
        }
        
        tablaItemsBody.appendChild(nuevaFila);
    }

    // --- EVENT LISTENERS (Controladores) ---

    // 1. Clic en "Agregar Ítem"
    btnAgregarItem.addEventListener('click', agregarFila);

    // 2. Clic en "Borrar Ítem" (usando delegación de eventos)
    tablaItemsBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete-item')) {
            if (tablaItemsBody.querySelectorAll("tr").length <= 1) {
                alert("Debe haber al menos un ítem en la orden.");
                return;
            }
            e.target.closest('tr').remove();
            
            // Solo recalcula si NO es Compra Ágil
            const tipo = tipoCompraSelect.value;
            if (tipo !== 'Compra Ágil' && tipo !== 'Licitación Pública' && tipo !== 'Licitación Privada') {
                calcularTotales();
            }
        }
    });

    // 3. Al cambiar un valor en la tabla (Cantidad o V. Unitario)
    tablaItemsBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('input-calc')) {
            // Solo recalcula si NO es Compra Ágil
            const tipo = tipoCompraSelect.value;
            if (tipo !== 'Compra Ágil' && tipo !== 'Licitación Pública' && tipo !== 'Licitación Privada') {
                calcularTotales();
            }
        }
    });

    // 4. Cuando cambia el "Tipo de Compra"
    tipoCompraSelect.addEventListener('change', manejarTipoCompra);

    // 5. Cuando el usuario cambia el presupuesto (para modo Compra Ágil)
    presupuestoInput.addEventListener('input', () => {
        const tipo = tipoCompraSelect.value;
        if (tipo === 'Compra Ágil' || tipo === 'Licitación Pública' || tipo === 'Licitación Privada') {
            const valorPresupuestoNum = parseFloat(presupuestoInput.value) || 0;
            const valorPresupuestoStr = valorPresupuestoNum.toLocaleString('es-CL');

            inputValorNeto.value = valorPresupuestoStr;
            inputValorTotal.value = valorPresupuestoStr;
            
            // Actualizar hiddens también
            valorNetoHidden.value = valorPresupuestoNum;
            valorTotalHidden.value = valorPresupuestoNum;
        }
    });

    // --- INICIALIZACIÓN ---
    // Ejecutar ambas funciones al cargar la página para establecer el estado inicial
    manejarTipoCompra();
    // (manejarTipoCompra ya llama a calcularTotales si es necesario)
});