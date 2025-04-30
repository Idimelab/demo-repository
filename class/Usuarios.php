<?php

/**
 * Clase para gestionar las operaciones CRUD de la tabla 'usuarios'.
 * Todas las interacciones con la base de datos para usuarios se manejan aquí.
 */
class Usuarios
{

    /**
     * Valida las credenciales de un usuario para iniciar sesión.
     *
     * @param string|null $email El email del usuario.
     * @param string|null $plainPassword La contraseña en texto plano.
     * @return array|false Devuelve un array con datos del usuario (id_usuario, id_rol, nombre) si la validación es exitosa, false en caso contrario.
     */
    static function validateLogin($email = NULL, $plainPassword = NULL)
    {
        // Validación básica de entrada
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("validateLogin: Email inválido o vacío.");
            return false;
        }
        if (empty($plainPassword)) {
            error_log("validateLogin: Contraseña vacía.");
            return false;
        }

        // Consultar usuario por email (solo activos)
        $sql = "
            SELECT
                id_usuario,
                id_rol,
                nombre,
                apellido,
                contrasena_hash  -- Se necesita el hash para verificar
            FROM
                usuarios
            WHERE
                email = '%s'
            AND
                activo = TRUE
            LIMIT 1
        ";
        // ¡¡IMPORTANTE!! Asegúrate de que $email esté saneado/escapado antes de sprintf si no usas prepared statements
        $escaped_email = Db::escape($email); // Asumiendo que Db::escape existe
        $sql = sprintf($sql, $escaped_email);

        $result = Db::query($sql);

