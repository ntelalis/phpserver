<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Required imports
require 'Functions/Email.php';
require 'Functions/RandomString.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['email'] = "ntelalis@gmail.com";

if (isset($_POST['email']) && !empty($_POST['email'])) {

    $email = $_POST['email'];

    //Check if email matches a record in database and return customerID
    $query = "  SELECT CustomerID
                FROM   Account
                WHERE  Email = ? ";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($customerID);
    $stmt->store_result();
    $stmt->fetch();

    $numrows = $stmt->num_rows;

    //If Customer is found
    if ($numrows == 1) {

        //Generate random string to email for verifaction
        $code = random_str(6, '0123456789');

        //Insert Verification code for the account in database
        $query = "  UPDATE Account
                    SET    VerificationCode = ?, ResetTime = now()
                    WHERE  CustomerID = ? ";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('si', $code, $customerID);
        $stmt->execute();
        $success = $stmt->execute();

        //Close Connections
        $stmt->close();
        $mysqli->close();

        //if code inserted in db
        if($success){

            //get email server
            $mail = getEmailServer();

            //Set up mail contents
            $mail->Subject = "Password Reset";
            $mail->Body = "Verification number: ".$code;

            //Send mail to this address
            $mail->addAddress($email);

            //success
            if ($mail->Send()) {
                $jObj->success = 1;
            }
            //Could not send email
            else {
                $jObj->success = 0;
                $jObj->errorMessage="Could not send email. Please try again later";
            }
        }
        //Could not update database
        else{
            $jObj->success = 0;
            $jObj->errorMessage="Sorry an error has occured. Please try again later";
        }
    }
    //Customer is not found
    else {
        $jObj->success = 0;
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
