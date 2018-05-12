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

/*$query = " SELECT r.ID "
."FROM Room r, RoomType rt "
."WHERE rt.Name=? AND r.RoomTypeID=rt.ID AND r.ID NOT IN (SELECT DISTINCT res.RoomID "
                                                        ."FROM Reservation res "
                                                        ."WHERE res.StartDate<=? AND res.EndDate>=?) "
."ORDER BY RAND() "
."LIMIT 1";

$query = "";

$stmt = $dbCon->prepare($query);
$stmt->bind_param('sss',$roomTypeID,$departure,$arrival);
$stmt->execute();
$stmt->bind_result($roomI);
$stmt->store_result();
$stmt->fetch();*/

$bookDate = date('Y-m-d');

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


$stmt->close();
$dbCon->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