        // Verificar si se encontró el usuario
        if (isset($result[0]['id_usuario']) && !empty($result[0]['contrasena_hash'])) {
            $userData = $result[0];

            // Verificar la contraseña usando el método de seguridad
            // Asumiendo que Security::passwordVerify existe y usa password_verify()
            if (Security::passwordVerify($plainPassword, $userData['contrasena_hash'])) {
                // Contraseña correcta: Devolver datos relevantes del usuario (sin el hash)
                unset($userData['contrasena_hash']); // No enviar el hash al frontend ni guardarlo en sesión directamente
                return $userData;
            } else {
                error_log("validateLogin: Contraseña incorrecta para email: " . $email);
                return false; // Contraseña incorrecta
            }
        } else {
            error_log("validateLogin: Usuario no encontrado o inactivo para email: " . $email);
            return false; // Usuario no encontrado o hash no disponible
        }
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     *
     * @param int $id_rol ID del rol asignado.
     * @param string $nombre Nombre del usuario.
     * @param string $apellido Apellido del usuario.
     * @param string $tipo_documento Tipo de documento (CC, CE, TI, PAS).
     * @param string $documento Número de documento (debe ser único).
     * @param string $email Email del usuario (debe ser único).
     * @param string $plainPassword Contraseña en texto plano (será hasheada).
     * @param string|null $fecha_nacimiento Fecha de nacimiento (YYYY-MM-DD).
     * @param string|null $telefono Teléfono de contacto.
     * @param string|null $direccion Dirección.
     * @param string|null $eps EPS.
     * @param string|null $palabra_seguridad Palabra de seguridad en texto plano (será hasheada, opcional).
     *
     * @return int|string|false Devuelve el ID del usuario creado si tiene éxito, un string con error específico ('EmailExistente', 'DocumentoExistente'), o false si falla la validación o la inserción.
     */
    static function create(
        $id_rol, $nombre, $apellido, $tipo_documento, $documento, $email, $plainPassword,
        $fecha_nacimiento = NULL, $telefono = NULL, $direccion = NULL, $eps = NULL, $palabra_seguridad = NULL
    ) {
        // --- Validación de Datos ---
        if (!is_numeric($id_rol) || $id_rol <= 0) return false; // Validar ID rol
        if (empty(trim($nombre))) return false;
        if (empty(trim($apellido))) return false;
        if (!in_array($tipo_documento, ['CC', 'CE', 'TI', 'PAS'])) return false; // Validar enum
        if (empty(trim($documento))) return false;
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        if (empty($plainPassword) || strlen($plainPassword) < 6) return false; // Ejemplo: mínimo 6 caracteres
        if ($fecha_nacimiento !== NULL && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) return false; // Formato YYYY-MM-DD
        // Añadir más validaciones si es necesario (longitud de strings, formato teléfono, etc.)

        // --- Hashear Contraseña y Palabra de Seguridad ---
        $contrasena_hash = Security::passwordConstructor($plainPassword);
        $palabra_seguridad_hash = ($palabra_seguridad !== NULL && !empty($palabra_seguridad)) ? Security::passwordConstructor($palabra_seguridad) : NULL;

        // --- Verificar Existencia (Email y Documento) ---
        if (self::checkIfExistsByEmail($email)) {
            return 'EmailExistente';
        }
        if (self::checkIfExistsByDocumento($documento)) {
            return 'DocumentoExistente';
        }

        // --- Construcción de la Consulta INSERT ---
        // ¡¡IMPORTANTE!! Escapar todas las variables antes de usar sprintf
        $escaped_nombre = Db::escape(trim($nombre));
        $escaped_apellido = Db::escape(trim($apellido));
        $escaped_tipo_documento = Db::escape($tipo_documento);
        $escaped_documento = Db::escape(trim($documento));
        $escaped_email = Db::escape(trim($email));
        $escaped_contrasena_hash = Db::escape($contrasena_hash); // Aunque sea hash, por si acaso
        $escaped_fecha_nacimiento = ($fecha_nacimiento !== NULL) ? "'" . Db::escape($fecha_nacimiento) . "'" : "NULL";
        $escaped_telefono = ($telefono !== NULL) ? "'" . Db::escape(trim($telefono)) . "'" : "NULL";
        $escaped_direccion = ($direccion !== NULL) ? "'" . Db::escape(trim($direccion)) . "'" : "NULL";
        $escaped_eps = ($eps !== NULL) ? "'" . Db::escape(trim($eps)) . "'" : "NULL";
        $escaped_palabra_seguridad_hash = ($palabra_seguridad_hash !== NULL) ? "'" . Db::escape($palabra_seguridad_hash) . "'" : "NULL";

        $sql = "
            INSERT INTO usuarios
            (
                id_rol, nombre, apellido, tipo_documento, documento, email, contrasena_hash,
                fecha_nacimiento, telefono, direccion, eps, palabra_seguridad_hash, activo
            )
            VALUES
            (
                %d, '%s', '%s', '%s', '%s', '%s', '%s',
                %s, %s, %s, %s, %s, TRUE
            )
        ";

        $sql = sprintf($sql,
            $id_rol, // Es numérico, no necesita comillas
            $escaped_nombre,
            $escaped_apellido,
            $escaped_tipo_documento,
            $escaped_documento,
            $escaped_email,
            $escaped_contrasena_hash,
            $escaped_fecha_nacimiento, // Ya incluye comillas o NULL
            $escaped_telefono,         // Ya incluye comillas o NULL
            $escaped_direccion,        // Ya incluye comillas o NULL
            $escaped_eps,              // Ya incluye comillas o NULL
            $escaped_palabra_seguridad_hash // Ya incluye comillas o NULL
        );

        // Ejecutar la consulta y devolver el ID o false
        $result = Db::query($sql); // Asumiendo que Db::query devuelve el ID en inserción exitosa o true/false

        // Verificar si Db::query devuelve el ID o un booleano y retornar apropiadamente
        if ($result && is_numeric($result)) { // Si devuelve el ID
            return (int)$result;
        } elseif ($result === true) { // Si solo devuelve true
            // Podríamos intentar obtener el último ID insertado si Db lo permite
            // return Db::getLastInsertId();
            return true; // O simplemente retornar true
        } else {
            error_log("Error al crear usuario: " ); // Asumiendo Db::getLastError
            return false;
        }
    }

