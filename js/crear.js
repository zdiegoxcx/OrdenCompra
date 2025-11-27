document.addEventListener('DOMContentLoaded', () => {

    // --- SELECCIÓN DE ELEMENTOS ---
    const tablaItemsBody = document.querySelector("#items-table tbody");
    const btnAgregarItem = document.querySelector(".btn-add-item");
    const itemsTable = document.getElementById('items-table');

    const tipoCompraSelect = document.getElementById('tipo-compra');
    const presupuestoInput = document.getElementById('presupuesto');

    // Totales
    const labelValorNeto = document.getElementById('label-valor-neto');
    const inputValorNeto = document.getElementById('input-valor-neto');
    const labelIva = document.getElementById('label-iva');
    const inputIva = document.getElementById('input-iva');
    const labelValorTotal = document.getElementById('label-valor-total');
    const inputValorTotal = document.getElementById('input-valor-total');
    const valorNetoHidden = document.getElementById("valor_neto_hidden");
    const ivaHidden = document.getElementById("iva_hidden");
    const valorTotalHidden = document.getElementById("valor_total_hidden");

    // Trato Directo
    const fieldsetTratoDirecto = document.getElementById('fieldset-trato-directo');
    const inputCotizacion = document.getElementById('cotizacion_file');
    const inputMemorando = document.getElementById('memorando_file');
    const inputDecreto = document.getElementById('decreto_file');
    
    // Licitación
    const fieldsetLicitacion = document.getElementById('fieldset-licitacion-publica');
    const inputLicitacion = document.getElementById('id_licitacion_publica');

    // --- FUNCIÓN CALCULAR TOTALES (Igual que antes) ---
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

        inputValorNeto.value = valorNetoTotal.toLocaleString('es-CL');
        inputIva.value = iva.toLocaleString('es-CL');
        inputValorTotal.value = valorTotal.toLocaleString('es-CL');
        valorNetoHidden.value = valorNetoTotal;
        ivaHidden.value = iva;
        valorTotalHidden.value = valorTotal;
    }

    // --- MANEJO DE VISIBILIDAD DE COLUMNAS ---
    function manejarTipoCompra() {
        const tipoSeleccionado = tipoCompraSelect.value;
        const valorPresupuestoNum = parseFloat(presupuestoInput.value) || 0;
        const valorPresupuestoStr = valorPresupuestoNum.toLocaleString('es-CL');

        const colsUnitario = itemsTable.querySelectorAll('.col-v-unitario');
        const colsTotalLinea = itemsTable.querySelectorAll('.col-total-linea');
        
        // **NUEVO: Seleccionar todas las celdas de ID Producto**
        const colsIdProducto = itemsTable.querySelectorAll('.col-id-producto');

        // 1. Lógica Suministro (Igual)
        if (tipoSeleccionado === 'Suministro') {
            fieldsetLicitacion.style.display = 'block';
            inputLicitacion.required = true;
        } else {
            fieldsetLicitacion.style.display = 'none';
            inputLicitacion.required = false;
        }

        // 2. Lógica Trato Directo (Igual)
        if (tipoSeleccionado === 'Trato Directo') {
            fieldsetTratoDirecto.style.display = 'block';
            inputCotizacion.required = true;
            inputMemorando.required = true;
            inputDecreto.required = true;
        } else {
            fieldsetTratoDirecto.style.display = 'none';
            inputCotizacion.required = false;
            inputMemorando.required = false;
            inputDecreto.required = false;
        }

        // 3. **NUEVO: Lógica Convenio Marco (ID Producto)**
        if (tipoSeleccionado === 'Convenio Marco') {
            // MOSTRAR la columna ID Producto
            colsIdProducto.forEach(col => col.style.display = 'table-cell');
            
            // Hacer requeridos los inputs de esa columna que sean visibles
            tablaItemsBody.querySelectorAll('input[name="item_codigo[]"]').forEach(input => input.required = true);
        } else {
            // OCULTAR la columna ID Producto
            colsIdProducto.forEach(col => col.style.display = 'none');
            
            // Quitar required
            tablaItemsBody.querySelectorAll('input[name="item_codigo[]"]').forEach(input => input.required = false);
        }

        // 4. Lógica Compra Ágil (Presupuesto vs Items) (Igual)
        if (tipoSeleccionado === 'Compra Ágil' || tipoSeleccionado === 'Licitación Pública' || tipoSeleccionado === 'Licitación Privada') {
            colsUnitario.forEach(col => col.style.display = 'none');
            colsTotalLinea.forEach(col => col.style.display = 'none');

            labelValorNeto.textContent = 'Presupuesto disponible:';
            inputValorNeto.value = valorPresupuestoStr;
            labelIva.style.display = 'none';
            inputIva.style.display = 'none';
            labelValorTotal.textContent = 'Total :';
            inputValorTotal.value = valorPresupuestoStr;

            valorNetoHidden.value = valorPresupuestoNum;
            ivaHidden.value = 0;
            valorTotalHidden.value = valorPresupuestoNum;
        } else {
            // Modo Normal (Reset)
            colsUnitario.forEach(col => col.style.display = '');
            colsTotalLinea.forEach(col => col.style.display = '');

            labelValorNeto.textContent = 'Valor Neto:';
            labelIva.style.display = '';
            inputIva.style.display = '';
            labelValorTotal.textContent = 'Valor Total:';
            
            calcularTotales();
        }
    }

    // --- AGREGAR FILA ---
    function agregarFila() {
        const nuevaFila = document.createElement('tr');
        
        // Creamos la fila incluyendo la celda nueva de ID Producto
        nuevaFila.innerHTML = `
            <td><input type="number" name="item_cantidad[]" class="input-calc" value="1" min="1" required></td>
            
            <td class="col-id-producto">
                <input type="text" name="item_codigo[]" placeholder="ID CM">
            </td>

            <td><input type="text" name="item_nombre[]" required></td>
            <td class="col-v-unitario"><input type="number" name="item_v_unitario[]" class="input-v-unitario input-calc" value="0" min="0" required></td>
            <td class="col-total-linea"><span class="total-linea">0</span></td>
            <td><button type="button" class="accion-btn btn-delete-item">🗑️</button></td>
        `;
        
        tablaItemsBody.appendChild(nuevaFila);

        // **IMPORTANTE**: Al agregar fila, debemos re-ejecutar la lógica 
        // para saber si ocultar o mostrar las columnas nuevas según lo seleccionado
        manejarTipoCompra();
    }

    // --- LISTENERS ---
    btnAgregarItem.addEventListener('click', agregarFila);

    tablaItemsBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete-item')) {
            if (tablaItemsBody.querySelectorAll("tr").length <= 1) {
                alert("Debe haber al menos un ítem.");
                return;
            }
            e.target.closest('tr').remove();
            
            const tipo = tipoCompraSelect.value;
            if (tipo !== 'Compra Ágil' && tipo !== 'Licitación Pública') {
                calcularTotales();
            }
        }
    });

    tablaItemsBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('input-calc')) {
            const tipo = tipoCompraSelect.value;
            if (tipo !== 'Compra Ágil' && tipo !== 'Licitación Pública') {
                calcularTotales();
            }
        }
    });

    tipoCompraSelect.addEventListener('change', manejarTipoCompra);

    presupuestoInput.addEventListener('input', () => {
        const tipo = tipoCompraSelect.value;
        if (tipo === 'Compra Ágil' || tipo === 'Licitación Pública') {
            const valor = parseFloat(presupuestoInput.value) || 0;
            inputValorNeto.value = valor.toLocaleString('es-CL');
            inputValorTotal.value = valor.toLocaleString('es-CL');
            valorNetoHidden.value = valor;
            valorTotalHidden.value = valor;
        }
    });

    // Iniciar
    manejarTipoCompra();
});