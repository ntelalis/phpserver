<?php

/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

require 'vendor/autoload.php';

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
    "cus" => 1
);

$jwt = JWT::encode($token, $key);

<<<<<<< HEAD

try {
  $decoded = JWT::decode($jwt, $key, array('HS256'));

} catch (\Exception $e) {
}


=======
echo $jwt;
echo "  ";
echo $decoded;
>>>>>>> 6fe3fa0bf03d781499cb2c0847129f15b791042c



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

//echo $JsonResponse;

echo $jwt;
$stmt->close();
$mysqli->close();
