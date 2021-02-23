<?php
class login{

    public function __construct($system = false)
    {

        if ($system)
            return;

        $action = app::get(ACTION);
        switch ($action){
            case ACTION_LOGIN:{
                $email    = app::get(INPUT_EMAIL);
                $password = app::get(INPUT_PASSWORD);
                $this->login($email , $password);
                break;
            }
            case ACTION_REGISTRATION:{
                $email    = app::get(INPUT_EMAIL);
                $username = app::get(INPUT_USERNAME);
                $password = app::get(INPUT_PASSWORD);
                $name    = app::get(INPUT_NAME);
                $sex      = app::get(INPUT_SEX);
                $this->registration($email , $username , $password , $name , $sex);
                break;
            }
        }

    }

    public function login($email , $password , $system = false){
        $conn = MyPDO::getInstance();
        $query = "SELECT users.id , users.username , users.email , users.name , users.sex , 
                  user_profile.id as profileID, user_profile.image 
                  FROM `users`
                  LEFT JOIN user_profile ON users.id = user_profile.user_id
                  WHERE users.email = :email AND users.password = SHA1(CONCAT(users.longer_pass , :password))" ;

        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email"    , $email);
        $stmt->bindParam(":password" , $password);

        try {
            $stmt->execute();
            while ($res = $stmt->fetch(PDO::FETCH_ASSOC)){


                $session = md5(sha1(microtime()));
                $query   = "UPDATE users SET session = '$session' WHERE id = " . $res['id'] ;
                $stmt    = $conn->prepare($query);
                $stmt->execute();

                $response['state'] = SUCCESS ;
                $users = array(
                    USER_ID        => $res['id']    ,
                    INPUT_EMAIL    => $res['email'] ,
                    INPUT_USERNAME => $res['username'] ,
                    INPUT_NAME     => $res['name'] ,
                    INPUT_SEX      => $res['sex']   ,
                    SESSION        => $session
                );


                $profile = array();
                $profile['id']            = $res['profileID'] ;
                $profile['image']         = $res['image'] ;


                $response['UserObject']    = $users ;
                $response['ProfileObject'] = $profile ;
                echo json_encode($response);
                exit;

            }

            $error = new MyError();
            $error->display("Wrong Login Data", "", MyError::$ERROR_WRONG_LOGIN_DATA);

        }catch (PDOException $ex){
            echo $ex->getMessage();
            $error = new MyError();
            $error->display("Server Error " , "" , MyError::$ERROR_MYPDO_SQL);
        }


    }


    public function registration($email , $username , $password , $name , $sex , $system = false){

        $error = new MyError();
        if ($email == -1 || $password == -1 || !filter_var($email , FILTER_VALIDATE_EMAIL)){
            $error->display("Invalid Data" , "" , MyError::$ERROR_INVALID_DATA);
        }

        $longer_pass = sha1(microtime()) . md5(microtime()) ;
        $session     = sha1(microtime()) . md5(microtime()) ;

        $conn = MyPDO::getInstance();

        $query = "INSERT INTO users (username , email , name , password , longer_pass , session , sex )"
                ."VALUES (:username , :email , :name , SHA1(CONCAT(:longer_pass , :password)) , :longer_pass , :session , :sex )";

        $stmt = $conn->prepare($query);

        $stmt->bindParam(":username"    , $username);
        $stmt->bindParam(":email"       , $email);
        $stmt->bindParam(":name"        , $name);
        $stmt->bindParam(":password"    , $password);
        $stmt->bindParam(":longer_pass" , $longer_pass);
        $stmt->bindParam(":session"     , $session);
        $stmt->bindParam(":sex"         , $sex , PDO::PARAM_INT);

        try {

            $stmt->execute();
            $id = MyPDO::getLastID($conn);

            $response = array();
            $response['session'] = $session ;
            $response['id'] = $id ;
            $response['state'] = SUCCESS ;
            echo json_encode($response);


        }catch (PDOException $ex){

            if ($ex->getCode() == 23000){
                $error->display("This email is already in use" , "" , MyError::$ERROR_DUPLICATE_EMAIL);

            }
            $error->display("System Error.", "", MyError::$ERROR_MYPDO_SQL);

            echo $ex->getMessage();
        }


    }


    public function checkLogin($id, $session) {


        $errorManager = new MyError();
        $conn = MyPDO::getInstance();
        $query = "SELECT id FROM users WHERE id = :id AND session = :session";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id" , $id , PDO::PARAM_INT);
        $stmt->bindParam(":session" , $session);


        try {

            $stmt->execute();
            if (MyPDO::getRowCount($stmt) == 1)  return true;

        } catch (PDOException $ex) {

            $errorManager->display("Server Erroe", "" , MyError::$ERROR_MYPDO_SQL);

        }


        $errorManager->display("Wrong Login Data.", "LogOut" , MyError::$ERROR_WRONG_LOGIN_DATA);
    }

}