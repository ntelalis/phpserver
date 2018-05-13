<?php

//Database connection variables
include 'dbConfig.php';
include 'dbMessages.php';

//Create new database object
$mysqli = new mysqli($dbip,$dbusername,$dbpass,$dbname);

// Select available titles from Database
$query = "SELECT ID,Name,Capacity,Price,Image,Description,Modified FROM RoomType";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id,$name,$capacity,$price,$image,$description,$modified);
$stmt->store_result();

if(isset($_POST['check']) && !empty($_POST['check'])){
  $jsonToCheck = json_decode($_POST['check']);
  $values = array();
  foreach($jsonToCheck as $item) {
    $idClient = $item->id;
    $modifiedClient = $item->modified;
    $values[$idClient]=$modifiedClient;
  }
}


//Create titles array from DB results
$roomTypeArray = array();
while($stmt->fetch()){
  if(isset($values[$id])){
    $timeInDB = strtotime($modified);
    $timeInClient = strtotime($values[$id]);
    if(!($timeInDB>$timeInClient)){
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


//If there are not roomTypes return error
$numrows = $stmt->num_rows;
if($numrows == 0){
  $jObj->success = 0;
  $jObj->error = "There are no roomTypes available";
}
else{
  $jObj->success = 1;
  $jObj->roomTypeArray = $roomTypeArray;
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;

$stmt->close();
$mysqli->close();

?>
