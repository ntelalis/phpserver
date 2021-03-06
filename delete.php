<?php

require 'dbConfig.php';


//Connection to Database
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

$query = "DELETE FROM Reservation";

$stmt = $mysqli->prepare($query);
$done = $stmt->execute();

if ($done) {
    $jObj->success=1;
} else {
    $jObj->success=0;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
