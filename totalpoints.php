<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");
//Response Object
$jObj = new stdClass();

if(isset($_POST['customerID'])){

  $customerID=$_POST['customerID'];

  $query = "SELECT IFNULL((SELECT sum(a1.Points*h.Quantity)
  FROM LoyaltyPointsEarningHistory h JOIN LoyaltyPointsEarningAction a1 on h.GainingPointsID=a1.ID WHERE h.CustomerID=? and h.DateEarned>=Now()-INTERVAL 1 YEAR),0) - IFNULL((SELECT sum(a2.Points*h.Quantity) FROM LoyaltyPointsSpendingHistory h JOIN LoyaltyPointsSpendingActionRoomType a2 on h.SpendingPointsID=a2.ID WHERE h.CustomerID=? and h.DateSpent>=Now()-INTERVAL 1 YEAR),0) as Points";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('ii', $customerID, $customerID);
  $success=$stmt->execute();
  $stmt->bind_result($points);
  $stmt->store_result();
  $stmt->fetch();


  if($success){
    $jObj->success=1;
    $jObj->points=$points;
  }
  else{
    $jObj->success=0;
    $jObj->errorMessage="Problem with getting points";
  }
  $stmt->close();
  $dbCon->close();
}
else{
  $jObj->success=0;
  $jObj->errorMessage="Wrong parameters";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);

echo $JsonResponse;
