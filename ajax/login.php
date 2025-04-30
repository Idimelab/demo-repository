<?php
/**
 * @Description: Procesa el inicio de sesión del usuario vía AJAX.
 * Valida credenciales, obtiene el rol, crea la sesión y devuelve un JSON específico.
 */

// 1. Importar archivos necesarios (Clases Db, Usuarios, Security, Roles, ValidateData, etc.)
// Asegúrate que este archivo carga todo lo necesario y maneja session_start() si es apropiado aquí.
require_once(dirname(__FILE__) . '/../configImport.php');

// --- Seguridad Adicional: Headers ---
header('Content-Type: application/json'); // Indicar que la respuesta es JSON
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
// Considera añadir políticas de seguridad de contenido (CSP) más estrictas

// --- Estructura de Respuesta por Defecto (Error) ---
$response = array(
    'status' => 'error',              // 'success' o 'error'
    'message' => 'Error desconocido.', // Mensaje descriptivo
    'redirectUrl' => null             // URL a la que redirigir en caso de éxito
);

// --- Obtener y Validar Datos de Entrada ---
$email = filter_input(INPUT_POST, "userEmail", FILTER_VALIDATE_EMAIL);
$password = isset($_POST['userPassword']) ? $_POST['userPassword'] : null;

if (!$email || $password === null) {
    error_log("Login AJAX: Email inválido o contraseña no proporcionada.");
    $response['message'] = 'Email o contraseña no proporcionados o formato inválido.';
    echo json_encode($response);
    exit;
}
$contraseña=Security::passwordConstructor($password);
// --- Validación de Credenciales ---
$userData = Usuarios::validateLogin($email, $password);

if ($userData !== false && is_array($userData)) {
    // Usuario y contraseña válidos

    // --- Obtener Nombre del Rol ---
    $id_rol = isset($userData['id_rol']) ? (int)$userData['id_rol'] : null;
    $role_name = ($id_rol !== null) ? Roles::getNombreById($id_rol) : null;

    if ($role_name !== null) {
        // Rol encontrado

        // --- Crear Sesión ---
        $user_id = (int)$userData['id_usuario'];
        $session_created = Security::sessionCreate($role_name, $user_id, $userData);

        if ($session_created) {
            // Éxito: Actualizar la respuesta
            $response['status'] = 'success';
            $response['message'] = 'Inicio de sesión exitoso. Redirigiendo...';

            // --- Determinar URL de Redirección ---
            // Puedes hacer esto más complejo basado en $role_name si es necesario
            // Ejemplo:
            // switch ($role_name) {
            //     case 'Administrador':
            //         $response['redirectUrl'] = '/admin/dashboard';
            //         break;
            //     case 'Auxiliar':
            //         $response['redirectUrl'] = '/aux/citas';
            //         break;
            //     case 'Paciente':
            //         $response['redirectUrl'] = '/paciente/mis-citas';
            //         break;
            //     default:
            //         $response['redirectUrl'] = '/dashboard'; // URL por defecto
            // }
            // Por ahora, usamos una URL genérica como en el ejemplo de formato:
            $response['redirectUrl'] = '/dashboard'; // O la URL raíz, o la específica que necesites

            error_log("Login Exitoso: Usuario ID {$user_id}, Rol: {$role_name}. Redirigiendo a {$response['redirectUrl']}");

        } else {
            // Falló la creación de la sesión
            error_log("Login Error: Falló sessionCreate para usuario ID {$user_id}.");
            $response['message'] = 'Error interno del servidor [SC]. Intente de nuevo.'; // Código de error interno opcional
        }

    } else {
        // Rol no encontrado en la BD (inconsistencia de datos)
        error_log("Login Error: Rol ID {$id_rol} no encontrado en la tabla 'roles' para usuario ID {$user_id}.");
        $response['message'] = 'Error de configuración [RC]. Contacte al administrador.'; // Código de error interno opcional
    }

} else {
    // Usuario o contraseña inválidos
    error_log("Login Fallido: Credenciales inválidas para email '{$email}'.");
    $response['message'] = 'El email o la contraseña son incorrectos.';
}

// --- Enviar Respuesta JSON Final ---
echo json_encode($response);

?>