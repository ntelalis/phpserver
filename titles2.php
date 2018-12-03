<?php

ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);

use \Firebase\JWT\JWT;

//Database connection variables
include 'dbConfig.php';
include 'dbMessages.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

// Select available titles from Database
$query = "SELECT ID,Title FROM Title";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id,$title);
$stmt->store_result();


//Create titles array from DB results
$titleArray = array();
while ($stmt->fetch()) {
    $titleObj = new stdClass();
    $titleObj->id = $id;
    $titleObj->title = $title;
    $titleArray[] = $titleObj;
}

$key = "example_key";
$token = array(
    "iss" => "http://example.org",
    "aud" => "http://example.com",
    "iat" => 1356999524,
    "nbf" => 1357000000
);

$jwt = JWT::encode($token, $key);
$decoded = JWT::decode($jwt, $key, array('HS256'));

print_r($decoded);



//If there are not titles return error
$numrows = $stmt->num_rows;
if ($numrows == 0) {
    $jObj->success = 0;
    $jObj->error = "There are no titles available";
} else {
    $jObj->success = 1;
    $jObj->titleList = $titleArray;
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;

$stmt->close();
$mysqli->close();
