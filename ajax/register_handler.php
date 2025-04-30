<?php
// ajax/register_handler.php

require_once(__DIR__ . '/../configImport.php'); // Ajusta ruta

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Error desconocido durante el registro.'];

// --- Seguridad básica: Verificar que sea POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

// --- Recolectar y Validar/Sanear Datos POST ---
// ¡IMPORTANTE: Añade validaciones más robustas aquí!
$nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING));
$apellido = trim(filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING));
$tipo_doc = filter_input(INPUT_POST, 'tipo_documento', FILTER_SANITIZE_STRING);
$documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING));
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = isset($_POST['plainPassword']) ? $_POST['plainPassword'] : null; // No sanear contraseña aquí
$telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?: null;
$fecha_nac = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_STRING) ?: null;
$direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING) ?: null;
$eps = filter_input(INPUT_POST, 'eps', FILTER_SANITIZE_STRING) ?: null;

// --- Validaciones del Lado del Servidor (CRUCIALES) ---
$errors = [];
if (empty($nombre)) $errors[] = 'El nombre es requerido.';
if (empty($apellido)) $errors[] = 'El apellido es requerido.';
if (empty($tipo_doc) || !in_array($tipo_doc, ['CC', 'CE', 'TI', 'PAS'])) $errors[] = 'Tipo de documento inválido.';
if (empty($documento)) $errors[] = 'El número de documento es requerido.';
if (!$email) $errors[] = 'El correo electrónico no es válido.';
if (empty($password) || strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
if (!empty($fecha_nac) && !ValidateData::validateDate($fecha_nac)) $errors[] = 'Formato de fecha de nacimiento inválido.'; // Usar tu validador

// Verificar duplicados ANTES de intentar crear
if (empty($errors)) {
    if (Usuarios::checkIfExistsByEmail($email)) {
        $errors[] = 'El correo electrónico ya está registrado.';
    }
    if (Usuarios::checkIfExistsByDocumento($documento)) {
        $errors[] = 'El número de documento ya está registrado.';
    }
}

// Si hay errores de validación, devolverlos
if (!empty($errors)) {
    $response['message'] = implode(' ', $errors);
    echo json_encode($response);
    exit;
}

// --- Intentar Crear el Usuario ---
try {
    // Obtener ID del rol 'Paciente' (Asumiendo ID=1 o consultando la BD)
    // $id_rol_paciente = Roles::getIdByNombre('Paciente'); // Idealmente
    $id_rol_paciente = 1; // O el ID que corresponda

    if (!$id_rol_paciente) {
        throw new Exception("Rol 'Paciente' no encontrado en la base de datos.");
    }

    // Llamar al método create de la clase Usuarios
    $result = Usuarios::create(
        $id_rol_paciente, $nombre, $apellido, $tipo_doc, $documento, $email, $password,
        $fecha_nac, $telefono, $direccion, $eps
    // Asegúrate que los parámetros coincidan con tu método Usuarios::create
    );

    if (is_numeric($result) && $result > 0) {
        // Éxito
        $response = ['status' => 'success', 'message' => '¡Registro completado exitosamente! Ya puedes iniciar sesión.'];
    } elseif (is_string($result)) {
        // Si Usuarios::create devuelve un error específico como string
        $response['message'] = 'Error al registrar: ' . $result;
    } else {
        // Otro tipo de error durante la creación
        $response['message'] = 'No se pudo crear la cuenta en este momento.';
        error_log("Error en Usuarios::create para email {$email}: " ); // Loguear error DB
    }

} catch (Exception $e) {
    error_log("Excepción en Registro Handler: " . $e->getMessage());
    $response['message'] = 'Ocurrió un error inesperado en el servidor.';
}

// --- Devolver Respuesta JSON ---
echo json_encode($response);
exit;

?>