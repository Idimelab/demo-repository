$(function() { // Equivalente a $(document).ready()

    const loginForm = $('#loginForm'); // Selecciona el formulario
    const emailInput = $('.js-email');
    const passwordInput = $('.js-password');
    const loginButton = $('.js-login-button');
    const messageArea = $('.js-login-message');

    // --- Manejador de envío del formulario ---
    loginForm.on('submit', function(event) {
        event.preventDefault(); // Previene el envío normal del formulario
        event.stopPropagation();

        // Limpia mensajes y estilos de error previos
        messageArea.text('');
        emailInput.removeClass('is-invalid');
        passwordInput.removeClass('is-invalid');
        loginForm.removeClass('was-validated'); // Quita validación nativa para controlar con JS

        // Validación simple de campos vacíos
        let isValid = true;
        if (emailInput.val() === null || emailInput.val().trim() === '') {
            emailInput.addClass('is-invalid');
            // Muestra el mensaje de feedback asociado al input
            emailInput.siblings('.invalid-feedback').text('Por favor, ingresa tu email.');
            isValid = false;
        }

        if (passwordInput.val() === null || passwordInput.val().trim() === '') {
            passwordInput.addClass('is-invalid');
            // Muestra el mensaje de feedback asociado al input
            passwordInput.siblings('.invalid-feedback').text('Por favor, ingresa tu contraseña.');
            isValid = false;
        }

        // Si la validación básica falla, no continuar
        if (!isValid) {
            return;
        }

        // Deshabilita el botón para evitar envíos múltiples
        loginButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ingresando...');

        // --- Llamada AJAX ---
        $.ajax({
            type: 'POST',
            // Construye la URL completa al endpoint de login en tu backend
            url: FULL_WEB_URL + 'ajax/login.php', // Ajusta esta ruta si es necesario
            data: {
                // Asegúrate que los nombres coincidan con lo que espera tu backend
                userEmail: emailInput.val(), // Cambiado de userName a userEmail
                userPassword: passwordInput.val()
            },
            dataType: 'json', // Esperamos una respuesta JSON del servidor
            success: function(response) {
                // ASUME que el backend responde con un JSON como:
                // Éxito: { "status": "success", "message": "Login correcto", "redirectUrl": "/dashboard" }
                // Error: { "status": "error", "message": "Email o contraseña incorrectos" }

                if (response && response.status === 'success') {
                    // Limpia campos (opcional)
                    // emailInput.val('');
                    // passwordInput.val('');

                    // Muestra mensaje de éxito (opcional, usualmente solo rediriges)
                    messageArea.removeClass('text-danger').addClass('text-success').text(response.message || 'Inicio de sesión exitoso. Redirigiendo...');

                    // Redirige a la URL proporcionada por el backend
                    if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else {
                        // O recarga la página si no hay URL específica (menos común para login)
                        location.reload();
                    }
                    // No necesitas habilitar el botón aquí porque la página cambiará

                } else {
                    // Muestra el mensaje de error del backend
                    messageArea.removeClass('text-success').addClass('text-danger').text(response.message || 'Error desconocido al iniciar sesión.');
                    // Marca los campos como inválidos (opcional, depende de tu lógica)
                    emailInput.addClass('is-invalid');
                    passwordInput.addClass('is-invalid');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Error en la comunicación AJAX (red, servidor caído, 500, etc.)
                console.error("Error AJAX:", textStatus, errorThrown);
                messageArea.removeClass('text-success').addClass('text-danger').text('No se pudo conectar con el servidor. Inténtalo de nuevo.');
            },
            complete: function() {
                // Se ejecuta siempre, después de success o error
                // Solo habilita el botón si hubo un error y no se redirigió
                if (messageArea.hasClass('text-danger')) {
                    loginButton.prop('disabled', false).html('Ingresar');
                }
            }
        }); // Fin AJAX
    }); // Fin submit handler

    // --- Opcional: Permitir envío con Enter en el campo de contraseña ---
    passwordInput.on('keypress', function(event) {
        if (event.key === 'Enter' || event.keyCode === 13) {
            event.preventDefault(); // Previene cualquier comportamiento por defecto del Enter
            loginForm.submit(); // Dispara el evento submit del formulario
        }
    });

}); // Fin document ready