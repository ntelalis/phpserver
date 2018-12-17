<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['check'] = '[{"id":5,"modified":"2018-01-18 01:00:58"}]';

// Select available roomtypes from Database
$query = "SELECT ID,Name,Capacity,Adults,ChildrenSupported,Image,Description,Modified FROM RoomType";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $name, $capacity, $adults, $childrenSupported, $image, $description, $modified);
$stmt->store_result();

//Check if customer has updated data for his reservations

//Check if customer has any data for checking
if (isset($_POST['check']) && !empty($_POST['check'])) {
    //parse json to array
    $jsonToCheck = json_decode($_POST['check']);
    //initialize a hash array which will be filled with rows client knows about
    $values = array();
    //for each row customer has
    foreach ($jsonToCheck as $item) {
        //get the id and the modified date of the row client knows
        $idClient = $item->id;
        $modifiedClient = $item->modified;
        //add these data to the array in order to be checked
        $values[$idClient]=$modifiedClient;
    }
}

//initialize the array which will be sent to client
$roomTypeArray = array();

//fetch server data row by row
while ($stmt->fetch()) {

//check if client knows about this row by checking if this id
    //is found in array which was filled with client data
    //
    if (isset($values[$id])) {
        //convert client's and server's timestamps to time
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        //remove this id from the client's array because it was found and compared
        unset($values[$id]);
        //if client has latest data skip the row and continue to next one
        if ($timeInDB==$timeInClient) {
            continue;
        }
    }

    //if client doesnt know about this row or he hasn't the latest data
    //add the row to the response array
    $roomType = new stdClass();
    $roomType->id = $id;
    $roomType->name = $name;
    $roomType->capacity = $capacity;
    $roomType->adults = $adults;
    $roomType->childrenSupported = $childrenSupported == 1;
    $roomType->image = base64_encode($image);
    ;
    $roomType->description = $description;
    $roomType->modified = $modified;
    $roomTypeArray[] = $roomType;
}

//for each row that was sent by the client and server didn't find
//a match with his query to database
foreach ($values as $key => $value) {
    //add it to response array but only set modified date with null value
    //so the client will delete it from his list  $roomType = new stdClass();
    $roomType->id = $key;
    $roomType->modified = null;
    $roomTypeArray[]=$roomType;
}

// RoomTypeCash

//Get all required prices for roomtypes
$query = "SELECT RoomTypeID,Adults,Children,Cash FROM RoomTypeCash";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $adults, $children, $cash);
$stmt->store_result();

$roomTypeCashArray = array();
while ($stmt->fetch()) {
    $roomTypeCash = new stdClass();
    $roomTypeCash->roomTypeID = $roomTypeID;
    $roomTypeCash->adults = $adults;
    $roomTypeCash->children = $children;
    $roomTypeCash->cash = $cash;
    $roomTypeCashArray[] = $roomTypeCash;
}

// RoomTypePoints

//Get all required points for roomtypes
$query = "SELECT RoomTypeID,Adults,Children,SpendingPoints,GainingPoints FROM RoomTypePoints";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $adults, $children, $spendingPoints, $gainingPoints);
$stmt->store_result();

$roomTypePointsArray = array();
while ($stmt->fetch()) {
    $roomTypePoints = new stdClass();
    $roomTypePoints->roomTypeID = $roomTypeID;
    $roomTypePoints->adults = $adults;
    $roomTypePoints->children = $children;
    $roomTypePoints->spendingPoints = $spendingPoints;
    $roomTypePoints->gainingPoints = $gainingPoints;
    $roomTypePointsArray[] = $roomTypePoints;
}

// RoomTypeCashPoints

//Get all required cash and points for roomtypes
$query = "SELECT RoomTypeID,Adults,Children,Cash,Points FROM RoomTypeCashPoints";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $adults, $children, $cash, $points);
$stmt->store_result();

$roomTypeCashPointsArray = array();
while ($stmt->fetch()) {
    $roomTypeCashPoints = new stdClass();
    $roomTypeCashPoints->roomTypeID = $roomTypeID;
    $roomTypeCashPoints->adults = $adults;
    $roomTypeCashPoints->children = $children;
    $roomTypeCashPoints->cash = $cash;
    $roomTypeCashPoints->points = $points;
    $roomTypeCashPointsArray[] = $roomTypeCashPoints;
}

//If there are not roomTypes return error
$numrows = $stmt->num_rows;

//Close Connection to DB
$stmt->close();
$mysqli->close();

if ($numrows == 0) {
    $jObj->success = 0;
    $jObj->error = "There are no roomTypes available";
} else {
    $jObj->success = 1;
    $jObj->roomTypeArray = $roomTypeArray;
    $jObj->roomTypeCashArray = $roomTypeCashArray;
    $jObj->roomTypePointsArray = $roomTypePointsArray;
    $jObj->roomTypeCashPointsArray = $roomTypeCashPointsArray;
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
