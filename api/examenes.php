<?php
require_once(dirname(__FILE__) . '/../configImport.php');
require_once __DIR__ . '/api_helpers.php'; // Incluir helpers
authenticateRequest();


$method = $_SERVER['REQUEST_METHOD'];
$response = ['error' => 'Método o solicitud no válida'];
$statusCode = 400;

try {
    switch ($method) {
        case 'GET':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                // Obtener examen por ID
                $examen = Examenes::getById($id);
                if ($examen) { $response = $examen; $statusCode = 200; }
                else { $response = ['error' => 'Examen no encontrado']; $statusCode = 404; }
            } else {
                // Listar exámenes (opción para listar solo activos o todos)
                $listAll = filter_input(INPUT_GET, 'all', FILTER_VALIDATE_BOOLEAN);
                if ($listAll) {
                    // TODO: Modificar Examenes::getAll para listar todos si es necesario, o usar el existente
                    $examenes = Examenes::getAll(1, 9999); // Asume getAll lista todos paginados
                } else {
                    $examenes = Examenes::getAllActive(); // Método existente
                }
                $response = ['data' => $examenes];
                $statusCode = 200;
            }
            break;

        case 'POST':
            // Crear examen
            $input = getJsonInput();
            // --- VALIDACIÓN ROBUSTA REQUERIDA ---
            if (!isset($input['nombre_examen'], $input['precio'])) { throw new InvalidArgumentException("Faltan campos requeridos (nombre_examen, precio)."); }
            if (!is_numeric($input['precio']) || $input['precio'] < 0) { throw new InvalidArgumentException("Precio inválido.");}

            $result = Examenes::create(
                $input['nombre_examen'], $input['precio'],
                $input['codigo_examen'] ?? null, $input['descripcion'] ?? null,
                $input['preparacion'] ?? null, $input['activo'] ?? true
            );
            if (is_numeric($result) && $result > 0) {
                $newExamen = Examenes::getById($result);
                $response = ['status' => 'success', 'message' => 'Examen creado.', 'data' => $newExamen];
                $statusCode = 201;
            } elseif (is_string($result)) { // Ej: CodigoExistente
                $response = ['error' => $result]; $statusCode = 409;
            } else { throw new RuntimeException("Error al crear el examen."); }
            break;

        case 'PUT':
            // Actualizar examen
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new InvalidArgumentException("Falta ID de examen.");
            $input = getJsonInput();
            if (empty($input)) throw new InvalidArgumentException("Faltan datos para actualizar.");
            // --- VALIDACIÓN ROBUSTA DE INPUT REQUERIDA ---

            $result = Examenes::update($id, $input);
            if ($result === true) {
                $updatedExamen = Examenes::getById($id);
                $response = ['status' => 'success', 'message' => 'Examen actualizado.', 'data' => $updatedExamen];
                $statusCode = 200;
            } elseif (is_string($result)) { // Ej: CodigoExistente
                $response = ['error' => $result]; $statusCode = 409;
            } else {
                if (!Examenes::getById($id)) { $statusCode = 404; $response = ['error' => 'Examen no encontrado']; }
                else { throw new RuntimeException("Error al actualizar el examen."); }
            }
            break;

        case 'DELETE':
            // Desactivar examen (borrado lógico)
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new InvalidArgumentException("Falta ID de examen.");
            if (!Examenes::getById($id)) { $statusCode = 404; $response = ['error' => 'Examen no encontrado']; break; }

            $result = Examenes::delete($id); // delete llama a update(['activo' => false])
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Examen desactivado.'];
                $statusCode = 200; // O 204
            } else { throw new RuntimeException("Error al desactivar el examen."); }
            break;

        default:
            $response = ['error' => "Método HTTP {$method} no soportado."];
            $statusCode = 405;
            break;
    }
} catch (InvalidArgumentException $e) { /* ... */ }

sendJsonResponse($response, $statusCode);
?>