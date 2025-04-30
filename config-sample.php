
<?php
/**
 * @Description: Documento que almacena las constantes de configuración del proyecto
 * @User: luis.chamorro
 * @Actualization: Joaquín Reyes
 * @Date: 04-12-2019
 */

//Definimos zona horaria de la página
date_default_timezone_set('America/Bogota');

//Definimos codificación de caracteres
//header("Content-Type: text/html;charset=utf-8");

//Evaluamos si el protocolo es 'https' o 'http'
if (isset($_SERVER['HTTPS']) === true)
    $protocol = 'https';
else
    $protocol = 'http';

//Definimos constante para el protocolo de la plataforma, puede ser (https-http)
define('SITE_PROTOCOL', $protocol);
define('DEBUG', true);

//Ruta general del proyecto
define('FULL_WEB_URL', constant('SITE_PROTOCOL').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/');

//Ruta directorio 'assets'
define('ASSETS_WEB_URL', constant('FULL_WEB_URL').'assets/');
define('FILES_COMPANY', __DIR__.'/files/');

//Ruta imágenes de la página
define('IMAGES_WEB_URL', constant('ASSETS_WEB_URL').'img/');
define('PAGINATION', 15);

//Ruta files de la página
define('FILES_WEB_URL', constant('SITE_PROTOCOL').'://'.$_SERVER['HTTP_HOST'].'/scripts/files/');

//Constantes para NodeJs
define('URL_NODE_SERVER', 'https://whatmas.com:8065');

$token='71d1120900846339a75ef892fa12fd69';

//Datos de conexión a la BD
define('DB_SERVER','localhost');
define('DB_USER','root'); //uwhatmas - root - phpmyadmin
define('DB_PASSWORD',''); //wps0luc10n35* - '' - usuario - whatmas
define('DB_DATABASE','jestaff_soft');//whatmas - velotax_messages

//$loca="localhost";
//$usuario="jestaffc_root";
//$contra="claveSegura2020";
//$base="jestaffc_soft";


