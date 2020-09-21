<?php
require_once("braintree.init.php");
require_once '../vendor/Braintree.php';
require_once '../includes/DbOperations.php';


$nonce = $_POST['nonce'];
$amount = $_POST['amount'];
$userID = $_POST['userID'];
$fun = new DbOperations;
$UserData = array();
$UserData = $fun->validate($userID);
$result = Braintree\Transaction::sale([
    'amount' => $amount,
    'paymentMethodNonce' => $nonce,
    'options' => [
        'submitForSettlement' => True
    ]
]);
if($fun->updateUserBalance($UserData['UserID'], $amount) == 0 ){
    echo $result;
}else{
    echo "Update Balance failed, please Contact the cashier";
}