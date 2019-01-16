<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['reservationID']='5';

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    
	$reservationID = $_POST['reservationID'];

    //Detailed Unpaid Charges
    $query = "SELECT Service.Name, SUM(Charge.Price) FROM Charge,Service WHERE Charge.ServiceID=Service.ID AND Charge.ReservationID=? AND Charge.TimePaid IS NULL GROUP BY Service.Name";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $success = $stmt->execute();
    $stmt->bind_result($serviceName, $servicePrice);
    $stmt->store_result();
	
	if($success){
	
		//Total Unpaid Charges
		$totalCharge = 0;
		
		$chargeArray = array();
		while ($stmt->fetch()) {
			$serviceModel = new stdClass();
			$serviceModel->service = $serviceName;
			$serviceModel->price = $servicePrice;
			$totalPrice += $servicePrice; 
			$chargeArray[] = $serviceModel;
		}

		$jObj->success = 1;
		$jObj->totalPrice=$totalPrice;
		$jObj->chargeArray = $chargeArray;	
	}
	else{
		//Problem with the query
		$jObj->success = 0;
		$jObj->errorMessage = "Internal Problem";
	}

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad Request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;