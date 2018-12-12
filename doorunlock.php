<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'Functions/doorUnlock.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['reservationID'] = 37;
//$_POST['roomPassword'] = "35439670-d4af-43ea-ab2f-e78d8a7b6ae2";

//Parse POST Variables
if (isset($_POST['reservationID'],$_POST['roomPassword'])) {
    $reservationID = $_POST['reservationID'];
    $roomPassword = $_POST['roomPassword'];


    //Get the password and roomId for this reservation while checking that the user hasn't checked out
    $query = "SELECT RoomID,RoomPasswordHash FROM Occupancy WHERE ReservationID=? AND Occupancy.CheckOut IS NULL";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($roomID, $roomPasswordHash);
    $stmt->store_result();
    $stmt->fetch();

    //if info are found
    if ($stmt->num_rows == 1) {
        //verify the password is correct
        if (password_verify($roomPassword, $roomPasswordHash)) {
            //unlock the door
            if (doorUnlock($roomID)) {
                $jObj->success=1;
            } else {
                //unlocking failed. There is a problem with the door unlocking system
                $jObj->success=0;
                $jObj->errorMessage="There is a problem with unlocking the door. Please contant hotel administration";
            }
        } else {
            //Wrong password
            $jObj->success=0;
            $jObj->errorMessage="Wrong password";
        }
    } else {
        //Query failed to find a match
        $jObj->success=0;
        $jObj->errorMessage="No room found";
    }
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
