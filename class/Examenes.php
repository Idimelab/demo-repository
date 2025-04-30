<?php

/**
 * Clase para gestionar las operaciones CRUD de la tabla 'examenes'.
 */
class Examenes
{

    /**
     * Crea un nuevo examen en el catálogo.
     *
     * @param string $nombre_examen Nombre del examen.
     * @param float|string $precio Precio del examen.
     * @param string|null $codigo_examen Código único (opcional).
     * @param string|null $descripcion Descripción detallada (opcional).
     * @param string|null $preparacion Instrucciones de preparación (opcional).
     * @param bool $activo Estado inicial (default: true).
     *
     * @return int|string|false ID del examen creado, 'CodigoExistente', o false en error.
     */
    static function create($nombre_examen, $precio, $codigo_examen = NULL, $descripcion = NULL, $preparacion = NULL, $activo = true)
    {
        // Validación básica
        if (empty(trim($nombre_examen))) return false;
        if (!is_numeric($precio) || $precio < 0) return false;

        // Verificar código existente si se proporciona
        if ($codigo_examen !== NULL && !empty(trim($codigo_examen))) {
            if (self::checkIfExistsByCodigo(trim($codigo_examen))) {
                return 'CodigoExistente';
            }
            $escaped_codigo = "'" . Db::escape(trim($codigo_examen)) . "'";
        } else {
            $escaped_codigo = "NULL";
        }

        // Escapar valores
        $escaped_nombre = Db::escape(trim($nombre_examen));
        $escaped_precio = floatval($precio); // Asegurar que sea número
        $escaped_descripcion = ($descripcion !== NULL) ? "'" . Db::escape(trim($descripcion)) . "'" : "NULL";
        $escaped_preparacion = ($preparacion !== NULL) ? "'" . Db::escape(trim($preparacion)) . "'" : "NULL";
        $escaped_activo = ($activo === true) ? 'TRUE' : 'FALSE';

        $sql = "
            INSERT INTO examenes
            (nombre_examen, precio, codigo_examen, descripcion, preparacion, activo)
            VALUES
            ('%s', %f, %s, %s, %s, %s)
        ";
        $sql = sprintf($sql,
            $escaped_nombre,
            $escaped_precio,
            $escaped_codigo,
            $escaped_descripcion,
            $escaped_preparacion,
            $escaped_activo
        );

        $result = Db::query($sql);

        // Devolver ID o false
        if ($result && is_numeric($result)) { return (int)$result; }
        elseif ($result === true) { return true; /* O intentar obtener ID */ }
        else { error_log("Error al crear examen: " ); return false; }
    }

