<?php
/**
 * Created by PhpStorm.
 * User: mesad
 * Date: 17/05/2019
 * Time: 1:53 PM
 */
require_once(dirname(__FILE__) . '/../configImport.php');

class Debug
{
    static function register($message, $type = 'message', $archivo, $line)
    {
        if (constant("DEBUG") == true) {
            if ($type == 'error') {
                $start = '::Error:: -> ';
            } elseif ($type == 'message') {
                $start = '::Message:: -> ';
            }

            //Si es un mensaje de error, se le agrega la linea
            if ($type == 'error') {
                $messageFinal = $start . ' ' . $message . " ($archivo Linea: $line)";
            } else {
                $messageFinal = $start . ' ' . $message;
            }

            trigger_error($messageFinal);
        }
    }
}