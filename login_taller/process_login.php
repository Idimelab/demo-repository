<?php
// Iniciar la sesión SIEMPRE al principio
session_start();

// Incluir el archivo de configuración para obtener la conexión $conn
require_once 'config.php'; // Usamos require_once para asegurar que se incluya una sola vez y es esencial

// Verificar si se recibieron datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = '';
    $password = '';
    $error_message = ''; // Variable para mensajes de error específicos de este script

    // Obtener y limpiar (básicamente) los datos del formulario
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validar que los campos no estén vacíos
    if (empty($username)) {
        $error_message = "Por favor, ingresa tu nombre de usuario.";
    } elseif (empty($password)) {
        $error_message = "Por favor, ingresa tu contraseña.";
    }

    // Si no hay errores de validación inicial, proceder a consultar la DB
    if (empty($error_message)) {

        // --- Consulta a la base de datos usando Sentencias Preparadas ---

        // 1. Preparar la consulta SQL para evitar inyección SQL
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // 2. Vincular variables a la sentencia preparada como parámetros
            // "s" significa que la variable $username es de tipo string
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Establecer el parámetro
            $param_username = $username;

            // 3. Intentar ejecutar la sentencia preparada
            if (mysqli_stmt_execute($stmt)) {

                // 4. Obtener el resultado de la sentencia preparada (¡Alternativa a bind_result!)
                $result = mysqli_stmt_get_result($stmt);

                // 5. Verificar si se encontró exactamente una fila (un usuario)
                if (mysqli_num_rows($result) == 1) {

                    // 6. Obtener la fila del resultado como un array asociativo
                    $user = mysqli_fetch_assoc($result);

                    // 7. Verificar la contraseña usando password_verify
                    // Compara la contraseña enviada ($password) con el hash almacenado ($user['password'])
                    if ($password==$user['password']) {
                        // ¡Contraseña correcta! Autenticación exitosa.

                        // Regenerar el ID de sesión por seguridad
                        session_regenerate_id(true);

                        // Almacenar datos en variables de sesión usando los datos del array $user
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $user['id']; // Guardar el ID del usuario
                        $_SESSION["username"] = $user['username']; // Guardar el nombre de usuario

                        // Redirigir al usuario al dashboard
                        header("location: dashboard.php");
                        exit; // Asegura que el script termine aquí

                    } else {
                        // La contraseña no es válida
                        $error_message = "La contraseña que ingresaste no es válida.";
                    }
                } else {
                    // El nombre de usuario no existe (0 filas) o hay un problema (más de 1 fila, ¡imposible con UNIQUE!)
                    $error_message = "No se encontró ninguna cuenta con ese nombre de usuario.";
                }

                // 8. Liberar el resultado (aunque mysqli lo hace al final, es buena práctica)
                mysqli_free_result($result);

            } else {
                // Error al ejecutar la consulta
                $error_message = "¡Ups! Algo salió mal al ejecutar la consulta. Por favor, inténtalo de nuevo más tarde.";
                // error_log("Error al ejecutar la consulta de login: " . mysqli_stmt_error($stmt));
            }

            // 9. Cerrar la sentencia preparada
            mysqli_stmt_close($stmt);

        } else {
            // Error al preparar la consulta
            $error_message = "¡Ups! Algo salió mal con la preparación de la consulta.";
            // error_log("Error al preparar la consulta de login: " . mysqli_error($conn));
        }
    }

    // Si hubo algún error ($error_message no está vacío), redirigir de vuelta al login
    if (!empty($error_message)) {
        $_SESSION['error_message'] = $error_message;
        header('Location: login.php');
        exit;
    }

    // 10. Cerrar la conexión a la base de datos
    mysqli_close($conn);

} else {
    // Si se intenta acceder directamente al script
    $_SESSION['error_message'] = "Acceso no permitido.";
    header('Location: login.php');
    exit;
}
?>