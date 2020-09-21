<?php

session_start();
require_once ("../vendor/autoload.php");
// if(file_exists(__DIR__."/../.env"))
// {
//     $dotenv = new Dotenv\Dotenv(__DIR__."/../");
//     $dotenv -> load();
// }

Braintree\Configuration::environment('sandbox');
Braintree\Configuration::merchantId('d665dfxkpfc28mqx');
Braintree\Configuration::publicKey('79km9d8cjkkxvk7r');
Braintree\Configuration::privateKey('702d6797c9561968e9618457baa96eec');
?>