<?php

require 'dbConfig.php';
require 'vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    //Check if email matches a record in database and return customerID
    $query = "SELECT Pinakas.ID AS RoomID, Pinakas.Number AS RoomNumber, Pinakas.floor, Beacon.ID,Beacon.UUID, Beacon.Major, Beacon.Minor
            FROM (SELECT Room.ID, Room.Number, Room.Floor, Room.BeaconID
                  FROM Reservation,Room
                  WHERE Reservation.ID=? AND Reservation.RoomTypeID=Room.RoomTypeID AND Room.ID NOT IN (SELECT Occupancy.RoomID
                                                                                                          FROM Occupancy
                                                                                                          WHERE Occupancy.CheckOut IS NULL)
                  ORDER by rand()
                  LIMIT 1) Pinakas,Beacon
            WHERE Pinakas.BeaconID=Beacon.ID";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($roomID, $roomNumber, $roomFloor, $beaconID, $beaconUUID, $beaconMajor, $beaconMinor);
    $stmt->store_result();
    $stmt->fetch();

    //Close Connections
    $stmt->close();

    $checkinDate=date("Y-m-d H:i:s");
    $query = "INSERT INTO Occupancy(RoomID,ReservationID,RoomPasswordHash,CheckIn) VALUES(?,?,?,?)";
    $stmt = $dbCon->prepare($query);

    try {
        // Generate a version 4 (random) UUID object
        $uuid4 = Uuid::uuid4();
        $roomPassword = $uuid4->toString();
        $roomPasswordHash = password_hash($roomPassword, PASSWORD_DEFAULT);
    } catch (UnsatisfiedDependencyException $e) {
        echo 'Caught exception: ' . $e->getMessage() . "\n";
    }

    $stmt->bind_param('iiss', $roomID, $reservationID, $roomPasswordHash, $checkinDate);
    $success = $stmt->execute();
    if ($dbCon->affected_rows==1) {
        $jObj->success=1;
        $jObj->room=$roomNumber;
        $jObj->date=$checkinDate;
        $jObj->floor=$roomFloor;
        $jObj->beaconID=$beaconID;
        $jObj->beaconUUID=$beaconUUID;
        $jObj->beaconMajor=$beaconMajor;
        $jObj->beaconMinor=$beaconMinor;
        $jObj->roomPassword=$roomPassword;
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
