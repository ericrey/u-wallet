<?php

class DbGetHistory
{

    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }

    public function getUserHistory($UserID)
    {
        $stmt = $this->con->prepare("Select paymenthistory.DatePurchase as Date, sellers.SellerName,  paymenthistory.Amount, paymenthistory.PaymentActivity AS Type
        From paymenthistory, sellers
        Where UserID = ? and sellers.SellerID = paymenthistory.SellerID
        UNION ALL
        Select transferhistory.DateTransfer as Date, users.Name,  transferhistory.Amount, 'Transfer Out' AS Type
        From transferhistory, users
        Where transferhistory.UserID = ? AND users.UserID = transferhistory.RecipientID
        UNION ALL Select transferhistory.DateTransfer as Date, users.Name,  transferhistory.Amount, 'Transfer In' AS Type
        From transferhistory, users
        Where transferhistory.RecipientID = ? AND users.UserID = transferhistory.RecipientID 
        ORDER BY `Date`  DESC;");
        $stmt->bind_param("sss", $UserID, $UserID, $UserID);
        $stmt->execute();
        $stmt->bind_result($Date, $SellerName, $Amount, $Type);
        $History = array();
        $Purchase = array();
        while ($stmt->fetch()) {
            $Purchase['SellerName'] = $SellerName;
            $Purchase['DatePurchase'] = $Date;
            $Purchase['Amount'] = $Amount;
            $Purchase['Type'] = $Type;
            array_push($History, $Purchase);
        }
        if ($History == null) {
            return null;
        }
        return $History;
    }
    public function getSellerHistory($SellerID)
    {
        $stmt = $this->con->prepare("Select UserID, DatePurchase, Amount, PaymentActivity 
            FROM paymenthistory
            where SellerID = ?
            ORDER BY DatePurchase DESC");
        $stmt->bind_param("s", $SellerID);
        $stmt->execute();
        $stmt->bind_result($UserID, $Date, $Amount, $Type);
        $History = array();
        $Transaction = array();
        while ($stmt->fetch()) {
            $Transaction['UserID'] = $UserID;
            $Transaction['Date'] = $Date;
            $Transaction['Amount'] = $Amount;
            $Transaction['Type'] = $Type;
            array_push($History, $Transaction);
        }
        return $History;
    }
}
