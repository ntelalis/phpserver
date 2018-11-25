<?php

/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';
require 'Functions/addpoints.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

$_POST['customerID'] = 23;
//$_POST['modified'] = "1994-03-30 07:25:04";

if (isset($_POST['customerID'])) {
    $customerID = $_POST['customerID'];

    //Get Points And ModifiedDate
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
    $stmt->bind_result($points, $modified);
    $stmt->store_result();
    $stmt->fetch();

    $shouldUpdate = true;
    if (isset($_POST['modified'])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($_POST['modified']);
        if ($timeInDB==$timeInClient) {
            $shouldUpdate = false;
        }
    }
    if ($shouldUpdate) {

    //Current Tier
        $query = "SELECT ID,Name,MinimumPoints FROM LoyaltyTier WHERE MinimumPoints=(Select Max(MinimumPoints) From LoyaltyTier WHERE MinimumPoints<=?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $points);
        $stmt->execute();
        $stmt->bind_result($tierID, $tierName, $tierPoints);
        $stmt->store_result();
        $stmt->fetch();

        //Next Tier
        $query = "SELECT Name,IFNULL(MinimumPoints,0) FROM LoyaltyTier WHERE MinimumPoints=(SELECT MIN(MinimumPoints) FROM LoyaltyTier WHERE MinimumPoints>?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $tierPoints);
        $stmt->execute();
        $stmt->bind_result($nextTierName, $nextTierPoints);
        $stmt->store_result();
        $stmt->fetch();

        $jObj->success=1;
        $jObj->customerID=$customerID;
        if ($points<0) {
            $jObj->points=0;
        } else {
            $jObj->points=$points;
        }

        if (is_null($tierPoints)) {
            $jObj->success=0;
            $jObj->errorMessage="Customer Not In Tier";
        } else {
            $jObj->tierName=$tierName;
            $jObj->tierPoints=$tierPoints;
        }
        if (is_null($nextTierPoints)) {
            $jObj->nextTierName="";
            $jObj->nextTierPoints=0;
        } else {
            $jObj->nextTierName=$nextTierName;
            $jObj->nextTierPoints=$nextTierPoints;
        }
        $jObj->modified = $modified;
        $stmt->close();
        $mysqli->close();
    } else {
      $jObj->success=1;
    }
} else {
    $jObj->success=0;
    $jObj->errorMessage="customerID not set";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
