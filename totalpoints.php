<?php

require 'dbConfig.php';
require 'Functions/addpoints.php';

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");
//Response Object
$jObj = new stdClass();

if(isset($_POST['customerID'])){

  $customerID=$_POST['customerID'];

  $points = getPointsByCustomerID($dbCon,$customerID);

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
