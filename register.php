<?php

# a;

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

$email = $_POST['email'];
$pass = $_POST['pass'];
$fname = $_POST['firstName'];
$lname = $_POST['lastName'];
$titleID = $_POST['titleID'];
$countryID = $_POST['countryID'];
$birthDate = $_POST['birthDate'];
$phone = $_POST['phone'];

$errorMessage = new stdClass();
$errorMessage->email = $email;
$errorMessage->pass = $pass;
$errorMessage->fname = $fname;
$errorMessage->lname = $lname;
$errorMessage->titleID = $titleID;
$errorMessage->countryID = $countryID;
$errorMessage->birthDate = $birthDate;
$errorMessage->phone = $phone;

$errorMessageStr = encode($errorMessage);
$jObj->success=0;
$jObj->errorMessage=$errorMessageStr;
/*
$query = "INSERT INTO Customer(TitleID,FirstName,LastName,BirthDate,CountryId) VALUES(?,?,?,?,?)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('isssi', $titleID, $fname, $lname, $birthDate, $email, $countryID);
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
*/