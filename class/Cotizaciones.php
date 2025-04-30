<?php

/**
 * Clase para gestionar las operaciones CRUD de las tablas 'cotizaciones' y 'cotizacion_detalles'.
 * Maneja la creación de la cotización y sus detalles asociados.
 */
class Cotizaciones
{

    /**
     * Crea una nueva cotización con sus detalles.
     * IMPORTANTE: Idealmente, esto debería ejecutarse dentro de una transacción de base de datos
     * para asegurar que o se crea todo (cabecera y detalles) o no se crea nada.
     * La implementación de transacciones depende de tu clase Db.
     *
     * @param int $id_usuario ID del usuario que solicita la cotización.
     * @param array $detalles Array de detalles, cada item es un array asociativo
     * ej: [['id_examen' => 1, 'cantidad' => 1], ['id_examen' => 5, 'cantidad' => 1]]
     * @param string|null $observaciones Observaciones generales para la cotización.
     * @param string|null $vigencia_hasta Fecha de vigencia 'YYYY-MM-DD' (opcional).
     *
     * @return int|false ID de la cotización creada o false en error.
     */
    static function create($id_usuario, $detalles, $observaciones = NULL, $vigencia_hasta = NULL)
    {
        // Validaciones básicas
        if (!is_numeric($id_usuario) || $id_usuario <= 0) return false;
        if (empty($detalles) || !is_array($detalles)) return false;
        if ($vigencia_hasta !== NULL && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $vigencia_hasta)) return false;

        // --- Iniciar Transacción (si es posible con tu clase Db) ---
        // Db::beginTransaction(); // Ejemplo conceptual

        // 1. Crear la cabecera de la cotización
        $escaped_observaciones = ($observaciones !== NULL) ? "'" . Db::escape(trim($observaciones)) . "'" : "NULL";
        $escaped_vigencia = ($vigencia_hasta !== NULL) ? "'" . Db::escape($vigencia_hasta) . "'" : "NULL";
        // Estado inicial 'Pendiente'
        $sql_header = "
            INSERT INTO cotizaciones
            (id_usuario, fecha_cotizacion, vigencia_hasta, observaciones, estado)
            VALUES
            (%d, NOW(), %s, %s, 'Pendiente')
        ";
        $sql_header = sprintf($sql_header, $id_usuario, $escaped_vigencia, $escaped_observaciones);

        $result_header = Db::query($sql_header);

        // Obtener el ID de la cotización recién creada
        $id_cotizacion = false;
        if ($result_header && is_numeric($result_header)) {
            $id_cotizacion = (int)$result_header;
        } elseif ($result_header === true) {
            // Si Db::query solo devuelve true, intentar obtener el último ID
            $id_cotizacion = Db::getLastInsertId(); // Asumiendo que existe
            if (!is_numeric($id_cotizacion) || $id_cotizacion <= 0) $id_cotizacion = false;
        }

        if ($id_cotizacion === false) {
            error_log("Error al crear cabecera de cotización para usuario: " . $id_usuario);
            // Db::rollbackTransaction(); // Ejemplo conceptual
            return false;
        }

        // 2. Crear los detalles de la cotización
        $precio_total_calculado = 0;
        $detalle_exitoso = true;

        foreach ($detalles as $item) {
            if (!isset($item['id_examen']) || !is_numeric($item['id_examen']) || $item['id_examen'] <= 0) {
                $detalle_exitoso = false; break; // Detalle inválido
            }
            $id_examen = (int)$item['id_examen'];
            $cantidad = (isset($item['cantidad']) && is_numeric($item['cantidad']) && $item['cantidad'] > 0) ? (int)$item['cantidad'] : 1;

            // Obtener el precio actual del examen
            $examen_data = Examenes::getById($id_examen); // Reutiliza la clase Examenes
            if (!$examen_data || !isset($examen_data['precio'])) {
                error_log("Error: No se encontró el examen ID {$id_examen} o su precio para la cotización {$id_cotizacion}.");
                $detalle_exitoso = false; break; // Examen no encontrado o sin precio
            }
            $precio_unitario_registrado = floatval($examen_data['precio']);
            $subtotal = $cantidad * $precio_unitario_registrado;
            $precio_total_calculado += $subtotal;

            // Insertar detalle
            $sql_detail = "
                INSERT INTO cotizacion_detalles
                (id_cotizacion, id_examen, cantidad, precio_unitario_registrado, subtotal)
                VALUES
                (%d, %d, %d, %f, %f)
            ";
            // No es necesario escapar aquí porque ya validamos y convertimos a tipos numéricos
            $sql_detail = sprintf($sql_detail, $id_cotizacion, $id_examen, $cantidad, $precio_unitario_registrado, $subtotal);

            $result_detail = Db::query($sql_detail);
            if (!$result_detail) {
                error_log("Error al insertar detalle para cotización {$id_cotizacion}, examen {$id_examen}.");
                $detalle_exitoso = false; break; // Falló la inserción del detalle
            }
        } // Fin foreach detalles

