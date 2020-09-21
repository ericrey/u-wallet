<?php

class DbPayment
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }
//transaction function
    public function Payment($sellerID, $amount, $userID, $type)
    {
        if ($amount > $this->getUserbalance($sellerID, $userID, $type)) {
            return BALANCE_NOTSUFFICIENT;
        } else {
            if (
                $this->UpdateUserBalance($amount, $userID, $type) &&
                $this->UpdateSellerBalance($amount, $sellerID, $type) &&
                $this->UpdatePaymentHistory($userID, $sellerID, $amount, $type)
            ) {
                return PAYMENT_SUCCESSFUL;
            } else {
                return PAYMENT_ERROR;
            }
        }
    }
    public function GetUserBalance($sellerID, $userID, $type)
    {
        if ($type == "Pay") {
            $stmt = $this->con->prepare("Select Balance from Users where UserID = ?");
            $stmt->bind_param("s", $userID);
        } else if ($type == "Refund") {
            $stmt = $this->con->prepare("Select SellerBalance from sellers where SellerID = ?");
            $stmt->bind_param("s", $sellerID);
        }
        $stmt->execute();
        $stmt->bind_result($Balance);
        $stmt->fetch();
        return $Balance;
    }
    public function UpdateUserBalance($amount, $userID, $type)
    {
        if ($type == "Pay") {
            $stmt = $this->con->prepare("Update users SET balance = balance - ? where UserID = ?");
        } else if ($type == "Refund") {
            $stmt = $this->con->prepare("Update users SET balance = balance + ? where UserID = ?");
        }
        $stmt->bind_param("ss", $amount, $userID);
        return $stmt->execute();
    }
    public function UpdateSellerBalance($amount, $sellerID, $type)
    {
        if ($type == "Pay") {
            $stmt = $this->con->prepare("Update sellers SET SellerBalance = SellerBalance + ? where SellerID = ?");
        } else if ($type == "Refund") {
            $stmt = $this->con->prepare("Update sellers SET SellerBalance = SellerBalance - ? where SellerID = ?");
        }
        $stmt->bind_param("ss", $amount, $sellerID);
        return $stmt->execute();
    }
    public function UpdatePaymentHistory($userID, $sellerID, $amount, $type)
    {
        $datetime = new DateTime("now", new DateTimeZone('+0800'));
        $date = $datetime->format('Y-m-d H:i:s');
        // echo $date;
        // $date = date("Y-m-d");
        $stmt = $this->con->prepare("INSERT INTO paymenthistory (UserID, SellerID, DatePurchase, Amount, PaymentActivity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $userID, $sellerID, $date, $amount, $type);
        return $stmt->execute();
    }
}
