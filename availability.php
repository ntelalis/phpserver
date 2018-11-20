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
$_POST['arrivalDate'] = '2018-06-19';
$_POST['departureDate'] = '2018-06-21';
$_POST['adults'] = 1;
$_POST['children'] = 0;

if (isset($_POST['arrivalDate'],$_POST['departureDate'],$_POST['adults'],$_POST['children'])) {
    $arrivalDate=$_POST['arrivalDate'];
    $departureDate=$_POST['departureDate'];
    $adults=$_POST['adults'];
    $children=$_POST['children'];

    //Check if customer wants a room with children support
    if($children>0){
      $childrenSupported=1;
    }
    else{
      $childrenSupported=0;
    }

    //get all available rooms based on parameters
    $query = "SELECT ID
              FROM   (SELECT COUNT(RoomTypeID) AS total,
                             RoomTypeID
                      FROM   Room
                      GROUP  BY RoomTypeID
                      UNION ALL
                      SELECT COUNT(RoomTypeID) *- 1 AS total,
                             RoomTypeID
                      FROM   Reservation
                      WHERE  NOT ( StartDate > ?
                                    OR EndDate < ? )
                      GROUP  BY RoomTypeID) AvailableRooms,
                     RoomType
              WHERE  RoomTypeID = ID
                     AND Adults >=?
                     AND Capacity >=?+?
                     AND ChildrenSupported IN ( ?, 1 )
              GROUP  BY RoomTypeID
              HAVING sum(total) > 0";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssiiii', $departureDate, $arrivalDate, $adults, $adults, $children,$childrenSupported);
    $stmt->execute();
    $stmt->bind_result($roomTypeID);
    $stmt->store_result();

    //Build the response
    $roomTypeArray = array();
    while ($stmt->fetch()) {
        $roomTypeArray[] = $roomTypeID;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();

    //Build the json response
    $jObj->success = 1;
    $jObj->roomTypeArray = $roomTypeArray;
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad Request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
