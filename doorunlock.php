<?php

$_POST['reservationID']= 16;
$_POST['roomPassword']="2";

//Parse POST Variables
if(isset($_POST['reservationID'],$_POST['roomPassword'])){

  $reservationID = $_POST['reservationID'];
  $roomPassword = $_POST['roomPassword'];

  require 'dbConfig.php';
  require 'Functions/doorUnlock.php';

  //Connection to Database
  $dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

  //Response Object
  $jObj = new stdClass();

  $query = "SELECT RoomID,RoomPasswordHash FROM Occupancy WHERE ReservationID=?";


  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();
  $stmt->bind_result($roomID,$roomPasswordHash);
  $stmt->store_result();
  $stmt->fetch();

  if($stmt->num_rows == 1){

    if(password_verify($roomPassword,$roomPasswordHash)){
      if(unlockDoor($roomID)){
        $jObj->success=1;
      }
      else{
        $jObj->success=0;
        $jObj->errorMessage="There is a problem with unlocking the door. Please contant hotel administration";
      }

    }
    else{
      $jObj->success=0;
      $jObj->errorMessage="Wrong password";
    }
  }
  else{
    $jObj->success=0;
    $jObj->errorMessage="No room found";
  }

  //Encode data in JSON Format
  $JsonResponse = json_encode($jObj);

  //Show Data
  echo $JsonResponse;
}

?>
