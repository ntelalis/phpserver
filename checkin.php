<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
$_POST['reservationID']='37';

if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    //Select a non occupied room based on reservation roomtype choice for check-in
    $query = "  SELECT Room.ID, Room.Number, Room.Floor
                FROM   Reservation, Room
                WHERE  Reservation.ID =?
                       AND Room.RoomTypeID = Reservation.RoomTypeID
                       AND Room.ID NOT IN (SELECT Occupancy.RoomID
                                           FROM   Occupancy
                                           WHERE  Occupancy.CheckOut IS NULL)
                /* DEBUG REMOVE THIS COMMENT!!! ORDER by rand()*/
                LIMIT  1";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($roomID, $roomNumber, $roomFloor);
    $stmt->store_result();
    $stmt->fetch();

    //Get all beacon regions for this room
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

    //fill the array with the regions
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

    //check in date is now
    $checkinDate=date("Y-m-d H:i:s");

    //try to generate a UUIDv4 in order to use it for room password
    try {
        // Generate a version 4 (random) UUID object
        $uuid4 = Uuid::uuid4();
        $roomPassword = $uuid4->toString();
        $roomPasswordHash = password_hash($roomPassword, PASSWORD_DEFAULT);
    } catch (UnsatisfiedDependencyException $e) {
        echo 'Caught exception: ' . $e->getMessage() . "\n";
    }

    //insert checkin data into the database
    $query = "  INSERT INTO Occupancy(RoomID,ReservationID,RoomPasswordHash,CheckIn,Modified)
                VALUES(?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iisss', $roomID, $reservationID, $roomPasswordHash, $checkinDate, $checkinDate);
    $success = $stmt->execute();

    //if successful
    if ($mysqli->affected_rows==1) {
        //Build the json response
        $jObj->success=1;
        $jObj->reservationID = $reservationID;
        $jObj->roomNumber=$roomNumber;
        $jObj->checkInDate=$checkinDate;
        $jObj->roomFloor=$roomFloor;
        $jObj->roomBeaconRegionArray=$roomBeaconRegionArray;
        $jObj->roomPassword=$roomPassword;
        $jObj->modified=$checkinDate;
    } else {
        //could not insert checkin data
        $jObj->success=0;
        $jObj->errorMessage= $mysqli->error;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad Request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
