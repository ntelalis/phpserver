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

//Select all room service categories from DB
$query = "SELECT ID, Name, BeginTime, EndTime FROM FoodMenuTime";
$stmt = $mysqli->prepare($query);
$executed = $stmt->execute();
$stmt->bind_result($categoryID, $categoryName, $categoryFrom, $categoryTo);
$stmt->store_result();

//initialize the response array
$menuTimeArray = array();
while ($stmt->fetch()) {
    $menuTime = new stdClass();
    $menuTime->id = $categoryID;
    $menuTime->name = $categoryName;
    $menuTime->from = $categoryFrom;
    $menuTime->to = $categoryTo;
    $menuTimeArray[] = $menuTime;
}

//Close Connection to DB
$stmt->close();
$mysqli->close();

//everything went ok
if ($executed) {
    $jObj->success=1;
    $jObj->menuTimeArray = $menuTimeArray;
}
//something went wrong
else {
    $jObj->success = 0;
    $jObj->errorMessage = "An error has occured";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
