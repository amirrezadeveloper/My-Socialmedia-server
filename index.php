<?php
include 'Config.php';
include 'ROUTER.php';
include 'app.php';
include 'MyPDO.php';

include 'classes/Login.php';
include 'classes/MyError.php';
include 'classes/profile.php';
include 'classes/posts.php';

$ROUTE   = app::get(ROUTE);
$SESSION = app::get(SESSION);
$ID      = app::get(USER_ID);

switch ($ROUTE){
    case ROUTE_LOGIN:{
        $login = new login();
        break;
    }
    case ROUTE_PROFILE:{
        $profile = new profile();
        break;
    }
    case ROUTE_POSTS:{

        $posts = new posts() ;
        break ;
    }

        $error = new MyError();
        $error->display("There Is No Valid Route", "", MyError::$ERROR_NO_ROUTE);
}