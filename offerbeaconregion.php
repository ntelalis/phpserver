<?php
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

$_POST['offerIDJsonArray'] = "[2,3]";
//$_POST['check']= '[{"id":1,"modified":"2019-01-01"},{"id":2,"modified":"2019-01-01"},{"id":3,"modified":"2019-01-01"},{"id":6,"modified":"2012-01-01"}]';

if (isset($_POST['offerIDJsonArray'])) {
    $offerIDArray = json_decode($_POST['offerIDJsonArray']);
    //create an array of '?' with size of $idArray length (how many keys are sent)
    $questionMarkArray = array_fill(0, count($offerIDArray), '?');
    //create a string like '?,?,?,?' from the array above
    $questionMarks = implode(',', $questionMarkArray);
    //create a string for bind_param types like "iiii"
    $paramTypes = str_repeat("i", count($offerIDArray));

    $query = "SELECT obr.ID, obr.BeaconMonitoredRegionID, obr.HotelServicesOfferID, obr.Modified
              FROM OfferBeaconRegion obr
              WHERE obr.HotelServicesOfferID IN ($questionMarks)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($paramTypes, ...$offerIDArray);
    $stmt->execute();
    $stmt->bind_result($id, $regionID, $offerID, $modified);
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


    $offerBeaconRegionFeatureArray = array();
    while ($stmt->fetch()) {
        if (isset($values[$id])) {
            $timeInDB = strtotime($modified);
            $timeInClient = strtotime($values[$id]);
            unset($values[$id]);
            if ($timeInDB==$timeInClient) {
                continue;
            }
        }

        $offerBeaconRegionFeature = new stdClass();
        $offerBeaconRegionFeature->id = $id;
        $offerBeaconRegionFeature->regionID = $regionID;
        $offerBeaconRegionFeature->offerID = $offerID;
        $offerBeaconRegionFeature->modified = $modified;
        $offerBeaconRegionFeatureArray[] = $offerBeaconRegionFeature;
    }

    foreach ($values as $key => $value) {
        $offerBeaconRegionFeature = new stdClass();
        $offerBeaconRegionFeature->id = $key;
        $offerBeaconRegionFeature->modified = null;
        $offerBeaconRegionFeatureArray[]=$offerBeaconRegionFeature;
    }
    $stmt->close();
    $mysqli->close();

    $jObj = new stdClass();
    $jObj->success = 1;
    $jObj->offerBeaconRegionFeatureArray = $offerBeaconRegionFeatureArray;
} else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad request";
    //$jObj->errorMessage = $_POST['offerIDJsonArray'];
}

    $JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
    echo $JsonResponse;
