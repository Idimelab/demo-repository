<?php
/**
 * Clase para interactuar con la tabla 'roles'.
 */
class Roles {

    /**
     * Obtiene el nombre de un rol por su ID.
     *
     * @param int $id_rol ID del rol.
     * @return string|null El nombre del rol o null si no se encuentra.
     */
    public static function getNombreById($id_rol) {
        if (!ValidateData::validateInt($id_rol) || $id_rol <= 0) { // Usando ValidateData
            return null;
        }
        // Asumiendo que Db::query existe y devuelve un array asociativo
        $sql = sprintf("SELECT nombre_rol FROM roles WHERE id_rol = %d LIMIT 1", $id_rol);
        $result = Db::query($sql); // Db::query debería manejar la conexión y ejecución

        return isset($result[0]['nombre_rol']) ? $result[0]['nombre_rol'] : null;
    }
// En la clase Roles
    public static function getAll() {
        $sql = "SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol ASC";
        $result = Db::query($sql);
        return $result ?: []; // Devuelve array de roles [ ['id_rol'=>1, 'nombre_rol'=>'Paciente'], ... ]
    }
} // Fin clase Roles
?>