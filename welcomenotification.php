<?php

require 'dbConfig.php';

//Connection to Database
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if (isset($_POST['customerID']) && !empty($_POST['customerID'])) {
    $customerID = $_POST['customerID'];

    $query = "SELECT COUNT(Occupancy.ReservationID)
            FROM Occupancy, Reservation
            WHERE Occupancy.ReservationID=Reservation.ID AND Reservation.CustomerID=?";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($reservationIDCount);
    $stmt->store_result();

    $stmt->fetch();
    if ($reservationIDCount>0) {
        $jObj->hasStayed=true;
    } else {
        $jObj->hasStayed=false;
    }

    $jObj->success=1;

    //Close Connections
    $stmt->close();
    $mysqli->close();
}
//Email variable is not supplied
else {
    $jObj->success = 0;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
