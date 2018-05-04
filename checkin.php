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
  $query = "SELECT Room.ID, Room.Number
            FROM Reservation,Room
            WHERE Reservation.ID=? AND Reservation.RoomTypeID=Room.RoomTypeID AND Room.ID NOT IN (SELECT Occupancy.RoomID
                                                                                                  FROM Occupancy
                                                                                                  WHERE Occupancy.CheckOut IS NULL)
            ORDER by rand()
            LIMIT 1";

  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();
  $stmt->bind_result($roomID,$roomNumber);
  $stmt->store_result();
  $stmt->fetch();


/*  $array=array();
  while ($stmt->fetch()) {
    $reservationObj = new stdClass();
    $reservationObj->reservationID=$reservationID;
    $reservationObj->arrivalDate=$arrivalDate;
    $reservationObj->departureDate=$departureDate;
    $reservationObj->adults=$adults;
    $reservationObj->roomType=$roomType;
    $array[]=$reservationObj;
  }
*/


  //Close Connections
  $stmt->close();


  $query = "INSERT INTO Occupancy(RoomID,ReservationID,CheckIn) VALUES(?,?,now())";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('ii',$roomID,$reservationID);
  $success = $stmt->execute();
  if($dbCon->affected_rows==1){
    $jObj->success=1;
    $jObj->room=$roomNumber;
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
  $jObj->errorMessage= "variables not correctly set";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
