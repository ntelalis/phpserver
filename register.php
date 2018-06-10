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

$query = "INSERT INTO Customer(TitleID,FirstName,LastName,BirthDate,Email,CountryId) VALUES(?,?,?,?,?,?)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('issssi', $titleID, $fname, $lname, $birthDate, $email, $countryID);
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
}


$stmt->close();
$dbCon->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
