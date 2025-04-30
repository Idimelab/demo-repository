// Espera a que el DOM esté listo
$(function() {

    // --- Constantes y Variables Globales ---
    // 'tipo' y 'user_id' vienen del bloque footer_scripts de Twig
    const formAction = typeof tipo !== 'undefined' ? tipo : null; // 'list', 'create', 'edit'
    const currentUserId = typeof user_id !== 'undefined' ? user_id : null;
    // Asume que FULL_WEB_URL está definida globalmente (ej. en baseDocument.twig)
    const baseApiUrl = FULL_WEB_URL + 'ajax/admin/usuario_handler.php'; // URL base para AJAX (CRUD)
    const listUrl = FULL_WEB_URL + '?class=usuarios&action=list'; // URL para redirigir a la lista

    // --- Selectores de Formulario (si estamos en create/edit) ---
    const $form = $('#usuarioForm');
    const $userIdInput = $('.js-user-id'); // Hidden input for edit
    const $nombreInput = $('.js-nombre');
    const $apellidoInput = $('.js-apellido');
    const $tipoDocSelect = $('.js-tipo-documento');
    const $documentoInput = $('.js-documento');
    const $emailInput = $('.js-email');
    const $telefonoInput = $('.js-telefono');
    const $fechaNacInput = $('.js-fecha-nacimiento');
    const $direccionTextarea = $('.js-direccion');
    const $epsInput = $('.js-eps');
    const $rolSelect = $('.js-rol');
    const $activoSelect = $('.js-activo');
    const $passwordInput = $('.js-password');
    const $passwordConfirmInput = $('.js-password-confirm');
    const $errorMessageDiv = $('#form-error-message');

    // --- Funciones Auxiliares ---

    // Limpiar mensajes y estilos de error del formulario
    function clearFormErrors() {
        $errorMessageDiv.addClass('d-none').text('');
        $form.find('.is-invalid').removeClass('is-invalid');
        // No necesitas quitar 'was-validated' si no lo añades
    }

    // Mostrar error general del formulario
    function showFormError(message) {
        $errorMessageDiv.removeClass('d-none').text(message || 'Ocurrió un error.');
    }

    // Mostrar feedback de validación en un campo específico
    function showInputError($input, message) {
        $input.addClass('is-invalid');
        // Asumiendo que el div de feedback está justo después del input
        $input.siblings('.invalid-feedback').text(message);
    }

    // Validar el formulario antes de enviar
    function validateForm(isEdit) {
        clearFormErrors();
        let isValid = true;

        // Validar campos requeridos
        if ($nombreInput.val().trim() === '') {
            showInputError($nombreInput, 'El nombre es requerido.');
            isValid = false;
        }
        if ($apellidoInput.val().trim() === '') {
            showInputError($apellidoInput, 'El apellido es requerido.');
            isValid = false;
        }
        if ($tipoDocSelect.val() === null || $tipoDocSelect.val() === '') {
            showInputError($tipoDocSelect, 'Selecciona un tipo de documento.');
            isValid = false;
        }
        if ($documentoInput.val().trim() === '') {
            showInputError($documentoInput, 'El número de documento es requerido.');
            isValid = false;
        }
        const emailVal = $emailInput.val().trim();
        if (emailVal === '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { // Validación simple de email
            showInputError($emailInput, 'Ingresa un correo electrónico válido.');
            isValid = false;
        }
        if ($rolSelect.val() === null || $rolSelect.val() === '') {
            showInputError($rolSelect, 'Selecciona un rol.');
            isValid = false;
        }

        // Validar Contraseña (solo si se está creando o si se ingresó algo en editar)
        const passwordVal = $passwordInput.val();
        const passwordConfirmVal = $passwordConfirmInput.val();

        if (!isEdit) { // Creando usuario
            if (passwordVal === '' || passwordVal.length < 6) {
                showInputError($passwordInput, 'La contraseña es requerida (mínimo 6 caracteres).');
                isValid = false;
            } else if (passwordVal !== passwordConfirmVal) {
                showInputError($passwordConfirmInput, 'Las contraseñas no coinciden.');
                isValid = false;
            }
        } else { // Editando usuario
            if (passwordVal !== '') { // Si el campo contraseña no está vacío en editar
                if (passwordVal.length < 6) {
                    showInputError($passwordInput, 'La contraseña debe tener al menos 6 caracteres.');
                    isValid = false;
                } else if (passwordVal !== passwordConfirmVal) {
                    showInputError($passwordConfirmInput, 'Las contraseñas no coinciden.');
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    // Recolectar datos del formulario
    function getFormData(isEdit) {
        const formData = {
            nombre: $nombreInput.val().trim(),
            apellido: $apellidoInput.val().trim(),
            tipo_documento: $tipoDocSelect.val(),
            documento: $documentoInput.val().trim(),
            email: $emailInput.val().trim(),
            telefono: $telefonoInput.val().trim() || null, // Enviar null si está vacío
            fecha_nacimiento: $fechaNacInput.val() || null,
            direccion: $direccionTextarea.val().trim() || null,
            eps: $epsInput.val().trim() || null,
            id_rol: $rolSelect.val(),
            activo: $activoSelect.val()
        };

        // Añadir contraseña solo si es necesario
        const passwordVal = $passwordInput.val();
        if (!isEdit || passwordVal !== '') { // Incluir en create o si se modificó en edit
            formData.plainPassword = passwordVal;
        }

        // Añadir ID si estamos editando
        if (isEdit) {
            formData.id_usuario = $userIdInput.val(); // Asegúrate que js-user-id tenga el valor
        }

        return formData;
    }

    // --- Función Principal para Guardar (Crear o Editar) ---
    function saveUser(isEdit) {
        if (!validateForm(isEdit)) {
            showFormError('Por favor corrige los errores indicados en el formulario.');
            return; // Detener si la validación falla
        }

        const formData = getFormData(isEdit);
        const submitButton = isEdit ? $('.js-edit') : $('.js-create');
        const originalButtonHtml = submitButton.html(); // Guardar texto original

        // Mostrar estado de carga y deshabilitar botón
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...');

        // Llamada AJAX
        $.ajax({
            url: baseApiUrl, // Script PHP que maneja create y update
            type: 'POST',
            data: formData, // El backend determinará si es create/update basado en si 'id_usuario' está presente
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    // Éxito - Mostrar mensaje y redirigir a la lista
                    // Usar SweetAlert para una mejor experiencia
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: response.message || (isEdit ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.'),
                        timer: 2000, // Cerrar automáticamente después de 2 segundos
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = listUrl; // Redirigir a la lista
                    });
                } else {
                    // Error del backend
                    showFormError(response.message || 'Ocurrió un error al guardar.');
                    submitButton.prop('disabled', false).html(originalButtonHtml); // Rehabilitar botón
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Error de AJAX
                console.error("Error AJAX:", textStatus, errorThrown);
                showFormError('Error de conexión al guardar. Inténtalo de nuevo.');
                submitButton.prop('disabled', false).html(originalButtonHtml); // Rehabilitar botón
            }
            // No necesitas 'complete' aquí si manejas el botón en success/error
        });
    }

// --- Event Listener for Delete Buttons (Delegated) ---
// Asegúrate que este código esté dentro de $(function() { ... }); o equivalente.
// Asegúrate que tu tabla tenga un <tbody> identificable (usar 'tbody' directo es común).
// Asegúrate que tus botones de eliminar tengan class="jsDelete" y data-id="EL_ID_DEL_USUARIO" y opcionalmente data-nombre="NOMBRE_USUARIO".
    $('.table tbody').on('click', '.jsDelete', function() {
        const $button = $(this); // El botón específico que fue clickeado
        const userIdToDelete = $button.data('id'); // Obtiene el valor de data-id
        const userNameToDelete = $button.data('nombre') || 'este usuario'; // Obtiene el nombre para el mensaje

        // Verifica si se obtuvo un ID válido
        if (userIdToDelete) {
            // Llama a la función que maneja la confirmación y el AJAX
            deleteUser(userIdToDelete, userNameToDelete, $button);
        } else {
            // Error si no se encuentra el data-id
            console.error("Error: No se encontró el atributo data-id en el botón de eliminar.", $button);
            // Muestra un error al usuario
            Swal.fire('Error Interno', 'No se pudo obtener el ID del usuario a eliminar.', 'error');
        }
    });


// --- Function to Handle Deletion Confirmation and AJAX Call ---
    function deleteUser(userId, userName, $button) {

        Swal.fire({
            title: 'Desea eliminar este registro',
            text: 'Esta accion no se puede revertir',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, eliminar'
        }).then((result) => {
            if (result.value) {


                // 3. Realizar la llamada AJAX
                //    ¡VERIFICA ESTOS PARÁMETROS CUIDADOSAMENTE!
                $.ajax({
                    // url: Debe apuntar a tu script PHP handler. Asegúrate que 'baseApiUrl' esté bien definida.
                    url: baseApiUrl, // Ejemplo: 'ajax/admin/usuario_handler.php'

                    // type: 'POST' es común. Verifica que tu backend espera POST.
                    type: 'POST',

                    // data: Verifica que tu backend espera EXACTAMENTE estos nombres de parámetros ('action', 'id_usuario').
                    data: {
                        action: 'delete', // ¿Tu backend busca este parámetro para saber que es una eliminación?
                        id_usuario: userId
                    },

                    // dataType: 'json' - Espera que el backend DEVUELVA JSON.
                    dataType: 'json',

                    // 4. Callback SUCCES (Si la llamada AJAX fue exitosa y el servidor respondió - ej: código 200)
                    success: function(response) {
                        // Verifica la estructura de la RESPUESTA JSON que envía tu backend.
                        // ¿Realmente devuelve un objeto con una propiedad 'status' que sea 'success'?
                        if (response && response.status === 'success') {
                            // Éxito LÓGICO (el backend dijo que se eliminó/desactivó)
                            Swal.fire(
                                '¡Desactivado!',
                                response.message || `El usuario ${userName} ha sido desactivado.`, // Usa mensaje del backend o uno genérico
                                'success'
                            );

                            // Actualizar la interfaz de usuario (opcional pero recomendado)
                            const $row = $button.closest('tr'); // Encuentra la fila de la tabla
                            // Cambiar el badge a Inactivo
                            $row.find('.badge').removeClass('bg-success').addClass('bg-danger').text('Inactivo');
                            // Quitar botones de acción restantes
                            $row.find('.btn-info').remove(); // Quita Editar
                            $button.remove(); // Quita el botón Eliminar
                            // Podrías también aplicar un efecto visual a la fila: $row.addClass('table-danger');

                        } else {
                            // Fallo LÓGICO (el backend respondió, pero indicó un error)
                            Swal.fire(
                                'Error',
                                response.message || 'No se pudo desactivar el usuario.', // Mensaje del backend o genérico
                                'error'
                            );
                            // Rehabilitar el botón si falló la operación lógica
                            $button.prop('disabled', false).html(originalButtonHtml);
                        }
                    },

                    // 5. Callback ERROR (Si la llamada AJAX falló - ej: error de red, 404, 500, error PHP)
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error AJAX en deleteUser:", textStatus, errorThrown);
                        // Mostrar detalles en consola para depuración
                        console.error("Response Text:", jqXHR.responseText);
                        Swal.fire(
                            'Error de Comunicación',
                            'No se pudo conectar con el servidor o el servidor devolvió un error. Revisa la consola (F12).',
                            'error'
                        );
                        // Rehabilitar el botón si falló la comunicación
                        $button.prop('disabled', false).html(originalButtonHtml);
                    }
                }); // Fin $.ajax
            } // Fin if (result.isConfirmed)
        }); // Fin Swal.fire().then
    } // Fin function deleteUser


    // --- Inicialización y Event Listeners ---

    // Tooltips de Bootstrap 5 (si los usas)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Listener para el formulario (Create/Edit)
    if (formAction === 'create' || formAction === 'edit') {
        $form.on('submit', function(event) {
            event.preventDefault(); // Prevenir envío normal
            saveUser(formAction === 'edit');
        });
    }

    // Listener para botones de eliminar (en la tabla) - Delegado
    if (formAction === 'list') {
        $('.table tbody').on('click', '.jsDelete', function() {
            const $button = $(this);
            const userIdToDelete = $button.data('id');
            const userNameToDelete = $button.data('nombre') || 'este usuario'; // Usar nombre del data attribute
            if (userIdToDelete) {
                deleteUser(userIdToDelete, userNameToDelete, $button);
            }
        });

        // Listener para el botón de búsqueda (si existe)
        // Nota: El form ya hace el submit GET, este es opcional si quieres más control
        // $('.JSbtnhSearh').on('click', function(e){
        //    e.preventDefault(); // Prevenir si es type=button, no necesario si es type=submit
        //    $(this).closest('form').submit();
        // });
    }

}); // Fin document ready