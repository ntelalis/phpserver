<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");

if (isset($_POST['rating'],$_POST['comments'],$_POST['reservationID'])) {
    $rating = $_POST['rating'];
    $comments = $_POST['comments'];
    $reservationID = $_POST['reservationID'];

    $jObj = new stdClass();

    $query = "INSERT INTO Rating(ReservationID,Rating,Comments) VALUES (?,?,?)";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('ids', $reservationID, $rating, $comments);
    $stmt->execute();

    if ($dbCon->affected_rows>0) {
        $jObj->success=1;
    } else {
        $jObj->success=0;
        $jObj->errorMessage="Sorry something went wrong. Please try again later.";
    }

    $stmt->close();
    $dbCon->close();
} else {
    $jObj->success=0;
    $jObj->errorMessage="Wrong given arguments";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);

//Show Data
echo $JsonResponse;
