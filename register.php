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

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['email'] = "gpaschos@epi.com.gr";
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

    $query = "INSERT INTO Customer(TitleID,FirstName,LastName,BirthDate,CountryId) VALUES(?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isssi', $titleID, $firstName, $lastName, $birthDate, $countryID);
    $success = $stmt->execute();


    $customerID = $mysqli->insert_id;
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $query = "INSERT INTO Account(CustomerID,Email,Hash) VALUES(?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iss', $customerID, $email, $hash);
    $success = $stmt->execute();

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();

    if ($success) {
        $jObj->success=1;
        $jObj->customerID=$customerID;
    } else {
        $jObj->success=0;
        $jObj->errorMessage=$mysqli->error;
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