    /**
     * Obtiene un examen por su ID.
     *
     * @param int $id_examen ID del examen.
     * @return array|false Array con datos del examen o false si no existe.
     */
    static function getById($id_examen)
    {
        if (!is_numeric($id_examen) || $id_examen <= 0) return false;

        $sql = sprintf("SELECT * FROM examenes WHERE id_examen = %d LIMIT 1", $id_examen);
        $result = Db::query($sql);
        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * Obtiene un examen por su código.
     *
     * @param string $codigo_examen Código del examen.
     * @return array|false Array con datos del examen o false si no existe.
     */
    static function getByCodigo($codigo_examen)
    {
        if (empty(trim($codigo_examen))) return false;
        $escaped_codigo = Db::escape(trim($codigo_examen));
        $sql = sprintf("SELECT * FROM examenes WHERE codigo_examen = '%s' LIMIT 1", $escaped_codigo);
        $result = Db::query($sql);
        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * Obtiene todos los exámenes activos.
     *
     * @return array Array de exámenes activos.
     */
    static function getAllActive()
    {
        $sql = "SELECT * FROM examenes WHERE activo = TRUE ORDER BY nombre_examen ASC";
        $result = Db::query($sql);
        return $result ? $result : []; // Devolver array vacío si no hay resultados
    }

    /**
     * Obtiene todos los exámenes (activos e inactivos).
     *
     * @return array Array de todos los exámenes.
     */
    static function getAll()
    {
        $sql = "SELECT * FROM examenes ORDER BY nombre_examen ASC";
        $result = Db::query($sql);
        return $result ? $result : [];
    }


    /**
     * Actualiza un examen existente.
     *
     * @param int $id_examen ID del examen a actualizar.
     * @param array $data Array asociativo con campos a actualizar (nombre_examen, precio, codigo_examen, descripcion, preparacion, activo).
     * @return bool|string True en éxito, false en fallo, o 'CodigoExistente'.
     */
    static function update($id_examen, $data)
    {
        if (!is_numeric($id_examen) || $id_examen <= 0 || empty($data) || !is_array($data)) {
            return false;
        }

        $allowed_fields = ['nombre_examen', 'precio', 'codigo_examen', 'descripcion', 'preparacion', 'activo'];
        $set_parts = [];

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed_fields)) continue;

            $escaped_value = NULL;
            switch ($field) {
                case 'nombre_examen':
                    if (empty(trim($value))) continue 2;
                    $escaped_value = "'" . Db::escape(trim($value)) . "'";
                    $set_parts[] = sprintf("%s = %s", $field, $escaped_value);
                    break;
                case 'precio':
                    if (!is_numeric($value) || $value < 0) continue 2;
                    $escaped_value = floatval($value);
                    $set_parts[] = sprintf("%s = %f", $field, $escaped_value);
                    break;
                case 'codigo_examen':
                    if ($value !== NULL && !empty(trim($value))) {
                        // Verificar si el nuevo código ya existe para OTRO examen
                        $existing_exam = self::getByCodigo(trim($value));
                        if ($existing_exam && $existing_exam['id_examen'] != $id_examen) {
                            return 'CodigoExistente';
                        }
                        $escaped_value = "'" . Db::escape(trim($value)) . "'";
                    } else {
                        $escaped_value = "NULL"; // Permitir quitar el código
                    }
                    $set_parts[] = sprintf("%s = %s", $field, $escaped_value);
                    break;
                case 'descripcion':
                case 'preparacion':
                    $escaped_value = ($value !== NULL && !empty(trim($value))) ? "'" . Db::escape(trim($value)) . "'" : "NULL";
                    $set_parts[] = sprintf("%s = %s", $field, $escaped_value);
                    break;
                case 'activo':
                    $escaped_value = ($value === true || $value === 1 || $value === '1') ? 'TRUE' : 'FALSE';
                    $set_parts[] = sprintf("%s = %s", $field, $escaped_value);
                    break;
            }
        }

        if (empty($set_parts)) return false; // No hay nada que actualizar

        $sql = "UPDATE examenes SET ";
        $sql .= implode(', ', $set_parts);
        $sql .= sprintf(" WHERE id_examen = %d", $id_examen);

        return Db::query($sql);
    }

    /**
     * Verifica si ya existe un examen con el código dado.
     *
     * @param string $codigo_examen Código a verificar.
     * @return bool True si existe, false si no.
     */
    static function checkIfExistsByCodigo($codigo_examen)
    {
        if (empty(trim($codigo_examen))) return false;
        $escaped_codigo = Db::escape(trim($codigo_examen));
        $sql = sprintf("SELECT 1 FROM examenes WHERE codigo_examen = '%s' LIMIT 1", $escaped_codigo);
        $result = Db::query($sql);
        return !empty($result);
    }

    // Podría añadirse un método delete($id_examen), aunque desactivar es más seguro.

    // En tu clase Examenes



    /**
     * "Elimina" un examen (Borrado Lógico - lo desactiva).
     *
     * @param int $id_examen ID del examen a desactivar.
     * @return bool True si tuvo éxito, false en caso contrario.
     */
    public static function delete($id_examen) {
        if (!ValidateData::validateInt($id_examen) || $id_examen <= 0) {
            return false;
        }
        // Llamar a update para cambiar el estado a inactivo
        return self::update($id_examen, ['activo' => false]);
    }


} // Fin clase Examenes