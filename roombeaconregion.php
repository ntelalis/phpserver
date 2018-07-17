<?php

/*ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

 $_POST['reservationID'] = '4';

if (isset($_POST['reservationID'])) {

  $reservationID = $_POST['reservationID'];

  $query ="SELECT v.ID, v.UniqueID, v.UUID, v.Major, v.Minor, v.Exclusive, v.Background, v.RegionType, v.Modified
           FROM BeaconMonitoredRegionRoom bmr, Occupancy o, BeaconRegionView v
           WHERE bmr.RoomID=o.RoomID AND o.ReservationID=? AND v.ID=bmr.BeaconRegionID" ;
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i',$reservationID);
  $stmt->execute();
  $stmt->bind_result($id, $uniqueID, $uuid, $major, $minor, $exclusive, $background, $regionType, $modified);
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


  $roomBeaconRegionArray = array();
  while($stmt->fetch()){

    if (isset($values[$id])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        unset($values[$id]);
        if (!($timeInDB>$timeInClient)) {
            continue;
        }
    }

    $roomBeaconRegion = new stdClass();
    $roomBeaconRegion->id = $id;
    $roomBeaconRegion->uniqueID = $uniqueID;
    $roomBeaconRegion->uuid = $uuid;
    $roomBeaconRegion->major = $major;
    $roomBeaconRegion->minor = $minor;
    $roomBeaconRegion->exclusive = $exclusive;
    $roomBeaconRegion->background = $background;
    $roomBeaconRegion->regionType = $regionType;
    $roomBeaconRegion->modified = $modified;
    $roomBeaconRegionArray[] = $roomBeaconRegion;
  }

  foreach($values as $key => $value){
    $roomBeaconRegion = new stdClass();
    $roomBeaconRegion->id = $key;
    $roomBeaconRegion->modified = null;
    $roomBeaconRegionArray[]=$roomBeaconRegion;
  }


  $stmt->close();
  $mysqli->close();

  $jObj = new stdClass();
  $jObj->success = 1;
  $jObj->roomBeaconRegionArray = $roomBeaconRegionArray;

}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
