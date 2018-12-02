<?php

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

//$_POST['beaconRegionIDJsonArray'] = "[13,16,17,18,28]";
//$_POST['check']= '[{"id":1,"modified":"2019-01-01"},{"id":2,"modified":"2019-01-01"},{"id":3,"modified":"2019-01-01"},{"id":6,"modified":"2012-01-01"}]';

if (isset($_POST['beaconRegionIDJsonArray'])) {
    $beaconRegionIDArray = json_decode($_POST['beaconRegionIDJsonArray']);
    //create an array of '?' with size of $idArray length (how many keys are sent)
    $questionMarkArray = array_fill(0, count($beaconRegionIDArray), '?');
    //create a string like '?,?,?,?' from the array above
    $questionMarks = implode(',', $questionMarkArray);
    //create a string for bind_param types like "iiii"
    $paramTypes = str_repeat("i", count($beaconRegionIDArray));

    $query = "SELECT rf.ID, rf.RegionID, f.Feature, rf.Modified
            FROM BeaconRegionFeature rf JOIN BeaconFeature f ON rf.FeatureID=f.ID
            WHERE RegionID IN ($questionMarks)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($paramTypes, ...$beaconRegionIDArray);
    $stmt->execute();
    $stmt->bind_result($id, $regionID, $feature, $modified);
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


    $beaconRegionFeatureArray = array();
    while ($stmt->fetch()) {
        if (isset($values[$id])) {
            $timeInDB = strtotime($modified);
            $timeInClient = strtotime($values[$id]);
            unset($values[$id]);
            if ($timeInDB==$timeInClient) {
                continue;
            }
        }

        $beaconRegionFeature = new stdClass();
        $beaconRegionFeature->id = $id;
        $beaconRegionFeature->regionID = $regionID;
        $beaconRegionFeature->feature = $feature;
        $beaconRegionFeature->modified = $modified;
        $beaconRegionFeatureArray[] = $beaconRegionFeature;
    }

    foreach ($values as $key => $value) {
        $beaconRegionFeature = new stdClass();
        $beaconRegionFeature->id = $key;
        $beaconRegionFeature->modified = null;
        $beaconRegionFeatureArray[]=$beaconRegionFeature;
    }
    $stmt->close();
    $mysqli->close();

    $jObj = new stdClass();
    $jObj->success = 1;
    $jObj->beaconRegionFeatureArray = $beaconRegionFeatureArray;
} else {
    $jObj->success = 0;
    //$jObj->errorMessage = "Bad request";
    $jObj->errorMessage = $_POST['beaconRegionIDJsonArray'];
}

    $JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
    echo $JsonResponse;
