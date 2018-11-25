<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['reservationID'] = 2;

if (isset($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    $jObj = new stdClass();

    $query = "SELECT r.Rating,r.Comments FROM Rating r WHERE r.ReservationID=?";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $reservationID);
    $stmt->execute();
    $stmt->bind_result($rating, $comments);
    $stmt->store_result();
    $stmt->fetch();

    $numrows = $stmt->num_rows;

    if ($numrows == 0) {
        $jObj->success = 1;
        $jObj->exists=false;
    } else {
        $jObj->success = 1;
        $jObj->exists=true;
        $jObj->rating=$rating;
        $jObj->comments=$comments;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
}
//Bad request
else{
    $jObj->success = 0;
    $jObj->errorMessage = "Bad request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
