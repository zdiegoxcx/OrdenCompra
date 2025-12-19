document.addEventListener('DOMContentLoaded', () => {

    // --- SELECCIÓN DE ELEMENTOS ---
    const tablaItemsBody = document.querySelector("#items-table tbody");
    const btnAgregarItem = document.querySelector(".btn-add-item");
    const itemsTable = document.getElementById('items-table');

    const tipoCompraSelect = document.getElementById('tipo-compra');
    const presupuestoInput = document.getElementById('presupuesto');

    // Totales y Checkbox IVA
    const labelValorNeto = document.getElementById('label-valor-neto');
    const inputValorNeto = document.getElementById('input-valor-neto');
    const labelIva = document.getElementById('label-iva');
    const inputIva = document.getElementById('input-iva');
    const checkAplicaIva = document.getElementById('aplica_iva'); // <--- NUEVO
    const labelValorTotal = document.getElementById('label-valor-total');
    const inputValorTotal = document.getElementById('input-valor-total');
    
    // Hidden fields
    const valorNetoHidden = document.getElementById("valor_neto_hidden");
    const ivaHidden = document.getElementById("iva_hidden");
    const valorTotalHidden = document.getElementById("valor_total_hidden");

    // Trato Directo & Licitación (Para visibilidad)
    const fieldsetTratoDirecto = document.getElementById('fieldset-trato-directo');
    const inputCotizacion = document.getElementById('cotizacion_file');
    const inputMemorando = document.getElementById('memorando_file');
    const inputDecreto = document.getElementById('decreto_file');
    
    const fieldsetLicitacion = document.getElementById('fieldset-licitacion-publica');
    const inputLicitacion = document.getElementById('id_licitacion_publica');

    // --- FUNCIÓN CALCULAR TOTALES (MEJORADA CON IVA) ---
    function calcularTotales() {
        let valorNetoTotal = 0;
        
        // Sumar líneas
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

        // Calcular IVA (Solo si el checkbox está marcado)
        let iva = 0;
        if (checkAplicaIva && checkAplicaIva.checked) {
            iva = Math.round(valorNetoTotal * 0.19);
        }

        const valorTotal = valorNetoTotal + iva;

        // Mostrar en inputs
        inputValorNeto.value = valorNetoTotal.toLocaleString('es-CL');
        inputIva.value = iva.toLocaleString('es-CL');
        inputValorTotal.value = valorTotal.toLocaleString('es-CL');
        
        // Guardar en hiddens
        valorNetoHidden.value = valorNetoTotal;
        ivaHidden.value = iva;
        valorTotalHidden.value = valorTotal;
    }


    // --- LÓGICA DE CARGA ACUMULATIVA DE ARCHIVOS (INTACTA) ---
    function habilitarCargaAcumulativa(inputId, listaId, maxArchivos) {
        const input = document.getElementById(inputId);
        const lista = document.getElementById(listaId);
        if (!input || !lista) return;

        let archivosAcumulados = [];

        input.addEventListener('change', (e) => {
            const nuevosArchivos = Array.from(input.files);
            if (archivosAcumulados.length + nuevosArchivos.length > maxArchivos) {
                alert(`Solo puedes subir un máximo de ${maxArchivos} archivos.`);
                actualizarInputReal(); 
                return;
            }
            nuevosArchivos.forEach(file => {
                if (!archivosAcumulados.some(f => f.name === file.name)) {
                    archivosAcumulados.push(file);
                }
            });
            actualizarInputReal();
            renderizarLista();
        });

        function actualizarInputReal() {
            const dt = new DataTransfer();
            archivosAcumulados.forEach(file => dt.items.add(file));
            input.files = dt.files;
        }

        function renderizarLista() {
            lista.innerHTML = '';
            if (archivosAcumulados.length === 0) return;
            const ul = document.createElement('ul');
            ul.style.listStyle = 'none'; ul.style.padding = '0'; ul.style.marginTop = '5px';
            archivosAcumulados.forEach((file, index) => {
                const li = document.createElement('li');
                li.style.cssText = "background:#f8f9fa; border:1px solid #ddd; padding:5px; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center; border-radius:4px;";
                li.innerHTML = `<span style="font-size:0.9em;">${file.name}</span><button type="button" style="border:none; background:transparent; color:red; cursor:pointer;">❌</button>`;
                li.querySelector('button').onclick = (e) => {
                    e.preventDefault();
                    archivosAcumulados.splice(index, 1);
                    actualizarInputReal();
                    renderizarLista();
                };
                ul.appendChild(li);
            });
            lista.appendChild(ul);
        }
    }

    // Inicializar cargas
    habilitarCargaAcumulativa('cotizacion_file', 'lista-cotizacion', 3);
    habilitarCargaAcumulativa('memorando_file', 'lista-memorando', 3);
    habilitarCargaAcumulativa('decreto_file', 'lista-decreto', 3);
    habilitarCargaAcumulativa('archivos_adicionales', 'lista-adicionales', 3);


    // --- MANEJO DE VISIBILIDAD (INTACTO + Reset de IVA) ---
    function manejarTipoCompra() {
        const tipoSeleccionado = tipoCompraSelect.value;
        const valorPresupuestoNum = parseFloat(presupuestoInput.value) || 0;
        const valorPresupuestoStr = valorPresupuestoNum.toLocaleString('es-CL');

        const colsUnitario = itemsTable.querySelectorAll('.col-v-unitario');
        const colsTotalLinea = itemsTable.querySelectorAll('.col-total-linea');
        const colsIdProducto = itemsTable.querySelectorAll('.col-id-producto');

        // Suministro
        if (tipoSeleccionado === 'Suministro') {
            fieldsetLicitacion.style.display = 'block';
            inputLicitacion.required = true;
        } else {
            fieldsetLicitacion.style.display = 'none';
            inputLicitacion.required = false;
        }

        // Trato Directo
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

        // Convenio Marco (ID Producto)
        if (tipoSeleccionado === 'Convenio Marco') {
            colsIdProducto.forEach(col => col.style.display = 'table-cell');
            tablaItemsBody.querySelectorAll('input[name="item_codigo[]"]').forEach(input => input.required = true);
        } else {
            colsIdProducto.forEach(col => col.style.display = 'none');
            tablaItemsBody.querySelectorAll('input[name="item_codigo[]"]').forEach(input => input.required = false);
        }

        // Compra Ágil / Licitaciones (Manejo de Presupuesto)
        if (tipoSeleccionado === 'Compra Ágil' || tipoSeleccionado === 'Licitación Pública' || tipoSeleccionado === 'Licitación Privada') {
            colsUnitario.forEach(col => col.style.display = 'none');
            colsTotalLinea.forEach(col => col.style.display = 'none');

            labelValorNeto.textContent = 'Presupuesto disponible:';
            inputValorNeto.value = valorPresupuestoStr;
            
            // Ocultar IVA visualmente ya que manda el presupuesto
            labelIva.style.display = 'none';
            inputIva.style.display = 'none';
            if(checkAplicaIva.parentElement) checkAplicaIva.parentElement.style.display = 'none'; // Ocultar check

            labelValorTotal.textContent = 'Total :';
            inputValorTotal.value = valorPresupuestoStr;

            valorNetoHidden.value = valorPresupuestoNum;
            ivaHidden.value = 0;
            valorTotalHidden.value = valorPresupuestoNum;
        } else {
            // Modo Normal
            colsUnitario.forEach(col => col.style.display = '');
            colsTotalLinea.forEach(col => col.style.display = '');

            labelValorNeto.textContent = 'Valor Neto:';
            
            // Mostrar IVA
            labelIva.style.display = '';
            inputIva.style.display = '';
            if(checkAplicaIva.parentElement) checkAplicaIva.parentElement.style.display = 'flex'; // Mostrar check

            labelValorTotal.textContent = 'Valor Total:';
            
            calcularTotales();
        }
    }

    // --- AGREGAR FILA ---
    function agregarFila() {
        const nuevaFila = document.createElement('tr');
        // Se mantiene tu HTML de fila
        nuevaFila.innerHTML = `
            <td>
                <input type="number" name="item_cantidad[]" class="input-calc" value="1" min="1" max="999999" 
                    oninput="if(this.value.length > 6) this.value = this.value.slice(0, 6);" 
                    onkeydown="if(['e', 'E', '.', '-', '+'].includes(event.key)) event.preventDefault();"
                    required>
            </td>
            <td class="col-id-producto">
                <input type="text" name="item_codigo[]" placeholder="ID CM" maxlength="30">
            </td>
            <td>
                <input type="text" name="item_nombre[]" 
                 maxlength="100" required>
            </td>
            <td class="col-v-unitario">
                <input type="number" name="item_v_unitario[]" class="input-v-unitario input-calc" value="0" min="0" step="1" 
                    onkeydown="if(['e', 'E', '.', '-', '+'].includes(event.key)) event.preventDefault();"
                    oninput="if(this.value.length > 15) this.value = this.value.slice(0, 15);"
                    required>
            </td>
            <td class="col-total-linea"><span class="total-linea">0</span></td>
            <td><button type="button" class="btn-delete-item"><i class="fas fa-trash-alt"></i></button></td>
        `;
        tablaItemsBody.appendChild(nuevaFila);
        manejarTipoCompra();
    }

    // --- LISTENERS ---
    
    // Checkbox IVA (Nuevo listener)
    if(checkAplicaIva) {
        checkAplicaIva.addEventListener('change', calcularTotales);
    }

    btnAgregarItem.addEventListener('click', agregarFila);

    // --- SOLUCIÓN: Usar closest para detectar el botón aunque se clickee el icono ---
    tablaItemsBody.addEventListener('click', (e) => {
        // Buscamos el botón más cercano al elemento clickeado
        const btnEliminar = e.target.closest('.btn-delete-item');

        // Si encontramos el botón...
        if (btnEliminar) {
            if (tablaItemsBody.querySelectorAll("tr").length <= 1) {
                alert("Debe haber al menos un ítem.");
                return;
            }
            
            // Eliminamos la fila correspondiente al botón encontrado
            btnEliminar.closest('tr').remove();
            
            const tipo = tipoCompraSelect.value;
            if (tipo !== 'Compra Ágil' && tipo !== 'Licitación Pública' && tipo !== 'Licitación Privada') {
                calcularTotales();
            }
        }
    });

    tablaItemsBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('input-calc')) {
            const tipo = tipoCompraSelect.value;
            if (tipo !== 'Compra Ágil' && tipo !== 'Licitación Pública' && tipo !== 'Licitación Privada') {
                calcularTotales();
            }
        }
    });

    tipoCompraSelect.addEventListener('change', manejarTipoCompra);

    presupuestoInput.addEventListener('input', () => {
        const tipo = tipoCompraSelect.value;
        if (tipo === 'Compra Ágil' || tipo === 'Licitación Pública' || tipo === 'Licitación Privada') {
            const valor = parseFloat(presupuestoInput.value) || 0;
            inputValorNeto.value = valor.toLocaleString('es-CL');
            inputValorTotal.value = valor.toLocaleString('es-CL');
            valorNetoHidden.value = valor;
            valorTotalHidden.value = valor;
            ivaHidden.value = 0;
        }
    });

    // Iniciar
    manejarTipoCompra();
});