    /**
     * Obtiene los datos de un usuario por su ID.
     *
     * @param int $id_usuario ID del usuario a buscar.
     * @return array|false Array con datos del usuario (sin contraseña) o false si no se encuentra.
     */
    static function getById($id_usuario)
    {
        if (!is_numeric($id_usuario) || $id_usuario <= 0) {
            return false;
        }

        $sql = "
            SELECT
                id_usuario, id_rol, nombre, apellido, tipo_documento, documento,
                fecha_nacimiento, telefono, direccion, email, eps, fecha_registro, activo
            FROM
                usuarios
            WHERE
                id_usuario = %d
            LIMIT 1
        ";
        $sql = sprintf($sql, $id_usuario);

        $result = Db::query($sql);

        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * Obtiene los datos de un usuario por su Email.
     * Útil para comprobaciones antes de crear o para mostrar perfil.
     *
     * @param string $email Email del usuario a buscar.
     * @return array|false Array con datos del usuario (sin contraseña) o false si no se encuentra.
     */
    static function getByEmail($email)
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // ¡¡IMPORTANTE!! Escapar email
        $escaped_email = Db::escape($email);
        $sql = "
            SELECT
                id_usuario, id_rol, nombre, apellido, tipo_documento, documento,
                fecha_nacimiento, telefono, direccion, email, eps, fecha_registro, activo
            FROM
                usuarios
            WHERE
                email = '%s'
            LIMIT 1
        ";
        $sql = sprintf($sql, $escaped_email);

        $result = Db::query($sql);

        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * Obtiene los datos de un usuario por su número de documento.
     *
     * @param string $documento Documento del usuario a buscar.
     * @return array|false Array con datos del usuario (sin contraseña) o false si no se encuentra.
     */
    static function getByDocumento($documento)
    {
        if (empty(trim($documento))) {
            return false;
        }
        // ¡¡IMPORTANTE!! Escapar documento
        $escaped_documento = Db::escape(trim($documento));
        $sql = "
            SELECT
                id_usuario, id_rol, nombre, apellido, tipo_documento, documento,
                fecha_nacimiento, telefono, direccion, email, eps, fecha_registro, activo
            FROM
                usuarios
            WHERE
                documento = '%s'
            LIMIT 1
        ";
        $sql = sprintf($sql, $escaped_documento);

        $result = Db::query($sql);

        return isset($result[0]) ? $result[0] : false;
    }


    /**
     * Actualiza los datos de un usuario existente.
     *
     * @param int $id_usuario ID del usuario a actualizar.
     * @param array $data Array asociativo con los campos a actualizar (ej: ['nombre' => 'Nuevo Nombre', 'telefono' => '12345']).
     * Campos permitidos: id_rol, nombre, apellido, tipo_documento, documento, fecha_nacimiento,
     * telefono, direccion, email, eps, activo, plainPassword, palabra_seguridad.
     * ¡No se debe permitir actualizar 'contrasena_hash' directamente! Usar 'plainPassword'.
     * @return bool|string True si la actualización fue exitosa, false si falló, o string con error específico.
     */
    static function update($id_usuario, $data)
    {
        if (!is_numeric($id_usuario) || $id_usuario <= 0 || empty($data) || !is_array($data)) {
            return false;
        }

        // Campos permitidos para actualización
        $allowed_fields = [
            'id_rol', 'nombre', 'apellido', 'tipo_documento', 'documento', 'fecha_nacimiento',
            'telefono', 'direccion', 'email', 'eps', 'activo', 'plainPassword', 'palabra_seguridad'
        ];

        $set_parts = []; // Partes de la consulta SET

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed_fields)) {
                continue; // Ignorar campos no permitidos
            }

