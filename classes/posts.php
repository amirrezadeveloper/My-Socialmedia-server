<?php
class posts{

    public function __construct($system = false)
    {

        if ($system) return ;

        $action = app::get(ACTION) ;
        $userID = app::get(USER_ID) ;

        switch ($action){

            case ACTION_ADD:{
                $caption = app::get(INPUT_CAPTION);
                $this->addPosts($userID , $caption) ;
                break ;
            }
            case ACTION_READ:{
                $start = app::get(INPUT_START) ;
                $this->readPosts($userID , $start) ;
                break ;
            }
            case ACTION_DELETE:{
                $postID = app::get(INPUT_POST_ID);
                $this->deletePost($userID , $postID) ;
                break ;
            }
            case ACTION_EDIT:{
                $postID = app::get(INPUT_POST_ID);
                $caption = app::get(INPUT_CAPTION) ;
                $this->editPost($userID , $postID , $caption) ;
                break ;
            }

            case ACTION_LIKE:{
                $postID = app::get(INPUT_POST_ID);
                $this->like($userID , $postID) ;
                break ;

            }
            case ACTION_UNLIKE:{

                $postID = app::get(INPUT_POST_ID);
                $this->unlike($userID , $postID) ;
                break ;

            }
            case ACTION_COMMENT:{

                $postID = app::get(INPUT_POST_ID);
                $comment = app::get(INPUT_COMMENT);
                $this->comment($postID , $comment) ;
            }


        }
    }

    private function addPosts($userID , $caption)
    {

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
        if ($_FILES[INPUT_IMAGE]["size"] > 5000000) {
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
                $this->registerImage($userID, $hash, $caption);
                echo json_encode($response) ;
            }


            else {
                $error->display("Sorry, there was an error uploading your file." , "" , MyError::$FILE_NOT_UPLOADED);
            }
        }

    }

    private function registerImage($userID, $hash, $caption){



        $conn = MyPDO::getInstance() ;
        $query = "INSERT INTO posts (user_id , image , caption) VALUES (:user_id , :image ,:caption)" ;
        $stmt = $conn->prepare($query) ;
        $stmt->bindParam(":user_id" , $userID , PDO::PARAM_INT) ;
        $stmt->bindParam(":image"   , $hash) ;
        $stmt->bindParam(":caption" , $caption) ;

        try {
            $stmt->execute() ;
            $id = MyPDO::getLastID($conn) ;
            $response = array("status" => SUCCESSFUL_UPLOAD , "id" => $id) ;
            echo json_encode($response) ;
        }catch (PDOException $ex){

            $error = new MyError();
            $error->display("Server Error " , "" , MyError::$ERROR_MYPDO_SQL);
        }

    }




    private function readPosts($userID , $start)
    {
        $conn = MyPDO::getInstance() ;
        $query = "SELECT posts.id , posts.user_id , posts.image , posts.caption , posts.date , likes.post_id FROM posts left join likes ON posts.id = likes.post_id WHERE posts.user_id = :user_id ORDER BY date DESC LIMIT :start , 20" ;
        //SELECT posts.id , posts.user_id , posts.image , posts.caption , posts.date , likes.post_id FROM `posts` LEFT JOIN likes ON posts.user_id = likes.user_id
        //SELECT * FROM posts left join likes ON posts.id = likes.post_id WHERE posts.user_id = :user_id ORDER BY date DESC LIMIT :start , 20
        $stmt = $conn->prepare($query) ;
        $stmt->bindParam(":user_id" , $userID , PDO::PARAM_INT) ;
        $stmt->bindParam(":start" , $start , PDO::PARAM_INT) ;
        try {

            $stmt->execute() ;
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)) ;
        }catch (PDOException $ex){

            echo $ex->getMessage();
            $error = new MyError();
            $error->display("Server Error" , "" , MyError::$ERROR_MYPDO_SQL);

             }

    }

    private function deletePost($userID , $postID)
    {
        $conn  = MyPDO::getInstance() ;
        $query = "DELETE FROM posts WHERE user_id = :user_id AND id = :post_id" ;
        $stmt  = $conn->prepare($query) ;
        $stmt->bindParam(":user_id" , $userID) ;
        $stmt->bindParam(":post_id" , $postID) ;

        try {
            $stmt->execute() ;
            echo SUCCESS ;
        }catch (PDOException $ex){
            $error = new MyError();
            $error->display("Server Error " , "" , MyError::$ERROR_MYPDO_SQL);

        }
    }

    private function editPost($userID ,$postID , $caption)
    {
        $conn = MyPDO::getInstance() ;
        $query = "UPDATE posts SET caption = :caption WHERE user_id = :user_id AND id = :post_id" ;
        $stmt = $conn->prepare($query) ;
        $stmt->bindParam(":caption" , $caption) ;
        $stmt->bindParam(":user_id" , $userID , PDO::PARAM_INT) ;
        $stmt->bindParam(":post_id" , $postID , PDO::PARAM_INT) ;

        try {
            $stmt->execute() ;
            echo SUCCESS ;
        }catch (PDOException $ex){

            $error = new MyError() ;
            $error->display("Server Error " , "" , MyError::$ERROR_MYPDO_SQL);
        }
    }

    private function like($userID , $postID){

        $conn = MyPDO::getInstance();
        $query = "INSERT INTO `likes` (user_id , post_id) VALUES (:user_id , :post_id)" ;
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id" , $userID) ;
        $stmt->bindParam(":post_id" , $postID) ;

        try {
            $stmt->execute();
            echo json_encode(SUCCESS) ;
        }catch (PDOException $ex){
            $error = new MyError() ;
            $error->display("Server Error " , "" , MyError::$ERROR_MYPDO_SQL);
        }

    }
    private function unlike($userID , $postID){

        $conn = MyPDO::getInstance();
        $query= "DELETE FROM `likes` WHERE user_id = :user_id AND post_id = :post_id" ;
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id" , $userID) ;
        $stmt->bindParam(":post_id" , $postID) ;

        try {
            $stmt->execute();
            echo json_encode(SUCCESS . "unlike") ;
        }catch (PDOException $ex){
            $error = new MyError() ;
            $error->display("Server Error " , "" , MyError::$ERROR_MYPDO_SQL);
        }
    }
    private function comment($postID , $comment){

        $conn = MyPDO::getInstance() ;
        $query = "INSERT INTO `comments` " ;

    }
}