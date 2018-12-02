<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['email'] = "ntelalis@gmail.com";
//$_POST['verCode'] = "306539";
//$_POST['pass'] = "asdF12!@";

if (isset($_POST['email'],$_POST['verCode'],$_POST['pass']) && !empty($_POST['email']) && !empty($_POST['verCode']) && !empty($_POST['pass'])) {

    $email = $_POST['email'];
    $verCode = $_POST['verCode'];
    $pass = $_POST['pass'];

    //Check if email matches a record in database and return customerID,Verification Code and VerificationTime
    $query = "  SELECT CustomerID, VerificationCode, ResetTime
                FROM   Account
                WHERE  Email =? ";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($customerID, $verCodeDB, $resetTime);
    $stmt->store_result();
    $stmt->fetch();

    $numrows = $stmt->num_rows;

    //Close Statement
    $stmt->close();

    //Customer is found
    if($numrows == 1){

        //If code given matches the one in DB
        if ($verCode==$verCodeDB) {

            //Current Time
            $now = time();
            //Verification Time in DB
            $timeDB = strtotime($resetTime);
            //How many seconds passed since password reset request
            $diff = $now - $timeDB;

            //Check if threshold hasn't passed
            if ($diff<3600) {

                //Encrypt the password
                $hash = password_hash($pass, PASSWORD_DEFAULT);

                //Set the encrypted passsword and remove verification code for the account in database
                $query = "  UPDATE Account
                            SET    Hash = ?, VerificationCode = NULL
                            WHERE  CustomerID = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('si', $hash, $customerID);
                $success = $stmt->execute();

                //Close Connections
                $stmt->close();
                $mysqli->close();

                //Success
                if ($success) {
                    $jObj->success=1;
                }
                //Can't update password
                else {
                    $jObj->success=0;
                    $jObj->errorMessage="Cannot update password";
                }
            }
            //Fail. Time has passed
            else{
                $jObj->success=0;
                $jObj->errorMessage="Verification code is no longer valid. Please repeat the procedure";
            }
        }
        //Fail. Verification code doesn't match
        else{
            $jObj->success=0;
            $jObj->errorMessage="Verification code doesn't match";
        }
    }
    //Customer not found
    else{
        $jObj->success=0;
        $jObj->errorMessage="Customer not found";
    }
}
//Bad request
else{
    $jObj->success = 0;
    $jObj->errorMessage = "Bad request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
