document.addEventListener('DOMContentLoaded', () => {

    const tablaItemsBody = document.querySelector("#items-table tbody");
    const btnAgregarItem = document.querySelector(".btn-add-item");

    // --- FUNCIÓN 1: CALCULAR TOTALES ---
    function calcularTotales() {
        let valorNetoTotal = 0;
        
        // Recorremos todas las filas de la tabla
        tablaItemsBody.querySelectorAll("tr").forEach(fila => {
            const inputCantidad = fila.querySelector(".input-calc");
            const inputValorUnitario = fila.querySelector(".input-v-unitario");
            const spanTotalLinea = fila.querySelector(".total-linea");

            const cantidad = parseFloat(inputCantidad.value) || 0;
            const valorUnitario = parseFloat(inputValorUnitario.value) || 0;
            
            const totalLinea = cantidad * valorUnitario;
            
            // Actualizamos el total de la línea (con formato)
            spanTotalLinea.textContent = totalLinea.toLocaleString('es-CL');
            
            // Sumamos al valor neto
            valorNetoTotal += totalLinea;
        });

        // Calculamos IVA y Total
        const iva = Math.round(valorNetoTotal * 0.19);
        const valorTotal = valorNetoTotal + iva;

        // Actualizamos los campos 'disabled' (para que el usuario los vea)
        document.getElementById("input-valor-neto").value = valorNetoTotal.toLocaleString('es-CL');
        document.getElementById("input-iva").value = iva.toLocaleString('es-CL');
        document.getElementById("input-valor-total").value = valorTotal.toLocaleString('es-CL');

        // ¡Actualizamos los campos 'hidden' (para que PHP los reciba)!
        document.getElementById("valor_neto_hidden").value = valorNetoTotal;
        document.getElementById("iva_hidden").value = iva;
        document.getElementById("valor_total_hidden").value = valorTotal;
    }

    // --- FUNCIÓN 2: AGREGAR FILA DE ÍTEM ---
    function agregarFila() {
        const nuevaFila = document.createElement('tr');
        nuevaFila.innerHTML = `
            <td><input type="number" name="item_cantidad[]" class="input-calc" value="1" min="1" required></td>
            <td><input type="text" name="item_nombre[]" required></td>
            <td class="col-v-unitario"><input type="number" name="item_v_unitario[]" class="input-v-unitario input-calc" value="0" min="0" required></td>
            <td class="col-total-linea"><span class="total-linea">0</span></td>
            <td><button type="button" class="accion-btn btn-delete-item">🗑️</button></td>
        `;
        tablaItemsBody.appendChild(nuevaFila);
        
        // Recalculamos totales después de agregar
        calcularTotales();
    }

    // --- EVENT LISTENERS (Controladores) ---

    // 1. Clic en "Agregar Ítem"
    btnAgregarItem.addEventListener('click', agregarFila);

    // 2. Clic en "Borrar Ítem" (usando delegación de eventos)
    tablaItemsBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-delete-item')) {
            // Prevenimos borrar la última fila
            if (tablaItemsBody.querySelectorAll("tr").length <= 1) {
                alert("Debe haber al menos un ítem en la orden.");
                return;
            }
            
            // e.target es el botón. .closest('tr') encuentra la fila padre
            e.target.closest('tr').remove();
            
            // Recalculamos totales después de borrar
            calcularTotales();
        }
    });

    // 3. Al cambiar un valor en la tabla (Cantidad o V. Unitario)
    tablaItemsBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('input-calc')) {
            calcularTotales();
        }
    });

    // Calculamos los totales una vez al cargar la página (por si hay datos precargados)
    calcularTotales();
});