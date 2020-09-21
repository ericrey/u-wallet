<?php


use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


// require '../vendor/autoload.php';

require_once '../includes/DbOperations.php';

require '../includes/DbCreateUser.php';

require '../includes/DbUserLogin.php';

require '../includes/DbCreateSeller.php';

require '../includes/DbGetHistory.php';

require '../includes/DbSellerLogin.php';

require '../includes/DbPayment.php';

require '../includes/DbTransfer.php';

require '../includes/DbRefund.php';

require_once '../includes/braintree.init.php';

require_once '../vendor/Braintree.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

// $app->add(new Tuupola\Middleware\HttpBasicAuthentication([
//     "secure" => false,
//     "users" => [
//         "ericrey" => "123456",
//     ],
//     "error" => function ($response, $arguments) {
//         $data = array();
//         $data["error"] = true;
//         $data["message"] = $arguments["message"];

//         // $body = $response->getBody();
//         // $body->write(json_encode($data, JSON_UNESCAPED_SLASHES));


//         $response->write(json_encode($data));

//         return $response
//             ->withHeader('Content-type', 'application/json')
//             ->withStatus(422);
//     }
// ]));

/* 
    endpoint: createuser
    parameters: email, password, name, school
    method: POST
*/
$app->post('/createuser', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('UserID', 'Name', 'Email', 'Password', 'Balance'), $request, $response)) {

        $request_data = $request->getParsedBody();

        $UserID = $request_data['UserID'];
        $Name = $request_data['Name'];
        $Email = $request_data['Email'];
        $Password = $request_data['Password'];
        $Balance = $request_data['Balance'];

        $hash_password = password_hash($Password, PASSWORD_DEFAULT);

        $db = new DbCreateUser;

        $result = $db->createUser($UserID, $Name, $Email, $hash_password, $Balance);

        if ($result == USER_CREATED) {

            $message = array();
            $message['error'] = false;
            $message['message'] = 'User created successfully';

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        } else if ($result == USER_FAILURE) {

            $message = array();
            $message['error'] = true;
            $message['message'] = 'Some error occurred';

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else if ($result == USER_EXISTS) {
            $message = array();
            $message['error'] = true;
            $message['message'] = 'User Already Exists';

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    }
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});
$app->post('/test', function(Request $request, Response $response){
echo 'test berhasil servernyaaa';
});

$app->post('/userlogin', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('UserID', 'Password'), $request, $response)) {
        $request_data = $request->getParsedBody();

        $UserID = $request_data['UserID'];
        $Password = $request_data['Password'];

        $db = new DbUserLogin;

        $result = $db->userLogin($UserID, $Password);

        if ($result == USER_AUTHENTICATED) {

            $user = $db->getUserByID($UserID);
            $response_data = array();

            $response_data['error'] = false;
            $response_data['message'] = 'Login Successful';
            $response_data['user'] = $user;

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == USER_NOT_FOUND) {
            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = 'User does not exist';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == USER_PASSWORD_DO_NOT_MATCH) {
            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = 'Password is incorrect';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});

