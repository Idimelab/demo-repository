<?php
/**
 * Created by PhpStorm.
 * User: mesad
 * Date: 17/05/2019
 * Time: 6:20 PM
 */


require_once(dirname(__FILE__).'/../configImport.php');

//Se inicializa la sesion
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

//***** Validamos el tiempo de actividad de la sesión *****

// Máxima duración de sesión activa en hora
//define( 'MAX_SESSION_TIEMPO', 21600);//6 horas

// Controla cuando se ha creado y cuando tiempo ha recorrido
//if ( isset( $_SESSION[ 'time_session_create' ] ) &&
//    ( time() - $_SESSION[ 'time_session_create' ] > MAX_SESSION_TIEMPO ) ) {
//
//    //Se cierran y eliminan las sesiones
//    Security::sessionClose();
//
//    //Se carga la vista de login
//    include_once (dirname(__FILE__).'/../controllers/logIn.php');
//}
//
//$_SESSION[ 'time_session_create' ] = time();
//********************************************************
//Se hacen las verificaciones de seguridad
if (

    isset($_SESSION['sessionStatus']) && $_SESSION['sessionStatus'] != null && $_SESSION['sessionStatus'] == 1    && isset($_SESSION['userRole']) && $_SESSION['userRole'] != null
    &&  ($_SESSION['userRole'] == 'Administrador' || $_SESSION['userRole'] == 'Auxiliar' || $_SESSION['userRole'] == 'Paciente')
){/* Esta tdo OK */}
else{

    //Se cierran y eliminan las sesiones
    Security::sessionClose();


}
