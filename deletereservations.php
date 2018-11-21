<?php
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

$_POST['customerID'] = 23;

if (isset($_POST['customerID'])) {

  $customerID = $_POST['customerID'];

  $query = "DELETE FROM Reservation WHERE CustomerID=?";

  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i',$customerID);
  $success = $stmt->execute();

  $jObj = new stdClass();
  if($success){
	$jObj->success = 1;
  }
  else{
	$jObj->success = 0;
	$jObj->errorMessage = "Could Not Delete";
  }
  $stmt->close();
  $mysqli->close();
}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
