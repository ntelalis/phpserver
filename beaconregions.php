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


  $query = "SELECT v.ID, v.UniqueID, v.UUID, v.Major, v.Minor, v.Modified
            FROM BeaconRegionView v
            WHERE v.exclusive=0";
  $stmt = $mysqli->prepare($query);
  $stmt->execute();
  $stmt->bind_result($id, $uniqueID, $uuid, $major, $minor, $modified);
  $stmt->store_result();

  if (isset($_POST['regionsCheck']) && !empty($_POST['regionsCheck'])) {
      $jsonToCheck = json_decode($_POST['regionsCheck']);
      $values = array();
      foreach ($jsonToCheck as $item) {
          $idClient = $item->id;
          $modifiedClient = $item->modified;
          $values[$idClient]=$modifiedClient;
      }
  }


  $beaconRegionsArray = array();
  while($stmt->fetch()){

    if (isset($values[$id])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        unset($values[$id]);
        if (!($timeInDB>$timeInClient)) {
            continue;
        }
    }

    $beaconRegions = new stdClass();
    $beaconRegions->id = $id;
    $beaconRegions->uniqueID = $uniqueID;
    $beaconRegions->uuid = $uuid;
    $beaconRegions->major = $major;
    $beaconRegions->minor = $minor;
    $beaconRegions->modified = $modified;
    $beaconRegionsArray[] = $beaconRegions;
  }

  foreach($values as $key => $value){
    $beaconRegions = new stdClass();
    $beaconRegions->id = $key;
    $beaconRegions->modified = null;
    $beaconRegionsArray[] = $beaconRegions;
  }


  $stmt->close();
  $mysqli->close();

  $jObj = new stdClass();
  $jObj->success = 1;
  $jObj->beaconRegionsArray = $beaconRegionsArray;


$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
