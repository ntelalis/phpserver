<?php

require 'dbConfig.php';

$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

$query = "SELECT ID,Name FROM Country";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($countryID, $countryName);
$stmt->store_result();

$countryList = array();
while ($stmt->fetch()) {
    $countryObj = new stdClass();
    $countryObj->id = $countryID;
    $countryObj->name = $countryName;
    $countryList[] = $countryObj;
}

  $jObj->success=1;
  $jObj->countryArray = $countryList;

$stmt->close();
$mysqli->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
