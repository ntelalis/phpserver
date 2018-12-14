<?php

//DEBUG
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

//DEBUG
//$_POST['check'] = '[{"id":15,"modified":"2018-11-18 20:57:39"}]';

//Check if customer has updated data for his beacon regions

//Check if customer has any data for checking
if (isset($_POST['check']) && !empty($_POST['check'])) {
    //parse json to array
    $jsonToCheck = json_decode($_POST['check']);
    //initialize a hash array which will be filled with rows client knows about
    $values = array();
    //for each row customer has
    foreach ($jsonToCheck as $item) {
        //get the id and the modified date of the row client knows
        $idClient = $item->id;
        $modifiedClient = $item->modified;
        //add these data to the array in order to be checked
        $values[$idClient]=$modifiedClient;
    }
}


//Get all regions
$query = "SELECT v.ID, v.UniqueID, v.UUID, v.Major, v.Minor, v.Exclusive, v.Background, v.Modified
          FROM BeaconRegionView v
          ORDER BY ID";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $uniqueID, $uuid, $major, $minor, $exclusive, $background, $modified);
$stmt->store_result();

//initialize the array which will be sent to client
$beaconRegionArray = array();

//fetch server data row by row
while ($stmt->fetch()) {
    //if region is exclusive and client doesn't know about this region then skip it
    if ($exclusive==1 && !isset($values[$id])) {
        continue;
    }
    //check if client knows about this row by checking if this id
    //is found in array which was filled with client data
    if (isset($values[$id])) {
        //convert client's and server's timestamps to time
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        //remove this id from the client's array because it was found and compared
        unset($values[$id]);
        //if client has latest data skip the row and continue to next one
        if ($timeInDB==$timeInClient) {
            continue;
        }
    }

    //build the region
    $beaconRegion = new stdClass();
    $beaconRegion->id = $id;
    $beaconRegion->uniqueID = $uniqueID;
    $beaconRegion->uuid = $uuid;
    $beaconRegion->major = $major;
    $beaconRegion->minor = $minor;
    $beaconRegion->background = $background == 1;
    $beaconRegion->modified = $modified;
    //add it to the array
    $beaconRegionArray[] = $beaconRegion;
}

//for each row that was sent by the client and server didn't find
//a match with his query to database
foreach ($values as $key => $value) {
    //add it to response array but only set modified date with null value
    //so the client will delete it from his list
    $beaconRegion = new stdClass();
    $beaconRegion->id = $key;
    $beaconRegion->modified = null;
    $beaconRegionArray[] = $beaconRegion;
}


//Close Connection to DB
$stmt->close();
$mysqli->close();

//Build the json response
$jObj->success = 1;
$jObj->beaconRegionArray = $beaconRegionArray;


//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
