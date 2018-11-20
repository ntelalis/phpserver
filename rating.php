<?php

include 'dbConfig.php';

$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

if (isset($_POST['rating'],$_POST['comments'],$_POST['reservationID'])) {
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];
    $reservationID = $_POST['reservationID'];

    $jObj = new stdClass();

    $query = "INSERT INTO Rating(ReservationID,Rating,Comments) VALUES (?,?,?)";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ids', $reservationID, $rating, $comments);
    $stmt->execute();

    if ($mysqli->affected_rows>0) {
        $jObj->success=1;
    } else {
        $jObj->success=0;
        $jObj->errorMessage="Sorry something went wrong. Please try again later.";
    }

    $stmt->close();
    $mysqli->close();
} else {
    $jObj->success=0;
    $jObj->errorMessage="Wrong given arguments";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);

//Show Data
echo $JsonResponse;
