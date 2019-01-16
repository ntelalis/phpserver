<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'Functions/addpoints.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['customerID'] = 23;
//$_POST['modified'] = "2018-09-19 21:29:34";

if (isset($_POST['customerID'])) {
    $customerID = $_POST['customerID'];
    $modifiedClient = $_POST['modified'];

    //Get Customer points And ModifiedDate of points
    $query = "SELECT (lpeh.Earned-lpsh.Spent) As Points, FROM_UNIXTIME(SUM(lpeh.EarnedDate+lpsh.SpentDate)/2,'%Y-%m-%d %H:%i:%s') As ModifiedDate
              FROM
                  (SELECT IFNULL(SUM(lpeh.Points),0) as Earned, IFNULL(AVG(UNIX_TIMESTAMP(DateEarned)),0) as EarnedDate
                   FROM LoyaltyPointsEarningHistory lpeh
                   WHERE lpeh.CustomerID = ? AND lpeh.DateEarned>=NOW()-INTERVAL 1 YEAR) lpeh,
                  (SELECT IFNULL(SUM(lpsh.Points),0) as Spent, IFNULL(AVG(UNIX_TIMESTAMP(DateSpent)),0) as SpentDate
                   FROM LoyaltyPointsSpendingHistory lpsh
                   WHERE lpsh.CustomerID = ? AND lpsh.DateSpent>=NOW()-INTERVAL 1 YEAR) lpsh
              GROUP BY lpeh.Earned, lpsh.Spent";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $customerID, $customerID);
    $stmt->execute();
    $stmt->bind_result($points, $modifiedDB);
    $stmt->store_result();
    $stmt->fetch();

    //Check if client is updated
    if (!is_null($modifiedClient) && $modifiedClient==$modifiedDB) {
        $jObj->success = 1;
    } else {

        //Current tier info
        $query = "SELECT ID,Name,MinimumPoints FROM LoyaltyTier WHERE MinimumPoints=(Select Max(MinimumPoints) From LoyaltyTier WHERE MinimumPoints<=?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $points);
        $stmt->execute();
        $stmt->bind_result($tierID, $tierName, $tierPoints);
        $stmt->store_result();
        $stmt->fetch();

        //Next tier info
        $query = "SELECT Name,IFNULL(MinimumPoints,0) FROM LoyaltyTier WHERE MinimumPoints=(SELECT MIN(MinimumPoints) FROM LoyaltyTier WHERE MinimumPoints>?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $tierPoints);
        $stmt->execute();
        $stmt->bind_result($nextTierName, $nextTierPoints);
        $stmt->store_result();
        $stmt->fetch();

        //Build the json response
        $jObj->success=1;
        //Set his points
        if ($points<0) {
            $jObj->points=0;
        } else {
            $jObj->points=intval($points);
        }

        if (is_null($tierPoints)) {
            //Customer not in a Tier
            $jObj->success=0;
            $jObj->errorMessage="Customer Not In Tier";
        } else {
            //Current tier info
            $jObj->tierName=$tierName;
            $jObj->tierPoints=$tierPoints;
        }
        if (is_null($nextTierPoints)) {
            //Next tier Not found
            $jObj->nextTierName="";
            $jObj->nextTierPoints=0;
        } else {
            //Next tier info
            $jObj->nextTierName=$nextTierName;
            $jObj->nextTierPoints=$nextTierPoints;
        }
        $jObj->modified = $modifiedDB;

        //Close Connection to DB
        $stmt->close();
        $mysqli->close();
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
