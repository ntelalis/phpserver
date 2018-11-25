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
//$_POST['reservationID'] = 1;
//$_POST['rating'] = "4";
//$_POST['comments'] = "Good";

if (isset($_POST['reservationID'],$_POST['rating'],$_POST['comments'])) {

    $reservationID = $_POST['reservationID'];
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];

    // insert rating into db
    $query = "INSERT INTO Rating(ReservationID,Rating,Comments) VALUES (?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ids', $reservationID, $rating, $comments);
    $stmt->execute();

    if ($mysqli->affected_rows>0) {
        $jObj->success=1;
    } else {
        $jObj->success=0;
        $errorno = $stmt->errno;
        if($errorno == 1062){
            $jObj->errorMessage="Reservation rating already left";
        }
        else if($errorno == 1452){
            $jObj->errorMessage="Reservation not found";
        }
        else{
            $jObj->errorMessage="Sorry something went wrong";
        }
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
