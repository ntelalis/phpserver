<?php

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

// Select available titles from Database
$query = "SELECT ID,Name,Capacity,Price,Image,Description,Modified FROM RoomType";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $name, $capacity, $price, $image, $description, $modified);
$stmt->store_result();

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
        if (!($timeInDB>$timeInClient)) {
            continue;
        }
    }
    $roomType = new stdClass();
    $roomType->id = $id;
    $imageBase64 = base64_encode($image);
    $roomType->name = $name;
    $roomType->capacity = $capacity;
    $roomType->price = $price;
    $roomType->image = $imageBase64;
    $roomType->description = $description;
    $roomType->modified = $modified;
    $roomTypeArray[] = $roomType;
}


//Currency
$query = "SELECT ID,Name,Code,Symbol FROM Currency";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $name, $code, $symbol);
$stmt->store_result();

$currencyArray = array();
while($stmt->fetch()){
  $currency = new stdClass();
  $currency->id = $id;
  $currency->name = $name;
  $currency->code = $code;
  $currency->symbol = $symbol;
  $currencyArray[] = $currency;
}


// RoomTypeCash

$query = "SELECT RoomTypeID,Persons,CurrencyID,Price FROM RoomTypeCash";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $persons, $currencyID, $price);
$stmt->store_result();

$roomTypeCashArray = array();
while($stmt->fetch()){
  $roomTypeCash = new stdClass();
  $roomTypeCash->roomTypeID = $roomTypeID;
  $roomTypeCash->persons = $persons;
  $roomTypeCash->currencyID = $currencyID;
  $roomTypeCash->price = $price;
  $roomTypeCashArray[] = $roomTypeCash;
}

// RoomTypeFreeNightsPoints

$query = "SELECT RoomTypeID,Persons,Points FROM RoomTypeFreeNightsPoints";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $persons, $points);
$stmt->store_result();

$roomTypeFreeNightsPointsArray = array();
while($stmt->fetch()){
  $roomTypeFreeNightsPoints = new stdClass();
  $roomTypeFreeNightsPoints->roomTypeID = $roomTypeID;
  $roomTypeFreeNightsPoints->persons = $persons;
  $roomTypeFreeNightsPoints->points = $points;
  $roomTypeFreeNightsPointsArray[] = $roomTypeFreeNightsPoints;
}

// RoomTypePointsAndCash

$query = "SELECT RoomTypeID,Persons,CurrencyID,Cash,Points FROM RoomTypePointsAndCash";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($roomTypeID, $persons,$currencyID,$cash, $points);
$stmt->store_result();

$roomTypePointsAndCashArray = array();
while($stmt->fetch()){
  $roomTypePointsAndCash = new stdClass();
  $roomTypePointsAndCash->roomTypeID = $roomTypeID;
  $roomTypePointsAndCash->persons = $persons;
  $roomTypePointsAndCash->currencyID = $currencyID;
  $roomTypePointsAndCash->cash = $cash;
  $roomTypePointsAndCash->points = $points;
  $roomTypePointsAndCashArray[] = $roomTypePointsAndCash;
}

//If there are not roomTypes return error
$numrows = $stmt->num_rows;
if ($numrows == 0) {
    $jObj->success = 0;
    $jObj->error = "There are no roomTypes available";
} else {
    $jObj->success = 1;
    $jObj->currencyArray = $currencyArray;
    $jObj->roomTypeArray = $roomTypeArray;
    $jObj->roomTypeCashArray = $roomTypeCashArray;
    $jObj->roomTypeFreeNightsPointsArray = $roomTypeFreeNightsPointsArray;
    $jObj->roomTypePointsAndCashArray = $roomTypePointsAndCashArray;
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;

$stmt->close();
$mysqli->close();
