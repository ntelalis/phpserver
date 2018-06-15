<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

$jObj = new stdClass();

if (isset($_POST['arrivalDate'],$_POST['departureDate'],$_POST['adults'],$_POST['children'])) {
    $arrivalDate=$_POST['arrivalDate'];
    $departureDate=$_POST['departureDate'];
    $adults=$_POST['adults'];
    $children=$_POST['children'];

    if($children>0){
      $childrenSupported=1;
    }
    else{
      $childrenSupported=0;
    }

    $query = " SELECT ID
            FROM (
                SELECT COUNT(RoomTypeID) AS total, RoomTypeID
                FROM Room
                GROUP BY RoomTypeID
                UNION ALL
                SELECT COUNT(RoomTypeID)*-1 AS total, RoomTypeID
                FROM Reservation
                WHERE NOT (StartDate > ? OR EndDate < ?)
                GROUP BY RoomTypeID
                ) AvailableRooms, RoomType
            WHERE RoomTypeID=ID AND Adults>=? AND Capacity>=?+? AND ChildrenSupported IN (?,1)
            GROUP BY RoomTypeID
            HAVING sum(total)>0";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('ssiiii', $departureDate, $arrivalDate, $adults, $adults, $children,$childrenSupported);
    $stmt->execute();
    $stmt->bind_result($rid);
    $stmt->store_result();

    $numrows = $stmt->num_rows;

    $typesArray = array();
    while ($stmt->fetch()) {
        $type = new stdClass();
        $type->roomTypeID = $rid;
        $typesArray[] = $type;
    }

    $stmt->close();
    $dbCon->close();

    $jObj->success = 1;
    $jObj->results = $typesArray;
} else {
    $jObj->success = 0;
    $jObj->errorMessage = $dbCon->error;
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;
