<?php

/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

require 'dbConfig.php';
require 'vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

//Connection to Database
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//$_POST['reservationID']='13';

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    //Check if email matches a record in database and return customerID
    $query = "SELECT Room.ID, Room.Number, Room.Floor
              FROM Reservation,Room
              WHERE Reservation.ID=? AND Room.RoomTypeID=Reservation.RoomTypeID AND Room.ID NOT IN (SELECT Occupancy.RoomID
                                                                                                    FROM Occupancy
                                                                                                    WHERE Occupancy.CheckOut IS NULL)
              /* !!REMOVE THIS COMMENT!!! ORDER by rand()*/
              LIMIT 1";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($roomID, $roomNumber, $roomFloor);
    $stmt->store_result();
    $stmt->fetch();

    $query = "SELECT v.ID, v.UniqueID,v.UUID,v.Major,v.Minor,v.Exclusive,v.Background,v.Modified
              FROM BeaconRegionView v
              WHERE v.ID IN(SELECT brm.BeaconRegionID
                            FROM BeaconRegionRoom brm
                            WHERE brm.RoomID = ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $roomID);
    $stmt->execute();
    $stmt->bind_result($brmID, $brmUniqueID, $brmUUID, $brmMajor, $brmMinor, $brmExclusive, $brmBackground, $brmModified);
    $stmt->store_result();

    $roomBeaconRegionArray = array();

    while ($stmt->fetch()) {
      $beaconRegion = new stdClass();
      $beaconRegion->id = $brmID;
      $beaconRegion->uniqueID = $brmUniqueID;
      $beaconRegion->uuid = $brmUUID;
      $beaconRegion->major = $brmMajor;
      $beaconRegion->minor = $brmMinor;
      $beaconRegion->exclusive= $brmExclusive == 1;
      $beaconRegion->background = $brmBackground == 1;
      $beaconRegion->modified = $brmModified;
      $roomBeaconRegionArray[] = $beaconRegion;
    }


    //Close Connections
    $stmt->close();

    $checkinDate=date("Y-m-d H:i:s");
    $query = "INSERT INTO Occupancy(RoomID,ReservationID,RoomPasswordHash,CheckIn,Modified) VALUES(?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);

    try {
        // Generate a version 4 (random) UUID object
        $uuid4 = Uuid::uuid4();
        $roomPassword = $uuid4->toString();
        $roomPasswordHash = password_hash($roomPassword, PASSWORD_DEFAULT);
    } catch (UnsatisfiedDependencyException $e) {
        echo 'Caught exception: ' . $e->getMessage() . "\n";
    }

    $stmt->bind_param('iisss', $roomID, $reservationID, $roomPasswordHash, $checkinDate, $checkinDate);
    $success = $stmt->execute();

    if ($mysqli->affected_rows==1) {
        $jObj->success=1;
        $jObj->reservationID = $reservationID;
        $jObj->roomNumber=$roomNumber;
        $jObj->checkInDate=$checkinDate;
        $jObj->roomFloor=$roomFloor;
        $jObj->roomBeaconRegionArray=$roomBeaconRegionArray;
        $jObj->roomPassword=$roomPassword;
        $jObj->modified=$checkinDate;
    } else {
        $jObj->success=0;
        $jObj->errorMessage= $mysqli->error;
    }

    $stmt->close();

    $mysqli->close();
}
//Email variable is not supplied
else {
    $jObj->success = 0;
    $jObj->errorMessage= "variables not correctly set";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
