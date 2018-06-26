<?php

/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

// Select available titles from Database
$query = "SELECT ID,Name,Capacity,Adults,ChildrenSupported,Image,Description,Modified FROM RoomType";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $name, $capacity, $adults, $childrenSupported, $image, $description, $modified);
$stmt->store_result();

//$_POST['check'] = '[{"id":5,"modified":"2018-01-18 01:00:58"}]';

//Create hashtable from client's data '$array[id]=modifiedClientDat'
if (isset($_POST['check']) && !empty($_POST['check'])) {
    $jsonToCheck = json_decode($_POST['check']);
    $values = array();
    foreach ($jsonToCheck as $item) {
        $idClient = $item->id;
        $modifiedClient = $item->modified;
        $values[$idClient]=$modifiedClient;
    }
}


//Create titles array from DB results
$roomTypeArray = array();
while ($stmt->fetch()) {
    if (isset($values[$id])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        unset($values[$id]);
        if (!($timeInDB>$timeInClient)) {
            continue;
        }
    }
    $roomType = new stdClass();
    $roomType->id = $id;
    $roomType->name = $name;
    $roomType->capacity = $capacity;
    $roomType->adults = $adults;
    if($childrenSupported==1){
      $roomType->childrenSupported = true;
    }
    else{
      $roomType->childrenSupported = false;
    }

    $imageBase64 = base64_encode($image);
    $roomType->image = $imageBase64;
    $roomType->description = $description;
    $roomType->modified = $modified;
    $roomTypeArray[] = $roomType;
}
foreach($values as $key => $value){
  $roomType = new stdClass();
  $roomType->id = $key;
  $roomType->modified = null;
  $roomTypeArray[]=$roomType;
}

// RoomTypeCash

$query = "SELECT RoomTypeID,Adults,Children,CurrencyID,Cash FROM RoomTypeCash";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $adults, $children, $currencyID, $cash);
$stmt->store_result();

$roomTypeCashArray = array();
while($stmt->fetch()){
  $roomTypeCash = new stdClass();
  $roomTypeCash->roomTypeID = $roomTypeID;
  $roomTypeCash->adults = $adults;
  $roomTypeCash->children = $children;
  $roomTypeCash->currencyID = $currencyID;
  $roomTypeCash->cash = $cash;
  $roomTypeCashArray[] = $roomTypeCash;
}

// RoomTypePoints

$query = "SELECT RoomTypeID,Adults,Children,SpendingPoints,GainingPoints FROM RoomTypePoints";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $adults, $children, $spendingPoints, $gainingPoints);
$stmt->store_result();

$roomTypePointsArray = array();
while($stmt->fetch()){
  $roomTypePoints = new stdClass();
  $roomTypePoints->roomTypeID = $roomTypeID;
  $roomTypePoints->adults = $adults;
  $roomTypePoints->children = $children;
  $roomTypePoints->spendingPoints = $spendingPoints;
  $roomTypePoints->gainingPoints = $gainingPoints;
  $roomTypePointsArray[] = $roomTypePoints;
}

// RoomTypeCashPoints

$query = "SELECT RoomTypeID,Adults,Children,CurrencyID,Cash,Points FROM RoomTypeCashPoints";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $adults, $children, $currencyID, $cash, $points);
$stmt->store_result();

$roomTypeCashPointsArray = array();
while($stmt->fetch()){
  $roomTypeCashPoints = new stdClass();
  $roomTypeCashPoints->roomTypeID = $roomTypeID;
  $roomTypeCashPoints->adults = $adults;
  $roomTypeCashPoints->children = $children;
  $roomTypeCashPoints->currencyID = $currencyID;
  $roomTypeCashPoints->cash = $cash;
  $roomTypeCashPoints->points = $points;
  $roomTypeCashPointsArray[] = $roomTypeCashPoints;
}

//If there are not roomTypes return error
$numrows = $stmt->num_rows;
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

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;

$stmt->close();
$mysqli->close();
