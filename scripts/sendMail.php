<?php

require_once(dirname(__DIR__).'/security/secure-session.php');
require_once(dirname(__DIR__).'/configImport.php');


require dirname(__DIR__) .'/assets/libraries/mail/src/Exception.php';
require dirname(__DIR__) .'/assets/libraries/mail/src/PHPMailer.php';
require dirname(__DIR__) .'/assets/libraries/mail/src/SMTP.php';



$return=  Aws::sendMail(NULL,"notificaciones.ingeniabpm@gmail.com","FACTURA","hola");

exit();

?>
