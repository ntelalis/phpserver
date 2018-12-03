<?php

//DEBUG

/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST["modified"] = '201,2018-11-18 23:24:26';

if (isset($_POST['modified'])) {

    $modifiedClient = $_POST["modified"];

    $query = "  SELECT CONCAT(COUNT(ID),',',MAX(Modified)) AS Modified
                FROM Country";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $stmt->bind_result($modifiedDB);
    $stmt->store_result();
    $stmt->fetch();
}

if(!is_null($modifiedClient) && $modifiedClient==$modifiedDB){
    $jObj->success = 1;
}
else{
    //Get all countries
    $query = "  SELECT ID, Name, Modified
                FROM Country";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $stmt->bind_result($countryID, $countryName, $countryModified);
    $stmt->store_result();

    $numrows = $stmt->num_rows;

    //If there are no countries return error
    if ($numrows == 0) {
        $jObj->success = 0;
        $jObj->errorMessage = "There are no countries available";
    }
    //Create countries array from DB results
    else {
        $countryArray = array();
        while ($stmt->fetch()) {
            $countryObj = new stdClass();
            $countryObj->id = $countryID;
            $countryObj->name = $countryName;
            $countryObj->modified = $countryModified;
            $countryArray[] = $countryObj;
        }

        //Close Connection to DB
        $stmt->close();
        $mysqli->close();

        $jObj->success = 1;
        $jObj->countryArray = $countryArray;
    }
}


//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
