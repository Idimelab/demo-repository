$(function() { // Document Ready

    // --- Selectores del Formulario de Registro ---
    const $form = $('#registerForm');
    const $nombreInput = $('.js-nombre');
    const $apellidoInput = $('.js-apellido');
    const $tipoDocSelect = $('.js-tipo-documento');
    const $documentoInput = $('.js-documento');
    const $emailInput = $('.js-email');
    const $telefonoInput = $('.js-telefono');
    const $fechaNacInput = $('.js-fecha-nacimiento');
    const $direccionInput = $('.js-direccion');
    const $epsInput = $('.js-eps');
    const $passwordInput = $('.js-password');
    const $passwordConfirmInput = $('.js-password-confirm');
    const $registerButton = $('.js-register-button');
    const $errorMessageDiv = $('#form-error-message');
    const $successMessageDiv = $('#form-success-message');

    // URL del backend handler (Asegúrate que exista este archivo PHP)
    const registerApiUrl = FULL_WEB_URL + 'ajax/register_handler.php';
    const loginUrl = FULL_WEB_URL; // URL a la que redirigir tras éxito (o mostrar mensaje)

    // --- Funciones Auxiliares ---
    function clearFormErrors() {
        $errorMessageDiv.addClass('d-none').text('');
        $successMessageDiv.addClass('d-none').text(''); // Limpiar éxito también
        $form.find('.is-invalid').removeClass('is-invalid');
    }

    function showFormError(message) {
        $successMessageDiv.addClass('d-none').text(''); // Ocultar éxito si hay error
        $errorMessageDiv.removeClass('d-none').text(message || 'Ocurrió un error.');
    }
    function showFormSuccess(message) {
        $errorMessageDiv.addClass('d-none').text(''); // Ocultar error si hay éxito
        $successMessageDiv.removeClass('d-none').text(message || 'Operación exitosa.');
    }

    function showInputError($input, message) {
        $input.addClass('is-invalid');
        $input.siblings('.invalid-feedback').text(message);
    }

    // --- Validación del Formulario ---
    function validateRegisterForm() {
        clearFormErrors();
        let isValid = true;

        // Campos requeridos básicos
        if ($nombreInput.val().trim() === '') { showInputError($nombreInput, 'Ingresa tu nombre.'); isValid = false; }
        if ($apellidoInput.val().trim() === '') { showInputError($apellidoInput, 'Ingresa tus apellidos.'); isValid = false; }
        if ($tipoDocSelect.val() === null || $tipoDocSelect.val() === '') { showInputError($tipoDocSelect, 'Selecciona un tipo.'); isValid = false; }
        if ($documentoInput.val().trim() === '') { showInputError($documentoInput, 'Ingresa tu número de documento.'); isValid = false; }

        // Email
        const emailVal = $emailInput.val().trim();
        if (emailVal === '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            showInputError($emailInput, 'Ingresa un correo electrónico válido.');
            isValid = false;
        }

        // Contraseñas
        const passwordVal = $passwordInput.val();
        const passwordConfirmVal = $passwordConfirmInput.val();
        if (passwordVal === '' || passwordVal.length < 6) {
            showInputError($passwordInput, 'La contraseña es requerida (mínimo 6 caracteres).');
            isValid = false;
        }
        if (passwordConfirmVal === '') {
            showInputError($passwordConfirmInput, 'Confirma tu contraseña.');
            isValid = false;
        }
        // Solo comparar si ambas contraseñas tienen algo y la primera es válida en longitud
        if (passwordVal.length >= 6 && passwordConfirmVal !== '' && passwordVal !== passwordConfirmVal) {
            showInputError($passwordConfirmInput, 'Las contraseñas no coinciden.');
            isValid = false;
        }

        // Validar formato fecha si se ingresó
        const fechaNacVal = $fechaNacInput.val();
        if (fechaNacVal !== '' && !/^\d{4}-\d{2}-\d{2}$/.test(fechaNacVal)) {
            showInputError($fechaNacInput, 'Formato de fecha inválido (AAAA-MM-DD).');
            isValid = false;
        }

        return isValid;
    }

    // --- Recolectar Datos del Formulario ---
    function getRegisterFormData() {
        return {
            nombre: $nombreInput.val().trim(),
            apellido: $apellidoInput.val().trim(),
            tipo_documento: $tipoDocSelect.val(),
            documento: $documentoInput.val().trim(),
            email: $emailInput.val().trim(),
            telefono: $telefonoInput.val().trim() || null,
            fecha_nacimiento: $fechaNacInput.val() || null,
            direccion: $direccionInput.val().trim() || null,
            eps: $epsInput.val().trim() || null,
            plainPassword: $passwordInput.val() // Enviar contraseña en texto plano (el backend la hashea)
            // No enviar confirmación de contraseña al backend
            // El rol se asigna en el backend (Paciente)
        };
    }

    // --- Manejador de Envío del Formulario ---
    $form.on('submit', function(event) {
        event.preventDefault(); // Prevenir envío normal

        if (!validateRegisterForm()) {
            showFormError('Por favor corrige los errores indicados.');
            // Enfocar el primer campo con error (opcional)
            $('.is-invalid').first().focus();
            return;
        }

        const formData = getRegisterFormData();
        const originalButtonHtml = $registerButton.html();

        // Mostrar estado de carga
        $registerButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Registrando...');
        clearFormErrors(); // Limpiar errores antes de enviar

        // --- Llamada AJAX ---
        $.ajax({
            url: registerApiUrl, // URL del handler PHP
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    // Éxito
                    $form[0].reset(); // Limpiar formulario
                    showFormSuccess(response.message || '¡Registro exitoso! Ya puedes iniciar sesión.');
                    // Opcional: Redirigir al login después de un tiempo
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registro Completo!',
                        text: response.message || 'Tu cuenta ha sido creada. Ahora puedes iniciar sesión.',
                        confirmButtonText: 'Ir a Iniciar Sesión'
                    }).then((result) => {
                        if (result.value) {
                            window.location.href = loginUrl;
                        }
                    });
                    // No rehabilitar botón aquí porque mostramos éxito/redirigimos

                } else {
                    // Error reportado por el backend (ej: email duplicado)
                    showFormError(response.message || 'No se pudo completar el registro.');
                    $registerButton.prop('disabled', false).html(originalButtonHtml); // Rehabilitar botón
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Error de conexión o del servidor
                console.error("Error AJAX Registro:", textStatus, errorThrown, jqXHR.responseText);
                showFormError('Error de conexión al intentar registrar. Inténtalo de nuevo más tarde.');
                $registerButton.prop('disabled', false).html(originalButtonHtml); // Rehabilitar botón
            }
        }); // Fin AJAX
    }); // Fin form submit handler

}); // Fin document ready