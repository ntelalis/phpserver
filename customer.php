<?php

/*ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//$_POST['customerID'] = '23';
//$_POST['modified'] = '2018-06-19 23:25:03';

if (isset($_POST['customerID'],$_POST['modified'])) {

$customerID = $_POST['customerID'];
$customerModified = $_POST['modified'];

// Select available titles from Database
$query = "SELECT ID,TitleID,FirstName,LastName,BirthDate,Email,CountryID,AdditionalInfo,Modified FROM Customer WHERE Customer.ID=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i',$customerID);
$stmt->execute();
$stmt->bind_result($id, $titleID, $firstName, $lastName, $birthDate, $email, $countryID, $additionalInfo, $modified);
$stmt->store_result();
$numrows = $stmt->num_rows;
$stmt->fetch();

if($numrows == 0){
  $jObj->success = 0;
  $jObj->errorMessage = "Error";
}
else{
  $timeInDB = strtotime($modified);
  $timeInClient = strtotime($customerModified);
  if ($timeInDB>$timeInClient) {
    $customer = new stdClass();
    $customer->id = $id;
    $customer->titleID = $titleID;
    $customer->firstName = $firstName;
    $customer->lastName = $lastName;
    $customer->birthDate = $birthDate;
    $customer->email = $email;
    $customer->countryID = $countryID;
    $customer->additionalInfo = $additionalInfo;
    $customer->modified = $modified;
    $jObj->success = 1;
    $jObj->customer = $customer;
  }
  else{
    $jObj->success = 1;
  }
}
$stmt->close();
$mysqli->close();

}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
