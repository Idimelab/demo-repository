<?php
// ajax/paciente/cita_handler.php

require_once(__DIR__ . '/../../configImport.php'); // Ajusta ruta

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Acción no válida o faltan datos.'];

// --- Seguridad: Verificar Sesión y Rol Paciente ---
if (session_status() == PHP_SESSION_NONE) session_start();
$id_user = Security::GetSessionUserId();

$id_paciente_session = $id_user;

// Determinar Acción y ID de Cita (si aplica)
$action = isset($_POST['action']) ? $_POST['action'] : null; // 'cancel' o implícito para create/update
$id_cita = isset($_POST['id_cita']) ? filter_var($_POST['id_cita'], FILTER_VALIDATE_INT) : null; // Para edit y cancel

try {
    // --- Acción Cancelar Cita ---
    if ($action === 'cancel' && $id_cita) {
        // 1. Obtener cita para verificar propiedad y regla 24h
        $cita_original = Citas::getById($id_cita);
        if (!$cita_original) {
            $response['message'] = 'Cita no encontrada.';
        } elseif ($cita_original['id_paciente'] != $id_paciente_session) {
            $response['message'] = 'No tienes permiso para cancelar esta cita.';
            http_response_code(403); // Forbidden
        } elseif (!Citas::isChangeAllowed($cita_original['fecha_cita'], $cita_original['hora_cita'])) {
            $response['message'] = 'No se puede cancelar la cita con menos de 24 horas de antelación.';
        } else {
            // Proceder a cancelar
            $result = Citas::cancel($id_cita, 'Paciente', $id_paciente_session); // Usar 'Paciente' como origen
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Tu cita ha sido cancelada exitosamente.'];
            } else {
                $response['message'] = 'Error al intentar cancelar la cita.';
            }
        }

        // --- Acción Editar/Reagendar Cita ---
    } elseif ($id_cita && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'cancel') {
        // 1. Obtener cita original para verificaciones
        $cita_original = Citas::getById($id_cita);
        if (!$cita_original) {
            $response['message'] = 'Cita no encontrada.';
        } elseif ($cita_original['id_paciente'] != $id_paciente_session) {
            $response['message'] = 'No tienes permiso para modificar esta cita.';
            http_response_code(403);
        } elseif (!in_array($cita_original['estado'], ['Programada', 'Confirmada'])) {
            $response['message'] = 'Solo se pueden reagendar citas en estado Programada o Confirmada.';
        } elseif (!Citas::isChangeAllowed($cita_original['fecha_cita'], $cita_original['hora_cita'])) {
            $response['message'] = 'No se puede reagendar la cita con menos de 24 horas de antelación.';
        } else {
            // 2. Validar nuevos datos (Fecha, Hora, Observaciones)
            $nueva_fecha = filter_input(INPUT_POST, 'fecha_cita', FILTER_SANITIZE_STRING);
            $nueva_hora = filter_input(INPUT_POST, 'hora_cita', FILTER_SANITIZE_STRING);
            $obs_paciente = filter_input(INPUT_POST, 'observaciones_paciente', FILTER_SANITIZE_STRING) ?: null;
            $hoy = date('Y-m-d');

            if (!ValidateData::validateDate($nueva_fecha) || $nueva_fecha < $hoy || empty($nueva_hora)) { // Validar fecha futura y hora no vacía
                $response['message'] = 'La nueva fecha y hora proporcionadas no son válidas.';
            } else {
                // 3. Verificar Disponibilidad del nuevo horario
                if (!Citas::checkAvailability($id_paciente_session, $nueva_fecha, $nueva_hora, $id_cita)) {
                    $response['message'] = 'El horario seleccionado ya no está disponible. Por favor, elige otro.';
                } else {
                    // 4. Proceder a actualizar (reagendar)
                    // El método update debería resetear estado a 'Programada'
                    $result = Citas::update($id_cita, null, null, $id_paciente_session, $obs_paciente, $nueva_fecha, $nueva_hora);
                    if ($result === true) {
                        $response = ['status' => 'success', 'message' => 'Cita reagendada correctamente.'];
                    } else {
                        $response['message'] = 'Error al reagendar la cita.';
                        error_log("Error Citas::update ID {$id_cita} por paciente {$id_paciente_session}: " );
                    }
                }
            }
        }

        // --- Acción Crear Cita ---
    } elseif (!$id_cita && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'cancel') {
        // 1. Validar y Recolectar datos
        $id_examen = filter_input(INPUT_POST, 'id_examen', FILTER_VALIDATE_INT);
        $fecha = filter_input(INPUT_POST, 'fecha_cita', FILTER_SANITIZE_STRING);
        $hora = filter_input(INPUT_POST, 'hora_cita', FILTER_SANITIZE_STRING);
        $obs_paciente = filter_input(INPUT_POST, 'observaciones_paciente', FILTER_SANITIZE_STRING) ?: null;
        $estado = 'Programada'; // Paciente siempre crea como Programada
        $hoy = date('Y-m-d');

        // Validaciones robustas
        if (!$id_examen || $id_examen <=0 || !ValidateData::validateDate($fecha) || $fecha < $hoy || empty($hora)) {
            $response['message'] = 'Datos inválidos (Examen, Fecha o Hora incorrectos).';
        } else {
            // 2. Verificar Disponibilidad
            if (!Citas::checkAvailability($id_paciente_session, $fecha, $hora)) {
                $response['message'] = 'El horario seleccionado no está disponible. Por favor, elige otro.';
            } else {
                // 3. Proceder a Crear
                $result = Citas::create($id_paciente_session, $id_examen, $fecha, $hora, $obs_paciente, $estado);
                if (is_numeric($result) && $result > 0) {
                    $response = ['status' => 'success', 'message' => 'Cita agendada correctamente con ID: ' . $result];
                } else {
                    $response['message'] = 'Error al agendar la cita.';
                    error_log("Error Citas::create para paciente {$id_paciente_session}: " );
                }
            }
        }
    } else {
        $response['message'] = 'Acción no válida o faltan datos.';
    }

} catch (Exception $e) {
    error_log("Error en AJAX Handler Paciente Citas: " . $e->getMessage());
    $response['message'] = 'Ocurrió un error inesperado en el servidor.';
}

// --- Devolver Respuesta JSON ---
echo json_encode($response);
exit;
?>