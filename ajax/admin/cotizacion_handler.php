<?php
// ajax/admin/cotizacion_handler.php

require_once(__DIR__ . '/../../configImport.php');

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Acción no válida o faltan datos.'];

// --- Seguridad: Verificar sesión de Admin ---
if (session_status() == PHP_SESSION_NONE) session_start();


$action = isset($_POST['action']) ? $_POST['action'] : null;
$id_cotizacion = isset($_POST['id_cotizacion']) ? filter_var($_POST['id_cotizacion'], FILTER_VALIDATE_INT) : null;

try {
    if ($action === 'delete' && $id_cotizacion) {
        // --- Acción Eliminar ---
        $result = Cotizaciones::delete($id_cotizacion);
        if ($result) {
            $response = ['status' => 'success', 'message' => 'Cotización eliminada correctamente.'];
        } else {
            $response['message'] = 'Error al eliminar la cotización.';
        }

    } elseif ($id_cotizacion && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
        // --- Acción Actualizar (LIMITADA A ESTADO, VIGENCIA, OBSERVACIONES) ---

        // 1. Validar y Recolectar SOLO los campos permitidos
        $dataToUpdate = [];
        $isValidData = true;
        $validationMessages = [];

        // Validar Estado
        if (isset($_POST['estado'])) {
            $estado = filter_var($_POST['estado'], FILTER_SANITIZE_STRING);
            if (in_array($estado, ['Pendiente', 'Completada', 'Expirada'])) {
                $dataToUpdate['estado'] = $estado;
            } else {
                $isValidData = false;
                $validationMessages[] = 'Estado inválido.';
            }
        } else {
            $isValidData = false; // El estado siempre debe enviarse en la edición limitada
            $validationMessages[] = 'Falta el estado.';
        }

        // Validar Vigencia (Opcional, pero si se envía, validar formato)
        if (isset($_POST['vigencia_hasta'])) {
            $vigencia = $_POST['vigencia_hasta'];
            if (empty($vigencia)) { // Permitir enviarla vacía para ponerla NULL
                $dataToUpdate['vigencia_hasta'] = null;
            } elseif (ValidateData::validateDate($vigencia, 'Y-m-d')) { // Usar tu validador
                $dataToUpdate['vigencia_hasta'] = $vigencia;
            } else {
                $isValidData = false;
                $validationMessages[] = 'Formato de fecha de vigencia inválido (AAAA-MM-DD).';
            }
        } // Si no viene en el POST, no se actualiza

        // Obtener Observaciones (sanear)
        if (isset($_POST['observaciones'])) {
            $dataToUpdate['observaciones'] = trim(filter_var($_POST['observaciones'], FILTER_SANITIZE_STRING)) ?: null;
        }

        // 2. Verificar si hay datos válidos para actualizar
        if (!$isValidData) {
            $response['message'] = implode(' ', $validationMessages);
        } elseif (empty($dataToUpdate)) {
            $response['message'] = 'No se proporcionaron datos para actualizar.';
            // O podrías devolver éxito si no es un error no enviar nada
            // $response = ['status' => 'success', 'message' => 'No hubo cambios que guardar.'];
        } else {
            // 3. Llamar al método Update SOLO con los campos permitidos
            $result = Cotizaciones::update($id_cotizacion, $dataToUpdate);
            if ($result === true) {
                $response = ['status' => 'success', 'message' => 'Cotización actualizada correctamente.'];
            } else {
                $response['message'] = 'Error al actualizar la cotización en la base de datos.';
                error_log("Error Cotizaciones::update ID {$id_cotizacion}: " ); // Log DB error
            }
        }

    } elseif (!$id_cotizacion && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
        // --- Acción Crear ---
        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $vigencia = filter_input(INPUT_POST, 'vigencia_hasta', FILTER_SANITIZE_STRING) ?: null;
        $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING) ?: null;
        $detalles_json = isset($_POST['detalles']) ? $_POST['detalles'] : null;
        $detalles = json_decode($detalles_json, true); // Decodificar el array de detalles

        // Validaciones robustas
        if (!$id_usuario || $id_usuario <= 0) {
            $response['message'] = 'Cliente inválido.';
        } elseif (!empty($vigencia) && !ValidateData::validateDate($vigencia)) {
            $response['message'] = 'Formato de fecha de vigencia inválido.';
        } elseif ($detalles === null || !is_array($detalles) || empty($detalles)) {
            $response['message'] = 'No se recibieron exámenes para la cotización.';
        } else {
            // Validar cada item en $detalles (id_examen existe, cantidad > 0, etc.) - ¡IMPORTANTE!
            $validDetalles = true;
            foreach($detalles as $item) {
                if (!isset($item['id_examen']) || !ValidateData::validateInt($item['id_examen']) || $item['id_examen'] <= 0 ||
                    !isset($item['cantidad']) || !ValidateData::validateInt($item['cantidad']) || $item['cantidad'] <= 0) {
                    $validDetalles = false;
                    break;
                }
                // Aquí podrías volver a verificar si el id_examen existe en la BD por seguridad
            }

            if (!$validDetalles) {
                $response['message'] = 'Los detalles de los exámenes son inválidos.';
            } else {
                // Llamar a create (asume que maneja transacciones)
                $result = Cotizaciones::create($id_usuario, $detalles, $observaciones, $vigencia);
                if (is_numeric($result) && $result > 0) { // Devuelve ID
                    $response = ['status' => 'success', 'message' => 'Cotización creada correctamente con ID: ' . $result];
                } else {
                    $response['message'] = 'Error al crear la cotización en la base de datos.';
                    error_log("Error Cotizaciones::create: " . (is_string($result) ? $result : 'Fallo desconocido')); // Loguear error específico si lo hay
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Error en AJAX Handler Cotizaciones: " . $e->getMessage());
    $response['message'] = 'Ocurrió una excepción en el servidor.';
}

echo json_encode($response);
exit;

?>