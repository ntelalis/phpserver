<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'serverConfig.php';
require 'Functions/Email.php';
require 'Functions/RandomString.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['email'] = "ntelalis@gmail.com";
//$_POST['pass'] = "Qqwerty1!";
//$_POST['firstName'] = "George";
//$_POST['lastName'] = "Paschos";
//$_POST['titleID'] = 2;
//$_POST['countryID'] = 4;
//$_POST['birthDate'] = "1991-11-02";
//$_POST['phone'] = "6987453152";

if(isset($_POST['email'],$_POST['pass'],$_POST['firstName'],$_POST['lastName'],
$_POST['titleID'],$_POST['countryID'],$_POST['birthDate'],
$_POST['phone'])){

    $email = $_POST['email'];
    $pass = $_POST['pass'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $titleID = $_POST['titleID'];
    $countryID = $_POST['countryID'];
    $birthDate = $_POST['birthDate'];
    $phone = $_POST['phone'];

    //begin transaction
    $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    //insert customer related data
    $query = "INSERT INTO Customer(TitleID,FirstName,LastName,BirthDate,CountryId) VALUES(?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isssi', $titleID, $firstName, $lastName, $birthDate, $countryID);
    $success = $stmt->execute();

    if($success){
        //if success get the customer id
        $customerID = $mysqli->insert_id;

        //hash the password
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        //Generate random string to email for verifaction
        $verificationCode = random_str(12);

        //insert it into database along with email
        $query = "INSERT INTO Account(CustomerID,Email,Hash,VerificationCode,Verified) VALUES(?,?,?,?,0)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('isss', $customerID, $email, $hash, $verificationCode);
        $success = $stmt->execute();

        if($success){

            $mail = getEmailServer();

            //Mail Contents
            $mail->Subject = "Verify your account";
            $msg = "{$url}verify.php?verCode={$verificationCode}";
            $mail->Body = $msg;

            //Send To
            $mail->addAddress($email);

            //Success
            if ($mail->Send()) {
                $mysqli->commit();
                //if success return customer ID
                $jObj->success=1;
                $jObj->customerID=$customerID;
            }
            //email could not be send
            else {
                $mysqli->rollback();
                $jObj->success = 0;
                $jObj->errorMessage="Could not send email. Please try again later";
            }
        }
        else{
            //inserting into Account failed
            $mysqli->rollback();
            $jObj->success=0;
            if($stmt->errno==1062){
                $jObj->errorMessage="Email is already in use";
            }
            else{
                $jObj->errorMessage="An error has occured";
            }
        }
    }
    else{
        //inserting into Customer failed
        $mysqli->rollback();
        $jObj->success=0;
        $jObj->errorMessage=$mysqli->error;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
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
