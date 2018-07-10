<?php

/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

require 'dbConfig.php';
require 'Functions/addpoints.php';
//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//$_POST['reservationID']='5';

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

    //Check if email matches a record in database and return customerID
    $checkoutDate=date("Y-m-d H:i:s");
    $query = "UPDATE Occupancy
  SET Occupancy.CheckOut=?
  WHERE Occupancy.ReservationID=?";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('si', $checkoutDate, $reservationID);
    $stmt->execute();

    if ($dbCon->affected_rows==1) {
        $query = "SELECT DATEDIFF(Occupancy.CheckOut,Occupancy.CheckIn) + 1
    FROM Occupancy
    WHERE Occupancy.ReservationID = ?";
        $stmt = $dbCon->prepare($query);
        $stmt->bind_param('i', $reservationID);
        $stmt->execute();
        $stmt->bind_result($days);
        $stmt->store_result();
        $stmt->fetch();

        if (addPointsByReservationID($dbCon, $reservationID, "night", $days)) {
            $jObj->success=1;
            $jObj->date=$checkoutDate;
            $jObj->modified=$checkoutDate;
        } else {
            $jObj->success=0;
            $jObj->errorMessage="Cannot set loyalty points";
        }
    } else {
        $jObj->success=0;
        $jObj->errorMessage="There is some problem with checkout procedure";
    }
    $stmt->close();
}
//Email variable is not supplied
else {
    $jObj->success = 0;
    $jObj->errorMessage= "reservationID not correctly set";
}

$dbCon->close();
//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