            // --- Validaciones específicas para cada campo ---
            $escaped_value = NULL;
            switch ($field) {
                case 'id_rol':
                    if (!is_numeric($value) || $value <= 0) continue 2; // Saltar al siguiente item del foreach
                    $escaped_value = (int)$value;
                    $set_parts[] = sprintf("id_rol = %d", $escaped_value);
                    break;
                case 'nombre':
                case 'apellido':
                    if (empty(trim($value))) continue 2;
                    $escaped_value = Db::escape(trim($value));
                    $set_parts[] = sprintf("%s = '%s'", $field, $escaped_value);
                    break;
                case 'tipo_documento':
                    if (!in_array($value, ['CC', 'CE', 'TI', 'PAS'])) continue 2;
                    $escaped_value = Db::escape($value);
                    $set_parts[] = sprintf("tipo_documento = '%s'", $escaped_value);
                    break;
                case 'documento':
                    if (empty(trim($value))) continue 2;
                    // Verificar si el nuevo documento ya existe para OTRO usuario
                    $existing_user = self::getByDocumento(trim($value));
                    if ($existing_user && $existing_user['id_usuario'] != $id_usuario) {
                        return 'DocumentoExistente';
                    }
                    $escaped_value = Db::escape(trim($value));
                    $set_parts[] = sprintf("documento = '%s'", $escaped_value);
                    break;
                case 'email':
                    if (empty($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) continue 2;
                    // Verificar si el nuevo email ya existe para OTRO usuario
                    $existing_user = self::getByEmail(trim($value));
                    if ($existing_user && $existing_user['id_usuario'] != $id_usuario) {
                        return 'EmailExistente';
                    }
                    $escaped_value = Db::escape(trim($value));
                    $set_parts[] = sprintf("email = '%s'", $escaped_value);
                    break;
                case 'plainPassword':
                    if (!empty($value) && strlen($value) >= 6) { // Solo actualizar si se provee y es válida
                        $contrasena_hash = Security::passwordConstructor($value);
                        $escaped_value = Db::escape($contrasena_hash);
                        $set_parts[] = sprintf("contrasena_hash = '%s'", $escaped_value);
                    }
                    break;
                case 'palabra_seguridad':
                    if (!empty($value)) {
                        $palabra_seguridad_hash = Security::passwordConstructor($value);
                        $escaped_value = Db::escape($palabra_seguridad_hash);
                        $set_parts[] = sprintf("palabra_seguridad_hash = '%s'", $escaped_value);
                    } else {
                        // Permitir borrar la palabra de seguridad
                        $set_parts[] = "palabra_seguridad_hash = NULL";
                    }
                    break;
                case 'fecha_nacimiento':
                    if ($value !== NULL && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) continue 2;
                    $escaped_value = ($value !== NULL) ? "'" . Db::escape($value) . "'" : "NULL";
                    $set_parts[] = sprintf("fecha_nacimiento = %s", $escaped_value); // Ya tiene comillas o NULL
                    break;
                case 'telefono':
                case 'direccion':
                case 'eps':
                    $escaped_value = ($value !== NULL && !empty(trim($value))) ? "'" . Db::escape(trim($value)) . "'" : "NULL";
                    $set_parts[] = sprintf("%s = %s", $field, $escaped_value); // Ya tiene comillas o NULL
                    break;
                case 'activo':
                    $escaped_value = ($value === true || $value === 1 || $value === '1') ? 'TRUE' : 'FALSE';
                    $set_parts[] = sprintf("activo = %s", $escaped_value);
                    break;
            }
        }

        if (empty($set_parts)) {
            return false; // No hay nada que actualizar
        }

        // Construir la consulta UPDATE
        $sql = "UPDATE usuarios SET ";
        $sql .= implode(', ', $set_parts);
        $sql .= sprintf(" WHERE id_usuario = %d", $id_usuario);

