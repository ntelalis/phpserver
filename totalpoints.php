<?php

require 'dbConfig.php';
require 'Functions/addpoints.php';

//Connection to Database
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");
//Response Object
$jObj = new stdClass();

if(isset($_POST['customerID'])){

  $customerID=$_POST['customerID'];

  $points = getPointsByCustomerID($mysqli,$customerID);
  if(isset($points)){
    $jObj->success=1;
    $jObj->points=$points;
  }
  else{
    $jObj->success=0;
    $jObj->errorMessage="There is a problem getting customer points";
  }
  $mysqli->close();
}
else{
  $jObj->success=0;
  $jObj->errorMessage="Wrong parameters";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);

echo $JsonResponse;
