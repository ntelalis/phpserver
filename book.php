<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

$customerID = $_POST['customerID'];
$roomTypeID = $_POST['roomTypeID'];
$arrival = $_POST['arrival'];
$departure = $_POST['departure'];
$persons = $_POST['persons'];

$bookDate = date('Y-m-d');

$query = "SELECT ID FROM Reservation WHERE CustomerID=? AND NOT (StartDate>=? OR EndDate<=?)";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('iss',$customerID,$departure,$arrival);
$stmt->execute();
$stmt->bind_result($resId);
$stmt->store_result();
$stmt->fetch();

$numrows = $stmt->num_rows;

if($numrows==0){

  $query = "INSERT INTO Reservation(CustomerID,RoomTypeID,ReservationTypeID,Adults,DateBooked,StartDate,EndDate) VALUES (?,?,3,?,?,?,?)";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('iiisss',$customerID,$roomTypeID,$persons,$bookDate,$arrival,$departure);
  $success = $stmt->execute();

  $reservationId = $dbCon->insert_id;
  if($success){
    $jObj->success=1;
    $jObj->reservationID=$reservationId;
    $jObj->bookedDate = $bookDate;
  }
  else{
    $jObj->success=0;
    $jObj->errorMessage=$dbCon->error;
  }

}
else{
  $jObj->success=0;
  $jObj->errorMessage="You already have an active reservation within these days";
}




$stmt->close();
$dbCon->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
