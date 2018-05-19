<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");

if (isset($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    $jObj = new stdClass();

    $query = "SELECT r.Rating,r.Comments FROM Rating r WHERE r.ReservationID=?";

    $stmt = $dbCon->prepare($query);
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
