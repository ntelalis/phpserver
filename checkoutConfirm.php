<?php

require 'dbConfig.php';
require 'Functions/addpoints.php';
//Connection to Database
$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if(isset($_POST['reservationID']) && !empty($_POST['reservationID'])){

  $reservationID = $_POST['reservationID'];

  //Check if email matches a record in database and return customerID
  $query = "UPDATE Occupancy
            SET Occupancy.CheckOut=now()
            WHERE Occupancy.ReservationID=?";

  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();

  if($dbCon->affected_rows==1){
    $query = "SELECT DATEDIFF(Occupancy.CheckOut,Occupancy.CheckIn) + 1
              FROM Occupancy
              WHERE Occupancy.ReservationID = ?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i',$reservationID);
    $stmt->execute();
    $stmt->bind_result($days);
    $stmt->store_result();
    $stmt->fetch();

    if(addPointsByReservationID($dbCon,$reservationID,"night",$days)){
      $jObj->success=1;
    }
    else{
      $jObj->success=0;
      $jObj->errorMessage="Cannot set loyalty points";
    }
  }
  else {
    $jObj->success=0;
    $jObj->errorMessage= $dbCon->error;
  }

  $stmt->close();
  $dbCon->close();
}
//Email variable is not supplied
else{
  $jObj->success = 0;
  $jObj->errorMessage= "reservationID not correctly set";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
