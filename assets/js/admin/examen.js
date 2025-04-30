$(function() {

    // --- Constantes y Variables ---
    const formAction = typeof tipo !== 'undefined' ? tipo : null;
    const currentExamenId = typeof examen_id !== 'undefined' ? examen_id : null;
    const baseApiUrl = FULL_WEB_URL + 'ajax/admin/examen_handler.php'; // URL del handler PHP
    const listUrl = FULL_WEB_URL + '?class=examenes&action=list';

    // --- Selectores de Formulario (si estamos en create/edit) ---
    const $form = $('#examenForm');
    const $examenIdInput = $('.js-examen-id');
    const $nombreInput = $('.js-nombre');
    const $codigoInput = $('.js-codigo');
    const $precioInput = $('.js-precio');
    const $activoSelect = $('.js-activo');
    const $descripcionTextarea = $('.js-descripcion');
    const $preparacionTextarea = $('.js-preparacion');
    const $errorMessageDiv = $('#form-error-message');

    // --- Funciones Auxiliares ---
    function clearFormErrors() {
        $errorMessageDiv.addClass('d-none').text('');
        $form.find('.is-invalid').removeClass('is-invalid');
    }

    function showFormError(message) {
        $errorMessageDiv.removeClass('d-none').text(message || 'Ocurrió un error.');
    }

    function showInputError($input, message) {
        $input.addClass('is-invalid');
        $input.siblings('.invalid-feedback').text(message);
        // Si es un input-group, buscar el feedback fuera
        if ($input.parent().hasClass('input-group')) {
            $input.parent().siblings('.invalid-feedback').text(message);
        }
    }

    // Validar el formulario
    function validateForm() {
        clearFormErrors();
        let isValid = true;

        if ($nombreInput.val().trim() === '') {
            showInputError($nombreInput, 'El nombre del examen es requerido.');
            isValid = false;
        }

        const precioVal = $precioInput.val();
        if (precioVal === '' || isNaN(parseFloat(precioVal)) || parseFloat(precioVal) < 0) {
            showInputError($precioInput, 'Ingresa un precio válido (número mayor o igual a 0).');
            isValid = false;
        }

        if ($activoSelect.val() === null || $activoSelect.val() === '') {
            showInputError($activoSelect, 'Selecciona un estado.');
            isValid = false;
        }
        // Otras validaciones opcionales (ej: longitud código)

        return isValid;
    }

    // Recolectar datos del formulario
    function getFormData(isEdit) {
        const formData = {
            nombre_examen: $nombreInput.val().trim(),
            codigo_examen: $codigoInput.val().trim() || null, // Enviar null si está vacío
            precio: parseFloat($precioInput.val()), // Asegurar que sea número
            activo: $activoSelect.val(),
            descripcion: $descripcionTextarea.val().trim() || null,
            preparacion: $preparacionTextarea.val().trim() || null
        };

        if (isEdit) {
            formData.id_examen = $examenIdInput.val();
        }
        return formData;
    }

    // --- Función Principal para Guardar (Crear o Editar) ---
    function saveExamen(isEdit) {
        if (!validateForm()) {
            showFormError('Por favor corrige los errores indicados.');
            return;
        }

        const formData = getFormData(isEdit);
        const submitButton = isEdit ? $('.js-edit') : $('.js-create');
        const originalButtonHtml = submitButton.html();

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...');

        $.ajax({
            url: baseApiUrl,
            type: 'POST',
            data: formData, // El backend distingue create/update por la presencia de id_examen
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: response.message || (isEdit ? 'Examen actualizado.' : 'Examen creado.'),
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = listUrl;
                    });
                } else {
                    showFormError(response.message || 'Error al guardar el examen.');
                    submitButton.prop('disabled', false).html(originalButtonHtml);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX:", textStatus, errorThrown);
                showFormError('Error de conexión al guardar. Inténtalo de nuevo.');
                submitButton.prop('disabled', false).html(originalButtonHtml);
            }
        });
    }

    // --- Función para Eliminar (Desactivar) Examen ---
    function deleteExamen(examenId, examenNombre, $button) {
        // Usar el formato de SweetAlert especificado por el usuario
        Swal.fire({
            title: '¿Desea desactivar este examen?', // Título ajustado
            text: `El examen "${examenNombre}" no estará disponible para nuevas citas o cotizaciones.`, // Texto ajustado
            // type: 'warning', // 'type' es obsoleto en SweetAlert2 -> usar 'icon'
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6', // Azul (Confirmar)
            cancelButtonColor: '#d33', // Rojo (Cancelar - aunque la acción sea peligrosa) - O usar #6c757d (gris)
            confirmButtonText: 'Sí, ¡desactivar!', // Texto ajustado
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            // Acceder a result.isConfirmed en lugar de result.value (SweetAlert2 v9+)
            if (result.value) {
                const originalButtonHtml = $button.html();
                $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                $.ajax({
                    url: baseApiUrl,
                    type: 'POST',
                    data: {
                        action: 'delete', // Backend debe reconocer esta acción
                        id_examen: examenId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.status === 'success') {
                            Swal.fire(
                                '¡Desactivado!',
                                response.message || `El examen ${examenNombre} ha sido desactivado.`,
                                'success'
                            );
                            // Actualizar UI
                            const $row = $button.closest('tr');
                            $row.find('.badge').removeClass('bg-success').addClass('bg-secondary').text('Inactivo');
                            $button.remove(); // Quitar botón eliminar/desactivar
                            // Podrías cambiar el botón editar si quisieras re-activar en lugar de eliminar
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo desactivar el examen.', 'error');
                            $button.prop('disabled', false).html(originalButtonHtml);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error AJAX:", textStatus, errorThrown);
                        Swal.fire('Error de Comunicación', 'No se pudo comunicar con el servidor.', 'error');
                        $button.prop('disabled', false).html(originalButtonHtml);
                    }
                }); // Fin AJAX Delete
            } // Fin if confirmed
        }); // Fin SweetAlert
    }

    // --- Inicialización y Event Listeners ---

    // Tooltips Bootstrap 5
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Listener para el formulario (Create/Edit)
    if (formAction === 'create' || formAction === 'edit') {
        $form.on('submit', function(event) {
            event.preventDefault();
            saveExamen(formAction === 'edit');
        });
    }

    // Listener para botones de eliminar (en la tabla) - Delegado
    if (formAction === 'list') {
        $('.table tbody').on('click', '.jsDelete', function() {
            const $button = $(this);
            const examenIdToDelete = $button.data('id');
            const examenNameToDelete = $button.data('nombre') || 'este examen';
            if (examenIdToDelete) {
                deleteExamen(examenIdToDelete, examenNameToDelete, $button);
            } else {
                console.error("Error: No se encontró data-id en el botón.", $button);
                Swal.fire('Error Interno', 'No se pudo obtener el ID del examen.', 'error');
            }
        });

        // Listener para búsqueda (opcional, el form GET ya funciona)
        // $('.JSbtnhSearh').on('click', function(e){
        //    $(this).closest('form').submit();
        // });
    }

}); // Fin document ready