        // 3. Si todos los detalles se insertaron, actualizar el precio total en la cabecera
        if ($detalle_exitoso) {
            $sql_update_total = sprintf("UPDATE cotizaciones SET precio_total_calculado = %f WHERE id_cotizacion = %d",
                $precio_total_calculado,
                $id_cotizacion);
            $result_update = Db::query($sql_update_total);
            if (!$result_update) {
                error_log("Error al actualizar precio total para cotización {$id_cotizacion}.");
                // Considerar si esto debe causar rollback o solo registrar el error
                // Db::rollbackTransaction(); return false; // Opción más estricta
            } else {
                // --- Confirmar Transacción ---
                // Db::commitTransaction(); // Ejemplo conceptual
                return $id_cotizacion; // ¡Éxito!
            }
        }

        // Si algo falló con los detalles o la actualización del total
        error_log("Rollback de cotización debido a error en detalles para cotización que iba a ser ID {$id_cotizacion}.");
        // Db::rollbackTransaction(); // Ejemplo conceptual
        // Podríamos intentar borrar la cabecera si la transacción no está disponible
        // Db::query(sprintf("DELETE FROM cotizaciones WHERE id_cotizacion = %d", $id_cotizacion));
        return false; // Indicar fallo general
    }


    /**
     * Obtiene una cotización por su ID, incluyendo los detalles y nombres de exámenes.
     *
     * @param int $id_cotizacion ID de la cotización.
     * @return array|false Array con datos de la cotización y un array 'detalles', o false.
     */
    static function getByIdWithDetails($id_cotizacion)
    {
        if (!is_numeric($id_cotizacion) || $id_cotizacion <= 0) return false;

        // 1. Obtener cabecera
        $sql_header = "
            SELECT c.*, u.nombre as nombre_usuario, u.apellido as apellido_usuario, u.email as email_usuario
            FROM cotizaciones c
            JOIN usuarios u ON c.id_usuario = u.id_usuario
            WHERE c.id_cotizacion = %d
            LIMIT 1
        ";
        $sql_header = sprintf($sql_header, $id_cotizacion);
        $header_result = Db::query($sql_header);

        if (!isset($header_result[0])) return false; // No encontrada
        $cotizacion_data = $header_result[0];

        // 2. Obtener detalles
        $sql_details = "
            SELECT cd.*, e.nombre_examen, e.codigo_examen
            FROM cotizacion_detalles cd
            JOIN examenes e ON cd.id_examen = e.id_examen
            WHERE cd.id_cotizacion = %d
            ORDER BY cd.id_cotizacion_detalle ASC
        ";
        $sql_details = sprintf($sql_details, $id_cotizacion);
        $details_result = Db::query($sql_details);

        $cotizacion_data['detalles'] = $details_result ? $details_result : []; // Añadir detalles al array

        return $cotizacion_data;
    }

    /**
     * Obtiene todas las cotizaciones de un usuario específico.
     *
     * @param int $id_usuario ID del usuario.
     * @param string $orderBy Orden (ej: 'fecha_cotizacion DESC').
     * @return array Array de cotizaciones (solo cabeceras).
     */
    static function getByUsuario($id_usuario, $orderBy = 'fecha_cotizacion DESC')
    {
        if (!is_numeric($id_usuario) || $id_usuario <= 0) return [];

        $sql = "
            SELECT c.*, u.nombre as nombre_usuario, u.apellido as apellido_usuario
            FROM cotizaciones c
            JOIN usuarios u ON c.id_usuario = u.id_usuario
            WHERE c.id_usuario = %d
        ";
        $sql = sprintf($sql, $id_usuario);

        // Ordenamiento (Sanear orderBy)
        $allowed_orderby = ['fecha_cotizacion DESC', 'fecha_cotizacion ASC', 'estado ASC', 'estado DESC', 'precio_total_calculado ASC', 'precio_total_calculado DESC'];
        if (in_array($orderBy, $allowed_orderby)) {
            $sql .= " ORDER BY " . $orderBy;
        } else {
            $sql .= " ORDER BY fecha_cotizacion DESC"; // Orden por defecto seguro
        }

        $result = Db::query($sql);
        return $result ? $result : [];
    }

    /**
     * Actualiza el estado de una cotización.
     *
     * @param int $id_cotizacion ID de la cotización.
     * @param string $nuevo_estado Nuevo estado ('Pendiente', 'Completada', 'Expirada').
     * @return bool True en éxito, false en fallo.
     */
    static function updateStatus($id_cotizacion, $nuevo_estado)
    {
        if (!is_numeric($id_cotizacion) || $id_cotizacion <= 0) return false;
        $allowed_status = ['Pendiente', 'Completada', 'Expirada'];
        if (!in_array($nuevo_estado, $allowed_status)) return false;

        $sql = sprintf("UPDATE cotizaciones SET estado = '%s' WHERE id_cotizacion = %d",
            Db::escape($nuevo_estado),
            $id_cotizacion);
        return Db::query($sql);
    }

    // Podrían añadirse métodos para obtener todas las cotizaciones (admin),
    // delete($id_cotizacion) (¡CUIDADO! Borraría detalles por ON DELETE CASCADE), etc.

    // En clase Cotizaciones
    public static function getAll($page = 1, $itemsPerPage = 20, $search = null, $filters = [], $returnCount = false) {
        $selectFields = "c.*, u.nombre as nombre_usuario, u.apellido as apellido_usuario, u.email as email_usuario";
        $baseSqlSelect = "SELECT {$selectFields} FROM cotizaciones c JOIN usuarios u ON c.id_usuario = u.id_usuario";
        $baseSqlCount = "SELECT COUNT(c.id_cotizacion) as total FROM cotizaciones c JOIN usuarios u ON c.id_usuario = u.id_usuario";
        $whereParts = [];

        // Filtro por estado
        if (!empty($filters['estado']) && in_array($filters['estado'], ['Pendiente', 'Completada', 'Expirada'])) {
            $whereParts[] = sprintf("c.estado = '%s'", Db::escape($filters['estado']));
        }
        // Filtro por fecha (rango)
        if (!empty($filters['fecha_desde']) && ValidateData::validateDate($filters['fecha_desde'])) {
            $whereParts[] = sprintf("DATE(c.fecha_cotizacion) >= '%s'", Db::escape($filters['fecha_desde']));
        }
        if (!empty($filters['fecha_hasta']) && ValidateData::validateDate($filters['fecha_hasta'])) {
            $whereParts[] = sprintf("DATE(c.fecha_cotizacion) <= '%s'", Db::escape($filters['fecha_hasta']));
        }
        // Filtro por usuario específico
        if (!empty($filters['id_usuario']) && ValidateData::validateInt($filters['id_usuario'])) {
            $whereParts[] = sprintf("c.id_usuario = %d", $filters['id_usuario']);
        }


        // Filtro de búsqueda general (ID cotización, nombre/apellido/email usuario)
        if (!empty(trim($search))) {
            $escaped_search_like = Db::escape('%' . trim($search) . '%');
            $escaped_search_int = filter_var(trim($search), FILTER_VALIDATE_INT);
            $searchConditions = [
                "u.nombre LIKE '{$escaped_search_like}'",
                "u.apellido LIKE '{$escaped_search_like}'",
                "u.email LIKE '{$escaped_search_like}'"
            ];
            if ($escaped_search_int !== false) {
                $searchConditions[] = "c.id_cotizacion = {$escaped_search_int}";
            }
            $whereParts[] = "(" . implode(" OR ", $searchConditions) . ")";
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(" AND ", $whereParts) : "";

        // Devolver conteo
        if ($returnCount) {
            $sql = $baseSqlCount . $whereClause;
            $result = Db::query($sql);
            return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
        }
        // Devolver lista paginada
        else {
            $offset = ($page - 1) * $itemsPerPage;
            $sql = $baseSqlSelect . $whereClause;
            // Orden por defecto, puede hacerse configurable
            $sql .= " ORDER BY c.fecha_cotizacion DESC LIMIT {$offset}, {$itemsPerPage}";
            $result = Db::query($sql);
            return $result ?: [];
        }
    }

    // En clase Cotizaciones
    public static function update($id_cotizacion, $data) {
        if (!ValidateData::validateInt($id_cotizacion) || $id_cotizacion <= 0 || empty($data) || !is_array($data)) {
            return false;
        }
        $allowed_fields = ['estado', 'observaciones', 'vigencia_hasta']; // Campos permitidos para editar
        $set_parts = [];

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed_fields)) continue;

            switch ($field) {
                case 'estado':
                    if (!in_array($value, ['Pendiente', 'Completada', 'Expirada'])) continue 2;
                    $set_parts[] = sprintf("estado = '%s'", Db::escape($value));
                    break;
                case 'observaciones':
                    $escaped_value = ($value !== NULL && !empty(trim($value))) ? "'" . Db::escape(trim($value)) . "'" : "NULL";
                    $set_parts[] = sprintf("observaciones = %s", $escaped_value);
                    break;
                case 'vigencia_hasta':
                    if ($value !== NULL && !ValidateData::validateDate($value)) continue 2; // Validar formato fecha
                    $escaped_value = ($value !== NULL) ? "'" . Db::escape($value) . "'" : "NULL";
                    $set_parts[] = sprintf("vigencia_hasta = %s", $escaped_value);
                    break;
            }
        }
        if (empty($set_parts)) return false; // Nada que actualizar

        $sql = "UPDATE cotizaciones SET " . implode(', ', $set_parts) . sprintf(" WHERE id_cotizacion = %d", $id_cotizacion);
        return Db::query($sql);
    }

    // En clase Cotizaciones
    public static function delete($id_cotizacion) {
        if (!ValidateData::validateInt($id_cotizacion) || $id_cotizacion <= 0) {
            return false;
        }
        // El ON DELETE CASCADE en cotizacion_detalles se encargará de los detalles
        $sql = sprintf("DELETE FROM cotizaciones WHERE id_cotizacion = %d", $id_cotizacion);
        return Db::query($sql);
    }

} // Fin clase Cotizaciones