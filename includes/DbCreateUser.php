<?php
require_once "DbOperations.php";
class DbCreateUser{
    private $con; 

        function __construct(){
            require_once dirname(__FILE__) . '/DbConnect.php';
            $db = new DbConnect; 
            $this->con = $db->connect(); 
        }

        public function createUser($UserID, $Name, $Email, $Password, $Balance){
           $fun = new DbOperations;
            if(!$fun->isUserExist("User",$UserID)){
                $stmt = $this->con->prepare("INSERT INTO users (UserID, Name, Email, Password, Balance) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $UserID, $Name,$Email, $Password, $Balance);
                if($stmt->execute()){
                    return USER_CREATED; 
                }else{
                    return USER_FAILURE;
                }
           }
           return USER_EXISTS; 
        }
}