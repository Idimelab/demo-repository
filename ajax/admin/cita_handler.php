<?php
// ajax/admin/cita_handler.php

require_once(__DIR__ . '/../../configImport.php');

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Acción no válida o faltan datos.'];

// --- Seguridad: Verificar sesión de Admin ---
if (session_status() == PHP_SESSION_NONE) session_start();

// Obtener ID del admin que realiza la acción
$id_admin_modificador = Security::GetSessionUserId();

if (!$id_admin_modificador) { // Debe existir el ID del admin
    $response['message'] = 'Error interno: No se pudo identificar al usuario modificador.';
    error_log("Error Crítico: ID de usuario admin no encontrado en sesión.");
    echo json_encode($response);
    exit;
}


// Determinar acción y ID
$action = isset($_POST['action']) ? $_POST['action'] : null; // create, update, cancel, update_status
$id_cita = isset($_POST['id_cita']) ? filter_var($_POST['id_cita'], FILTER_VALIDATE_INT) : null;

try {
    // --- Acción Cancelar ---
    if ($action === 'cancel' && $id_cita) {
        // El motivo es opcional
        $motivo_cancelacion = "Cancelado por Administrador.";
        $result = Citas::cancel($id_cita, 'Auxiliar', $id_admin_modificador, $motivo_cancelacion); // Usar 'Auxiliar' como origen admin
        if ($result) {
            $response = ['status' => 'success', 'message' => 'Cita cancelada correctamente.'];
        } else {
            $response['message'] = 'Error al cancelar la cita.';
        }

        // --- Acción Actualizar Estado ---
    } elseif ($action === 'update_status' && $id_cita) {
        $nuevo_estado = isset($_POST['nuevo_estado']) ? filter_var($_POST['nuevo_estado'], FILTER_SANITIZE_STRING) : null;
        if ($nuevo_estado && in_array($nuevo_estado, Citas::ESTADOS_CITA)) { // Validar estado
            // Usar el método update general (asegúrate que acepte estado y modificador)
            $result = Citas::update($id_cita, $nuevo_estado, null, $id_admin_modificador, null, null, null); // Solo actualizar estado y modificador
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Estado de la cita actualizado.'];
            } else {
                $response['message'] = 'Error al actualizar el estado.';
            }
        } else {
            $response['message'] = 'Estado proporcionado no válido.';
        }

        // --- Acción Actualizar Cita (Reprogramar/Notas) ---
    } elseif ($id_cita && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'cancel' && $action !== 'update_status') {
        // Validar y recolectar datos para EDITAR
        $nueva_fecha = filter_input(INPUT_POST, 'fecha_cita', FILTER_SANITIZE_STRING);
        $nueva_hora = filter_input(INPUT_POST, 'hora_cita', FILTER_SANITIZE_STRING);
        $nuevo_estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
        $obs_paciente = filter_input(INPUT_POST, 'observaciones_paciente', FILTER_SANITIZE_STRING) ?: null;
        $obs_internas = filter_input(INPUT_POST, 'observaciones_internas', FILTER_SANITIZE_STRING) ?: null;

        // Validaciones
        if (!ValidateData::validateDate($nueva_fecha) || empty($nueva_hora) || // Validar hora más estrictamente si es necesario
            !in_array($nuevo_estado, Citas::ESTADOS_CITA)) {
            $response['message'] = 'Datos inválidos para actualizar (Fecha, Hora o Estado incorrectos).';
        } else {
            // Llamar a update (modificado para aceptar fecha/hora)
            $result = Citas::update($id_cita, $nuevo_estado, $obs_internas, $id_admin_modificador, $obs_paciente, $nueva_fecha, $nueva_hora);
            if ($result === true) {
                $response = ['status' => 'success', 'message' => 'Cita actualizada correctamente.'];
            } else {
                $response['message'] = 'Error al actualizar la cita.';
                error_log("Error Citas::update ID {$id_cita}: " );
            }
        }

        // --- Acción Crear Cita ---
    } elseif (!$id_cita && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'cancel' && $action !== 'update_status') {
        // Validar y recolectar datos para CREAR
        $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_VALIDATE_INT);
        $id_examen = filter_input(INPUT_POST, 'id_examen', FILTER_VALIDATE_INT);
        $fecha = filter_input(INPUT_POST, 'fecha_cita', FILTER_SANITIZE_STRING);
        $hora = filter_input(INPUT_POST, 'hora_cita', FILTER_SANITIZE_STRING);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING) ?: 'Programada'; // Default a Programada
        $obs_paciente = filter_input(INPUT_POST, 'observaciones_paciente', FILTER_SANITIZE_STRING) ?: null;
        // $obs_internas = filter_input(INPUT_POST, 'observaciones_internas', FILTER_SANITIZE_STRING) ?: null; // Admin puede añadir obs internas al crear?

        // Validaciones más robustas
        if (!$id_paciente || !$id_examen || !ValidateData::validateDate($fecha) || empty($hora) || !in_array($estado, Citas::ESTADOS_CITA)) {
            $response['message'] = 'Datos inválidos para crear la cita (Paciente, Examen, Fecha, Hora o Estado incorrectos).';
            // Podrías añadir validación si el paciente o examen existen aquí antes de llamar a create
        } else {
            $result = Citas::create($id_paciente, $id_examen, $fecha, $hora, $obs_paciente, $estado);
            if (is_numeric($result) && $result > 0) {
                $response = ['status' => 'success', 'message' => 'Cita creada correctamente con ID: ' . $result];
            } else {
                $response['message'] = 'Error al crear la cita.';
                error_log("Error Citas::create: " );
            }
        }
    } else {
        $response['message'] = 'Acción o método no permitido.';
    }

} catch (Exception $e) {
    error_log("Error en AJAX Handler Citas: " . $e->getMessage());
    $response['message'] = 'Ocurrió una excepción en el servidor: ' . $e->getMessage(); // Más info para debug
}

echo json_encode($response);
exit;
?>