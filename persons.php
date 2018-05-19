<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

$query = "SELECT MAX(Capacity) as maxCap FROM RoomType";

$stmt = $dbCon->prepare($query);

$stmt->execute();

$stmt->bind_result($capacity);

$stmt->store_result();

$stmt->fetch();

$numrows = $stmt->num_rows;

if ($numrows == 0) {
    $jObj->success = 0;
} else {
    $jObj->success = 1;
    $jObj->capacity = $capacity;
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;

$stmt->close();
$dbCon->close();
