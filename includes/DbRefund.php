<?php

class DbRefund
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function Refund($SellerID, $Amount, $UserID)
    {
        if ($Amount > $this->getSellerbalance($SellerID)) {
            return BALANCE_NOTSUFFICIENT;
        } else {
            if (
                $this->UpdateUserBalance($Amount, $UserID) &&
                $this->UpdateSellerBalance($Amount, $SellerID) &&
                $this->UpdatePaymentHistory($UserID, $SellerID, $Amount, "Refund")
            ) {
                return PAYMENT_SUCCESSFUL;
            } else {
                return PAYMENT_ERROR;
            }
            // return $this->UpdateUserBalance($NewBalance, $UserID);
            //belom record history
        }
    }
    public function GetSellerBalance($SellerID)
    {
        $stmt = $this->con->prepare("Select SellerBalance from sellers where SellerID = ?");
        $stmt->bind_param("s", $SellerID);
        $stmt->execute();
        $stmt->bind_result($Balance);
        $stmt->fetch();
        return $Balance;
    }
    public function UpdateUserBalance($Amount, $UserID)
    {
        $plus = '+';
        $stmt = $this->con->prepare("Update users SET balance = balance ? ? where UserID = ?");
        $stmt->bind_param("sss",$plus, $Amount, $UserID);
        return $stmt->execute();
    }
    public function UpdateSellerBalance($Amount, $SellerID)
    {
        $minus = '-';
        $stmt = $this->con->prepare("Update sellers SET SellerBalance = SellerBalance ? ? where SellerID = ?");
        $stmt->bind_param("sss",$minus, $Amount, $SellerID);
        return $stmt->execute();
    }
    public function UpdatePaymentHistory($UserID, $SellerID, $Amount, $TransactionType)
    {
        $date = date("Y-m-d");
        $stmt = $this->con->prepare("INSERT INTO paymenthistory (UserID, SellerID, DatePurchase, Amount, PaymentActivity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $UserID, $SellerID, $date, $Amount, $TransactionType);
        return $stmt->execute();
    }
}
