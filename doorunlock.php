<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
$_POST['reservationID'] = 37;
$_POST['roomPassword'] = "35439670-d4af-43ea-ab2f-e78d8a7b6ae2";

//Parse POST Variables
if (isset($_POST['reservationID'],$_POST['roomPassword'])) {
    $reservationID = $_POST['reservationID'];
    $roomPassword = $_POST['roomPassword'];

    require 'dbConfig.php';
    require 'Functions/doorUnlock.php';

    //Connection to Database
    $mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

    //Response Object
    $jObj = new stdClass();

    $query = "SELECT RoomID,RoomPasswordHash FROM Occupancy WHERE ReservationID=? AND Occupancy.CheckOut IS NULL";


    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($roomID, $roomPasswordHash);
    $stmt->store_result();
    $stmt->fetch();

    if ($stmt->num_rows == 1) {
        if (password_verify($roomPassword, $roomPasswordHash)) {
            //if (doorUnlock($roomID)) {
            //debug
            if (true) {
                $jObj->success=1;
            } else {
                $jObj->success=0;
                $jObj->errorMessage="There is a problem with unlocking the door. Please contant hotel administration";
            }
        } else {
            $jObj->success=0;
            $jObj->errorMessage="Wrong password";
        }
    } else {
        $jObj->success=0;
        $jObj->errorMessage="No room found";
    }

    //Encode data in JSON Format
    $JsonResponse = json_encode($jObj);

    //Show Data
    echo $JsonResponse;
}
