<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");


//DEBUG
//$_GET['verCode'] = 'ASDFASVasdVASDVFasdf';

if(isset($_GET['verCode'])){

    $verCode = $_GET['verCode'];

    //Get customerID and Hash for this email
    $query = "UPDATE Account SET Verified=1 WHERE Account.VerificationCode = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $verCode);
    $stmt->execute();

    //account is verified
    if ($mysqli->affected_rows==1) {
        echo "<h1>Account Verified</h1>";
    }
    //account is not found
    else{
        echo "<h1>Account Not Found</h1>";
    }
    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
}
else{
    echo "<h1>Bad request</h1>";
}
