<?php

//Importamos el archivo que contiene la configuración global
require_once(dirname(__FILE__).'/configImport.php');

//Se incluye el archivo de seguridad que no permite hacer nada si no estan las sesiones OK
require_once(dirname(__FILE__).'/security/secure-session.php');

if(session_status() == PHP_SESSION_NONE){
    @session_start();
}


//Se carga el rol del usuario
if(isset($_SESSION['userRole']) && $_SESSION['userRole'] != null && $_SESSION['userRole'] != ''){
    $user_role = $_SESSION['userRole'];
}
else{
    $user_role = "N/A";
}

$domain = $_SERVER['HTTP_HOST'];

//Validacion segun el rol
    switch ($user_role){
        case 'Administrador':
            include_once (dirname(__FILE__).'/controllers/administrador/index.php');
            break;
        case 'Paciente':
            include_once (dirname(__FILE__).'/controllers/paciente/index.php');
            break;
        default:
            include_once (dirname(__FILE__).'/controllers/Login.php');
            break;
    }




