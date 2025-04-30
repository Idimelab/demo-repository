$(function() {

    // --- Constantes y Variables ---
    const viewType = typeof tipo !== 'undefined' ? tipo : null; // list, create, edit
    const currentCitaId = typeof cita_id !== 'undefined' ? cita_id : null;
    // URLs y datos globales (deben venir del script block de Twig)
    const handlerUrl = typeof citaHandlerUrl !== 'undefined' ? citaHandlerUrl : null; // Handler del paciente
    const listUrl = typeof listCitasUrl !== 'undefined' ? listCitasUrl : '?class=citas&action=list';
    const currentUserId = typeof currentPatientId !== 'undefined' ? currentPatientId : null; // ID Paciente

    // --- Selectores Formulario (si aplica) ---
    const $form = $('#citaForm');
    const $citaIdInput = $('.js-cita-id');
    const $pacienteIdInput = $('.js-paciente-id'); // Hidden input con ID paciente
    const $examenSelect = $('.js-examen');
    const $fechaInput = $('.js-fecha');
    const $horaInput = $('.js-hora');
    const $obsPacienteTextarea = $('.js-observaciones-paciente');
    // const $estadoSelect = $('.js-estado'); // Paciente no cambia estado directamente
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
    }

    // --- Validación Formulario Cita (Paciente) ---
    function validateCitaFormPaciente(isEdit) {
        clearFormErrors();
        let isValid = true;

        if ($examenSelect.val() === null || $examenSelect.val() === '') {
            showInputError($examenSelect, 'Selecciona un examen.'); isValid = false;
        }
        const fechaVal = $fechaInput.val();
        // Validar que la fecha sea hoy o futura
        const hoy = new Date().toISOString().split('T')[0];
        if (!fechaVal || !/^\d{4}-\d{2}-\d{2}$/.test(fechaVal) || fechaVal < hoy) {
            showInputError($fechaInput, 'Ingresa una fecha válida (hoy o futura).'); isValid = false;
        }
        const horaVal = $horaInput.val();
        if (!horaVal) {
            showInputError($horaInput, 'Ingresa una hora válida.'); isValid = false;
        }
        // Podría añadirse validación de hora futura si la fecha es hoy

        // En edición, las reglas son más estrictas (se validan en backend)
        // Aquí solo validamos el formato básico

        return isValid;
    }

    // --- Recolectar Datos Formulario Cita (Paciente) ---
    function getCitaFormDataPaciente(isEdit) {
        const formData = {
            // id_paciente se toma de currentUserId o del input oculto si fuera necesario
            id_paciente: currentUserId,
            id_examen: $examenSelect.val(), // Se envía aunque esté deshabilitado en edit (backend decide si usarlo)
            fecha_cita: $fechaInput.val(),
            hora_cita: $horaInput.val(),
            observaciones_paciente: $obsPacienteTextarea.val().trim() || null,
            // El paciente no manda estado ni obs internas ni modificador (se asigna en backend)
        };
        if (isEdit) {
            formData.id_cita = $citaIdInput.val();
        }
        return formData;
    }

    // --- Guardar Cita (Create / Edit-Reschedule por Paciente) ---
    function saveCitaPaciente(isEdit) {
        if (!validateCitaFormPaciente(isEdit)) {
            showFormError('Por favor corrige los errores indicados.');
            return;
        }

        const formData = getCitaFormDataPaciente(isEdit);
        const submitButton = isEdit ? $('.js-edit') : $('.js-create');
        const originalButtonHtml = submitButton.html();

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Procesando...');
        clearFormErrors();

        $.ajax({
            url: handlerUrl, // Handler del paciente
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message || (isEdit ? 'Tu cita ha sido reagendada.' : 'Tu cita ha sido agendada.'),
                        timer: 2500, timerProgressBar: true, showConfirmButton: false
                    }).then(() => {
                        window.location.href = listUrl; // Volver a "Mis Citas"
                    });
                } else {
                    // Mostrar error específico del backend (ej: horario no disponible, <24h, etc.)
                    showFormError(response.message || 'No se pudo guardar la cita.');
                    submitButton.prop('disabled', false).html(originalButtonHtml);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX saveCitaPaciente:", textStatus, errorThrown);
                showFormError('Error de conexión al guardar. Inténtalo de nuevo.');
                submitButton.prop('disabled', false).html(originalButtonHtml);
            }
        });
    }

    // --- Cancelar Cita (Paciente) ---
    function cancelCitaPaciente(citaId, citaInfo, $button) {
        // Verificar regla 24h (Client-Side - Backend DEBE revalidar)
        // Necesitarías la fecha/hora de la cita aquí (podrías añadirla al data-* del botón)
        // Ejemplo: const citaFechaHoraStr = $button.data('fecha') + ' ' + $button.data('hora');
        // const esModificable = isChangeAllowedJS(citaFechaHoraStr); // Necesitas función JS similar a la PHP
        // if (!esModificable) {
        //    Swal.fire('Acción no permitida', 'No puedes cancelar esta cita porque faltan menos de 24 horas.', 'warning');
        //    return;
        // }

        Swal.fire({
            title: '¿Desea cancelar esta cita?',
            text: `La cita ${citaInfo} será cancelada. ¿Estás seguro?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Rojo para confirmar cancelación
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ¡cancelar cita!',
            cancelButtonText: 'No, mantenerla'
        }).then((result) => {
            if (result.value) {
                const originalButtonHtml = $button.html();
                $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: handlerUrl, // Handler del paciente
                    type: 'POST',
                    data: {
                        action: 'cancel',
                        id_cita: citaId
                        // El backend usará el ID de sesión como id_modificador y 'Paciente' como quien_cancela
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.status === 'success') {
                            Swal.fire('¡Cancelada!', response.message || `Tu cita ${citaInfo} ha sido cancelada.`, 'success');
                            // Actualizar UI en la lista
                            const $row = $button.closest('tr');
                            if ($row.length) {
                                $row.find('.badge').removeClass('bg-success bg-warning bg-primary bg-info').addClass('bg-danger').text('Cancelada_Paciente');
                                $row.find('.jsCancel, a[href*="action=edit"]').remove(); // Quitar botones Cancelar/Reagendar
                            }
                        } else {
                            // Mostrar error del backend (ej: <24h)
                            Swal.fire('Error', response.message || 'No se pudo cancelar la cita.', 'error');
                            $button.prop('disabled', false).html(originalButtonHtml);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error AJAX cancelCitaPaciente:", textStatus, errorThrown);
                        Swal.fire('Error de Comunicación', 'No se pudo comunicar con el servidor.', 'error');
                        $button.prop('disabled', false).html(originalButtonHtml);
                    }
                }); // Fin AJAX
            } // Fin if confirmed
        }); // Fin SweetAlert
    }


    // --- Inicialización y Listeners ---
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });

    // Formulario (Create/Edit)
    if (viewType === 'create' || viewType === 'edit') {
        // Deshabilitar campos no editables por paciente en modo edit
        if (viewType === 'edit') {
            // $examenSelect.prop('disabled', true); // Descomentar si decides no permitir cambio de examen
        }

        $form.on('submit', function(event) {
            event.preventDefault();
            saveCitaPaciente(viewType === 'edit');
        });
    }

    // Lista (Botón Cancelar)
    if (viewType === 'list') {
        $('.table tbody').on('click', '.jsCancel', function() {
            const $button = $(this);
            const citaIdToCancel = $button.data('id');
            const citaInfo = $button.data('info') || 'seleccionada';
            if (citaIdToCancel) {
                cancelCitaPaciente(citaIdToCancel, citaInfo, $button);
            }
        });
    }

}); // Fin document ready

// --- Función JS Helper (Opcional) para chequear 24h (similar a la PHP) ---
// function isChangeAllowedJS(dateTimeString) {
//     try {
//         const appointmentDateTime = new Date(dateTimeString); // Intentar parsear YYYY-MM-DD HH:MM:SS
//         const now = new Date();
//         const nowPlus24h = new Date(now.getTime() + (24 * 60 * 60 * 1000));
//         return appointmentDateTime > nowPlus24h;
//     } catch (e) {
//         console.error("Error parsing date for 24h check:", e);
//         return false; // Default a no permitir si hay error
//     }
// }