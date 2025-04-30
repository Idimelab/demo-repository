<?php
// ajax/admin/usuario_handler.php

require_once(__DIR__ . '/../../configImport.php'); // Ajusta ruta

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Acción no válida o faltan datos.'];

// --- Seguridad: Verificar sesión de Admin ---
if (session_status() == PHP_SESSION_NONE) session_start();

// Determinar la acción (create, update, delete)
$action = isset($_POST['action']) ? $_POST['action'] : null; // Podrías enviar 'delete' explícitamente
$id_usuario = isset($_POST['id_usuario']) ? filter_var($_POST['id_usuario'], FILTER_VALIDATE_INT) : null;

try {
    if ($action === 'delete' && $id_usuario) {
        // --- Acción Eliminar (Desactivar) ---
        $result = Usuarios::delete($id_usuario); // Usa tu método de borrado lógico
        if ($result) {
            $response = ['status' => 'success', 'message' => 'Usuario desactivado correctamente.'];
        } else {
            $response['message'] = 'Error al desactivar el usuario.';
        }

    } elseif ($id_usuario && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
        // --- Acción Actualizar ---
        // Recolectar datos de $_POST (asegúrate de validar/sanear!)
        $dataToUpdate = [];
        // (Ejemplo: $dataToUpdate['nombre'] = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING); ...)
        // Mapea los campos de $_POST a los esperados por Usuarios::update
        // ...
        if(isset($_POST['nombre'])) $dataToUpdate['nombre'] = trim($_POST['nombre']);
        if(isset($_POST['apellido'])) $dataToUpdate['apellido'] = trim($_POST['apellido']);
        if(isset($_POST['tipo_documento'])) $dataToUpdate['tipo_documento'] = $_POST['tipo_documento'];
        if(isset($_POST['documento'])) $dataToUpdate['documento'] = trim($_POST['documento']);
        if(isset($_POST['email'])) $dataToUpdate['email'] = trim($_POST['email']);
        if(isset($_POST['telefono'])) $dataToUpdate['telefono'] = trim($_POST['telefono']);
        if(isset($_POST['fecha_nacimiento'])) $dataToUpdate['fecha_nacimiento'] = $_POST['fecha_nacimiento'] ?: null;
        if(isset($_POST['direccion'])) $dataToUpdate['direccion'] = trim($_POST['direccion']);
        if(isset($_POST['eps'])) $dataToUpdate['eps'] = trim($_POST['eps']) ?: null;
        if(isset($_POST['id_rol'])) $dataToUpdate['id_rol'] = filter_var($_POST['id_rol'], FILTER_VALIDATE_INT);
        if(isset($_POST['activo'])) $dataToUpdate['activo'] = filter_var($_POST['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if(!empty($_POST['plainPassword'])) $dataToUpdate['plainPassword'] = $_POST['plainPassword']; // Solo si se envió

        $result = Usuarios::update($id_usuario, $dataToUpdate);

        if ($result === true) {
            $response = ['status' => 'success', 'message' => 'Usuario actualizado correctamente.'];
        } else {
            // Si update devuelve un string de error específico (ej: 'EmailExistente')
            if (is_string($result)) {
                $response['message'] = 'Error al actualizar: ' . $result;
            } else {
                $response['message'] = 'Error al actualizar el usuario.';
            }
        }

    } elseif (!$id_usuario && $_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'delete') {
        // --- Acción Crear ---
        // Recolectar datos de $_POST (asegúrate de validar/sanear!)
        $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
        $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
        $tipo_doc = filter_input(INPUT_POST, 'tipo_documento', FILTER_SANITIZE_STRING);
        $doc = filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = isset($_POST['plainPassword']) ? $_POST['plainPassword'] : null; // ¡Validar longitud!
        $fecha_nac = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_STRING) ?: null;
        $tel = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?: null;
        $dir = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING) ?: null;
        $eps = filter_input(INPUT_POST, 'eps', FILTER_SANITIZE_STRING) ?: null;
        // activo se maneja por defecto en la BD o se pasa si es necesario

        // Validaciones adicionales importantes (longitud pass, etc.) aquí antes de llamar a create...

        $result = Usuarios::create(
            $id_rol, trim($nombre), trim($apellido), $tipo_doc, trim($doc), trim($email), $password,
            $fecha_nac, $tel, $dir, $eps
        // Añadir más parámetros si create los necesita
        );

        if (is_numeric($result) && $result > 0) { // create devuelve ID
            $response = ['status' => 'success', 'message' => 'Usuario creado correctamente.'];
        } else {
            // Si create devuelve un string de error específico
            if (is_string($result)) {
                $response['message'] = 'Error al crear: ' . $result;
            } else {
                $response['message'] = 'Error al crear el usuario.';
            }
        }
    }

} catch (Exception $e) {
    error_log("Error en AJAX Handler Usuarios: " . $e->getMessage());
    $response['message'] = 'Ocurrió una excepción en el servidor.';
}

echo json_encode($response);
exit;

?>