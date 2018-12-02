<?php

require 'dbConfig.php';

$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");
//Response Object
$jObj = new stdClass();

$query = "SELECT ID, Name, BeginTime, EndTime FROM FoodMenuTime";

$stmt = $mysqli->prepare($query);
$executed = $stmt->execute();
$stmt->bind_result($categoryID, $categoryName, $categoryFrom, $categoryTo);
$stmt->store_result();

$timeCategory = array();

while ($stmt->fetch()) {
    $categoryObj = new stdClass();
    $categoryObj->id = $categoryID;
    $categoryObj->name = $categoryName;
    $categoryObj->from = $categoryFrom;
    $categoryObj->to = $categoryTo;
    $timeCategory[] = $categoryObj;
}

$stmt->close();
$mysqli->close();

if ($executed) {
    $jObj->success=1;
    $jObj->timeCategory = $timeCategory;
} else {
    $jObj->success=0;
    $jObj->errorMessage=$mysqli->error;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);

//Show Data
echo $JsonResponse;
