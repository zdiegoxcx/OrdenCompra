document.addEventListener('DOMContentLoaded', () => {

    // --- Contenedor de firma (para obtener el ID de la orden) ---
    const firmaContainer = document.getElementById('fieldset-firma-accion');
    
    // Si el contenedor no existe (porque el usuario no debe firmar), no hacemos nada.
    if (!firmaContainer) {
        return;
    }

    // --- Botones de la página principal ---
    const btnRechazar = document.getElementById('btn-rechazar');
    const btnFirmar = document.getElementById('btn-firmar');
    const inputToken = document.getElementById('token-input');
    
    // --- Elementos del Modal ---
    const modalOverlay = document.getElementById('modal-overlay');
    const modalRechazo = document.getElementById('modal-rechazo');
    const textareaMotivo = document.getElementById('motivo-rechazo-textarea');
    
    // --- Botones dentro del Modal ---
    const btnCancelarRechazo = document.getElementById('btn-cancelar-rechazo');
    const btnEnviarRechazo = document.getElementById('btn-enviar-rechazo');

    // Obtenemos el ID de la orden desde el atributo data-*
    const ordenId = firmaContainer.dataset.ordenId;

    // --- Funciones para abrir y cerrar el modal ---
    function mostrarModal() {
        modalOverlay.classList.add('modal-show');
        modalRechazo.classList.add('modal-show');
    }

    function ocultarModal() {
        modalOverlay.classList.remove('modal-show');
        modalRechazo.classList.remove('modal-show');
        // Limpiamos el texto por si el usuario vuelve a abrirlo
        textareaMotivo.value = '';
    }

    // --- Función Asíncrona para enviar datos al Backend ---
    async function procesarAccion(data) {
        try {
            const response = await fetch('procesar_firma.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // ¡Éxito!
                alert(result.message);
                window.location.href = 'index.php';
            } else {
                // Error controlado por el backend
                alert('Error: ' + result.message);
            }

        } catch (error) {
            // Error de red o JSON
            console.error('Error en fetch:', error);
            alert('Error de conexión. No se pudo procesar la solicitud.');
        } finally {
            // Reactivar botones
            btnFirmar.disabled = false;
            btnEnviarRechazo.disabled = false;
            ocultarModal();
        }
    }


    // --- Lógica de Eventos ---

    // 1. Al presionar "Rechazar Orden" (en la página)
    btnRechazar.addEventListener('click', (e) => {
        e.preventDefault();
        mostrarModal();
    });

    // 2. Al presionar "Cancelar" (en el modal)
    btnCancelarRechazo.addEventListener('click', (e) => {
        e.preventDefault();
        ocultarModal();
    });

    // 3. Al presionar "Enviar Rechazo" (en el modal)
    btnEnviarRechazo.addEventListener('click', (e) => {
        e.preventDefault();
        const motivo = textareaMotivo.value.trim(); // .trim() quita espacios en blanco

        if (!motivo) {
            alert('Por favor, debe ingresar un motivo para el rechazo.');
            return;
        }

        // Desactivar botón para evitar doble clic
        btnEnviarRechazo.disabled = true;

        const datos = {
            orden_id: ordenId,
            accion: 'rechazar',
            motivo: motivo
        };

        procesarAccion(datos);
    });

    // 4. Al presionar "Firmar y Aprobar" (en la página)
    btnFirmar.addEventListener('click', (e) => {
        e.preventDefault();
        const token = inputToken.value.trim();

        if (token.length !== 6 || !/^\d+$/.test(token)) {
            alert('El token debe ser de 6 números.');
            return;
        }

        // Desactivar botón para evitar doble clic
        btnFirmar.disabled = true;

        const datos = {
            orden_id: ordenId,
            accion: 'firmar',
            token: token
        };

        procesarAccion(datos);
    });

    // 5. (Opcional) Cerrar el modal al hacer clic en el fondo oscuro
    modalOverlay.addEventListener('click', () => {
        ocultarModal();
    });

});