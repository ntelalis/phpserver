<?php

//Database connection variables
include 'dbConfig.php';
include 'dbMessages.php';

//Create new database object
$mysqli = new mysqli($dbip,$dbusername,$dbpass,$dbname);

// Select available titles from Database
$query = "SELECT Title FROM Title";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($title);
$stmt->store_result();


//Create titles array from DB results
$titleArray = array();
while($stmt->fetch()){
  $titleArray[] = $title;
}


//If there are not titles return error
$numrows = $stmt->num_rows;
if($numrows == 0){
  $jObj->success = 0;
  $jObj->error = "There are no titles available";
}
else{
  $jObj->success = 1;
  $jObj->titleList = $titleArray;
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;

$stmt->close();
$mysqli->close();

?>
