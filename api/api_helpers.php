<?php
// api/api_helpers.php (o dentro de cada archivo API)

function authenticateRequest() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : ''); // Case-insensitive check

    if (empty($authHeader)) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Falta encabezado de autorización.']);
        exit;
    }

    // Espera formato "Bearer TU_API_KEY"
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $providedKey = $matches[1];
        if ($providedKey === STATIC_API_KEY) {
            // Autenticación exitosa
            return true;
        } else {
            // Clave incorrecta
            http_response_code(403); // Forbidden
            echo json_encode(['error' => 'Clave API inválida.']);
            exit;
        }
    } else {
        // Formato de encabezado incorrecto
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Formato de encabezado de autorización inválido. Usar: Bearer <API_KEY>']);
        exit;
    }
}

function sendJsonResponse($data, $statusCode = 200) {
    // Evitar enviar cabeceras si ya se enviaron
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
    }
    echo json_encode($data);
    exit;
}

function getJsonInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'JSON inválido en el cuerpo de la solicitud: ' . json_last_error_msg()], 400);
    }
    return $input ?: []; // Devolver array vacío si el input es null o vacío
}

?>