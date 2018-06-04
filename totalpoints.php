<?php 

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");
//Response Object
$jObj = new stdClass();



$query = "SELECT IFNULL((SELECT sum(a.Points*h.Quantity)
FROM LoyaltyPointsEarningHistory h JOIN LoyaltyPointsEarningAction a on h.GainingPointsID=a.ID WHERE h.CustomerID=? and h.DateEarned>=Now()-INTERVAL 1 YEAR),0) - IFNULL((SELECT sum(a.Points*h.Quantity) FROM LoyaltyPointsSpendingHistory h JOIN LoyaltyPointsSpendingAction a on h.SpendingPointsID=a.ID WHERE h.CustomerID=? and h.DateSpent>=Now()-INTERVAL 1 YEAR),0) as Points";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('ii', $customerID, $customerID);
$stmt->execute();
$stmt->bind_result($points);
$stmt->store_result();
$stmt->fetch();
