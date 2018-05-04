<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if(isset($_POST['reservationID']) && !empty($_POST['reservationID'])){

  $reservationID = $_POST['reservationID'];

  // Total Charges
  $query = "SELECT IFNULL(SUM(Price),0) FROM Charge WHERE ReservationID=?";

  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();
  $stmt->bind_result($totalPrice);
  $stmt->store_result();
  $stmt->fetch();

  //Detailed Charges
  $query = "SELECT Name, SUM(Price) FROM Charge,ChargedService WHERE Charge.ChargedServiceID=ChargedService.ID AND ReservationID=? GROUP BY Name";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();
  $stmt->bind_result($serviceName,$servicePrice);
  $stmt->store_result();

  $serviceArray = array();
  while($stmt->fetch()){
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
}
else{
  $jObj->success = 0;
  $jObj->errorMessage= "variables not correctly set";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
