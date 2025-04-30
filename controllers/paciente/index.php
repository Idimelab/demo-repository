<?php
/**
 * @Description: Documento que procesa los controladores y las acciones para renderizar las vistas con 'Twig' para el rol 'operador'
 * @User: luis.chamorro
 * @Date: 18/11/19
 */

//Headers para evitar el caché del navegador
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 1 Jul 2000 05:00:00 GMT"); // Fecha en el pasado

require_once(dirname(__FILE__) . '/../../security/secure-session.php');



//Se cargan las variables de sesion necesarias
//Se inicializa el manejo de sesiones
if (session_status() == PHP_SESSION_NONE)
    session_start();

//Se obtiene el id de la session
$user_id = $_SESSION['userId'];
$id_paciente_session=$user_id;
//Se obtiene el id de la empresa
$token_session = Security::GetSessionToken();
$user_role = $_SESSION['userRole'];
//Se obtiene el id de la empresa
//rae los datos con el fin de obtener el codigo dle idioma de este usuario y así traducir la plataforma
//$data_user=CompanyUser::getAll($user_id);
//$idioma_user=$data_user[0]['codigo_idioma'];


//Idioma de la base de datos segun el usuario
//$lang = $idioma_user;
$lang = 'es_CO';

//Dominio => Va relacionado con el PoEdit
$text_domain = 'message';

//Connfiguracón del idioma (es_CO - en_US)
putenv('LC_ALL=' . $lang);
setlocale(LC_ALL, $lang);


// --- Obtener Datos de Sesión Seguros ---

// $token_session = Security::GetSessionToken(); // Si usas tokens CSRF en sesión

// --- Configuración de Idioma (Opcional, si usas gettext) ---
$lang = 'es_CO';
$text_domain = 'messages'; // Dominio para gettext
putenv('LC_ALL=' . $lang);
setlocale(LC_ALL, $lang);
bindtextdomain($text_domain, dirname(__FILE__) . '/../../locale'); // Ajusta ruta
textdomain($text_domain);

// --- Configuración de Twig ---
require_once(dirname(__FILE__) . '/../../vendor/autoload.php'); // Carga el autoload de Composer

// Ajusta la ruta a tu directorio de vistas para el admin
$path_views = realpath(dirname(__FILE__) . '/../../views/paciente/');
$loader = new Twig_Loader_Filesystem($path_views);

// Ajusta la ruta a tu directorio de caché de Twig (debe tener permisos de escritura)
$path_cache_twig = dirname(__FILE__) . '/../../temp/cache/paciente/';
if (!is_dir($path_cache_twig)) {
    mkdir($path_cache_twig, 0775, true); // Intentar crear directorio si no existe
}

//Twig load enviroment
$twig = new Twig_Environment($loader, array(
    'cache' => $path_cache_twig, // O 'false' para deshabilitar caché durante desarrollo
    'auto_reload' => true,      // Recarga plantillas si cambian (útil en desarrollo)
    'debug' => false             // Habilitar 'true' para usar {{ dump() }} en Twig
));


$twig->addExtension(new Twig_Extensions_Extension_I18n());
// configure Twig the way you want

// iterate over all your templates
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path_views), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
    // force compilation
    if ($file->isFile()) {
        $twig->loadTemplate(str_replace($path_views, '', $file));
    }
}

