<?php
require_once 'DbOperations.php';
class DbUserLogin
{

    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function userLogin($UserID, $Password)
    {
        $fun = new DbOperations;
        if ($fun->isUserExist("User",$UserID)) {
            $hashed_password = $fun->getUsersPasswordByID("User",$UserID);
            if (password_verify($Password, $hashed_password)) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASSWORD_DO_NOT_MATCH;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }
    // private function getUsersPasswordByID($UserID)
    // {
    //     $stmt = $this->con->prepare("SELECT Password FROM users WHERE UserID = ?");
    //     $stmt->bind_param("s", $UserID);
    //     $stmt->execute();
    //     $stmt->bind_result($password);
    //     $stmt->fetch();
    //     return $password;
    // }
    public function getUserByID($UserID)
    {
        $fun = new DbOperations;
        $stmt = $this->con->prepare("SELECT UserID, Name,Email, Balance FROM users WHERE UserID = ?");
        $stmt->bind_param("s", $UserID);
        $stmt->execute();
        $stmt->bind_result($UserID, $Name,$Email, $Balance);
        $stmt->fetch();
        $user = array();
        $user['UserID'] = $UserID;
        $user['Name'] = $Name;
        $user['Email'] = $Email;
        $user['Balance'] = "$Balance";
        $user['Token']= $fun->createToken("User", $UserID);
        $user['NFCData']= $fun->encrypt($UserID) ;
        return $user;
    }
}
