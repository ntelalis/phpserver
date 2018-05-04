<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

$email = $_POST['email'];
$pass = $_POST['pass'];
$fname = $_POST['firstName'];
$lname = $_POST['lastName'];
$title = $_POST['title'];

$query = "SELECT ID FROM Title WHERE Title=?";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('s',$title);
$stmt->execute();
$stmt->bind_result($titleID);
$stmt->store_result();
$stmt->fetch();

$query = "INSERT INTO Customer(TitleID,FirstName,LastName,Email,CountryId) VALUES(?,?,?,?,56)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('isss',$titleID,$fname,$lname,$email);
$success = $stmt->execute();


$customerId = $dbCon->insert_id;
$hash = password_hash($pass,PASSWORD_DEFAULT);
$query = "INSERT INTO Account(CustomerID,Email,Hash) VALUES(?,?,?)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('iss',$customerId,$email,$hash);
$success = $stmt->execute();

if($success){
  $jObj->success=1;
}
else{
  $jObj->success=0;
}


$stmt->close();
$dbCon->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