$app->post('/transfer', function (Request $request, Response $response) {
    if (!haveEmptyParameters(array('jwt', 'recipientdata', 'amount'), $request, $response)) {
        $request_data = $request->getParsedBody();
        $jwt = $request_data['jwt'];
        $Recipientdata = $request_data['recipientdata'];
        $Amount = $request_data['amount'];

        $fun = new DbOperations;
        $db = new DbTransfer;
        $fun->validate($jwt);
        $UserData = array();
        $UserData = $fun->validate($jwt);
        if ($UserData['Type'] == "User") {
            $RecipientID = $fun->decrypt($Recipientdata);
            $Payment = $db->Transfer($UserData['UserID'], $Amount, $RecipientID);
            if ($Payment == TRANSFER_SUCCESSFUL) {
                $response_data = array();

                $response_data['error'] = false;
                $response_data['message'] = 'Transfer Success';
                $response->write(json_encode($response_data));

                return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
            }
            if ($Payment == BALANCE_NOTSUFFICIENT) {
                $response_data = array();

                $response_data['error'] = true;
                $response_data['message'] = 'Balance is not sufficient';
                $response->write(json_encode($response_data));

                return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
            }
            if ($Payment == TRANSFER_ERROR) {
                $response_data = array();

                $response_data['error'] = true;
                $response_data['message'] = 'Payment Error';
                $response->write(json_encode($response_data));

                return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
            }
        } else {
            $response_data = array();

            $response_data['error'] = true;
            $response_data['response'] = "Token is not valid, Please try to Relogin";


            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        }
    }
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});
$app->get('/history', function (Request $request, Response $response) {
    $request_params = $request->getQueryParams();
    // echo "isinyaaaa"+$request_params;
    $jwt = $request_params['jwt'];
    $validate = new DbOperations;
    $UserData = array();
    $UserData = $validate->validate($jwt);
    if ($UserData['Type'] == "User") {
        $db = new DbGetHistory;

        $history = $db->getUserHistory($UserData['UserID']);

        $response_data = array();
        $response_data['error'] = false;
        $response_data['History'] = $history;

        $response->write(json_encode($response_data));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
    } else if ($UserData['Type'] == "Seller") {
        $db = new DbGetHistory;
        $history = $db->getSellerHistory($UserData['SellerID']);

        $response_data = array();

        $response_data['error'] = false;
        $response_data['History'] = $history;

        $response->write(json_encode($response_data));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
    } else {
        $response_data = array();

        $response_data['error'] = true;
        $response_data['response'] = "Token is not valid, Please try to Relogin";


        $response->write(json_encode($response_data));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});
$app->get('/userrefresh', function (Request $request, Response $response) {
    $request_params = $request->getQueryParams();
    // echo "isinyaaaa"+$request_params;
    $jwt = $request_params['jwt'];
    $validate = new DbOperations;
    $UserData = array();
    $UserData = $validate->validate($jwt);
    if ($UserData['Type'] == "User") {
        $db = new DbUserLogin;

        $history = $db->getUserByID($UserData['UserID']);

        $response_data = array();
        $response_data['error'] = false;
        $response_data['user'] = $history;

        $response->write(json_encode($response_data));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
    } else if ($UserData['Type'] == "Seller") {
        $db = new DbSellerLogin;
        $history = $db->getSellerByEmail($UserData['SellerID']);

        $response_data = array();

        $response_data['error'] = false;
        $response_data['user'] = $history;

        $response->write(json_encode($response_data));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
    } else {
        $response_data = array();

        $response_data['error'] = true;
        $response_data['response'] = "Token is not valid, Please try to Relogin";


        $response->write(json_encode($response_data));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});
$app->get('/topup', function () {
    return Braintree\ClientToken::generate();
});
////// SellerPART
$app->post('/sellerregister', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('SellerName', 'SellerEmail', 'SellerPassword'), $request, $response)) {

        $request_data = $request->getParsedBody();

        $Name = $request_data['SellerName'];
        $Email = $request_data['SellerEmail'];
        $Password = $request_data['SellerPassword'];

        $hash_password = password_hash($Password, PASSWORD_DEFAULT);

        $db = new DbCreateSeller;
        $login = new DbSellerLogin;

        $result = $db->createseller($Name, $Email, $hash_password);

        if ($result == USER_CREATED) {
            $user = $login->getSellerByEmail($Email);
            $message = array();
            $message['error'] = false;
            $message['message'] = 'Seller created successfully';
            $message['user'] = $user;

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        } else if ($result == USER_FAILURE) {

            $message = array();
            $message['error'] = true;
            $message['message'] = 'Some error occurred';

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == USER_EXISTS) {
            $message = array();
            $message['error'] = true;
            $message['message'] = 'Seller Already Exists';

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == EMAIL_EXISTS) {
            $message = array();
            $message['error'] = true;
            $message['message'] = 'Email Already Exists';

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        }
    }
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});
$app->post('/sellerlogin', function (Request $request, Response $response) {

    if (!haveEmptyParameters(array('SellerEmail', 'Password'), $request, $response)) {
        $request_data = $request->getParsedBody();

        $SellerEmail = $request_data['SellerEmail'];
        $Password = $request_data['Password'];
        $db = new DbSellerLogin;

        $result = $db->SellerLogin($SellerEmail, $Password);

        if ($result == USER_AUTHENTICATED) {

            $user = $db->getSellerByEmail($SellerEmail);
            $response_data = array();

            $response_data['error'] = false;
            $response_data['message'] = 'Login Successful';
            $response_data['user'] = $user;

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == USER_NOT_FOUND) {
            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = 'Account does not exist, Please Register your account';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == USER_PASSWORD_DO_NOT_MATCH) {
            $response_data = array();

            $response_data['error'] = true;
            $response_data['message'] = 'Password is incorrect';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});
$app->post('/payment', function (Request $request, Response $response) {
    if (!haveEmptyParameters(array('jwt', 'userData', 'amount', 'paymentType'), $request, $response)) {
        $request_data = $request->getParsedBody();

        $jwt = $request_data['jwt'];
        $buyerdata = $request_data['userData'];
        $amount = $request_data['amount'];
        $type = $request_data['paymentType'];

        $fun = new DbOperations;
        $db = new DbPayment;
        $fun->validate($jwt);
        $sellerData = array();
        $sellerData = $fun->validate($jwt);
        if ($sellerData['Type'] == "Seller") {
            $UserID = $fun->decrypt($buyerdata);
            $Payment = $db->Payment($sellerData['SellerID'], $amount, $UserID, $type);
            if ($Payment == PAYMENT_SUCCESSFUL) {
                $response_data = array();

                $response_data['error'] = false;
                $response_data['message'] = 'Transaction Success';
                $response->write(json_encode($response_data));

                return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
            }
            if ($Payment == BALANCE_NOTSUFFICIENT) {
                $response_data = array();

                $response_data['error'] = true;
                $response_data['message'] = 'Balance is not sufficient';
                $response->write(json_encode($response_data));

                return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
            }
            if ($Payment == PAYMENT_ERROR) {
                $response_data = array();

                $response_data['error'] = true;
                $response_data['message'] = 'Transaction Error';
                $response->write(json_encode($response_data));

                return $response
                    ->withHeader('Content-type', 'application/json')
                    ->withStatus(200);
            }
        } else {
            $response_data = array();

            $response_data['error'] = true;
            $response_data['response'] = "Token is not valid, Please try to Relogin";


            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);
});
// $app->post('/refund', function (Request $request, Response $response) {
//     if (!haveEmptyParameters(array('jwt', 'userdata', 'amount'), $request, $response)) {
//         $request_data = $request->getParsedBody();

//         $jwt = $request_data['jwt'];
//         $buyerdata = $request_data['userdata'];
//         $Amount = $request_data['amount'];

//         $fun = new DbOperations;
//         $db = new DbRefund;
//         $fun->validate($jwt);
//         $SellerData = array();
//         $SellerData = $fun->validate($jwt);
//         if ($SellerData['Type'] == "Seller") {
//             $UserID = $fun->decrypt($buyerdata);
//             $Payment = $db->Refund($SellerData['SellerID'], $Amount, $UserID);
//             if ($Payment == PAYMENT_SUCCESSFUL) {
//                 $response_data = array();

//                 $response_data['error'] = false;
//                 $response_data['message'] = 'Payment Success';
//                 $response->write(json_encode($response_data));

//                 return $response
//                     ->withHeader('Content-type', 'application/json')
//                     ->withStatus(200);
//             }
//             if ($Payment == BALANCE_NOTSUFFICIENT) {
//                 $response_data = array();

//                 $response_data['error'] = true;
//                 $response_data['message'] = 'Balance is not sufficient';
//                 $response->write(json_encode($response_data));

//                 return $response
//                     ->withHeader('Content-type', 'application/json')
//                     ->withStatus(200);
//             }
//             if ($Payment == PAYMENT_ERROR) {
//                 $response_data = array();

//                 $response_data['error'] = true;
//                 $response_data['message'] = 'Payment Error';
//                 $response->write(json_encode($response_data));

//                 return $response
//                     ->withHeader('Content-type', 'application/json')
//                     ->withStatus(200);
//             }
//         } else {
//             $response_data = array();

//             $response_data['error'] = true;
//             $response_data['response'] = "Token is not valid, Please try to Relogin";


//             $response->write(json_encode($response_data));

//             return $response
//                 ->withHeader('Content-type', 'application/json')
//                 ->withStatus(200);
//         }
//     }

//     return $response
//         ->withHeader('Content-type', 'application/json')
//         ->withStatus(422);
// });

function haveEmptyParameters($required_params, $request, $response)
{
    $error = false;
    $error_params = '';
    $request_params = $request->getParsedBody();

    foreach ($required_params as $param) {
        if (!isset($request_params[$param]) || strlen($request_params[$param]) <= 0) {
            $error = true;
            $error_params .= $param . ', ';
        }
    }

    if ($error) {
        $error_detail = array();
        $error_detail['error'] = true;
        $error_detail['message'] = 'Required parameters ' . substr($error_params, 0, -2) . ' are missing or empty';
        $response->write(json_encode($error_detail));
    }
    return $error;
}



$app->run();
