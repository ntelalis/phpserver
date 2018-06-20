<?php

/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

$_POST['arg1'] = '1';

if (isset($_POST['arg1'])) {

  $arg1 = $_POST['arg1'];

  $query = "SELECT Col1,Col2 FROM Table WHERE Table.Col3=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i',$arg1);
  $stmt->execute();
  $stmt->bind_result($col1,$col2);
  $stmt->store_result();

  $stmt->fetch();

  $stmt->close();
  $mysqli->close();

  if($stmt->num_rows == 0){
    $jObj->success = 0;
    $jObj->errorMessage = "Error";
  }
  else{
    $jObj->success = 1;
  }
}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";

}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
