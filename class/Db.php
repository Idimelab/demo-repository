<?php
require_once(dirname(__FILE__) . '/../configImport.php');

class Db
{

    public static $query_error; //mario orozco haciendo una prueba para ver si se puede guardar el error en esta variable y

    //consultarse inmediatamente despues de llamar una funcion query
    // Función para escapar datos de entrada
    public static function escape($value)
    {
        // Crear una conexión temporal solo para el escape
        $dbConnection = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
        $dbConnection->set_charset('utf8mb4');

        // Verificar si la conexión es correcta
        if ($dbConnection->connect_error) {
            self::$query_error = $dbConnection->error;
            Debug::register("Error de Conexión ( $dbConnection->connect_errno ) $dbConnection->connect_error", "error", __FILE__, __LINE__);
            return $value; // Devuelve el valor sin escapar si no hay conexión
        }

        // Escapar el valor
        $escapedValue = $dbConnection->real_escape_string($value);

        // Cerrar la conexión
        $dbConnection->close();

        return $escapedValue;
    }

    static function query($query)
    {
        //Para evitar errores cuando la consulta inicia con varios espacios
        $typeQuery = trim($query);
        $typeQuery = trim($query);
        $typeQuery = trim($query);
        $typeQuery = trim($query);
        $typeQuery = trim($query);
        $typeQuery = trim($query);

        //Se separa el string por espacios
        $typeQuery = explode(" ", $typeQuery);

        //Se obtiene la primer parte del string
        $typeQuery = trim(strtolower($typeQuery[0]));

        switch ($typeQuery) {
            case 'select':
                //Inicialization
                $resultArray = array();

                //The connection
                $dbConnection = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
                //$dbConnection->query("SET NAMES utf8mb4");
                $dbConnection->set_charset('utf8mb4');
                if ($dbConnection->connect_error) {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error de Conexión ( $dbConnection->connect_errno ) $dbConnection->connect_error", "error", __FILE__, __LINE__);
                }

                //trigger_error("La consulta es : ".$query);
                $query = $dbConnection->query($query);
                if ($query != null || $query != false) {
                    while ($result = $query->fetch_assoc()) {
                        $resultArray[] = $result;
                    }
                } else {
                    Debug::register("Error en la consulta Mysql: $dbConnection->error", "error", __FILE__, __LINE__);
                    //esta linea a continuación la agrega mario orozco como sugerencia y para evaluación de conveniencia
                    //throw new Exception("Error en la consulta Mysql: $dbConnection->error");
                    //Esto nos permite saber el error de manera más fácil cuando se implementa un try catch en el código.

                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                }

                if (isset($result)) {
                    $result->free();
                }

                $dbConnection->close();

                return $resultArray;


                break;

            case 'insert':
                //Inicialization
                $result_final = false;

                //The connection
                //trigger_error("Variables ".DB_SERVER." ".DB_USER." ".DB_PASSWORD." ".DB_DATABASE);
                $dbConnection = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
                //$dbConnection->query("SET NAMES utf8mb4");
                $dbConnection->set_charset('utf8mb4');

                if ($dbConnection->connect_error) {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error de Conexión ( $dbConnection->connect_errno ) $dbConnection->connect_error", "error", __FILE__, __LINE__);
                }

                //The query
                $query = $dbConnection->query($query);
                if ($query != null || $query != false) {
                    $result_final = $dbConnection->insert_id;
                } else {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error en la consulta Mysql: $dbConnection->error", "error", __FILE__, __LINE__);
                }

                if (isset($result)) {
                    $result->free();
                }

                $dbConnection->close();

                return $result_final;
                break;

            case 'update':
                //Inicialization
                $result_final = false;

                //The connection
                $dbConnection = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
                if ($dbConnection->connect_error) {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error de Conexión ( $dbConnection->connect_errno ) $dbConnection->connect_error", "error", __FILE__, __LINE__);
                }
                $dbConnection->set_charset('utf8mb4');
                //The query
                $query = $dbConnection->query($query);
                if ($query != null || $query != false) {
                    $result_final = $dbConnection->affected_rows;
                } else {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error en la consulta Mysql: $dbConnection->error", "error", __FILE__, __LINE__);
                }

                $dbConnection->close();


                if ($result_final != -1) {
                    return true;
                } else {
                    return false;
                }
                break;
            case 'delete':
                //Inicialization
                $result_final = false;

                //The connection
                $dbConnection = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
                $dbConnection->set_charset('utf8mb4');
                if ($dbConnection->connect_error) {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error de Conexión ( $dbConnection->connect_errno ) $dbConnection->connect_error", "error", __FILE__, __LINE__);
                }

                //The query
                $query = $dbConnection->query($query);
                if ($query != null || $query != false) {
                    $result_final = $dbConnection->affected_rows;
                } else {
                    self::$query_error = $dbConnection->error;  //aqui hago uso de la variable query_error (mario orozco)
                    Debug::register("Error en la consulta Mysql: $dbConnection->error", "error", __FILE__, __LINE__);
                }

                $dbConnection->close();


                if ($result_final != -1) {
                    return true;
                } else {
                    return false;
                }
                break;
            default :

                Debug::register("No se logro determinar el tipo de consulta a la base de datos.", "error", __FILE__, __LINE__);
                break;
        }
    }
}
