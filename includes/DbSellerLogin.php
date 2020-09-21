<?php
require_once 'DbOperations.php';
require_once 'DbCreateSeller.php';
// require 'GenerateJwt.php';
class DbSellerLogin
{

    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function SellerLogin($SellerEmail, $Password)
    {
        $fun = new DbCreateSeller;
        $pass = new DbOperations;
        if ($fun->isEmailExist( $SellerEmail)) {
            $hashed_password = $pass->getUsersPasswordByID("Seller", $SellerEmail);
            if (password_verify($Password, $hashed_password)) {
                return USER_AUTHENTICATED;
            } else {
                return USER_PASSWORD_DO_NOT_MATCH;
            }
        } else {
            return USER_NOT_FOUND;
        }
    }
    // private function getSellerPasswordByName($SellerName)
    // {
    //     $stmt = $this->con->prepare("SELECT SellerPassword FROM Sellers WHERE SellerName = ?");
    //     $stmt->bind_param("s", $SellerName);
    //     $stmt->execute();
    //     $stmt->bind_result($password);
    //     $stmt->fetch();
    //     return $password;
    // }
    public function getSellerByEmail($SellerEmail)
    {
        $jwt = new DbOperations;
        $stmt = $this->con->prepare("SELECT SellerID, SellerName,SellerEmail, SellerBalance FROM Sellers WHERE SellerEmail = ? or SellerID = ?");
        $stmt->bind_param("ss", $SellerEmail, $SellerEmail);
        $stmt->execute();
        $stmt->bind_result($SellerID, $SellerName,$SellerEmail, $Balance);
        $stmt->fetch();
        $user = array();
        $user['SellerID'] = $SellerID;
        $user['SellerName'] = $SellerName;
        $user['SellerEmail'] = $SellerEmail;
        $user['Balance'] = $Balance;
        $user['Token'] = $jwt->createToken("Seller", $SellerID);
        return $user;
    }
}
