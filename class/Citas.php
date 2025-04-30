<?php

/**
 * Clase para gestionar las operaciones CRUD de la tabla 'citas'.
 */
class Citas
{
    // Definir los estados posibles para usar en validaciones y lógica
    const ESTADOS_CITA = ['Programada', 'Confirmada', 'Realizada', 'Cancelada_Paciente', 'Cancelada_Auxiliar', 'No_Asistio'];

    /**
     * Crea una nueva cita.
     *
     * @param int $id_paciente ID del usuario paciente.
     * @param int $id_examen ID del examen agendado.
     * @param string $fecha_cita Fecha en formato 'YYYY-MM-DD'.
     * @param string $hora_cita Hora en formato 'HH:MM:SS' o 'HH:MM'.
     * @param string|null $observaciones_paciente Observaciones del paciente (opcional).
     * @param string $estado Estado inicial (default: 'Programada').
     *
     * @return int|false ID de la cita creada o false en error.
     */
    static function create($id_paciente, $id_examen, $fecha_cita, $hora_cita, $observaciones_paciente = NULL, $estado = 'Programada')
    {
        // Validaciones
        if (!is_numeric($id_paciente) || $id_paciente <= 0) return false;
        if (!is_numeric($id_examen) || $id_examen <= 0) return false;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_cita)) return false;
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora_cita)) return false; // Acepta HH:MM o HH:MM:SS
        if (!in_array($estado, self::ESTADOS_CITA)) $estado = 'Programada'; // Estado por defecto si es inválido

        // Podríamos añadir validación para verificar si el paciente y el examen existen, aunque las FK constraints deberían manejarlo.
        // También se podría validar si ya existe una cita para ese paciente/examen/hora o si la hora está disponible.

        // Escapar valores
        $escaped_fecha = Db::escape($fecha_cita);
        $escaped_hora = Db::escape($hora_cita);
        $escaped_observaciones = ($observaciones_paciente !== NULL) ? "'" . Db::escape(trim($observaciones_paciente)) . "'" : "NULL";
        $escaped_estado = Db::escape($estado);

        $sql = "
            INSERT INTO citas
            (id_paciente, id_examen, fecha_cita, hora_cita, estado, observaciones_paciente, fecha_creacion, fecha_modificacion)
            VALUES
            (%d, %d, '%s', '%s', '%s', %s, NOW(), NOW())
        ";
        $sql = sprintf($sql,
            $id_paciente,
            $id_examen,
            $escaped_fecha,
            $escaped_hora,
            $escaped_estado,
            $escaped_observaciones
        );

        $result = Db::query($sql);

        // Devolver ID o false
        if ($result && is_numeric($result)) { return (int)$result; }
        elseif ($result === true) { return true; /* O intentar obtener ID */ }
        else {}
    }

    /**
     * Obtiene una cita por su ID, incluyendo datos del paciente y examen.
     *
     * @param int $id_cita ID de la cita.
     * @return array|false Array con datos de la cita o false si no existe.
     */
    static function getById($id_cita)
    {
        if (!is_numeric($id_cita) || $id_cita <= 0) return false;

        // Consulta con JOINs para obtener información útil
        $sql = "
            SELECT
                c.*, -- Todos los campos de cita
                u.nombre as nombre,
                u.apellido as apellido,
                u.documento as documento,
                u.email as email,
                u.telefono as telefono,
                e.nombre_examen,
                e.codigo_examen,
                e.preparacion as preparacion_examen,
                modif.nombre as nombre_modificador,  -- Nombre de quien modificó
                modif.apellido as apellido_modificador -- Apellido de quien modificó
            FROM
                citas c
            JOIN
                usuarios u ON c.id_paciente = u.id_usuario
            JOIN
                examenes e ON c.id_examen = e.id_examen
            LEFT JOIN -- Usar LEFT JOIN por si modificado_por es NULL
                usuarios modif ON c.modificado_por = modif.id_usuario
            WHERE
                c.id_cita = %d
            LIMIT 1
        ";
        $sql = sprintf($sql, $id_cita);

        $result = Db::query($sql);
        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * Obtiene citas de un paciente específico, opcionalmente filtradas.
     *
     * @param int $id_paciente ID del paciente.
     * @param array $filters Filtros opcionales (ej: ['estado' => 'Programada', 'fecha_desde' => 'Y-m-d', 'fecha_hasta' => 'Y-m-d']).
     * @param string $orderBy Campo para ordenar (ej: 'fecha_cita DESC').
     * @return array Array de citas.
     */
    static function getByPaciente($id_paciente, $filters = [], $orderBy = 'fecha_cita DESC, hora_cita DESC')
    {
        if (!is_numeric($id_paciente) || $id_paciente <= 0) return [];

        // Construcción de la consulta base con JOINs
        $sql = "
            SELECT
                c.*,
                u.nombre as nombre_paciente, u.apellido as apellido_paciente,
                e.nombre_examen, e.codigo_examen
            FROM
                citas c
            JOIN
                usuarios u ON c.id_paciente = u.id_usuario
            JOIN
                examenes e ON c.id_examen = e.id_examen
            WHERE
                c.id_paciente = %d
        ";
        $sql = sprintf($sql, $id_paciente);

        // Aplicar filtros (¡CUIDADO CON SQL INJECTION AQUÍ!)
        $where_parts = [];
        if (!empty($filters)) {
            if (isset($filters['estado']) && in_array($filters['estado'], self::ESTADOS_CITA)) {
                $where_parts[] = sprintf("c.estado = '%s'", Db::escape($filters['estado']));
            }
            if (isset($filters['fecha_desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['fecha_desde'])) {
                $where_parts[] = sprintf("c.fecha_cita >= '%s'", Db::escape($filters['fecha_desde']));
            }
            if (isset($filters['fecha_hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['fecha_hasta'])) {
                $where_parts[] = sprintf("c.fecha_cita <= '%s'", Db::escape($filters['fecha_hasta']));
            }
            // Añadir más filtros si son necesarios...
        }

        if (!empty($where_parts)) {
            $sql .= " AND " . implode(" AND ", $where_parts);
        }

        // Ordenamiento (Sanear orderBy para evitar inyección)
        $allowed_orderby = ['fecha_cita ASC', 'fecha_cita DESC', 'hora_cita ASC', 'hora_cita DESC', 'estado ASC', 'estado DESC'];
        if (in_array($orderBy, $allowed_orderby)) {
            $sql .= " ORDER BY " . $orderBy; // $orderBy ya está validado contra una lista segura
        } else {
            $sql .= " ORDER BY fecha_cita DESC, hora_cita DESC"; // Orden por defecto seguro
        }


        $result = Db::query($sql);
        return $result ? $result : [];
    }

    /**
     * Obtiene todas las citas dentro de un rango de fechas, opcionalmente filtradas.
     * Similar a getByPaciente pero sin filtrar por paciente inicialmente. Útil para Admin/Aux.
     *
     * @param string $fecha_desde Fecha inicio 'YYYY-MM-DD'.
     * @param string $fecha_hasta Fecha fin 'YYYY-MM-DD'.
     * @param array $filters Filtros adicionales (ej: ['estado' => 'Programada']).
     * @param string $orderBy Campo para ordenar.
     * @return array Array de citas.
     */
    static function getByDateRange($fecha_desde, $fecha_hasta, $filters = [], $orderBy = 'fecha_cita ASC, hora_cita ASC')
    {

        // Construcción de la consulta base con JOINs
        $sql = "
            SELECT
                c.*,
                u.nombre as nombre_paciente, u.apellido as apellido_paciente, u.documento as documento_paciente,
                e.nombre_examen, e.codigo_examen
            FROM
                citas c
            JOIN
                usuarios u ON c.id_paciente = u.id_usuario
            JOIN
                examenes e ON c.id_examen = e.id_examen
            WHERE
                c.fecha_cita BETWEEN '%s' AND '%s'
        ";
        $sql = sprintf($sql, Db::escape($fecha_desde), Db::escape($fecha_hasta));
        // Aplicar filtros adicionales
        $where_parts = [];
        if (!empty($filters)) {
            if (isset($filters['estado']) && in_array($filters['estado'], self::ESTADOS_CITA)) {
                $where_parts[] = sprintf("c.estado = '%s'", Db::escape($filters['estado']));
            }
            if (isset($filters['id_paciente']) && is_numeric($filters['id_paciente'])) {
                $where_parts[] = sprintf("c.id_paciente = %d", $filters['id_paciente']);
            }
            if (isset($filters['id_examen']) && is_numeric($filters['id_examen'])) {
                $where_parts[] = sprintf("c.id_examen = %d", $filters['id_examen']);
            }
            //... otros filtros
        }

        if (!empty($where_parts)) {
            $sql .= " AND " . implode(" AND ", $where_parts);
        }

        // Ordenamiento (Sanear orderBy)
        $allowed_orderby = ['fecha_cita ASC', 'fecha_cita DESC', 'hora_cita ASC', 'hora_cita DESC', 'estado ASC', 'estado DESC', 'nombre_paciente ASC', 'nombre_examen ASC'];
        if (in_array($orderBy, $allowed_orderby)) {
            $sql .= " ORDER BY " . $orderBy;
        } else {
            $sql .= " ORDER BY fecha_cita ASC, hora_cita ASC"; // Orden por defecto seguro
        }

        $result = Db::query($sql);
        return $result ? $result : [];
    }


    /**
     * Actualiza el estado y/u observaciones de una cita.
     *
     * @param int $id_cita ID de la cita a modificar.
     * @param string|null $nuevo_estado Nuevo estado (si se cambia). Debe ser uno de self::ESTADOS_CITA.
     * @param string|null $observaciones_internas Observaciones del auxiliar/admin (si se añaden/modifican).
     * @param int|null $id_modificador ID del usuario (auxiliar/admin) que realiza la modificación.
     * @param string|null $observaciones_paciente Observaciones del paciente (si se modifican).
     * @return bool True en éxito, false en fallo.
     */
    static function update($id_cita, $nuevo_estado = NULL, $observaciones_internas = NULL, $id_modificador = NULL, $observaciones_paciente = NULL)
    {
        if (!is_numeric($id_cita) || $id_cita <= 0) return false;

        $set_parts = [];

        // Actualizar estado
        if ($nuevo_estado !== NULL && in_array($nuevo_estado, self::ESTADOS_CITA)) {
            $set_parts[] = sprintf("estado = '%s'", Db::escape($nuevo_estado));
        }

        // Actualizar observaciones internas (permitir NULL para borrar)
        if ($observaciones_internas !== NULL) {
            $escaped_obs_int = empty(trim($observaciones_internas)) ? "NULL" : "'" . Db::escape(trim($observaciones_internas)) . "'";
            $set_parts[] = sprintf("observaciones_internas = %s", $escaped_obs_int);
        }

        // Actualizar observaciones paciente (permitir NULL para borrar)
        if ($observaciones_paciente !== NULL) {
            $escaped_obs_pac = empty(trim($observaciones_paciente)) ? "NULL" : "'" . Db::escape(trim($observaciones_paciente)) . "'";
            $set_parts[] = sprintf("observaciones_paciente = %s", $escaped_obs_pac);
        }


        // Actualizar quién modificó (solo si se proporciona un ID válido)
        if ($id_modificador !== NULL && is_numeric($id_modificador) && $id_modificador > 0) {
            $set_parts[] = sprintf("modificado_por = %d", $id_modificador);
        }

        // Si no hay nada que actualizar, retornar true (o false si se prefiere indicar que no hubo cambios)
        if (empty($set_parts)) return true;

        // Añadir actualización de fecha_modificacion siempre que se actualice algo
        $set_parts[] = "fecha_modificacion = NOW()";

        $sql = "UPDATE citas SET ";
        $sql .= implode(', ', $set_parts);
        $sql .= sprintf(" WHERE id_cita = %d", $id_cita);

        return Db::query($sql);
    }

    /**
     * Método específico para cancelar una cita.
     *
     * @param int $id_cita ID de la cita.
     * @param string $quien_cancela 'Paciente' o 'Auxiliar'.
     * @param int|null $id_modificador ID del usuario que cancela (obligatorio si cancela Auxiliar).
     * @param string|null $motivo Motivo de la cancelación (se guarda en observaciones internas).
     * @return bool True en éxito, false en fallo.
     */
    static function cancel($id_cita, $quien_cancela, $id_modificador = NULL, $motivo = NULL) {
        if (!is_numeric($id_cita) || $id_cita <= 0) return false;
        if (!in_array($quien_cancela, ['Paciente', 'Auxiliar'])) return false;
        if ($quien_cancela == 'Auxiliar' && (!is_numeric($id_modificador) || $id_modificador <=0)) return false; // ID Modificador requerido para Auxiliar

        $nuevo_estado = ($quien_cancela == 'Paciente') ? 'Cancelada_Paciente' : 'Cancelada_Auxiliar';
        $observaciones = "Cancelada por {$quien_cancela}.";
        if ($motivo !== NULL && !empty(trim($motivo))) {
            $observaciones .= " Motivo: " . trim($motivo);
        }

        // Usar el método update general
        return self::update($id_cita, $nuevo_estado, $observaciones, $id_modificador);
    }

    /**
     * Verifica si una fecha/hora de cita está a más de 24 horas en el futuro.
     *
     * @param string $fecha_cita Fecha en formato 'YYYY-MM-DD'.
     * @param string $hora_cita Hora en formato 'HH:MM:SS' o 'HH:MM'.
     * @return bool True si la cita es modificable (más de 24h آینده), false si no.
     */
    public static function isChangeAllowed($fecha_cita, $hora_cita) {
        try {
            // Crear objeto DateTime con la fecha y hora de la cita
            $appointmentDateTime = new DateTime($fecha_cita . ' ' . $hora_cita);
            // Crear objeto DateTime con la hora actual + 24 horas
            $nowPlus24h = new DateTime('+24 hours');

            // Comparar: la cita debe ser posterior a ahora + 24h
            return $appointmentDateTime > $nowPlus24h;
        } catch (Exception $e) {
            // Error al parsear fechas/horas
            error_log("Error en isChangeAllowed: " . $e->getMessage());
            return false; // No permitir cambios si hay error de fecha
        }
    }

    /**
     * Verifica si un paciente tiene disponibilidad en una fecha/hora específica.
     * Excluye una cita existente si se está reagendando.
     * Solo considera citas activas ('Programada', 'Confirmada').
     *
     * @param int $id_paciente ID del paciente.
     * @param string $fecha_cita Fecha 'YYYY-MM-DD'.
     * @param string $hora_cita Hora 'HH:MM' o 'HH:MM:SS'.
     * @param int|null $id_cita_to_exclude ID de la cita a excluir de la verificación (para reagendar).
     * @return bool True si el horario está libre, false si está ocupado.
     */
    public static function checkAvailability($id_paciente, $fecha_cita, $hora_cita, $id_cita_to_exclude = null) {
        if (!ValidateData::validateInt($id_paciente) || !ValidateData::validateDate($fecha_cita) || empty($hora_cita)) {
            return false; // Datos inválidos
        }

        // Simplificación: Chequeo de hora exacta. Una implementación real podría chequear solapamiento si las citas tienen duración.
        $escaped_fecha = Db::escape($fecha_cita);
        $escaped_hora = Db::escape($hora_cita); // Asume que la hora viene en formato que MySQL entiende
        $active_statuses = "'Programada', 'Confirmada'"; // Estados que ocupan horario

        $sql = sprintf(
            "SELECT COUNT(id_cita) as count FROM citas WHERE id_paciente = %d AND fecha_cita = '%s' AND TIME(hora_cita) = TIME('%s') AND estado IN (%s)",
            $id_paciente, $escaped_fecha, $escaped_hora, $active_statuses
        );

        // Excluir la cita actual si estamos reagendando
        if ($id_cita_to_exclude !== null && ValidateData::validateInt($id_cita_to_exclude)) {
            $sql .= sprintf(" AND id_cita != %d", $id_cita_to_exclude);
        }

        $result = Db::query($sql);
        $count = isset($result[0]['count']) ? (int)$result[0]['count'] : 0;

        return $count === 0; // Disponible si no hay otras citas (count = 0)
    }

    /**
     * Actualiza datos de una cita (modificado para incluir fecha/hora).
     *
     * @param int $id_cita ID de la cita.
     * @param string|null $nuevo_estado Nuevo estado.
     * @param string|null $observaciones_internas (Usado por Admin/Aux).
     * @param int|null $id_modificador ID del usuario que modifica (Paciente/Admin/Aux).
     * @param string|null $observaciones_paciente Nuevas observaciones del paciente.
     * @param string|null $nueva_fecha Nueva fecha si se reagenda ('YYYY-MM-DD').
     * @param string|null $nueva_hora Nueva hora si se reagenda ('HH:MM' o 'HH:MM:SS').
     * @return bool True en éxito, false en fallo.
     */
    public static function update_paciente($id_cita, $nuevo_estado = NULL, $observaciones_internas = NULL, $id_modificador = NULL, $observaciones_paciente = NULL, $nueva_fecha = NULL, $nueva_hora = NULL)
    {
        if (!ValidateData::validateInt($id_cita) || $id_cita <= 0) return false;

        $set_parts = [];
        $reset_estado = false; // Flag para saber si se reagendó

        // Actualizar Fecha (si se proporciona y es válida)
        if ($nueva_fecha !== NULL) {
            if (!ValidateData::validateDate($nueva_fecha)) return false; // Validar formato
            $set_parts[] = sprintf("fecha_cita = '%s'", Db::escape($nueva_fecha));
            $reset_estado = true;
        }
        // Actualizar Hora (si se proporciona y es válida)
        if ($nueva_hora !== NULL) {
            if (empty($nueva_hora)) return false; // Validar no vacía (formato se asume correcto o validar con regex)
            $set_parts[] = sprintf("hora_cita = '%s'", Db::escape($nueva_hora));
            $reset_estado = true;
        }

        // Actualizar Estado (solo si no se reagendó, o si se especifica explícitamente)
        if ($nuevo_estado !== NULL && !$reset_estado && in_array($nuevo_estado, self::ESTADOS_CITA)) {
            $set_parts[] = sprintf("estado = '%s'", Db::escape($nuevo_estado));
        } elseif ($reset_estado) {
            // Si se reagendó, volver a 'Programada'
            $set_parts[] = "estado = 'Programada'";
        }

        // Actualizar observaciones paciente (permitir NULL para borrar)
        if ($observaciones_paciente !== NULL) {
            $escaped_obs_pac = empty(trim($observaciones_paciente)) ? "NULL" : "'" . Db::escape(trim($observaciones_paciente)) . "'";
            $set_parts[] = sprintf("observaciones_paciente = %s", $escaped_obs_pac);
        }
        // Actualizar observaciones internas (normalmente no por paciente)
        if ($observaciones_internas !== NULL) {
            $escaped_obs_int = empty(trim($observaciones_internas)) ? "NULL" : "'" . Db::escape(trim($observaciones_internas)) . "'";
            $set_parts[] = sprintf("observaciones_internas = %s", $escaped_obs_int);
        }

        // Actualizar quién modificó (importante pasar el ID del paciente)
        if ($id_modificador !== NULL && ValidateData::validateInt($id_modificador)) {
            $set_parts[] = sprintf("modificado_por = %d", $id_modificador);
        }

        if (empty($set_parts)) return true; // No hubo cambios solicitados válidos

        // Añadir actualización de fecha_modificacion
        $set_parts[] = "fecha_modificacion = NOW()";

        $sql = "UPDATE citas SET " . implode(', ', $set_parts) . sprintf(" WHERE id_cita = %d", $id_cita);
        return Db::query($sql);
    }
    // Se podría añadir un método delete($id_cita), pero generalmente es mejor cancelar o marcar como realizada/no asistió.

} // Fin clase Citas