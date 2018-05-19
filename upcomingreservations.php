<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if (isset($_POST['customerID']) && !empty($_POST['customerID'])) {
    $customerID = $_POST['customerID'];

    $query = "SELECT r1.ID, CURRENT_DATE AS CurrentDate, r1.StartDate, r1.EndDate, r1.Adults, r3.Name, r.Number, o.CheckIn, o.CheckOut
  FROM Reservation r1 INNER JOIN RoomType r3 ON r1.RoomTypeID = r3.ID
  LEFT JOIN Occupancy o ON r1.ID = o.ReservationID
  LEFT JOIN Room r ON r.ID = o.RoomID
  WHERE r1.CustomerID = ? AND r1.EndDate >= CURRENT_DATE";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($reservationID, $currentDate, $arrivalDate, $departureDate, $adults, $roomType, $roomNumber, $checkIn, $checkOut);
    $stmt->store_result();

    $array=array();
    while ($stmt->fetch()) {
        $reservationObj = new stdClass();
        $reservationObj->reservationID=$reservationID;
        $reservationObj->arrivalDate=$arrivalDate;
        $reservationObj->departureDate=$departureDate;
        $reservationObj->adults=$adults;
        $reservationObj->roomType=$roomType;
        $reservationObj->roomNumber=$roomNumber;
        $reservationObj->checkInDate=$checkIn;
        $reservationObj->checkOutDate=$checkOut;
        if (is_null($roomNumber)) {
            if ($currentDate<$arrivalDate) {
                $reservationObj->statusCode=1;
            } else {
                $reservationObj->statusCode=2;
            }
        } else {
            if (is_null($checkOut)) {
                if ($currentDate==$departureDate) {
                    $reservationObj->statusCode=4;
                } else {
                    $reservationObj->statusCode=3;
                }
            } else {
                $reservationObj->statusCode=5;
            }
        }
        $array[]=$reservationObj;
    }

    $jObj->success=1;
    $jObj->reservations=$array;

    //Close Connections
    $stmt->close();
    $dbCon->close();
}
//Email variable is not supplied
else {
    $jObj->success = 0;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
