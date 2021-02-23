<?php
class profile{



    public function __construct($system = false)
    {
        if ($system)return;
        $action = app::get(ACTION);

        switch ($action){
            case ACTION_GET:{
                $id = app::get(USER_ID);
                $this->getProfileImage($id);
                break ;
            }
            case ACTION_SET:{
                $this->setProfileImage();
                break ;
            }
        }

    }

    public function getProfileImage($id){

        $conn  = MyPDO::getInstance();
        $query = "SELECT * FROM user_profile WHERE user_id = :user_id" ;
        $stmt  = $conn->prepare($query);
        $stmt->bindParam(":user_id" , $id , PDO::PARAM_INT);

        try {
            $stmt->execute();
            while ($res = $stmt->fetch(PDO::FETCH_ASSOC)){
                return $res ;
            }
        }catch (PDOException $ex){
            $error = new MyError();
            $error->display("System error!" , "" , MyError::$ERROR_MYPDO_SQL);
        }

    }
    public function setProfileImage(){

        $error = new MyError();

        if (!isset($_REQUEST[SESSION]) ||
            !isset($_REQUEST[USER_ID]) ||
            !isset($_FILES[INPUT_IMAGE])
        ){
            $error->display("Not enough data" , "" , MyError::$NOT_ENOUGH_DATA);
            exit;
        }


        $fileName = basename($_FILES[INPUT_IMAGE]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($fileName , PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES[INPUT_IMAGE]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $error->display("File is not an image." , "" , MyError::$FILE_NOT_ALLOWED);
            $uploadOk = 0;
        }

        // Check if file already exists
        if (file_exists($fileName)) {
            $error->display("Sorry, file already exists." , "" , MyError::$FILE_NOT_ALLOWED);
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES[INPUT_IMAGE]["size"] > 500000) {
            $error->display("Sorry, your file is too large." , "" , MyError::$FILE_NOT_ALLOWED);
            $uploadOk = 0;
        }


        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png"){
            $error->display("The file type not allowed!" , "" , MyError::$FILE_NOT_ALLOWED);
            $uploadOk = 0;
            exit;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $error->display("Sorry, your file was not uploaded." , "" , MyError::$FILE_NOT_UPLOADED);
       // if everything is ok, try to upload file
        } else {


            $hash = UPLOAD_DIRECTORY . app::generateRandomString(20) . "." .$imageFileType ;

            if (move_uploaded_file($_FILES[INPUT_IMAGE]["tmp_name"] , $hash)){

                $response['state'] = SUCCESS;
                echo json_encode($response) ;
            }


            else {
                $error->display("Sorry, there was an error uploading your file." , "" , MyError::$FILE_NOT_UPLOADED);
            }
        }

    }


    public function registerImage($user_id , $hash){

        $conn  = MyPDO::getInstance();
        $query = "INSERT INTO user_profile (user_id , image) VALUES (:user_id , :image)" ;
        $stmt  = $conn->prepare($query);

        $stmt->bindParam(":user_id" , $user_id);
        $stmt->bindParam(":image"   , $hash);

        try {
            $stmt->execute();
        }catch (PDOException $ex){

            $error = new MyError();
            $error->display("System error!" , "" , MyError::$ERROR_MYPDO_SQL);
        }


    }

}