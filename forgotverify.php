<?php

require 'dbConfig.php';

//Connection to Database
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if (isset($_POST['verification'], $_POST['email']) && !empty($_POST['verification']) && !empty($_POST['email'])) {
    $email = $_POST['email'];
    $verification = $_POST['verification'];

    //Check if email matches a record in database and return customerID,Verification Code and VerificationTime
    $query = "SELECT CustomerID,VerificationCode,ResetTime FROM Account WHERE Email=?";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($customerID, $verificationDB, $resetTime);
    $stmt->store_result();
    $stmt->fetch();
    $numrows = $stmt->num_rows;

    //Current Time
    $now = time();
    //Verification Time
    $timeDB = strtotime($resetTime);
    //Seconds Passed
    $diff = $now - $timeDB;

    //Check if threshold hasn't passed
    if ($diff<3600) {
        if ($verification==$verificationDB) {

      //Success
            $jObj->success=1;
        } else {

      //Fail: Verification Doesn't match
            $jObj->success=0;
            $jObj->errorMessage="Verification code doesn't match";
        }
    } else {
        //Fail: Time has passed
        $jObj->success=0;
        $jObj->errorMessage="Verification code is no longer valid. Please repeat the procedure";
    }


    //Close Connections
    $stmt->close();
    $mysqli->close();
} else {
    //Variables not set
    $jObj->success=0;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
