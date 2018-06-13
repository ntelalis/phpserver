<?php

ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);

require 'dbConfig.php';
require 'Functions/addpoints.php';

$_POST['customerID']=23;

//Connection to Database
$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");
//Response Object
$jObj = new stdClass();

if(isset($_POST['customerID'])){

  $customerID=$_POST['customerID'];

  $points = getPointsByCustomerID($dbCon,$customerID);
  if(isset($points)){
    $jObj->success=1;
    $jObj->points=$points;
  }
  else{
    $jObj->success=0;
    $jObj->errorMessage="There is a problem getting customer points";
  }
  $dbCon->close();
}
else{
  $jObj->success=0;
  $jObj->errorMessage="Wrong parameters";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);

echo $JsonResponse;
