<?php

/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/
include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

$email = $_POST['email'];// = "gpaschos@epi.com.gr";
$pass = $_POST['pass'];// = "Qqwerty1!";
$fname = $_POST['firstName'];// = "ghj";
$lname = $_POST['lastName'];// = "bbn";
$titleID = $_POST['titleID'];// = 2;
$countryID = $_POST['countryID'];// = 4;
$birthDate = $_POST['birthDate'];// = "2000-11-02";
$phone = $_POST['phone'];// = 96599965899;

$query = "INSERT INTO Customer(TitleID,FirstName,LastName,BirthDate,CountryId) VALUES(?,?,?,?,?)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('isssi', $titleID, $fname, $lname, $birthDate, $countryID);
$success = $stmt->execute();


$customerId = $dbCon->insert_id;
$hash = password_hash($pass, PASSWORD_DEFAULT);
$query = "INSERT INTO Account(CustomerID,Email,Hash) VALUES(?,?,?)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('iss', $customerId, $email, $hash);
$success = $stmt->execute();

if ($success) {
    $jObj->success=1;
    $jObj->customerID=$customerId;
} else {
    $jObj->success=0;
    $jObj->errorMessage=$dbCon->error;
}


$stmt->close();
$dbCon->close();


//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
