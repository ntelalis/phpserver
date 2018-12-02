<?php

/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//$_POST['reservationID'] = '4';

if (isset($_POST['reservationID'])) {

  $reservationID = $_POST['reservationID'];

  $query ="SELECT v.ID, v.UniqueID, v.UUID, v.Major, v.Minor, v.Exclusive, v.Background, v.Modified
           FROM BeaconRegionRoom brr, Occupancy o, BeaconRegionView v
           WHERE brr.RoomID=o.RoomID AND o.ReservationID=? AND v.ID=brr.BeaconRegionID" ;
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();
  $stmt->bind_result($id, $uniqueID, $uuid, $major, $minor, $exclusive, $background, $modified);
  $stmt->store_result();

  if (isset($_POST['check']) && !empty($_POST['check'])) {
      $jsonToCheck = json_decode($_POST['check']);
      $values = array();
      foreach ($jsonToCheck as $item) {
          $idClient = $item->id;
          $modifiedClient = $item->modified;
          $values[$idClient]=$modifiedClient;
      }
  }


  $beaconRegionArray = array();
  while($stmt->fetch()){

    if (isset($values[$id])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        unset($values[$id]);
        if ($timeInDB==$timeInClient) {
            continue;
        }
    }

    $beaconRegion = new stdClass();
    $beaconRegion->id = $id;
    $beaconRegion->uniqueID = $uniqueID;
    $beaconRegion->uuid = $uuid;
    $beaconRegion->major = $major;
    $beaconRegion->minor = $minor;
    $beaconRegion->exclusive = $exclusive == 1;
    $beaconRegion->background = $background == 1;
    $beaconRegion->modified = $modified;
    $beaconRegionArray[] = $beaconRegion;
  }

  foreach($values as $key => $value){
    $beaconRegion = new stdClass();
    $beaconRegion->id = $key;
    $beaconRegion->modified = null;
    $beaconRegionArray[]=$beaconRegion;
  }


  $stmt->close();
  $mysqli->close();

  $jObj = new stdClass();
  $jObj->success = 1;
  $jObj->beaconRegionArray = $beaconRegionArray;

}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
