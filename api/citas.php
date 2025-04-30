<?php
require_once(dirname(__FILE__) . '/../configImport.php');
require_once __DIR__ . '/api_helpers.php'; // Incluir helpers
authenticateRequest();

$method = $_SERVER['REQUEST_METHOD'];
$response = ['error' => 'Método o solicitud no válida'];
$statusCode = 400;

// Asumir que la API Key da permisos de Admin/Aux por simplicidad
// Una API real necesitaría verificar permisos del usuario/paciente
$id_modificador_api = 0; // Podrías obtener un ID asociado a la API Key si tuvieras ese sistema

try {
    switch ($method) {
        case 'GET':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                // Obtener cita por ID
                $cita = Citas::getById($id); // getById debería hacer JOINs
                if ($cita) {
                    $response = $cita;
                    $statusCode = 200;
                } else {
                    $response = ['error' => 'Cita no encontrada'];
                    $statusCode = 404;
                }
            } else {
                // Listar citas (filtrable por fecha, estado, paciente, etc.)
                $fecha_desde = filter_input(INPUT_GET, 'fecha_desde', FILTER_SANITIZE_STRING);
                $fecha_hasta = filter_input(INPUT_GET, 'fecha_hasta', FILTER_SANITIZE_STRING);
                $estado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING);
                $id_paciente = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
                // Validar fechas si se proporcionan
                if ((!empty($fecha_desde) && !ValidateData::validateDate($fecha_desde)) || (!empty($fecha_hasta) && !ValidateData::validateDate($fecha_hasta))) {
                    throw new InvalidArgumentException("Formato de fecha inválido para filtros (usar YYYY-MM-DD).");
                }
                $fecha_desde = $fecha_desde ?: date('Y-m-d', strtotime('-7 days')); // Default si no se provee
                $fecha_hasta = $fecha_hasta ?: date('Y-m-d', strtotime('+30 days')); // Default si no se provee

                $filters = [];
                if ($estado && in_array($estado, Citas::ESTADOS_CITA)) $filters['estado'] = $estado;
                if ($id_paciente) $filters['id_paciente'] = $id_paciente;
                // Añadir más filtros desde GET si es necesario

                $citas = Citas::getByDateRange($fecha_desde, $fecha_hasta, $filters);
                $response = ['data' => $citas]; // Devolver directamente el array de citas
                $statusCode = 200;
            }
            break;

        case 'POST':
            // Crear cita
            $input = getJsonInput();
            // --- VALIDACIÓN ROBUSTA REQUERIDA ---
            if (!isset($input['id_paciente'], $input['id_examen'], $input['fecha_cita'], $input['hora_cita'])) {
                throw new InvalidArgumentException("Faltan campos requeridos (id_paciente, id_examen, fecha_cita, hora_cita).");
            }
            if (!ValidateData::validateInt($input['id_paciente']) || !ValidateData::validateInt($input['id_examen']) || !ValidateData::validateDate($input['fecha_cita']) || empty($input['hora_cita'])) {
                throw new InvalidArgumentException("Datos de cita inválidos.");
            }
            // Verificar disponibilidad (¡CRUCIAL!)
            if (!Citas::checkAvailability($input['id_paciente'], $input['fecha_cita'], $input['hora_cita'])) {
                $response = ['error' => 'Horario no disponible para el paciente seleccionado.'];
                $statusCode = 409; // Conflict
                break; // Salir del switch
            }

            $result = Citas::create(
                $input['id_paciente'], $input['id_examen'], $input['fecha_cita'], $input['hora_cita'],
                $input['observaciones_paciente'] ?? null,
                $input['estado'] ?? 'Programada' // Default a Programada
            );
            if (is_numeric($result) && $result > 0) {
                $newCita = Citas::getById($result);
                $response = ['status' => 'success', 'message' => 'Cita creada.', 'data' => $newCita];
                $statusCode = 201;
            } else {
                throw new RuntimeException("Error al crear la cita.");
            }
            break;

        case 'PUT':
            // Actualizar cita (reagendar, cambiar estado, notas)
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException("Falta ID de cita para actualizar.");
            }
            $input = getJsonInput();
            if (empty($input)) {
                throw new InvalidArgumentException("Faltan datos para actualizar.");
            }
            // --- VALIDACIÓN ROBUSTA DE INPUT REQUERIDA ---
            // Validar nueva fecha/hora, estado, etc.

            // Verificar si se intenta reagendar y chequear disponibilidad y reglas (ej: 24h) si aplica
            $needsAvailabilityCheck = isset($input['fecha_cita']) || isset($input['hora_cita']);
            $citaOriginal = null;
            if($needsAvailabilityCheck) {
                $citaOriginal = Citas::getById($id);
                if(!$citaOriginal) {
                    $response = ['error' => 'Cita no encontrada']; $statusCode = 404; break;
                }
                // Aquí podrías añadir la lógica de isChangeAllowed si esta API la usan pacientes
                // if (!Citas::isChangeAllowed($citaOriginal['fecha_cita'], $citaOriginal['hora_cita'])) { ... }

                if (!Citas::checkAvailability(
                    $citaOriginal['id_paciente'], // Usar ID paciente original
                    $input['fecha_cita'] ?? $citaOriginal['fecha_cita'],
                    $input['hora_cita'] ?? $citaOriginal['hora_cita'],
                    $id // Excluir la cita actual
                )) {
                    $response = ['error' => 'Nuevo horario no disponible.']; $statusCode = 409; break;
                }
            }

            // Construir array $dataToUpdate solo con los campos permitidos/enviados
            $dataToUpdate = [];
            if(isset($input['estado'])) $dataToUpdate['estado'] = $input['estado']; // Validar estado
            if(isset($input['observaciones_internas'])) $dataToUpdate['observaciones_internas'] = $input['observaciones_internas'];
            if(isset($input['observaciones_paciente'])) $dataToUpdate['observaciones_paciente'] = $input['observaciones_paciente'];
            if(isset($input['fecha_cita'])) $dataToUpdate['fecha_cita'] = $input['fecha_cita']; // Validar fecha
            if(isset($input['hora_cita'])) $dataToUpdate['hora_cita'] = $input['hora_cita']; // Validar hora

            // Llamar a Citas::update modificado
            $result = Citas::update($id,
                $dataToUpdate['estado'] ?? null,
                $dataToUpdate['observaciones_internas'] ?? null,
                $id_modificador_api, // ID de quien modifica (API Key user)
                $dataToUpdate['observaciones_paciente'] ?? null,
                $dataToUpdate['fecha_cita'] ?? null,
                $dataToUpdate['hora_cita'] ?? null
            );

            if ($result === true) {
                $updatedCita = Citas::getById($id);
                $response = ['status' => 'success', 'message' => 'Cita actualizada.', 'data' => $updatedCita];
                $statusCode = 200;
            } else {
                if (!Citas::getById($id)) { $statusCode = 404; $response = ['error' => 'Cita no encontrada']; }
                else { throw new RuntimeException("Error al actualizar la cita."); }
            }
            break;

        case 'DELETE':
            // Cancelar cita
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException("Falta ID de cita para cancelar.");
            }
            // Verificar si existe y aplicar reglas si es necesario (ej: 24h para paciente API key)
            $citaOriginal = Citas::getById($id);
            if (!$citaOriginal) { $statusCode = 404; $response = ['error' => 'Cita no encontrada']; break; }
            // Aquí iría chequeo isChangeAllowed si la key no fuera de admin

            $result = Citas::cancel($id, 'Auxiliar', $id_modificador_api, 'Cancelada vía API'); // Asume Admin/Aux cancela
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Cita cancelada.'];
                $statusCode = 200; // O 204 No Content
            } else {
                throw new RuntimeException("Error al cancelar la cita.");
            }
            break;

        default:
            $response = ['error' => "Método HTTP {$method} no soportado."];
            $statusCode = 405;
            break;
    }
} catch (InvalidArgumentException $e) { /* ... */ }

sendJsonResponse($response, $statusCode);
?>