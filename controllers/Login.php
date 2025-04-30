<?php


//Definimos las variables generales a utilizar con 'twig'
$generalParam = array(
    "full_web_url" => constant('FULL_WEB_URL'),
    "full_assets_url" => constant('ASSETS_WEB_URL')
);

//Registramos el cargador automático de Twig
require_once(dirname(__FILE__) . '/../vendor/autoload.php');
$loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/../views/');

//Twig load enviroment
$twig = new Twig_Environment($loader, array(//    'cache' => '/path/to/compilation_cache',
));

$class = filter_input(INPUT_GET, 'class', FILTER_SANITIZE_STRING, array("options" => array("default" => "login")));
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING, array("options" => array("default" => "")));
$action_2 = filter_input(INPUT_GET, 'action_2', FILTER_SANITIZE_STRING, array("options" => array("default" => "")));

    switch ($class){
        case 'registro':
            echo $twig->render('register.twig', array(
                'general' => $generalParam,
            ));
            break;
        case 'catalog':
            $lista_examenes = Examenes::getAllActive(); // Usar el método existente
            // Renderizar la plantilla del catálogo
            echo $twig->render('catalog.twig', [ // Nueva plantilla
                'general'        => $generalParam,
                'lista_examenes' => $lista_examenes
            ]);
            break;
        default:
            echo $twig->render('login.twig', array(
                'general' => $generalParam,
                'name' => 'Holita Mundo!'
            ));
            break;
    }



