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
//$_POST['beaconRegionArray'] = "[13,16,17,18,28]";
//$_POST['check']= '[{"id":1,"modified":"2018-07-26 20:55:46"},{"id":2,"modified":"2018-07-26 20:55:46"},{"id":3,"modified":"2018-07-26 20:55:46"},{"id":6,"modified":"2018-07-26 20:55:46"}]';

//Check input data
if (isset($_POST['beaconRegionArray'])) {

    //Response array
    $beaconRegionFeatureArray = array();

    //Check if array isn't empty
    if ($_POST['beaconRegionArray']!="[]") {

        //decode input intto object
        $beaconRegionArray = json_decode($_POST['beaconRegionArray']);
        //create an array of '?' with size of $idArray length (how many keys are sent)
        $questionMarkArray = array_fill(0, count($beaconRegionArray), '?');
        //create a string like '?,?,?,?' from the array above
        $questionMarks = implode(',', $questionMarkArray);
        //create a string for bind_param types like "iiii"
        $paramTypes = str_repeat("i", count($beaconRegionArray));

        //Get region features
        $query = "SELECT rf.ID, rf.RegionID, f.Feature, rf.Modified
                  FROM BeaconRegionFeature rf JOIN BeaconFeature f ON rf.FeatureID=f.ID
                  WHERE RegionID IN ($questionMarks)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param($paramTypes, ...$beaconRegionArray);
        $stmt->execute();
        $stmt->bind_result($id, $regionID, $feature, $modified);
        $stmt->store_result();


        //Check if customer has updated data for his beacon features

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

        //fetch server data row by row
        while ($stmt->fetch()) {
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

            //build the region feature
            $beaconRegionFeature = new stdClass();
            $beaconRegionFeature->id = $id;
            $beaconRegionFeature->regionID = $regionID;
            $beaconRegionFeature->feature = $feature;
            $beaconRegionFeature->modified = $modified;
            //add it to the array
            $beaconRegionFeatureArray[] = $beaconRegionFeature;
        }

        //for each row that was sent by the client and server didn't find
        //a match with his query to database
        foreach ($values as $key => $value) {
            //add it to response array but only set modified date with null value
            //so the client will delete it from his list
            $beaconRegionFeature = new stdClass();
            $beaconRegionFeature->id = $key;
            $beaconRegionFeature->modified = null;
            $beaconRegionFeatureArray[]=$beaconRegionFeature;
        }
        //Close Connection to DB
        $stmt->close();
        $mysqli->close();
    }
    //Build the json response
    $jObj->success = 1;
    $jObj->beaconRegionFeatureArray = $beaconRegionFeatureArray;
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad Request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
