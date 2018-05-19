<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

if (isset($_POST['customerID'])) {
    $customerID = $_POST['customerID'];

    //Customer Info
    $query = "SELECT FirstName,LastName FROM Customer WHERE ID=?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName);
    $stmt->store_result();
    $stmt->fetch();

    //Total Points
    $query = "SELECT IFNULL((SELECT sum(a.Points*h.Quantity)
  FROM LoyaltyPointsEarningHistory h JOIN LoyaltyPointsEarningAction a on h.GainingPointsID=a.ID WHERE h.CustomerID=? and h.DateEarned>=Now()-INTERVAL 1 YEAR),0) - IFNULL((SELECT sum(a.Points*h.Quantity) FROM LoyaltyPointsSpendingHistory h JOIN LoyaltyPointsSpendingAction a on h.SpendingPointsID=a.ID WHERE h.CustomerID=? and h.DateSpent>=Now()-INTERVAL 1 YEAR),0) as Points";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('ii', $customerID, $customerID);
    $stmt->execute();
    $stmt->bind_result($points);
    $stmt->store_result();
    $stmt->fetch();

    //Current Tier
    $query = "SELECT ID,Name,MinimumPoints FROM LoyaltyTier WHERE MinimumPoints=(Select Max(MinimumPoints) From LoyaltyTier WHERE MinimumPoints<=?)";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $points);
    $stmt->execute();
    $stmt->bind_result($tierID, $tierName, $tierPoints);
    $stmt->store_result();
    $stmt->fetch();

    //Next Tier
    $query = "SELECT Name,IFNULL(MinimumPoints,0) FROM LoyaltyTier WHERE MinimumPoints=(SELECT MIN(MinimumPoints) FROM LoyaltyTier WHERE MinimumPoints>?)";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $tierPoints);
    $stmt->execute();
    $stmt->bind_result($nextTierName, $nextTierPoints);
    $stmt->store_result();
    $stmt->fetch();

    //Benefits
    $query = "SELECT lb.Name FROM LoyaltyTierBenefits ltb inner join LoyaltyBenefits lb on ltb.BenefitID=lb.ID WHERE TierID=?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('i', $tierID);
    $stmt->execute();
    $stmt->bind_result($benefit);
    $stmt->store_result();



    $benefits = array();
    while ($stmt->fetch()) {
        $benefits[]=$benefit;
    }

    $jObj->success=1;
    $jObj->firstName=$firstName;
    $jObj->lastName=$lastName;
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
    $jObj->tierBenefits = $benefits;

    $stmt->close();
    $dbCon->close();
} else {
    $jObj->success=0;
    $jObj->errorMessage="customerID not set";
}
$JsonResponse = json_encode($jObj);

echo $JsonResponse;
