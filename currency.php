<?php

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Currency
$query = "SELECT ID,Name,Code,Symbol FROM Currency";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $name, $code, $symbol);
$stmt->store_result();
$numrows = $stmt->num_rows;

$currencyArray = array();
while ($stmt->fetch()) {
    $currency = new stdClass();
    $currency->id = $id;
    $currency->name = $name;
    $currency->code = $code;
    $currency->symbol = $symbol;
    $currencyArray[] = $currency;
}


//If there are not roomTypes return error


$stmt->close();
$mysqli->close();

if ($numrows == 0) {
    $jObj->success = 0;
    $jObj->errorMessage = "There are no roomTypes available";
} else {
    $jObj->success = 1;
    $jObj->currencyArray = $currencyArray;
}

$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
