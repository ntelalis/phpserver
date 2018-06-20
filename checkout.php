<?php

/*ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//$_POST['reservationID']='5';

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    // Total Charges
    $query = "SELECT IFNULL(SUM(Charge.Price),0) FROM Charge WHERE ReservationID=?";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($totalPrice);
    $stmt->store_result();
    $stmt->fetch();

    //Detailed Charges
    $query = "SELECT HotelService.Name, SUM(Charge.Price) FROM Charge,HotelService WHERE Charge.HotelServiceID=HotelService.ID AND Charge.ReservationID=? GROUP BY HotelService.Name";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($serviceName, $servicePrice);
    $stmt->store_result();


    $serviceArray = array();
    while ($stmt->fetch()) {
        $serviceModel = new stdClass();
        $serviceModel->service = $serviceName;
        $serviceModel->price = $servicePrice;
        $serviceArray[] = $serviceModel;
    }

    $jObj->success = 1;
    $jObj->totalPrice=$totalPrice;
    $jObj->chargeDetails = $serviceArray;

    //Close Connections
    $stmt->close();
    $dbCon->close();
} else {
    $jObj->success = 0;
    $jObj->errorMessage= "variables not correctly set";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
