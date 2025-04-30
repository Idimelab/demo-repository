<?php
require_once(dirname(__FILE__) . '/../configImport.php');
require_once __DIR__ . '/api_helpers.php'; // Incluir helpers
authenticateRequest(); // Verificar API Key
$method = $_SERVER['REQUEST_METHOD'];
$response = ['error' => 'Método o solicitud no válida'];
$statusCode = 400;

try {
    switch ($method) {
        case 'GET':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                // Obtener usuario por ID
                $user = Usuarios::getById($id);
                if ($user) {
                    // ¡IMPORTANTE! No devolver el hash de la contraseña en la API
                    unset($user['contrasena_hash'], $user['palabra_seguridad_hash']);
                    $response = $user;
                    $statusCode = 200;
                } else {
                    $response = ['error' => 'Usuario no encontrado'];
                    $statusCode = 404;
                }
            } else {
                // Listar usuarios (con paginación y búsqueda opcional)
                $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
                $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 20]]);
                $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
                // Podrías añadir filtros GET para rol, estado, etc.
                $filters = []; // Ej: $filters['id_rol'] = filter_input(INPUT_GET, 'rol', FILTER_VALIDATE_INT);

                $users = Usuarios::getAll($page, $limit, $search, false, $filters);
                // Quitar contraseñas de la lista
                foreach ($users as &$user) {
                    unset($user['contrasena_hash'], $user['palabra_seguridad_hash']);
                }
                unset($user); // Romper referencia

                $total = Usuarios::getAll($page, $limit, $search, true, $filters);
                $response = [
                    'data' => $users,
                    'pagination' => [
                        'total' => $total,
                        'perPage' => $limit,
                        'currentPage' => $page,
                        'totalPages' => ceil($total / $limit)
                    ]
                ];
                $statusCode = 200;
            }
            break;

        case 'POST':
            // Crear usuario
            $input = getJsonInput();
            // --- VALIDACIÓN ROBUSTA DEL INPUT ES CRUCIAL AQUÍ ---
            if (!isset($input['email'], $input['plainPassword'], $input['nombre'], $input['apellido'], $input['id_rol'], $input['tipo_documento'], $input['documento'])) {
                throw new InvalidArgumentException("Faltan campos requeridos para crear usuario.");
            }
            // Añadir más validaciones (longitud, formato, rol existe?, etc.)

            // Llamar a create (asume que valida duplicados y hashea contraseña)
            $result = Usuarios::create(
                $input['id_rol'], $input['nombre'], $input['apellido'], $input['tipo_documento'],
                $input['documento'], $input['email'], $input['plainPassword'],
                $input['fecha_nacimiento'] ?? null, $input['telefono'] ?? null, $input['direccion'] ?? null,
                $input['eps'] ?? null, $input['palabra_seguridad'] ?? null
            );

            if (is_numeric($result) && $result > 0) {
                $newUser = Usuarios::getById($result); // Obtener datos del usuario creado
                unset($newUser['contrasena_hash'], $newUser['palabra_seguridad_hash']); // Quitar hash
                $response = ['status' => 'success', 'message' => 'Usuario creado.', 'data' => $newUser];
                $statusCode = 201; // Created
            } elseif (is_string($result)) { // Error específico de create (ej: EmailExistente)
                $response = ['error' => $result];
                $statusCode = 409; // Conflict (o 400 Bad Request)
            } else {
                throw new RuntimeException("Error al crear el usuario en la base de datos.");
            }
            break;

        case 'PUT':
            // Actualizar usuario
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException("Falta ID de usuario para actualizar.");
            }
            $input = getJsonInput();
            if (empty($input)) {
                throw new InvalidArgumentException("Faltan datos para actualizar.");
            }
            // --- VALIDACIÓN ROBUSTA DEL INPUT ---
            // (validar campos enviados, formato email, etc.)

            $result = Usuarios::update($id, $input); // update maneja qué campos actualizar

            if ($result === true) {
                $updatedUser = Usuarios::getById($id);
                unset($updatedUser['contrasena_hash'], $updatedUser['palabra_seguridad_hash']);
                $response = ['status' => 'success', 'message' => 'Usuario actualizado.', 'data' => $updatedUser];
                $statusCode = 200;
            } elseif (is_string($result)) { // Error específico de update (ej: EmailExistente)
                $response = ['error' => $result];
                $statusCode = 409; // Conflict (o 400 Bad Request)
            } else {
                // Verificar si el usuario no existía
                if (!Usuarios::getById($id)) {
                    $response = ['error' => 'Usuario no encontrado para actualizar'];
                    $statusCode = 404;
                } else {
                    throw new RuntimeException("Error al actualizar el usuario.");
                }
            }
            break;

        case 'DELETE':
            // Desactivar/Eliminar usuario
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException("Falta ID de usuario para eliminar.");
            }

            // Verificar si existe antes de intentar borrar
            if (!Usuarios::getById($id)) {
                $response = ['error' => 'Usuario no encontrado para eliminar'];
                $statusCode = 404;
            } else {
                $result = Usuarios::delete($id); // Asume borrado lógico (desactivar)
                if ($result) {
                    $response = ['status' => 'success', 'message' => 'Usuario desactivado.'];
                    $statusCode = 200; // O 204 No Content si no devuelves cuerpo
                } else {
                    throw new RuntimeException("Error al desactivar el usuario.");
                }
            }
            break;

        default:
            $response = ['error' => "Método HTTP {$method} no soportado."];
            $statusCode = 405; // Method Not Allowed
            break;
    }

} catch (InvalidArgumentException $e) {
    $statusCode = 400; // Bad Request
    $response = ['error' => $e->getMessage()];
} catch (RuntimeException | Exception $e) {
    // Error genérico del servidor o de lógica
    $statusCode = 500; // Internal Server Error
    $response = ['error' => 'Error interno del servidor: ' . $e->getMessage()];
    error_log("API Error (usuarios.php): " . $e->getMessage()); // Loguear el error real
}

sendJsonResponse($response, $statusCode);

?>