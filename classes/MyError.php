<?php

class MyError{

    public function display($message , $action , $code){

        $error = array("error" => $message , "action" => $action , "code" => $code);
        echo json_encode($error);
        exit;
    }

    public static  $ERROR_MYPDO_SQL        = 1 ;
    public static  $ERROR_NO_ROUTE         = 2 ;
    public static  $ERROR_INVALID_DATA     = 3 ;
    public static  $ERROR_DUPLICATE_EMAIL  = 4 ;
    public static  $ERROR_WRONG_LOGIN_DATA = 5 ;
    public static  $NOT_ENOUGH_DATA        = 6 ;
    public static  $FILE_NOT_ALLOWED       = 7 ;
    public static  $FILE_NOT_UPLOADED      = 8 ;
}