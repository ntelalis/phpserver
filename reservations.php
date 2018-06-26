<?php

/*ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

$_POST['customerID'] = '23';

if (isset($_POST['customerID'])) {

  $customerID = $_POST['customerID'];

  $query = "SELECT res.ID, res.RoomTypeID, res.Adults, res.Children, res.DateBooked, res.StartDate, res.EndDate, o.CheckIn, o.CheckOut, r.BeaconID, r.Number, r.Floor, GREATEST(res.Modified,IFNULL(o.Modified,0)) AS Modified
            FROM Reservation res LEFT JOIN Occupancy o ON res.ID = o.ReservationID
                                 LEFT JOIN Room r ON r.ID = o.RoomID
            WHERE res.CustomerID = ? AND res.EndDate >= CURRENT_DATE";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i',$customerID);
  $stmt->execute();
  $stmt->bind_result($id, $roomTypeID, $adults, $children, $bookDate, $startDate, $endDate, $checkIn, $checkOut, $roomBeaconID, $roomNumber, $roomFloor, $modified);
  $stmt->store_result();

  if (isset($_POST['check']) && !empty($_POST['check'])) {
      $jsonToCheck = json_decode($_POST['check']);
      $values = array();
      foreach ($jsonToCheck as $item) {
          $idClient = $item->id;
          $modifiedClient = $item->modified;
          $values[$idClient]=$modifiedClient;
      }
  }


  $upcomingReservationsArray = array();
  while($stmt->fetch()){

    if (isset($values[$id])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        unset($values[$id]);
        if (!($timeInDB>$timeInClient)) {
            continue;
        }
    }

    $upcomingReservation = new stdClass();
    $upcomingReservation->reservationID = $id;
    $upcomingReservation->roomTypeID = $roomTypeID;
    $upcomingReservation->adults = $adults;
    $upcomingReservation->children = $children;
    $upcomingReservation->bookedDate = $bookDate;
    $upcomingReservation->startDate = $startDate;
    $upcomingReservation->endDate = $endDate;
    $upcomingReservation->checkIn = $checkIn;
    $upcomingReservation->checkOut = $checkOut;
    $upcomingReservation->roomBeaconID = $roomBeaconID;
    $upcomingReservation->roomNumber = $roomNumber;
    $upcomingReservation->roomFloor = $roomFloor;
    $upcomingReservation->modified = $modified;
    $upcomingReservationsArray[] = $upcomingReservation;
  }

  foreach($values as $key => $value){
    $upcomingReservation = new stdClass();
    $upcomingReservation->reservationID = $key;
    $upcomingReservation->modified = null;
    $upcomingReservationsArray[]=$upcomingReservation;
  }


  $stmt->close();
  $mysqli->close();

  $jObj = new stdClass();
  $jObj->success = 1;
  $jObj->upcomingReservationsArray = $upcomingReservationsArray;

}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
