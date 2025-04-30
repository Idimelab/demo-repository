
    // --- Constantes y Variables ---
    const currentCotizacionId = typeof cotizacion_id !== 'undefined' ? cotizacion_id : null;
    const baseApiUrl = FULL_WEB_URL + 'ajax/admin/cotizacion_handler.php';
    const listUrl = FULL_WEB_URL + '?class=cotizaciones&action=list';
    const examenesMap = {}; // Crear un mapa para acceso rápido por ID
    console.log(examenesDisponibles)
    if (tipo === 'create' ) {
        examenesDisponibles.forEach(ex => {
            examenesMap[ex.id_examen] = ex;
        });
    }
console.log(examenesDisponibles)
console.log(examenesMap)
    let detalleItems = []; // Array para guardar los exámenes añadidos en modo 'create'

    // --- Selectores ---
    const $form = $('#cotizacionForm');
    const $cotizacionIdInput = $('.js-cotizacion-id');
    const $usuarioSelect = $('.js-usuario'); // Solo en create
    const $vigenciaInput = $('.js-vigencia');
    const $observacionesTextarea = $('.js-observaciones');
    const $estadoSelect = $('.js-estado'); // Solo en edit

    // Selectores para añadir exámenes (solo en create)
    const $examenSelect = $('.js-select-examen');
    const $cantidadInput = $('.js-input-cantidad');
    const $addExamenBtn = $('.js-add-examen');
    const $tablaDetallesBody = $('#tabla-detalles tbody');
    const $displayTotal = $('#display-total');
    const $filaSinItems = $('#fila-sin-items'); // Fila placeholder

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

    // Actualizar total y tabla en modo 'create'
    function actualizarVistaDetalles() {
        $tablaDetallesBody.find('tr:not(#fila-sin-items)').remove(); // Limpiar filas existentes (excepto placeholder)
        let totalGeneral = 0;

        if (detalleItems.length === 0) {
            $filaSinItems.removeClass('d-none'); // Mostrar mensaje si no hay items
        } else {
            $filaSinItems.addClass('d-none'); // Ocultar mensaje
            detalleItems.forEach((item, index) => {
                const subtotal = item.cantidad * item.precio_unitario;
                totalGeneral += subtotal;
                const filaHtml = `
                    <tr data-index="${index}" data-id-examen="${item.id_examen}">
                        <td>${item.codigo_examen}</td>
                        <td>${item.nombre_examen}</td>
                        <td class="text-center">${item.cantidad}</td>
                        <td class="text-end">${formatCurrency(item.precio_unitario)}</td>
                        <td class="text-end">${formatCurrency(subtotal)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm js-remove-detalle" data-index="${index}" title="Quitar">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $tablaDetallesBody.append(filaHtml);
            });
        }
        // Actualizar el total en el tfoot
        $displayTotal.text(formatCurrency(totalGeneral));
    }


    // --- Validación del Formulario ---
    function validateForm(isEdit) {
        clearFormErrors();
        let isValid = true;

        if (isEdit) {
            // --- VALIDACIÓN PARA EDITAR (LIMITADO) ---
            // Solo validar los campos que se permiten editar
            const estadoVal = $estadoSelect.val(); // Asegúrate que el selector es correcto
            if (!estadoVal || !['Pendiente', 'Completada', 'Expirada'].includes(estadoVal)) {
                showInputError($estadoSelect, 'Selecciona un estado válido.');
                isValid = false;
            }

            const vigenciaVal = $vigenciaInput.val();
            if (vigenciaVal !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(vigenciaVal)) {
                // Solo valida formato si no está vacío
                showInputError($vigenciaInput, 'Formato de fecha inválido (AAAA-MM-DD).');
                isValid = false;
            }
            // No necesitas validar observaciones a menos que haya reglas específicas

        }  else {
            // Validar campos de creación
            if ($usuarioSelect.val() === null || $usuarioSelect.val() === '') {
                showInputError($usuarioSelect, 'Debes seleccionar un cliente.');
                isValid = false;
            }
            if (detalleItems.length === 0) {
                showFormError('Debes añadir al menos un examen a la cotización.');
                // Podríamos marcar el select de examen como inválido visualmente
                $examenSelect.addClass('is-invalid'); // O un área general
                isValid = false;
            }
            // Validar formato de fecha si se ingresó una vigencia
            if ($vigenciaInput.val() !== '' && !/^\d{4}-\d{2}-\d{2}$/.test($vigenciaInput.val())) {
                showInputError($vigenciaInput, 'Formato de fecha inválido (AAAA-MM-DD).');
                isValid = false;
            }
        }
        return isValid;
    }

    // --- Recolectar Datos del Formulario ---
    function getFormData(isEdit) {
        let formData = {};
        if (isEdit) {
            formData = {
                id_cotizacion: $cotizacionIdInput.val(),
                estado: $estadoSelect.val(),
                vigencia_hasta: $vigenciaInput.val() || null,
                observaciones: $observacionesTextarea.val().trim() || null
            };
        } else {
            formData = {
                id_usuario: $usuarioSelect.val(),
                vigencia_hasta: $vigenciaInput.val() || null,
                observaciones: $observacionesTextarea.val().trim() || null,
                // Enviar los detalles como un string JSON
                detalles: JSON.stringify(detalleItems)
            };
        }
        return formData;
    }

    // --- Guardar Cotización (Create/Edit) ---
    function saveCotizacion(isEdit) {
        if (!validateForm(isEdit)) {
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
            data: formData, // Backend diferencia por id_cotizacion y/o 'detalles'
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: '¡Guardado!',
                        text: response.message || (isEdit ? 'Cotización actualizada.' : 'Cotización creada.'),
                        timer: 2000, timerProgressBar: true, showConfirmButton: false
                    }).then(() => {
                        window.location.href = listUrl; // Redirigir a la lista
                    });
                } else {
                    showFormError(response.message || 'Error al guardar la cotización.');
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


    // --- Eliminar Cotización ---
    function deleteCotizacion(cotizacionId, cotizacionInfo, $button) {
        Swal.fire({
            title: '¿Desea eliminar este registro?', // Título del usuario
            text: `La cotización "${cotizacionInfo}" será eliminada permanentemente. ¡Esta acción no se puede revertir!`, // Texto ajustado
            icon: 'warning', // Icono en lugar de type
            showCancelButton: true,
            confirmButtonColor: '#3085d6', // Azul (Confirmar)
            cancelButtonColor: '#d33', // Rojo (Cancelar acción peligrosa)
            confirmButtonText: 'Sí, ¡eliminar!', // Texto del botón
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) { // Usar result.isConfirmed
                const originalButtonHtml = $button.html();
                $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                $.ajax({
                    url: baseApiUrl,
                    type: 'POST',
                    data: {
                        action: 'delete', // Acción para el backend
                        id_cotizacion: cotizacionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.status === 'success') {
                            Swal.fire('¡Eliminado!', response.message || `La cotización ${cotizacionInfo} ha sido eliminada.`, 'success');
                            // Eliminar la fila de la tabla
                            $button.closest('tr').fadeOut(500, function() { $(this).remove(); });
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo eliminar la cotización.', 'error');
                            $button.prop('disabled', false).html(originalButtonHtml);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error AJAX:", textStatus, errorThrown);
                        Swal.fire('Error de Comunicación', 'No se pudo comunicar con el servidor.', 'error');
                        $button.prop('disabled', false).html(originalButtonHtml);
                    }
                }); // Fin AJAX
            } // Fin if confirmed
        }); // Fin SweetAlert
    }


    // --- Event Listeners ---

    // Tooltips Bootstrap 5
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });

    // Formulario (Create/Edit)
    if (tipo === 'create' || tipo === 'edit') {
        $form.on('submit', function(event) {
            event.preventDefault();
            saveCotizacion(tipo === 'edit');
        });

        // Listeners específicos para modo CREATE (añadir/quitar exámenes)
        if (tipo === 'create') {
            // Botón Añadir Examen
            $addExamenBtn.on('click', function() {
                const selectedId = $examenSelect.val();
                const selectedOption = $examenSelect.find('option:selected');
                const cantidad = parseInt($cantidadInput.val()) || 1;

                if (!selectedId) {
                    showInputError($examenSelect, 'Selecciona un examen.');
                    return;
                }
                // Verificar si ya está añadido
                const yaExiste = detalleItems.some(item => item.id_examen == selectedId);
                if (yaExiste) {
                    // Podríamos mostrar un alert o simplemente no hacer nada
                    Swal.fire({icon: 'info', title: 'Examen ya añadido', text: 'Este examen ya se encuentra en la lista.', timer: 1500, showConfirmButton: false });
                    return;
                }
console.log(examenesMap)
console.log(selectedId)
                // Obtener datos del examen desde el mapa JS
                const examenInfo = examenesMap[selectedId];
                if (!examenInfo) {
                    console.error("No se encontró info para examen ID:", selectedId);
                    return; // No debería pasar si el select está bien poblado
                }

                // Añadir al array de detalles
                detalleItems.push({
                    id_examen: selectedId,
                    cantidad: cantidad,
                    precio_unitario: parseFloat(examenInfo.precio), // Usar precio del mapa
                    nombre_examen: examenInfo.nombre_examen, // Guardar nombre y código para mostrar
                    codigo_examen: examenInfo.codigo_examen || 'N/A'
                });

                // Actualizar la tabla y el total
                actualizarVistaDetalles();

                // Limpiar selección y cantidad
                $examenSelect.val('');
                $cantidadInput.val(1);
                $examenSelect.removeClass('is-invalid'); // Limpiar error si lo había
            });

            // Botón Quitar Examen (Delegado)
            $tablaDetallesBody.on('click', '.js-remove-detalle', function() {
                const indexToRemove = $(this).data('index');
                if (typeof indexToRemove !== 'undefined') {
                    detalleItems.splice(indexToRemove, 1); // Eliminar del array por índice
                    actualizarVistaDetalles(); // Redibujar tabla y total
                }
            });

            // Inicializar la tabla por si acaso (muestra "sin items" al inicio)
            actualizarVistaDetalles();
        } // Fin if tipo === 'create'
    } // Fin if create or edit

    // Listener para botones de eliminar (en la tabla) - Delegado
    if (tipo === 'list') {
        $('.table tbody').on('click', '.jsDelete', function() {
            const $button = $(this);
            const cotIdToDelete = $button.data('id');
            const cotInfoToDelete = $button.data('info') || 'esta cotización'; // Usar data-info
            if (cotIdToDelete) {
                deleteCotizacion(cotIdToDelete, cotInfoToDelete, $button);
            } else {
                console.error("Error: No se encontró data-id en el botón.", $button);
                Swal.fire('Error Interno', 'No se pudo obtener el ID de la cotización.', 'error');
            }
        });

        // Listener para búsqueda (opcional, form GET ya funciona)
        // $('.JSbtnhSearh').on('click', function(e){ $(this).closest('form').submit(); });
    }

