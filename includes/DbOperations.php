<?php
require_once '../bootstrap.php';

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class DbOperations
{

    private $con;

    function __construct()
    {
        require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect;
        $this->con = $db->connect();
    }
    public function isUserExist($UserType, $UserID)
    {
        if ($UserType == "User") {
            $stmt = $this->con->prepare("SELECT UserID FROM users WHERE UserID = ?");
            $stmt->bind_param("s", $UserID);
        }
        if ($UserType == "Seller") {
            $stmt = $this->con->prepare("SELECT SellerName FROM sellers WHERE SellerName = ?");
            $stmt->bind_param("s", $UserID);
        }

        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
    public function updateUserBalance($userID, $amount)
    {
        
        $stmt = $this->con->prepare("UPDATE users SET Balance= Balance + ? WHERE UserID = ?");
        $stmt->bind_param("ss",$amount, $userID);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function getUsersPasswordByID($UserType, $UserID)
    {
        if ($UserType == "User") {
            $stmt = $this->con->prepare("SELECT Password FROM users WHERE UserID = ?");
            $stmt->bind_param("s", $UserID);
        } elseif ($UserType == "Seller") {
            $stmt = $this->con->prepare("SELECT SellerPassword FROM Sellers WHERE SellerEmail = ?");
            $stmt->bind_param("s", $UserID);
        }

        $stmt->execute();
        $stmt->bind_result($password);
        $stmt->fetch();
        return $password;
    }
    public function getUserByID($UserType, $UserID)
    {
        $jwt = new DbOperations;
        if ($UserType == "User") {
            $stmt = $this->con->prepare("SELECT UserID, Name, Balance FROM users WHERE UserID = ?");
            $stmt->bind_param("s", $UserID);
            $stmt->execute();
            $stmt->bind_result($UserID, $Name, $Balance);
            $stmt->fetch();
            $user = array();
            $user['UserID'] = $UserID;
            $user['Name'] = $Name;
            $user['Balance'] = "$Balance";
            $user['Token'] = $jwt->createToken("User", $UserID);
        }
        return $user;
    }
    //JWT
    public function createToken($UserType, $UserID)
    {
        $secret = $_ENV['SECRET'];

        // Create the token header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        // Create the token payload
        $payload = json_encode([
            'UserType' => $UserType,
            'UserID' => $UserID
        ]);

        // Encode Header
        $base64UrlHeader = base64UrlEncode($header);

        // Encode Payload
        $base64UrlPayload = base64UrlEncode($payload);

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = base64UrlEncode($signature);

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
        // echo "Your token:\n" . $jwt;
    }
    public function validate($jwt)
    {

        // get the local secret key
        $secret = $_ENV['SECRET'];
        // split the token
        $tokenParts = explode('.', $jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        // build a signature based on the header and payload using the secret
        $base64UrlHeader = base64UrlEncode($header);
        $base64UrlPayload = base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = base64UrlEncode($signature);

        // verify it matches the signature provided in the token
        $signatureValid = ($base64UrlSignature === $signatureProvided);

        // echo "Header:\n" . $header . "\n";
        // echo "Payload:\n" . $payload . "\n";
        $UserType = json_decode($payload)->UserType;
        $id = json_decode($payload)->UserID;

        // if ($tokenExpired) {
        //     echo "Token has expired.\n";
        // } else {
        //     echo "Token has not expired yet.\n";
        // }

        if ($signatureValid) {
            if ($UserType == "User") {
                $response_data = array();
                $response_data['Type'] = "User";
                $response_data['UserID'] = $id;
                return $response_data;
            }
            if ($UserType == "Seller") {
                $response_data = array();
                $response_data['Type'] = "Seller";
                $response_data['SellerID'] = $id;
                return $response_data;
            }
            // return $id;
            // echo "The signature is valid.\n";
        } else {
            return null;
            // echo "The signature is NOT valid\n";
        }
    }
    //NFC data Encryption
    public function encrypt($UserID)
    {
        $secret = $_ENV['KEY'];
        $key = Key::loadFromAsciiSafeString($secret);
        return bin2hex(Crypto::encrypt($UserID, $key));
    }
    public function decrypt($UserData)
    {
        $secret = $_ENV['KEY'];
        $key = Key::loadFromAsciiSafeString($secret);
        $data = hex2bin($UserData);
        return Crypto::decrypt($data, $key);
    }
}
