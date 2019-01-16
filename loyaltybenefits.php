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
//$_POST['customerID'] = 23;

if (isset($_POST['customerID'])) {
    $customerID = $_POST['customerID'];

    //Get LoyaltyBenefits for this customer
    $query = "SELECT lb.Name FROM LoyaltyTierBenefits ltb inner join LoyaltyBenefits lb on ltb.BenefitID=lb.ID WHERE TierID=getTierIDByCustomerID(?);";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($benefit);
    $stmt->store_result();

    $benefits = array();
    while ($stmt->fetch()) {
        $benefits[]=$benefit;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();

    //Build the json response
    $jObj->success = 1;
    $jObj->benefits = $benefits;
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
