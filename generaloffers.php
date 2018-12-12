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
//$_POST['check'] = '[{"id":12,"modified":"2018-08-18 13:12:29"}]';

//Get general offers from the database
$query = "SELECT og.ID, o.Price, o.Discount, o.Title, o.Description, o.Details, og.StartDate, og.EndDate, GREATEST(o.Modified,og.Modified) AS Modified
          FROM OfferGeneral og, Offer o
          WHERE og.OfferID=o.ID";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $price, $discount, $title, $description, $details, $startDate, $endDate, $modified);
$stmt->store_result();

//Check if customer has updated data for general offers

//Check if customer has any data for checking
if (isset($_POST['check']) && !empty($_POST['check'])) {
    //parse json to array
    $jsonToCheck = json_decode($_POST['check']);
    //initialize a hash array which will be filled with reservations client knows about
    $values = array();
    //for each general offer customer has
    foreach ($jsonToCheck as $item) {
        //get the id and the modified date of the general offer client knows
        $idClient = $item->id;
        $modifiedClient = $item->modified;
        //add these data to the array in order to be checked
        $values[$idClient]=$modifiedClient;
    }
}

//init the array response
$generalOfferArray = array();

//check if client knows about this general offer by checking if this general offer id
    //is found in array which was filled with client data
    //
    while($stmt->fetch()){
    if (isset($values[$id])) {
        //convert client's and server's timestamps to time
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        //remove this id from the client's array because it was found and compared
        unset($values[$id]);
        //if client has latest data skip the general offer and continue to next one
        if ($timeInDB==$timeInClient) {
            continue;
        }
    }

    //if client doesnt know about this general offer or he hasn't the latest data
    //add the general offer to the response array

    $generalOffer = new stdClass();
    $generalOffer->id = $id;

    //find if discount or fixed price
    if($price!=null){
        $generalOffer->priceDiscount = $price."€";
    }
    else{
        $generalOffer->priceDiscount = ($discount*100)."%";
    }
    $generalOffer->title = $title;
    $generalOffer->description = $description;
    $generalOffer->details = $details;
    $generalOffer->startDate = $startDate;
    $generalOffer->endDate = $endDate;
    $generalOffer->modified = $modified;
    $generalOfferArray[] = $generalOffer;
}

//for each general offer that was sent by the client and server didn't find
//a match with his query to database
foreach ($values as $key => $value) {
    //add it to response array but only set modified date with null value
    //so the client will delete it from his list
    $generalOffer = new stdClass();
    $generalOffer->id = $key;
    $generalOffer->modified = null;
    $generalOfferArray[]=$generalOffer;
}

//Close Connection to DB
$stmt->close();
$mysqli->close();

//Build the json response
$jObj->success = 1;
$jObj->generalOfferArray = $generalOfferArray;

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
