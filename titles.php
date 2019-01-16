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

// Select available titles from Database
$query = "SELECT ID,Title FROM Title";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id,$title);
$stmt->store_result();

$numrows = $stmt->num_rows;

//Create titles array from DB results
$titleArray = array();
while ($stmt->fetch()) {
    $titleObj = new stdClass();
    $titleObj->id = $id;
    $titleObj->title = $title;
    $titleArray[] = $titleObj;
}

//Close Connection to DB
$stmt->close();
$mysqli->close();

//If there are not titles return error
if ($numrows == 0) {
    $jObj->success = 0;
    $jObj->errorMessage = "There are no titles available";
} else {
    $jObj->success = 1;
    $jObj->titleArray = $titleArray;
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
