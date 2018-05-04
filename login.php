<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

$email = $_POST['email'];
$pass = $_POST['pass'];

$query = "SELECT c.CustomerID,(Select Title from Title where Title.ID=c1.TitleID) as Title, c1.FirstName, c1.LastName, c.Hash
          FROM (SELECT a.CustomerID,a.Hash
                FROM Account a
                WHERE Email = ?) c,Customer c1
          WHERE c.CustomerID = c1.ID";

$stmt = $dbCon->prepare($query);

$stmt->bind_param('s',$email);

$stmt->execute();

$stmt->bind_result($customer,$title,$firstName,$lastName,$hash);

$stmt->store_result();

$stmt->fetch();


$numrows = $stmt->num_rows;

if($numrows == 1 && $verify = password_verify($pass,$hash)){
  $jObj->success = 1;
  $jObj->customerID = $customer;
  $jObj->title = $title;
  $jObj->firstName = $firstName;
  $jObj->lastName = $lastName;
}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Login failed";
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;

$stmt->close();
$dbCon->close();

?>
