<?php
// ajax/admin/examen_handler.php

require_once(__DIR__ . '/../../configImport.php'); // Ajusta ruta

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Acción no válida o faltan datos.'];

// --- Seguridad: Verificar sesión de Admin ---
if (session_status() == PHP_SESSION_NONE) session_start();


// Determinar acción y ID
$action = isset($_POST['action']) ? $_POST['action'] : null;
$id_examen = isset($_POST['id_examen']) ? filter_var($_POST['id_examen'], FILTER_VALIDATE_INT) : null;

try {
    if ($action === 'delete' && $id_examen) {
        // --- Acción Eliminar (Desactivar) ---
        $result = Examenes::delete($id_examen); // Usa borrado lógico
        if ($result) {
            $response = ['status' => 'success', 'message' => 'Examen desactivado correctamente.'];
        } else {
            $response['message'] = 'Error al desactivar el examen.';
        }

    } elseif ($id_examen && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
        // --- Acción Actualizar ---
        // Validar y recolectar datos de $_POST
        $dataToUpdate = [];
        if(isset($_POST['nombre_examen'])) $dataToUpdate['nombre_examen'] = trim(filter_var($_POST['nombre_examen'], FILTER_SANITIZE_STRING));
        $dataToUpdate['codigo_examen'] = isset($_POST['codigo_examen']) ? trim(filter_var($_POST['codigo_examen'], FILTER_SANITIZE_STRING)) : null; // Permitir null
        if(isset($_POST['precio'])) $dataToUpdate['precio'] = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT); // Validar float
        if(isset($_POST['activo'])) $dataToUpdate['activo'] = filter_var($_POST['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE); // Validar booleano
        $dataToUpdate['descripcion'] = isset($_POST['descripcion']) ? trim(filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING)) : null;
        $dataToUpdate['preparacion'] = isset($_POST['preparacion']) ? trim(filter_var($_POST['preparacion'], FILTER_SANITIZE_STRING)) : null;

        // Añadir validaciones más estrictas aquí (ej. precio >= 0)
        if (empty($dataToUpdate['nombre_examen']) || $dataToUpdate['precio'] === false || $dataToUpdate['precio'] < 0 || $dataToUpdate['activo'] === null) {
            $response['message'] = 'Datos inválidos para actualizar (Nombre, Precio y Estado son requeridos).';
        } else {
            $result = Examenes::update($id_examen, $dataToUpdate);
            if ($result === true) {
                $response = ['status' => 'success', 'message' => 'Examen actualizado correctamente.'];
            } elseif (is_string($result)) { // Si update devuelve error específico (ej: CodigoExistente)
                $response['message'] = 'Error al actualizar: ' . $result;
            } else {
                $response['message'] = 'Error al actualizar el examen.';
            }
        }

    } elseif (!$id_examen && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
        // --- Acción Crear ---
        // Validar y recolectar datos
        $nombre = trim(filter_input(INPUT_POST, 'nombre_examen', FILTER_SANITIZE_STRING));
        $codigo = trim(filter_input(INPUT_POST, 'codigo_examen', FILTER_SANITIZE_STRING)) ?: null;
        $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
        $activo = filter_input(INPUT_POST, 'activo', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING)) ?: null;
        $preparacion = trim(filter_input(INPUT_POST, 'preparacion', FILTER_SANITIZE_STRING)) ?: null;

        // Validar requeridos
        if (empty($nombre) || $precio === false || $precio < 0 || $activo === null) {
            $response['message'] = 'Datos inválidos para crear (Nombre, Precio y Estado son requeridos).';
        } else {
            $result = Examenes::create($nombre, $precio, $codigo, $descripcion, $preparacion, $activo);
            if (is_numeric($result) && $result > 0) {
                $response = ['status' => 'success', 'message' => 'Examen creado correctamente.'];
            } elseif (is_string($result)) { // Si create devuelve error (ej: CodigoExistente)
                $response['message'] = 'Error al crear: ' . $result;
            } else {
                $response['message'] = 'Error al crear el examen.';
            }
        }
    }

} catch (Exception $e) {
    error_log("Error en AJAX Handler Examenes: " . $e->getMessage());
    $response['message'] = 'Ocurrió una excepción en el servidor.';
}

echo json_encode($response);
exit;

?>