<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

$query = "SELECT Name FROM Country";
$stmt = $dbCon->prepare($query);
$stmt->execute();
$stmt->bind_result($countryName);
$stmt->store_result();

$countryList = array();
while ($stmt->fetch()) {
    $countryList[] = $countryName;
}

  $jObj->success=1;
  $jObj->countryArray = $countryList;

$stmt->close();
$dbCon->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