$class = filter_input(INPUT_GET, 'class', FILTER_SANITIZE_STRING, ["options" => ["default" => "dashboard"]]);
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING, ["options" => ["default" => "list"]]); // Default a 'list'
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT, ["options" => ["default" => null]]);
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT, ["options" => ["default" => 1]]); // Para paginación
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING); // Para búsquedas
// --- Parámetros Generales para Twig ---
// Estos se pasarán a TODAS las plantillas
$generalParam = array(
    "full_web_url" => constant('FULL_WEB_URL'),           // URL base del sitio
    "full_assets_url" => constant('ASSETS_WEB_URL'),      // URL base para assets (CSS, JS, img)
    "id_session" => $user_id,                             // ID del usuario en sesión
    "user_role_session" => $user_role,                    // Rol del usuario en sesión
    "current_class" => $class,                            // Clase/Módulo actual
    "current_action" => $action,                          // Acción actual
    "current_id" => $id,                                  // ID actual (si aplica)
    "current_page" => $page,                              // Página actual (paginación)
    "current_search" => $search,                          // Término de búsqueda actual
    "sidebar_template" => "sidebar.twig",    // Plantilla del menú lateral para admin
    "topbar_template" => "topbar.twig",      // Plantilla del menú superior para admin
    "footer_template" => "footer.twig",            // Plantilla del pie de página
    // Añade aquí cualquier otra variable global que necesites en las vistas
);
// --- Lógica del Controlador ---
try {
    switch ($class) {
        case 'citas':
            // Obtener estados posibles (necesario en varias acciones)
            $estados_posibles = defined('Citas::ESTADOS_CITA') ? Citas::ESTADOS_CITA : ['Programada', 'Confirmada', 'Realizada', 'Cancelada_Paciente', 'Cancelada_Auxiliar', 'No_Asistio'];

            if ($action === 'create') {
                // --- Mostrar Formulario para Agendar Nueva Cita ---
                $lista_examenes = Examenes::getAllActive(); // Obtener exámenes disponibles
                echo $twig->render('cita_form.twig', [
                    'general'                => $generalParam,
                    'form_action'            => 'create',
                    'cita'                   => null, // No hay datos de cita previa
                    'lista_examenes_activos' => $lista_examenes,
                    'id_paciente_actual'     => $id_paciente_session, // Pasar ID del paciente actual
                    'estados_posibles'       => ['Programada'] // Solo puede crear como programada
                ]);

            }
            else if ($action === 'edit' && $id !== null) {
                // --- Mostrar Formulario para Reagendar Cita ---
                if ($id <= 0) throw new InvalidArgumentException("ID de cita inválido.");

                $cita_a_editar = Citas::getById($id);

                // *** VERIFICACIÓN DE PROPIEDAD Y REGLA 24H ***
                if (!$cita_a_editar) throw new RuntimeException("Cita no encontrada.");
                if ($cita_a_editar['id_paciente'] != $id_paciente_session) {
                    // Intento de editar cita ajena
                    throw new Exception("No tienes permiso para modificar esta cita.");
                }
                // Comprobar si aún se puede modificar (más de 24h)
                $puede_modificar = Citas::isChangeAllowed($cita_a_editar['fecha_cita'], $cita_a_editar['hora_cita']);
                // Comprobar si el estado permite reagendar ('Programada' o 'Confirmada')
                $estado_permite_modificar = in_array($cita_a_editar['estado'], ['Programada', 'Confirmada']);


                // Renderizar formulario (puede estar deshabilitado si no se permite)
                echo $twig->render('cita_form.twig', [
                    'general'                => $generalParam,
                    'form_action'            => 'edit',
                    'cita'                   => $cita_a_editar,
                    'lista_examenes_activos' => [$cita_a_editar], // Mostrar solo examen actual (deshabilitado)
                    'id_paciente_actual'     => $id_paciente_session,
                    'estados_posibles'       => [$cita_a_editar['estado']], // Mostrar estado actual (deshabilitado)
                    'puede_modificar'        => ($puede_modificar && $estado_permite_modificar) // Pasar flag a la vista
                ]);

            }
            else { // Acción por defecto: 'list'
                // --- Mostrar Lista de "Mis Citas" ---
                $filtro_estado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING);
                $filtros_lista = [];
                if ($filtro_estado && in_array($filtro_estado, $estados_posibles)) {
                    $filtros_lista['estado'] = $filtro_estado;
                }

                // Obtener solo las citas del paciente logueado
                $lista_citas = Citas::getByPaciente($id_paciente_session, $filtros_lista);

                // Añadir flag 'puede_modificar' a cada cita para la vista
                $ahora = new DateTime();
                foreach ($lista_citas as &$cita) { // Pasar por referencia para modificar
                    $estado_permite = in_array($cita['estado'], ['Programada', 'Confirmada']);
                    $cita['puede_modificar'] = $estado_permite && Citas::isChangeAllowed($cita['fecha_cita'], $cita['hora_cita']);
                }
                unset($cita); // Romper referencia

                echo $twig->render('cita_list.twig', [
                    'general'         => $generalParam,
                    'citas'           => $lista_citas,
                    'estados_posibles'=> $estados_posibles, // Para el filtro
                    'estado_filtro'   => $filtro_estado
                ]);
            }
            break; // Fin case 'citas'

        case 'perfil':
            // TODO: Implementar vista/edición de perfil del paciente
            echo "Página de Perfil del Paciente (pendiente)";
            break;

        case 'salir':
            Security::sessionClose();
            header("Location: " . constant('FULL_WEB_URL'));
            exit;
            break;

        default:
            // Redirigir a la vista principal del paciente (mis citas)
            header("Location: ?class=citas");
            exit;
    }

} catch (Exception $e) {
    // --- Manejo de Errores ---
    error_log("Error en Controlador Paciente: " . $e->getMessage());
    // Mostrar página de error genérica para paciente
    echo $twig->render('error_paciente.twig', [ // Necesitas crear esta plantilla
        'general' => $generalParam,
        'error_message' => 'Ocurrió un error inesperado. Por favor, inténtalo más tarde.'
    ]);
    exit;
}
