<?php
/**
 * Created by PhpStorm.
 * User: mesad
 * Date: 17/05/2019
 * Time: 2:19 PM
 */
require_once(dirname(__FILE__) . '/../configImport.php');

class ValidateData
{
    static function validateStringReturnNULl($data)
    {
        if ($data == "" || $data == NULL) {
            return NULL;
        }
        return $data;

    }
    static function validateMail($data)
    {
        if (filter_var($data, FILTER_VALIDATE_EMAIL) == false)
            return false;
        else
            return true;
    }

    static function validateInt($data)
    {

        if (filter_var($data, FILTER_VALIDATE_INT) == false && $data != '')
            return false;
        else
            return true;
    }

    static function validateStringIsUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL))
            return true;
        else
            return false;

    }
//Construye el password con varios mecanismos de seguridad
    /**
     * Crea un hash de contraseña seguro usando el algoritmo predeterminado de PHP.
     *
     * @param string $plainPassword La contraseña en texto plano.
     * @return string|false El hash de la contraseña o false en caso de error.
     */
    public static function passwordConstructor($plainPassword) {
        // PASSWORD_DEFAULT usa el algoritmo más fuerte disponible (actualmente bcrypt)
        // y se actualiza automáticamente en futuras versiones de PHP.
        // Considera usar PASSWORD_ARGON2ID si tu versión de PHP >= 7.2 lo soporta y tus requisitos lo permiten.
        $options = [
            // 'cost' => 12, // Opcional: Ajusta el coste si es necesario (más alto = más seguro pero más lento)
        ];
        return password_hash($plainPassword, PASSWORD_DEFAULT, $options);
    }

    /**
     * Verifica si una contraseña en texto plano coincide con un hash existente.
     *
     * @param string $plainPassword La contraseña en texto plano ingresada por el usuario.
     * @param string $hash El hash almacenado en la base de datos.
     * @return bool True si la contraseña coincide, false en caso contrario.
     */
    public static function passwordVerify($plainPassword, $hash) {
        // Compara de forma segura la contraseña con el hash.
        // Extrae automáticamente el algoritmo y la sal del hash.
        return password_verify($plainPassword, $hash);
    }

    /**
     * @Description: Método que destruye y cierra las sesiones
     */
    static function sessionClose()
    {

        //Se valida si ya se ha iniciado el manejo de sesiones, en caso de que no se haya iniciado, se realiza la inicializacion
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            $id_sesion = ValidateData::GetSessionUserId();
            $company_id = Company::getCompanyIdByCompanyUserId($id_sesion);


        } else {
            $id_sesion = ValidateData::GetSessionUserId();
            $company_id = Company::getCompanyIdByCompanyUserId($id_sesion);


        }

//        Apc::getAllApcOperador()
//        se obtiene el id de la sesion para eliminar el token creado

//        se elimina el token creado con el parametro id de la sesion, osea, el id usuario empresa

        //Destruimos todas las sesiones
        session_destroy();

        //Limpiamos todas las sesiones
        session_unset();


        return true;
    }

    static function GetSessionUserId()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['userId']))
            return $_SESSION['userId'];

    }


//metodo para obtener el id de la sesion

    /**
     * Crea la sesion, osea hace el login que le permite al usuario continuar
     */
    static function sessionCreate($user_role = null, $user_id = null, $token = NULL)
    {

        //Se inicializa el manejo de sesiones
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
//
//            //Se carga el token de sesion de seguridad
//            $_SESSION['tokenLoginSecret'] = constant('TOKEN_SESSION_GENERAL');
//            $_SESSION['tokenLoginSecret'] = Security::generateRandomToken();

        //Se carga el estado del login
        $_SESSION['sessionStatus'] = '1';

        //Se carga el rol empresa
        $_SESSION['userRole'] = $user_role;

        //Creamos sesión con tiempo, para controlar cuando destruirla por inactividad
        $_SESSION['time_session_create'] = time();

        //Se carga el id del usuario
        $_SESSION['userId'] = $user_id;
        $_SESSION['sessionToken'] = $token;

        return true;

    }

    static function GetSessionToken()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }


        return $_SESSION['sessionToken'];

    }


    static function generateRandomToken()
    {

        $token_pre = bin2hex(openssl_random_pseudo_bytes(16));
        $time = time();

        $token = md5($token_pre . $time);

        return $token;
    }



    /**
     * Valida si una cadena de texto representa una fecha válida según un formato específico.
     * Por defecto valida el formato 'YYYY-MM-DD'.
     *
     * @param string|null $value La cadena de texto a validar como fecha.
     * @param string $format El formato esperado (según los caracteres de formato de PHP date(), ej: 'Y-m-d', 'd/m/Y').
     * @return bool True si la cadena es una fecha válida en el formato especificado, false en caso contrario.
     */
    public static function validateDate($value, $format = 'Y-m-d') {
        // 1. Verificar que el valor sea un string no vacío
        if (!isset($value) || !is_string($value) || trim($value) === '') {
            return false;
        }

        // 2. Intentar crear un objeto DateTime desde el formato y el valor
        // El '!' antes del formato resetea los campos a la época Unix antes de parsear,
        // lo que ayuda a evitar fechas inválidas como '2023-02-30' que a veces
        // se interpretan como '2023-03-02' sin el '!'.
        $dateTime = DateTime::createFromFormat('!' . $format, $value);

        // 3. Comprobar si la creación fue exitosa Y si al formatear de vuelta
        //    el objeto DateTime obtenido, coincide exactamente con la cadena original.
        //    Esto asegura que no hubo "overflow" o corrección automática (ej: día 31 en mes de 30 días)
        //    y que el formato era estrictamente el esperado.
        if ($dateTime && $dateTime->format($format) === $value) {
            // La fecha es válida y coincide exactamente con el formato
            return true;
        } else {
            // La fecha no es válida o no coincide estrictamente con el formato
            return false;
        }
    }



}





