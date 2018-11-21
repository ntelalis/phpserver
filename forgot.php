<?php

require 'dbConfig.php';


require 'Functions/Email.php';
require 'Functions/RandomString.php';

//Connection to Database
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $email = $_POST['email'];

    //Check if email matches a record in database and return customerID
    $query = "SELECT CustomerID FROM Account WHERE Email=?";

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

      $query = "UPDATE Account SET VerificationCode=?,ResetTime=now() WHERE CustomerID=?";

      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('si', $code, $customerID);
      $stmt->execute();
      $success = $stmt->execute();

      $mail = getEmailServer();

        //Mail Contents
        $mail->Subject = "Password Reset";
        $mail->Body = "Verification number: ".$code;

        //Send To
        $mail->addAddress($email);

        //Unsuccess
        if (!$mail->Send()) {
            $jObj->success = 0;
            $jObj->errorMessage="Could not send email. Please try again later";
        }
        //success
        else {
            $jObj->success = 1;
        }
    }
    //Customer is not found
    else {
        $jObj->success = 0;
        $jObj->errorMessage="Customer not found";
    }

    //Close Connections
    $stmt->close();
    $mysqli->close();
}
//Email variable is not supplied
else {
    $jObj->success = 0;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
