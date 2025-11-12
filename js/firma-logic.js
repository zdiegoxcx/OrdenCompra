
document.addEventListener('DOMContentLoaded', () => {

    // --- Botones de la página principal ---
    const btnRechazar = document.getElementById('btn-rechazar');
    const btnFirmar = document.getElementById('btn-firmar');

    // --- Elementos del Modal ---
    const modalOverlay = document.getElementById('modal-overlay');
    const modalRechazo = document.getElementById('modal-rechazo');
    const textareaMotivo = document.getElementById('motivo-rechazo-textarea');
    
    // --- Botones dentro del Modal ---
    const btnCancelarRechazo = document.getElementById('btn-cancelar-rechazo');
    const btnEnviarRechazo = document.getElementById('btn-enviar-rechazo');

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

    // --- Lógica de Eventos ---

    // 1. Al presionar "Rechazar Orden" (en la página)
    if (btnRechazar) {
        btnRechazar.addEventListener('click', (e) => {
            e.preventDefault();
            mostrarModal();
        });
    }

    // 2. Al presionar "Cancelar" (en el modal)
    if (btnCancelarRechazo) {
        btnCancelarRechazo.addEventListener('click', (e) => {
            e.preventDefault();
            ocultarModal();
        });
    }

    // 3. Al presionar "Enviar Rechazo" (en el modal)
    if (btnEnviarRechazo) {
        btnEnviarRechazo.addEventListener('click', (e) => {
            e.preventDefault();
            const motivo = textareaMotivo.value.trim(); // .trim() quita espacios en blanco

            if (motivo) {
                alert('Orden rechazada con motivo: ' + motivo + '\n\nRedirigiendo al inicio...');
                window.location.href = 'index.html';
            } else {
                alert('Por favor, debe ingresar un motivo para el rechazo.');
            }
        });
    }

    // 4. Al presionar "Firmar y Aprobar" (en la página)
    if (btnFirmar) {
        btnFirmar.addEventListener('click', (e) => {
            e.preventDefault();
            // (Aquí iría la lógica de validación del token)
            alert('Orden firmada y aprobada.\n\nRedirigiendo al inicio...');
            window.location.href = 'index.html';
        });
    }

    // 5. (Opcional) Cerrar el modal al hacer clic en el fondo oscuro
    if (modalOverlay) {
        modalOverlay.addEventListener('click', () => {
            ocultarModal();
        });
    }

});