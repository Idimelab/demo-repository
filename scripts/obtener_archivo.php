<?php
//$file = "/var/www/html/ordertaken-app/app/assets/prueba.sln";


require_once(dirname(__DIR__).'/configImport.php');

$name_file =filter_input(INPUT_GET, "name_file", FILTER_SANITIZE_STRING);
$directory =filter_input(INPUT_GET, "directory", FILTER_SANITIZE_STRING);
$id_company =filter_input(INPUT_GET, "id_company", FILTER_SANITIZE_STRING);
$type =filter_input(INPUT_GET, "type", FILTER_SANITIZE_STRING);

if ($type==NULL){
    $type="image";
}
$id_user = Security::GetSessionUserId();

if ($id_company==NULL)
    $id_company = Company::getCompanyIdByCompanyUserId($id_user);


if ($id_company==NULL)
    $id_company=0;

$directorio_parametro=constant('FILES_COMPANY') . $id_company . '/';
$url_archivo=$directorio_parametro . $directory . '/' . $name_file;


$file_extension = ValidateData::getExtensionFile($name_file);

//    esta linea de codigo es la que arregla el error sin esta linea de ocdigo no funciona la imagen
if ($type === 'download'){

    ob_clean();
//-----------------------------------
    $archivo = fopen($url_archivo, "r");

    $content = fread($archivo,filesize($url_archivo));
    header("Content-Disposition: attachment; filename=" . urlencode($name_file));
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Description: File Transfer");

    header('Content-length: ' . filesize($url_archivo));

    print($content);
    exit();
}else if ($type == 'logo' || $type == 'image'){

    $url_archivo = constant('FILES_COMPANY') . $id_company . '/' . $directory . '/' . $name_file;
}
ob_clean();

$archivo = fopen($url_archivo, "r");
$content = fread($archivo,filesize($url_archivo));

header('Content-Description: File Transfer');
header("Content-Transfer-Encoding: binary");
header('Content-Type: image/jpeg');
header('Content-length: ' . filesize($url_archivo));

print($content);
exit();

