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
$path_views = realpath(dirname(__FILE__) . '/../../views/administrador/');
$loader = new Twig_Loader_Filesystem($path_views);

// Ajusta la ruta a tu directorio de caché de Twig (debe tener permisos de escritura)
$path_cache_twig = dirname(__FILE__) . '/../../temp/cache/administrador/';
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

// --- Enrutador Principal (Switch basado en la 'clase') ---
// Este switch determina qué sección administrativa se está manejando.
// Dentro de cada case, se manejan las acciones específicas (list, create, edit...).
    switch ($class) {

        // --- Dashboard del Administrador ---
        case 'dashboard':
            // Podrías cargar aquí datos resumen (ej: nº usuarios, nº citas hoy)
            echo $twig->render('dashboard.twig', [
                'general' => $generalParam,
                // 'stats' => $estadisticas // Ejemplo
            ]);
            break;

        // --- Gestión de Usuarios ---
        case 'usuarios':
            // TODO: Crear método Roles::getAll() que devuelva un array [id_rol => nombre_rol]
             $lista_roles = Roles::getAll(); // Necesario para create y edit

            if ($action === 'create') {
                // Mostrar formulario para crear usuario
                echo $twig->render('usuario_form.twig', [
                    'general' => $generalParam,
                    'form_action' => 'create', // Indica a la plantilla que es modo creación
                    'usuario' => null, // No hay datos de usuario existente
                     'roles_disponibles' => $lista_roles
                ]);
            }
            else if ($action === 'edit' && $id !== null) {
                // Mostrar formulario para editar usuario
                $usuario_a_editar = Usuarios::getById($id);
                if (!$usuario_a_editar) {
                    throw new Exception("Usuario con ID {$id} no encontrado.");
                }
                echo $twig->render('usuario_form.twig', [
                    'general' => $generalParam,
                    'form_action' => 'edit', // Indica a la plantilla que es modo edición
                    'usuario' => $usuario_a_editar, // Pasar datos del usuario a la vista
                     'roles_disponibles' => $lista_roles
                ]);
            }
            else { // Acción por defecto: 'list'
                // Mostrar lista de usuarios
                // TODO: Crear método Usuarios::getAll($page, $itemsPerPage, $search) para listar/paginar/buscar
                $itemsPerPage = 20; // O configurable
                 $lista_usuarios = Usuarios::getAll($page, $itemsPerPage, $search);
                 $total_usuarios = Usuarios::getAll(null, null, $search, 'count'); // Para calcular paginación
                 $paginacion = ['total' => $total_usuarios, 'perPage' => $itemsPerPage, 'currentPage' => $page];

                echo $twig->render('usuario_list.twig', [
                    'general' => $generalParam,
                     'usuarios' => $lista_usuarios,
                     'paginacion' => $paginacion
                ]);
            }
            break; // Fin case 'usuarios'

        // --- Gestión de Cotizaciones ---
        case 'cotizaciones':
            if ($action === 'view' && $id !== null) {
                // Ver detalle de una cotización
                $cotizacion_detalle = Cotizaciones::getByIdWithDetails($id);
                if (!$cotizacion_detalle) {
                    throw new Exception("Cotización con ID {$id} no encontrada.");
                }
                echo $twig->render('cotizacion_view.twig', [
                    'general' => $generalParam,
                    'cotizacion' => $cotizacion_detalle
                ]);
            }
            else if ($action === 'create') {
                // Mostrar formulario para crear cotización (Admin puede crear para un usuario)
                // TODO: Crear Usuarios::getAll() o uno que liste solo pacientes
                 $lista_pacientes = Usuarios::getAll(1,999,null,false,['id_rol' => 1, 'activo' => true]); // Ejemplo conceptual
                 $lista_examenes = Examenes::getAllActive(); // Ya existe
                echo $twig->render('cotizacion_form.twig', [
                    'general' => $generalParam,
                    'form_action' => 'create',
                    'cotizacion' => null,
                     'lista_pacientes' => $lista_pacientes,
                     'lista_examenes_activos' => $lista_examenes
                ]);
            }
            else if ($action === 'edit' && $id !== null) {
                // --- Mostrar formulario para Editar Cotización (Limitado a Estado, Vigencia, Notas) ---

                // 1. Validar ID (ya filtrado como número, verificar > 0)
                if ($id <= 0) {
                    // Puedes manejar el error como prefieras: excepción, mensaje, redirección
                    throw new InvalidArgumentException("ID de cotización inválido proporcionado.");
                }

                // 2. Obtener datos completos de la cotización (incluyendo detalles para mostrar)
                $cotizacion_a_editar = Cotizaciones::getByIdWithDetails($id);

                // 3. Verificar si la cotización existe
                if (!$cotizacion_a_editar) {
                    // Lanzar excepción para que la maneje el bloque try/catch general
                    throw new RuntimeException("Cotización con ID {$id} no encontrada.");
                    // O si prefieres redirigir:
                    // echo "Error: Cotización no encontrada"; // O un mensaje flash
                    // exit;
                }

                // 4. Renderizar la plantilla del formulario en modo edición
                // Pasamos los datos de la cotización existente
                echo $twig->render('cotizacion_form.twig', [
                    'general'        => $generalParam,        // Parámetros generales
                    'form_action'    => 'edit',              // Indica a la plantilla que es modo edición
                    'cotizacion'     => $cotizacion_a_editar // Los datos de la cotización a editar
                    // No es necesario pasar lista_pacientes ni lista_examenes aquí
                ]);
            }
            else { // Acción por defecto: 'list'
                // Mostrar lista de cotizaciones
                // TODO: Crear Cotizaciones::getAll($page, $itemsPerPage, $search, $filters)
                $itemsPerPage = 20;
                 $lista_cotizaciones = Cotizaciones::getAll($page, $itemsPerPage, $search);
                 $total_cotizaciones = Cotizaciones::getAll(null, null, $search, null, 'count');
                 $paginacion = ['total' => $total_cotizaciones, 'perPage' => $itemsPerPage, 'currentPage' => $page];

                echo $twig->render('cotizacion_list.twig', [
                    'general' => $generalParam,
                     'cotizaciones' => $lista_cotizaciones,
                     'paginacion' => $paginacion
                ]);
            }
            break; // Fin case 'cotizaciones'

        // --- Gestión de Exámenes ---
        case 'examenes':
            if ($action === 'create') {
                echo $twig->render('examen_form.twig', [
                    'general' => $generalParam,
                    'form_action' => 'create',
                    'examen' => null
                ]);
            }
            else if ($action === 'edit' && $id !== null) {
                $examen_a_editar = Examenes::getById($id);
                if (!$examen_a_editar) {
                    throw new Exception("Exámen con ID {$id} no encontrado.");
                }
                echo $twig->render('examen_form.twig', [
                    'general' => $generalParam,
                    'form_action' => 'edit',
                    'examen' => $examen_a_editar
                ]);
            }
            else { // Acción por defecto: 'list'
                // Usar el método que ya existe
                $lista_examenes = Examenes::getAll(); // Trae activos e inactivos
                echo $twig->render('examen_list.twig', [
                    'general' => $generalParam,
                    'examenes' => $lista_examenes
                ]);
            }
            break; // Fin case 'examenes'


        // --- Gestión de Citas (Ejemplo básico) ---
// --- Gestión de Citas ---
    case 'citas':
        // Obtener los estados posibles una vez para usarlos donde se necesiten
        // Asumiendo que Citas::ESTADOS_CITA es una constante pública o tienes una función que los devuelve
        $estados_posibles = defined('Citas::ESTADOS_CITA') ? Citas::ESTADOS_CITA : ['Programada', 'Confirmada', 'Realizada', 'Cancelada_Paciente', 'Cancelada_Auxiliar', 'No_Asistio']; // O define el array aquí directamente

        // Determinar la acción específica dentro de la gestión de citas
        if ($action === 'create') {
            // --- Acción: Mostrar Formulario de Creación ---

            // Necesitamos la lista de pacientes activos y exámenes activos para los selects
            // TODO: Asegúrate que Usuarios::getAll soporte el filtro por id_rol y activo
            $lista_pacientes = Usuarios::getAll(1, 99999, null, false, ['id_rol' => 1, 'activo' => true]); // Asumiendo Rol Paciente ID = 1
            $lista_examenes = Examenes::getAllActive();

            echo $twig->render('cita_form.twig', [ // Renderizar el formulario
                'general'                => $generalParam,
                'form_action'            => 'create',         // Indicar modo creación
                'cita'                   => null,             // No hay datos de cita existente
                'lista_pacientes'        => $lista_pacientes, // Para el select de paciente
                'lista_examenes_activos' => $lista_examenes,  // Para el select de examen
                'estados_posibles'       => $estados_posibles // Para el select de estado
            ]);

        } else if ($action === 'edit' && $id !== null) {
            // --- Acción: Mostrar Formulario de Edición ---

            if ($id <= 0) { // Validar ID
                throw new InvalidArgumentException("ID de cita inválido.");
            }

            // Obtener los datos de la cita a editar (incluye datos de paciente/examen)
            $cita_a_editar = Citas::getById($id);
            if (!$cita_a_editar) { // Verificar si se encontró
                throw new RuntimeException("Cita con ID {$id} no encontrada.");
            }
            $lista_pacientes = Usuarios::getAll(1, 99999, null, false, ['id_rol' => 1, 'activo' => true]); // Asumiendo Rol Paciente ID = 1
            // Renderizar el mismo formulario, pero en modo edición y con datos
            echo $twig->render('cita_form.twig', [
                'general'                => $generalParam,
                'form_action'            => 'edit',           // Indicar modo edición
                'cita'                   => $cita_a_editar,   // Pasar los datos de la cita
                'lista_pacientes'        => [$cita_a_editar], // Pasar solo el paciente actual si el select está deshabilitado
                'lista_examenes_activos' => [$cita_a_editar], // Pasar solo el examen actual si el select está deshabilitado
                'estados_posibles'       => $estados_posibles // Para el select de estado
                // Nota: Si permitieras cambiar paciente/examen en editar, necesitarías pasar las listas completas aquí también.
            ]);

        } else if ($action === 'view' && $id !== null) {
            // --- Acción: Mostrar Vista de Detalle (Opcional) ---
            // Útil si quieres una página dedicada al detalle además del modal

            if ($id <= 0) { throw new InvalidArgumentException("ID de cita inválido."); }
            $cita_detalle = Citas::getById($id);
            if (!$cita_detalle) { throw new RuntimeException("Cita con ID {$id} no encontrada."); }

            // Renderizar una plantilla específica para la vista de detalle
            echo $twig->render('cita_view.twig', [ // Necesitarás crear 'cita_view.twig'
                'general' => $generalParam,
                'cita' => $cita_detalle,
                'estados_posibles' => $estados_posibles // Por si hay acciones en esta vista
            ]);

        } else if ($action === 'list_view') {
            // --- Acción: Mostrar Vista de Lista Tabular ---
            // Esta es la lógica que tenías en tu 'else' original

            // Obtener fechas y filtros de GET
            $fecha_desde = filter_input(INPUT_GET, 'fecha_desde', FILTER_SANITIZE_STRING) ?: date('Y-m-d', strtotime('-7 days')); // Default últimos 7 días
            $fecha_hasta = filter_input(INPUT_GET, 'fecha_hasta', FILTER_SANITIZE_STRING) ?: date('Y-m-d'); // Default hasta hoy

            // Validar formato de fechas (usando tu validador)
            if (!ValidateData::validateDate($fecha_desde) || !ValidateData::validateDate($fecha_hasta)) {
                // Manejar error de formato de fecha, quizás usar defaults seguros
                error_log("Formato de fecha inválido en filtros de lista de citas.");
                $fecha_desde = date('Y-m-d', strtotime('-7 days'));
                $fecha_hasta = date('Y-m-d');
                // Podrías añadir un mensaje flash para el usuario
            }

            // Recoger otros filtros
            $filtros_activos = [];
            $estado_filtro = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING);
            if ($estado_filtro && in_array($estado_filtro, $estados_posibles)) {
                $filtros_activos['estado'] = $estado_filtro;
            }
            // Añadir aquí filtros por paciente, examen, etc. si los implementas

            // Llamar al método del modelo para obtener las citas filtradas
            // Asegúrate que getByDateRange maneje bien los filtros
            $lista_citas = Citas::getByDateRange($fecha_desde, $fecha_hasta, $filtros_activos);
            // Renderizar la plantilla de la lista tabular
            echo $twig->render('cita_list.twig', [ // Necesitarás crear 'cita_list.twig'
                'general'            => $generalParam,
                'citas'              => $lista_citas,
                'fecha_desde_filtro' => $fecha_desde,      // Para pre-rellenar filtros
                'fecha_hasta_filtro' => $fecha_hasta,
                'estado_filtro'      => $estado_filtro,     // Para pre-rellenar filtros
                'estados_posibles'   => $estados_posibles  // Para el dropdown de filtro
                // Añadir datos de paginación si la implementas
            ]);

        } // ... otros 'else if' para create, edit, view, list_view ...

        else {
            // --- Acción por Defecto (o action=calendar): Mostrar Calendario ---
            // Esta es la vista principal para la gestión de citas.

            // 1. Preparar datos necesarios para la plantilla del calendario:
            //    - Ya tenemos $estados_posibles definido al inicio del 'case'.

            // 2. (Opcional) Obtener datos para filtros avanzados (Select2) si los usas:
            //    Si en tu plantilla 'cita_calendar.twig' usas selects con búsqueda
            //    para filtrar por paciente o examen, necesitas cargar esas listas aquí.
            //    Si usas inputs de texto simples para filtrar (como en el ejemplo JS/Twig),
            //    NO necesitas cargar estas listas completas aquí, ya que el filtrado
            //    se haría en el backend al llamar a Citas::getByDateRange.

            /* --- Descomenta y ajusta si usas Select2 para filtros en el calendario ---
            // Obtener lista de pacientes activos (ej: para un <select> con búsqueda)
             // TODO: Ajusta el ID de rol si es diferente para Paciente
            $lista_pacientes_para_filtro = Usuarios::getAll(1, 99999, null, false, ['id_rol' => 1, 'activo' => true]);

            // Obtener lista de exámenes activos (ej: para un <select> con búsqueda)
            */
            $lista_examenes_para_filtro = Examenes::getAllActive();
            // 3. Renderizar la plantilla del calendario
            //    Pasamos los parámetros generales y los estados posibles para el dropdown de filtro.
            //    Si descomentaste las listas anteriores, pásalas también.
            echo $twig->render('cita_calendar.twig', [ // Asegúrate que el path sea correcto
                'general'                   => $generalParam,          // Datos generales (URLs, sesión, etc.)
                'estados_posibles'          => $estados_posibles,        // Para el dropdown de filtro de estado
                'lista_examenes_filtro'     => $lista_examenes_para_filtro

                /* --- Descomenta si pasas las listas para filtros Select2 ---
                , // Añade coma arriba
                'lista_pacientes_filtro'    => $lista_pacientes_para_filtro,
                */

            ]);
        } // Fin del else (acción por defecto - calendario)

        break; // Fin case 'citas'


        // --- Salir ---
        case 'salir':
            // Destruir la sesión
            Security::sessionClose(); // Usar método de tu clase Security

            // Redirigir al login (URL base)
            $ruta_redireccion = constant('FULL_WEB_URL');
            header("Location: $ruta_redireccion");
            exit; // Detener ejecución
            break;

        // --- Default ---
        default:
            // Si la 'clase' no coincide con ninguna sección válida, mostrar dashboard o error 404
            error_log("Clase de controlador de Admin no válida: " . $class);
            // Redirigir al dashboard por seguridad
            header("Location: ?class=dashboard");
            exit;
            // O mostrar una plantilla de error 404
            // http_response_code(404);
            // echo $twig->render('404.twig', ['general' => $generalParam]);
            break;
    } // Fin switch($class)


