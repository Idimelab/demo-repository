<?php
// ajax/admin/citas_feed.php
require_once(__DIR__ . '/../../configImport.php');

header('Content-Type: application/json');
$events = []; // Array para los eventos del calendario

// --- Seguridad: Verificar sesión de Admin ---
if (session_status() == PHP_SESSION_NONE) session_start();


// --- Obtener Parámetros GET ---
// FullCalendar envía 'start' y 'end'
$start_date = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
$end_date = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);

// Validar fechas (básico)
if (!$start_date || !$end_date ||
    !preg_match('/^\d{4}-\d{2}-\d{2}/', $start_date) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}/', $end_date)) {
    error_log("Citas Feed: Fechas start/end inválidas.");
    echo json_encode($events);
    exit;
}

// Recoger filtros adicionales
$filters = [];
$estado_filtro = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING);
$search_paciente = trim(filter_input(INPUT_GET, 'search_paciente', FILTER_SANITIZE_STRING));
$search_examen = trim(filter_input(INPUT_GET, 'search_examen', FILTER_SANITIZE_STRING));
// $id_paciente_filtro = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
// $id_examen_filtro = filter_input(INPUT_GET, 'id_examen', FILTER_VALIDATE_INT);

if ($estado_filtro && in_array($estado_filtro, Citas::ESTADOS_CITA)) { // Asumiendo constante en Citas
    $filters['estado'] = $estado_filtro;
}
// if ($id_paciente_filtro) $filters['id_paciente'] = $id_paciente_filtro;
// if ($id_examen_filtro) $filters['id_examen'] = $id_examen_filtro;

// Búsqueda por texto (necesitarías adaptar Citas::getByDateRange para buscar en paciente/examen)
$search_term = null; // O combinar paciente y examen si es necesario
if (!empty($search_paciente)) $filters['search_paciente'] = $search_paciente; // Necesitas adaptar el método getByDateRange
if (!empty($search_examen)) $filters['search_examen'] = $search_examen; // Necesitas adaptar el método getByDateRange


try {
    // --- Obtener Citas de la BD ---
    // Asegúrate que getByDateRange devuelva al menos:
    // id_cita, fecha_cita, hora_cita, estado,
    // u.nombre as nombre_paciente, u.apellido as apellido_paciente,
    // u.documento as documento_paciente, u.email as email_paciente, u.telefono as telefono_paciente,
    // e.nombre_examen, e.codigo_examen
    // c.observaciones_paciente, c.observaciones_internas
    $citas_db = Citas::getByDateRange($start_date, $end_date, $filters);
    // --- Formatear para FullCalendar ---
    if ($citas_db) {
        foreach ($citas_db as $cita) {
            $eventClass = 'fc-event-' . strtolower(str_replace(' ', '_', $cita['estado']));
            $events[] = [
                'id' => $cita['id_cita'],
                'estado' => $cita['estado'],
                'title' => sprintf("%s %s - %s", $cita['nombre_paciente'], $cita['apellido_paciente'], $cita['nombre_examen']),
                'start' => $cita['fecha_cita'] . 'T' . $cita['hora_cita'], // Formato ISO 8601
                // 'end' => ..., // Podrías calcularlo si tienes duración
                'className' => $eventClass,
                'extendedProps' => [ // Datos extra para el modal
                    'paciente' => $cita['nombre_paciente'] . ' ' . $cita['apellido_paciente'],
                    'documento' => $cita['documento_paciente'] ?? 'N/A',
                    'email' => $cita['email_paciente'] ?? '-',
                    'telefono' => $cita['telefono_paciente'] ?? '-',
                    'examen' => $cita['nombre_examen'] ?? 'N/A',
                    'codigo_examen' => $cita['codigo_examen'] ?? 'N/A',
                    'estado' => $cita['estado'],
                    'fecha' => $cita['fecha_cita'],
                    'hora' => substr($cita['hora_cita'], 0, 5), // HH:MM
                    'obs_paciente' => $cita['observaciones_paciente'] ?? '',
                    'obs_internas' => $cita['observaciones_internas'] ?? ''
                ]
            ];
        }
    }

} catch (Exception $e) {
    error_log("Error en Citas Feed: " . $e->getMessage());
    // Devolver array vacío o un objeto de error si FullCalendar lo maneja
}

// --- Devolver JSON ---
echo json_encode($events);
exit;
?>