<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if(isset($_POST['reservationID']) && !empty($_POST['reservationID'])){

  $reservationID = $_POST['reservationID'];

  //Check if email matches a record in database and return customerID
  $checkoutDate=date("Y-m-d H:i:s");
  $query = "UPDATE Occupancy
            SET Occupancy.CheckOut=?
            WHERE Occupancy.ReservationID=?";

  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('si',$checkoutDate,$reservationID);
  $stmt->execute();

  if($dbCon->affected_rows==1){
    $jObj->success=1;
    $jObj->date=$checkoutDate;
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
