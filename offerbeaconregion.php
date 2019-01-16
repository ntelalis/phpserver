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
//$_POST['offerArray'] = "[2,3]";
//$_POST['check']= '[{"id":1,"modified":"2018-10-25 15:51:58"},{"id":2,"modified":"2019-01-01"},{"id":3,"modified":"2019-01-01"}]';

if (isset($_POST['offerArray'])) {
    $offerArray = json_decode($_POST['offerArray']);
    //check if array is empty
    if(!empty($offerArray)){
      //create an array of '?' with size of $idArray length (how many keys are sent)
      $questionMarkArray = array_fill(0, count($offerArray), '?');
      //create a string like '?,?,?,?' from the array above
      $questionMarks = implode(',', $questionMarkArray);
      //create a string for bind_param types like "iiii"
      $paramTypes = str_repeat("i", count($offerArray));

      $query = "SELECT bro.ID, bro.BeaconRegionID, bro.OfferExclusiveID, bro.Modified
                FROM BeaconRegionOffer bro
                WHERE bro.OfferExclusiveID IN ($questionMarks)";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param($paramTypes, ...$offerArray);
      $stmt->execute();
      $stmt->bind_result($id, $regionID, $offerID, $modified);
      $stmt->store_result();

      //Check if customer has updated data for his offerregions

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


      //initialize the array which will be sent to client
      $beaconRegionOfferArray = array();

      //fetch server data row by row
      while ($stmt->fetch()) {

      //check if client knows about this row by checking if this id
          //is found in array which was filled with client data
          //
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

          $beaconRegionOffer = new stdClass();
          $beaconRegionOffer->id = $id;
          $beaconRegionOffer->regionID = $regionID;
          $beaconRegionOffer->offerID = $offerID;
          $beaconRegionOffer->modified = $modified;
          $beaconRegionOfferArray[] = $beaconRegionOffer;
      }

      //for each row that was sent by the client and server didn't find
      //a match with his query to database
      foreach ($values as $key => $value) {
          //add it to response array but only set modified date with null value
          //so the client will delete it from his list
          $beaconRegionOffer = new stdClass();
          $beaconRegionOffer->id = $key;
          $beaconRegionOffer->modified = null;
          $beaconRegionOfferArray[]=$beaconRegionOffer;
      }
      //Close Connection to DB
      $stmt->close();
      $mysqli->close();

      //Build the json response
      $jObj->success = 1;
      $jObj->beaconRegionOfferArray = $beaconRegionOfferArray;
    }
    else{
      $jObj->success = 0;
      $jObj->errorMessage = "No offers supplied";
    }
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
