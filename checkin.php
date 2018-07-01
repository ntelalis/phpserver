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
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

// $_POST['reservationID']='9';

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    //Check if email matches a record in database and return customerID
    $query = "SELECT Room.ID, Room.Number, Room.Floor, Room.BeaconRegionID
              FROM Reservation,Room
              WHERE Reservation.ID=? AND Room.RoomTypeID=Reservation.RoomTypeID AND Room.ID NOT IN (SELECT Occupancy.RoomID
                                                                                                    FROM Occupancy
                                                                                                    WHERE Occupancy.CheckOut IS NULL)
              ORDER by rand()
              LIMIT 1";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($roomID, $roomNumber, $roomFloor, $beaconRegionID);
    $stmt->store_result();
    $stmt->fetch();

    //Close Connections
    $stmt->close();

    $checkinDate=date("Y-m-d H:i:s");
    $query = "INSERT INTO Occupancy(RoomID,ReservationID,RoomPasswordHash,CheckIn,Modified) VALUES(?,?,?,?,?)";
    $stmt = $dbCon->prepare($query);

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

    if ($dbCon->affected_rows==1) {
        $jObj->success=1;
        $jObj->reservationID = $reservationID;
        $jObj->roomNumber=$roomNumber;
        $jObj->checkInDate=$checkinDate;
        $jObj->roomFloor=$roomFloor;
        $jObj->beaconRegionID=$beaconRegionID;
        $jObj->roomPassword=$roomPassword;
        $jObj->modified=$checkinDate;
    } else {
        $jObj->success=0;
        $jObj->errorMessage= $dbCon->error;
    }

    $stmt->close();

    $dbCon->close();
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
