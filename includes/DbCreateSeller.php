<?php
require_once 'DbOperations.php';
class DbCreateSeller
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function createseller($SellerName, $SellerEmail, $SellerPassword)
    {
        $fun = new DbOperations;

        if (!$fun->isUserExist("Seller", $SellerName)) {
            if ($this->isEmailExist($SellerEmail)) {
                return EMAIL_EXISTS;
            }
            $stmt = $this->con->prepare("INSERT INTO Sellers ( SellerName, SellerEmail, SellerPassword) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $SellerName, $SellerEmail, $SellerPassword);
            if ($stmt->execute()) {
                return USER_CREATED;
            } else {
                return USER_FAILURE;
            }
        } else {
            return USER_EXISTS;
        }
    }
    public function isEmailExist($SellerEmail)
    {
        $stmt = $this->con->prepare("SELECT SellerName FROM sellers WHERE SellerEmail = ?");
        $stmt->bind_param("s", $SellerEmail);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}
