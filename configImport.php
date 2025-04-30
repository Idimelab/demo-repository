
<?php
/**
 * @Description: Documento que importará el archivo con las constantes del proyecto
 * @User: luis.chamorro
 * @Date: 21/02/19
 */

//Importamos el archivo 'config.php' para reutilizar esta configuración en toda la plataforma sin hacer redundancia de llamados
require_once (dirname(__FILE__).'/config.php');


//Esto permite autoinstanciar las clases segun se necesiten sin necesidad de hacer imports en cada archivo
function autoloader_class($class) {

    //Excepciones
    if($class == 'SebastianBergmann\Invoker\Invoker'){
        //No se importa nada para que no afecte a unit test
    }
    else{
        require_once(dirname(__FILE__).'/class/' . $class . '.php');
    }



}

spl_autoload_register('autoloader_class');