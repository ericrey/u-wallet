<?php

require "../bootstrap.php";
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

require "../vendor/autoload.php";

// $key = Key::createNewRandomKey();

$secret = $_ENV['KEY'];
// $aa  =Key::saveToAsciiSafeString();
// $storeMe = $key->saveToAsciiSafeString();
// echo $secret;
$key = Key::loadFromAsciiSafeString($secret);
// $storeMe = bin2hex($key);
// echo $storeMe;
// $key = hex2bin($storeMe);
$message = "def50200ac25112265f4dc2ff5a722f5f205facfd472e76ef783b5b04b3ab63d3934ac6eac7730f08daa977a85be49ac5e14dad01b6b3b0fea40d51c235395493d54c297eb91a337f7dd525f18e1172546c3bfa629f9b1c4";

// $encrypt = Crypto::encrypt($message, $key);
$decrypt = Crypto::decrypt($message, $key);

// echo "<hr>Encrypted :" , $encrypt;
// echo "<hr>hexed: ", bin2hex($encrypt);
echo "<hr>\ndecrypted :" , $decrypt;