        // Ejecutar y devolver resultado
        return Db::query($sql); // Asumiendo que Db::query devuelve true en éxito, false en fallo
    }


    /**
     * Verifica si ya existe un usuario con el email dado.
     *
     * @param string $email Email a verificar.
     * @return bool True si existe, false si no.
     */
    static function checkIfExistsByEmail($email)
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $escaped_email = Db::escape($email);
        $sql = sprintf("SELECT 1 FROM usuarios WHERE email = '%s' LIMIT 1", $escaped_email);
        $result = Db::query($sql);
        return !empty($result); // Devuelve true si el query encontró algo
    }

    /**
     * Verifica si ya existe un usuario con el documento dado.
     *
     * @param string $documento Documento a verificar.
     * @return bool True si existe, false si no.
     */
    static function checkIfExistsByDocumento($documento)
    {
        if (empty(trim($documento))) {
            return false;
        }
        $escaped_documento = Db::escape(trim($documento));
        $sql = sprintf("SELECT 1 FROM usuarios WHERE documento = '%s' LIMIT 1", $escaped_documento);
        $result = Db::query($sql);
        return !empty($result); // Devuelve true si el query encontró algo
    }

    // Se podrían añadir métodos para:
    // - delete($id_usuario) (o mejor, solo desactivar con update(['activo' => false]))
    // - getAll($filters = []) para obtener listas de usuarios con filtros
    // - changePassword($id_usuario, $newPlainPassword)
    // - etc.

    // En la clase Usuarios
    public static function delete($id_usuario) {
        if (!ValidateData::validateInt($id_usuario) || $id_usuario <= 0) return false; // Asumiendo validateInt existe
        // Borrado Lógico (Recomendado)
        return self::update($id_usuario, ['activo' => false]);
        // O Borrado Físico (¡CUIDADO!)
        // $sql = sprintf("DELETE FROM usuarios WHERE id_usuario = %d", $id_usuario);
        // return Db::query($sql);
    }


 /**
  * Obtiene una lista paginada/filtrada de usuarios.
  * Permite buscar por texto y filtrar por campos específicos como id_rol.
  *
  * @param int $page Página actual.
  * @param int $itemsPerPage Usuarios por página.
  * @param string|null $search Término de búsqueda (nombre, apellido, doc, email).
  * @param bool $returnCount Si es true, devuelve solo el conteo total.
  * @param array $filters Array asociativo para filtros (ej: ['id_rol' => 1, 'activo' => true]).
  * @return array|int Lista de usuarios o conteo total.
  */
 public static function getAll($page = 1, $itemsPerPage = 20, $search = null, $returnCount = false, $filters = []) {

     // Construir partes de la consulta
     $selectFields = $returnCount ? "COUNT(u.id_usuario) as total" : "u.*, r.nombre_rol";
     $baseSql = "FROM usuarios u LEFT JOIN roles r ON u.id_rol = r.id_rol"; // Usar LEFT JOIN por si algún usuario no tuviera rol (aunque no debería pasar)

     $whereParts = []; // Array para almacenar condiciones WHERE

     // --- Filtro por Estado (Ejemplo: permitir filtrar por activo/inactivo o solo activos) ---
     // Por defecto, podrías querer ver solo activos, a menos que $filters lo anule
     $defaultActiveFilter = true;
     if (isset($filters['activo'])) {
         if (is_bool($filters['activo'])) {
             $whereParts[] = $filters['activo'] ? "u.activo = TRUE" : "u.activo = FALSE";
             $defaultActiveFilter = false; // Ya se aplicó un filtro de activo explícito
         } elseif ($filters['activo'] === 'todos') { // Opción para ver todos
             $defaultActiveFilter = false;
         }
     }
     // Aplicar filtro activo por defecto si no se especificó uno diferente
     if ($defaultActiveFilter) {
         $whereParts[] = "u.activo = TRUE";
     }


     // --- Filtro por Rol (¡Lo que necesitas!) ---
     if (isset($filters['id_rol']) && ValidateData::validateInt($filters['id_rol']) && $filters['id_rol'] > 0) {
         // Añadir condición para id_rol si es un entero válido y positivo
         $whereParts[] = sprintf("u.id_rol = %d", (int)$filters['id_rol']);
     }
     // Podrías añadir más filtros aquí si los necesitas (ej: por tipo_documento, etc.)


     // --- Filtro de Búsqueda General ---
     if (!empty(trim($search))) {
         // ¡IMPORTANTE! Usar el método de escape correcto de tu clase Db
         // Asumiendo que es Db::escape, no Db::escape
         $escaped_search = Db::escape('%' . trim($search) . '%');
         $searchConditions = [
             "u.nombre LIKE '{$escaped_search}'",
             "u.apellido LIKE '{$escaped_search}'",
             "u.documento LIKE '{$escaped_search}'",
             "u.email LIKE '{$escaped_search}'"
             // Podrías añadir búsqueda por nombre de rol si es necesario: "r.nombre_rol LIKE '{$escaped_search}'"
         ];
         $whereParts[] = "(" . implode(" OR ", $searchConditions) . ")";
     }

     // --- Construir Cláusula WHERE ---
     $whereClause = "";
     if (!empty($whereParts)) {
         $whereClause = " WHERE " . implode(" AND ", $whereParts);
     }
     // --- Ejecutar Consulta ---
     if ($returnCount) {
         // Devolver solo el conteo
         $sql = "SELECT " . $selectFields . " " . $baseSql . $whereClause;
         $result = Db::query($sql);
         return isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
     } else {
         // Devolver la lista paginada
         $offset = ($page > 0 ? $page - 1 : 0) * $itemsPerPage; // Asegurar que offset no sea negativo
         $sql = "SELECT " . $selectFields . " " . $baseSql . $whereClause;
         // Añadir ordenamiento
         $sql .= " ORDER BY u.apellido ASC, u.nombre ASC"; // O hacerlo configurable
         // Añadir límite y offset
         $sql .= sprintf(" LIMIT %d, %d", $offset, $itemsPerPage);

         $result = Db::query($sql);
         return $result ?: []; // Devuelve el array de resultados o un array vacío
     }
 }



} // Fin clase Usuarios