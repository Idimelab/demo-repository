$(function() {

    // --- Constantes y Variables ---
    const viewType = typeof tipo !== 'undefined' ? tipo : null; // calendar, list, create, edit
    const currentCitaId = typeof cita_id !== 'undefined' ? cita_id : null;
    // URLs y datos globales (deben venir del script block de Twig)
    const currentAdminUserId = typeof currentUserId !== 'undefined' ? currentUserId : null;
    const estadosCitaDisponibles = typeof ESTADOS_CITA_JS !== 'undefined' ? ESTADOS_CITA_JS : [];
    const $form = $('#citaForm');


    // --- Selectores Comunes ---
    const $errorMessageDiv = $('#form-error-message'); // Para formularios

    // --- Instancia del Calendario (si aplica) ---
    let calendar = null;
    let citaModalInstance = null; // Instancia del Modal de Bootstrap

    // --- Funciones Auxiliares ---
    function clearFormErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $errorMessageDiv.addClass('d-none').text('');
    }
    function showFormError(message) {
        $errorMessageDiv.removeClass('d-none').text(message || 'Ocurrió un error.');
    }
    function showInputError($input, message) {
        $input.addClass('is-invalid');
        $input.siblings('.invalid-feedback').text(message);
    }

    // --- Inicialización del Calendario ---
    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return; // Salir si el elemento no existe

        // Crear instancia del Modal de Bootstrap
        const citaModalEl = document.getElementById('citaModal');
        if (citaModalEl) {
            citaModalInstance = new bootstrap.Modal(citaModalEl);
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            // --- Opciones de FullCalendar v6 ---
            locale: 'es', // Usar locale español
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' // Vistas disponibles
            },
            initialView: 'timeGridWeek', // Vista inicial
            navLinks: true, // Permite click en días/semanas para navegar
            selectable: true, // Permite seleccionar rangos de tiempo
            selectMirror: true,
            editable: false, // No permitir arrastrar/redimensionar eventos por defecto
            dayMaxEvents: true, // Permite "+ more" link cuando hay muchos eventos
            nowIndicator: true, // Muestra línea de hora actual
            slotMinTime: '07:00:00', // Hora de inicio visible
            slotMaxTime: '19:00:00', // Hora de fin visible
            // Cargar eventos desde AJAX
            events: function(fetchInfo, successCallback, failureCallback) {
                // Recoger valores de los filtros
                const params = {
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr,
                    estado: $('#filtro-estado-cal').val() || '',
                    // Añadir paciente y examen si usas inputs simples o Select2
                    // id_paciente: $('#filtro-paciente').val() || '', // Si usas select
                    // id_examen: $('#filtro-examen').val() || '',   // Si usas select
                    search_paciente: $('#filtro-paciente-nombre').val() || '', // Si usas input
                    search_examen: $('#filtro-examen-nombre').val() || ''     // Si usas input
                };

                $.ajax({
                    url: feedUrl, // Definido en el script block
                    type: 'GET',
                    data: params,
                    dataType: 'json',
                    success: function(data) {
                        // --- Mapeo CORREGIDO ---
                        if (!Array.isArray(data)) {
                            console.error("Error: La respuesta del feed no es un array.", data);
                            failureCallback("Formato de respuesta inválido");
                            return;
                        }

                        const events = data.map(function(citaFeed) {
                            // 'citaFeed' es el objeto del JSON: {id: "1", title: "...", start: "...", ...}

                            // Validar datos básicos necesarios por FullCalendar
                            if (!citaFeed.id || !citaFeed.title || !citaFeed.start) {
                                console.warn("Evento inválido recibido del feed (faltan id, title o start):", citaFeed);
                                return null; // Omitir este evento inválido
                            }

                            return {
                                id: citaFeed.id,             // <-- Usar la clave 'id' del feed
                                title: citaFeed.title,         // <-- Usar la clave 'title' del feed
                                start: citaFeed.start,         // <-- Usar la clave 'start' del feed
                                className: citaFeed.className, // <-- Usar la clave 'className' del feed (o calcularla si no viene)
                                // Asegúrate que tu feed envíe TODO lo que necesitas dentro de extendedProps
                                extendedProps: citaFeed.extendedProps || {} // Usar las props directamente
                            };
                        }).filter(event => event !== null); // Filtrar eventos inválidos

                        // Para depurar: mira qué eventos se están pasando a FullCalendar
                        console.log('Eventos mapeados para FullCalendar:', events);

                        successCallback(events); // Pasar los eventos corregidos
                    },
                    error: function(xhr, status, error) {
                        console.error("Error cargando eventos del calendario:", error, xhr.responseText);
                        failureCallback(error); // Notificar a FullCalendar del error
                        Swal.fire('Error', 'No se pudieron cargar las citas.', 'error');
                    }
                });
            },
            // --- Callbacks de Interacción ---
            eventClick: function(info) {
                // Click en un evento existente -> Mostrar Modal
                if (!citaModalInstance) return;

                const props = info.event.extendedProps;
                const citaId = info.event.id;

                // Llenar contenido del modal
                $('#citaModalLabel').text(`Cita #${citaId}`);
                const contentHtml = `
                    <dl class="row">
                      <dt class="col-sm-4">Paciente:</dt><dd class="col-sm-8">${props.paciente} (${props.documento || 'N/A'})</dd>
                      <dt class="col-sm-4">Contacto:</dt><dd class="col-sm-8">${props.email || '-'} / ${props.telefono || '-'}</dd>
                      <dt class="col-sm-4">Examen:</dt><dd class="col-sm-8">${props.examen} (${props.codigo_examen || 'N/A'})</dd>
                      <dt class="col-sm-4">Fecha y Hora:</dt><dd class="col-sm-8">${props.fecha} ${props.hora}</dd>
                      <dt class="col-sm-4">Estado Actual:</dt><dd class="col-sm-8"><span class="badge bg-${props.estado.toLowerCase().replace(/ /g, '_')}">${props.estado}</span></dd>
                      <dt class="col-sm-4">Obs. Paciente:</dt><dd class="col-sm-8">${props.obs_paciente || '-'}</dd>
                      <dt class="col-sm-4">Obs. Internas:</dt><dd class="col-sm-8">${props.obs_internas || '-'}</dd>
                    </dl>
                 `;
                $('#citaModalContent').html(contentHtml);

                // Configurar botones del modal
                $('#btn-editar-cita-modal').attr('href', editUrlBase + citaId);
                $('#btn-cancelar-cita-modal').data('id', citaId).data('info', `Cita #${citaId} de ${props.paciente}`); // Pasar info
                $('#select-cambiar-estado-modal').data('id', citaId).val(''); // Resetear select de estado

                // Mostrar modal
                citaModalInstance.show();

                info.jsEvent.preventDefault(); // Evitar que el navegador siga el href '#' si lo hubiera
            },
            select: function(info) {
                // Selección de un rango de tiempo -> Abrir formulario de creación (opcional)
                // console.log('Selected ' + info.startStr + ' to ' + info.endStr);
                // Podrías redirigir prellenando fecha/hora
                // const fecha = info.startStr.split('T')[0];
                // const hora = info.startStr.split('T')[1] ? info.startStr.split('T')[1].substring(0, 5) : '';
                // window.location.href = `?class=citas&action=create&fecha=${fecha}&hora=${hora}`;
                calendar.unselect(); // Limpiar selección visual
            },
            dateClick: function(info) {
                // Click en un día específico (en vista de mes/semana)
                // console.log('Clicked on: ' + info.dateStr);
                // Podrías cambiar a vista diaria o redirigir a crear con esa fecha
                // calendar.changeView('timeGridDay', info.dateStr);
            }
        });

        calendar.render(); // Dibujar el calendario

        // Listeners para filtros del calendario
        $('#btn-aplicar-filtros-cal').on('click', function() {
            calendar.refetchEvents(); // Recargar eventos con nuevos filtros
        });
        $('#btn-limpiar-filtros-cal').on('click', function() {
            $('#filtro-estado-cal').val('');
            $('#filtro-paciente-nombre').val('');
            $('#filtro-examen-nombre').val('');
            // Limpiar selects si los usas: $('#filtro-paciente, #filtro-examen').val('').trigger('change'); // Si usas Select2
            calendar.refetchEvents(); // Recargar eventos sin filtros
        });

        // Listener para el botón de Cancelar Cita DENTRO del modal
        $('#btn-cancelar-cita-modal').on('click', function() {
            const $button = $(this);
            const citaIdToCancel = $button.data('id');
            const citaInfo = $button.data('info') || 'esta cita';
            if (citaIdToCancel) {
                // Ocultar el modal ANTES de mostrar SweetAlert para evitar solapamiento
                citaModalInstance.hide();
                // Llamar a la función de cancelación (definida más abajo)
                cancelCita(citaIdToCancel, citaInfo, $button, true); // true para indicar que viene del modal
            }
        });

        // Listener para el Select de Cambiar Estado DENTRO del modal
        $('#select-cambiar-estado-modal').on('change', function() {
            const $select = $(this);
            const citaIdToUpdate = $select.data('id');
            const nuevoEstado = $select.val();

            if (citaIdToUpdate && nuevoEstado) {
                // Ocultar modal
                citaModalInstance.hide();
                // Llamar a función para actualizar estado (definida más abajo)
                updateCitaStatus(citaIdToUpdate, nuevoEstado, $select, true);
            }
        });


    } // Fin initCalendar

    // --- Validación del Formulario de Citas (Create/Edit) ---
    function validateCitaForm() {
        clearFormErrors($form); // Pasar el formulario como argumento
        let isValid = true;
        const isEdit = (formAction === 'edit');

        // Validar paciente y examen (solo requeridos en creación si no están deshabilitados)
        if (!isEdit) {
            if ($('.js-paciente').val() === null || $('.js-paciente').val() === '') {
                showInputError($('.js-paciente'), 'Selecciona un paciente.'); isValid = false;
            }
            if ($('.js-examen').val() === null || $('.js-examen').val() === '') {
                showInputError($('.js-examen'), 'Selecciona un examen.'); isValid = false;
            }
        }

        // Validar fecha y hora
        const fechaVal = $('.js-fecha').val();
        if (!fechaVal || !/^\d{4}-\d{2}-\d{2}$/.test(fechaVal)) {
            showInputError($('.js-fecha'), 'Ingresa una fecha válida (AAAA-MM-DD).'); isValid = false;
        }
        const horaVal = $('.js-hora').val();
        if (!horaVal) { // Formato time usualmente validado por navegador, solo checar vacío
            showInputError($('.js-hora'), 'Ingresa una hora válida.'); isValid = false;
        }

        // Validar estado
        const estadoVal = $('.js-estado').val();
        if (!estadoVal || !estadosCitaDisponibles.includes(estadoVal)) {
            showInputError($('.js-estado'), 'Selecciona un estado válido.'); isValid = false;
        }

        return isValid;
    }

    // --- Recolectar Datos del Formulario de Citas ---
    function getCitaFormData(isEdit) {
        const formData = {
            // Siempre enviar estos en edit y create (si es necesario)
            fecha_cita: $('.js-fecha').val(),
            hora_cita: $('.js-hora').val(),
            estado: $('.js-estado').val(),
            observaciones_paciente: $('.js-observaciones-paciente').val().trim() || null,
            observaciones_internas: $('.js-observaciones-internas').val().trim() || null,
            // ID del admin/aux que modifica (importante para trazabilidad)
            id_modificador: currentAdminUserId
        };
        if (isEdit) {
            formData.id_cita = $('.js-cita-id').val();
            // En edit, NO enviamos paciente ni examen si están deshabilitados
        } else {
            // En create, SÍ enviamos paciente y examen
            formData.id_paciente = $('.js-paciente').val();
            formData.id_examen = $('.js-examen').val();
        }
        return formData;
    }

    // --- Guardar Cita (Create/Edit) ---
    function saveCita(isEdit) {
        if (!validateCitaForm()) {
            showFormError('Por favor corrige los errores indicados.');
            return;
        }
        const formData = getCitaFormData(isEdit);
        const submitButton = isEdit ? $('.js-edit') : $('.js-create');
        const originalButtonHtml = submitButton.html();

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        $.ajax({
            url: citaHandlerUrl,
            type: 'POST',
            data: formData, // Backend diferencia por id_cita
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: '¡Guardado!',
                        text: response.message || (isEdit ? 'Cita actualizada.' : 'Cita creada.'),
                        timer: 2000, timerProgressBar: true, showConfirmButton: false
                    }).then(() => {
                        window.location.href = calendarUrl; // Volver al calendario
                    });
                } else {
                    showFormError(response.message || 'Error al guardar la cita.');
                    submitButton.prop('disabled', false).html(originalButtonHtml);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX saveCita:", textStatus, errorThrown);
                showFormError('Error de conexión al guardar. Inténtalo de nuevo.');
                submitButton.prop('disabled', false).html(originalButtonHtml);
            }
        });
    }

    // --- Cancelar Cita ---
    function cancelCita(citaId, citaInfo, $button, fromModal = false) {
        Swal.fire({
            title: '¿Desea cancelar este registro?', // Título del usuario
            text: `La cita "${citaInfo}" será marcada como Cancelada por Auxiliar.`, // Texto ajustado
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Rojo para confirmar cancelación
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ¡cancelar cita!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.value) {
                const originalButtonHtml = $button ? $button.html() : null;
                if ($button) $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: citaHandlerUrl,
                    type: 'POST',
                    data: {
                        action: 'cancel', // Acción específica para el backend
                        id_cita: citaId,
                        id_modificador: currentAdminUserId // Quién cancela
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.status === 'success') {
                            Swal.fire('¡Cancelada!', response.message || `La cita ${citaInfo} ha sido cancelada.`, 'success');
                            // Refrescar calendario si la acción vino de ahí
                            if (fromModal && calendar) {
                                calendar.refetchEvents();
                            }
                            // Si estamos en una vista de lista, actualizar la fila
                            else if (viewType === 'list' && $button) {
                                const $row = $button.closest('tr');
                                $row.find('.badge').removeClass('bg-success bg-warning bg-primary bg-info').addClass('bg-danger').text('Cancelada_Auxiliar');
                                $button.remove(); // Quitar botón cancelar
                                // Quizás deshabilitar otros botones
                            }
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo cancelar la cita.', 'error');
                            if ($button) $button.prop('disabled', false).html(originalButtonHtml);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error AJAX cancelCita:", textStatus, errorThrown);
                        Swal.fire('Error de Comunicación', 'No se pudo comunicar con el servidor.', 'error');
                        if ($button) $button.prop('disabled', false).html(originalButtonHtml);
                    }
                }); // Fin AJAX
            } // Fin if confirmed
        }); // Fin SweetAlert
    }

    // --- Actualizar Estado de Cita ---
    function updateCitaStatus(citaId, nuevoEstado, $triggerElement, fromModal = false) {
        // Podríamos añadir una confirmación aquí también si se desea
        const $originalTrigger = $triggerElement; // Guardar referencia al select/botón
        // Mostrar algún indicador de carga si es posible

        $.ajax({
            url: citaHandlerUrl,
            type: 'POST',
            data: {
                action: 'update_status', // Acción específica
                id_cita: citaId,
                nuevo_estado: nuevoEstado,
                id_modificador: currentAdminUserId
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Estado Actualizado', timer: 1500, showConfirmButton: false });
                    // Refrescar calendario si la acción vino de ahí
                    if (fromModal && calendar) {
                        calendar.refetchEvents();
                    }
                    // Si estamos en una vista de lista, actualizar la fila
                    else if (viewType === 'list' && $originalTrigger) {
                        const $row = $originalTrigger.closest('tr');
                        // Actualizar badge (necesitas lógica para clases CSS según estado)
                        let badgeClass = 'secondary';
                        if (nuevoEstado === 'Pendiente') badgeClass = 'warning text-dark';
                        else if (nuevoEstado === 'Confirmada') badgeClass = 'primary';
                        else if (nuevoEstado === 'Realizada') badgeClass = 'success';
                        else if (nuevoEstado.startsWith('Cancelada')) badgeClass = 'danger';
                        else if (nuevoEstado === 'No_Asistio') badgeClass = 'secondary';
                        $row.find('.badge').removeClass('bg-success bg-warning bg-primary bg-info bg-danger bg-secondary text-dark').addClass('bg-' + badgeClass).text(nuevoEstado);
                    }
                } else {
                    Swal.fire('Error', response.message || 'No se pudo actualizar el estado.', 'error');
                    // Resetear el select si vino de ahí
                    if ($originalTrigger && $originalTrigger.is('select')) {
                        $originalTrigger.val(''); // O al valor anterior si lo guardaste
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error AJAX updateCitaStatus:", textStatus, errorThrown);
                Swal.fire('Error de Comunicación', 'No se pudo comunicar con el servidor.', 'error');
                if ($originalTrigger && $originalTrigger.is('select')) {
                    $originalTrigger.val('');
                }
            }
        }); // Fin AJAX
    }


    // --- Inicialización Específica de la Vista ---
    if (viewType === 'calendar') {
        initCalendar();
    } else if (viewType === 'create' || viewType === 'edit') {
        // Listener para el formulario de Cita
        $form.on('submit', function(event) {
            event.preventDefault();
            saveCita(viewType === 'edit');
        });
        // Inicializar Select2 o Datepickers aquí si los usas
        // $('.js-paciente, .js-examen').select2();
    } else if (viewType === 'list') {
        // Listener para botones de cancelar en la lista (delegado)
        $('.table tbody').on('click', '.jsCancel', function() {
            const $button = $(this);
            const citaIdToCancel = $button.data('id');
            const citaInfo = $button.closest('tr').find('td').eq(1).text() + ' - ' + $button.closest('tr').find('td').eq(3).text(); // Info de Paciente - Examen
            if (citaIdToCancel) {
                cancelCita(citaIdToCancel, citaInfo, $button);
            }
        });
        // Listener para cambio de estado en lista (si implementas un select/dropdown por fila)
        $('.table tbody').on('change', '.js-change-status-list', function() {
            const $select = $(this);
            const citaIdToUpdate = $select.data('id'); // El select debe tener data-id
            const nuevoEstado = $select.val();
            if (citaIdToUpdate && nuevoEstado) {
                updateCitaStatus(citaIdToUpdate, nuevoEstado, $select);
            }
        });

    }

}); // Fin document ready