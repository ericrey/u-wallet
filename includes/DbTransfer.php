<?php

class DbTransfer
{
    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function Transfer($UserID, $Amount, $RecipientID)
    {
        if ($Amount > $this->getUserbalance($UserID)) {
            return BALANCE_NOTSUFFICIENT;
        } else {
            if (
                $this->UpdateUserBalance($Amount, $UserID) &&
                $this->UpdateRecipientBalance($Amount, $RecipientID) &&
                $this->UpdateTransferHistory($UserID, $RecipientID, $Amount)
            ) {
                return TRANSFER_SUCCESSFUL;
            } else {
                return TRANSFER_ERROR;
            }
            // return $this->UpdateUserBalance($NewBalance, $UserID);
            //belom record history
        }
    }
    public function GetUserBalance($UserID)
    {
        $stmt = $this->con->prepare("Select Balance from Users where UserID = ?");
        $stmt->bind_param("s", $UserID);
        $stmt->execute();
        $stmt->bind_result($Balance);
        $stmt->fetch();
        return $Balance;
    }
    public function UpdateUserBalance($Amount, $UserID)
    {
        $stmt = $this->con->prepare("Update users SET balance = balance - ? where UserID = ?");
        $stmt->bind_param("ss", $Amount, $UserID);
        return $stmt->execute();
    }
    public function UpdateRecipientBalance($Amount, $RecipientID)
    {
        $stmt = $this->con->prepare("Update users SET Balance = Balance + ? where UserID = ?");
        $stmt->bind_param("ss", $Amount, $RecipientID);
        return $stmt->execute();
    }
    public function UpdateTransferHistory($UserID, $RecipientID, $Amount)
    {
        $date = date("Y-m-d");
        $stmt = $this->con->prepare("INSERT INTO transferhistory (UserID, RecipientID, DateTransfer, Amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $UserID, $RecipientID, $date, $Amount);
        return $stmt->execute();
    }
}
