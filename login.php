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

//$_POST['email'] = 'kate@gmail.com';
//$_POST['pass'] = 'asdF12!@';
//$_POST['modified'] = '2018-06-19 23:25:06';

if(isset($_POST['email'],$_POST['pass'])){

	$email = $_POST['email'];
	$pass = $_POST['pass'];

	$query = "SELECT a.CustomerID,a.Hash,c.Modified FROM Account a, Customer c WHERE a.Email = ? AND c.ID=a.CustomerID";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('s', $email);
	$stmt->execute();
	$stmt->bind_result($customerID,$hash,$modifiedDB);
	$stmt->store_result();
	$stmt->fetch();

	if ($stmt->num_rows == 1 && password_verify($pass, $hash)) {

		$jObj = new stdClass();

		$jObj->success = 1;

		if(isset($_POST['modified'])){
			$modifiedClient = $_POST['modified'];
			$timeInDB = strtotime($modifiedDB);
			$timeInClient = strtotime($modifiedClient);
		}

		if (!isset($modifiedClient) || $timeInDB>$timeInClient) {
			$query = "SELECT c.TitleID, c.FirstName, c.LastName, c.BirthDate, c.CountryID, c.Modified, (SELECT COUNT(o.ID)
																																											FROM Occupancy o, Reservation r
																																											WHERE r.CustomerID=c.ID AND o.ReservationID=r.ID
																																										  AND o.CheckOut IS NOT NULL) as finishedStays
			FROM Customer c
			WHERE c.ID = ?";
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param('i', $customerID);
			$stmt->execute();
			$stmt->bind_result($titleID, $firstName, $lastName, $birthDate, $countryID, $modified, $finishedStays);
			$stmt->store_result();
			$stmt->fetch();

			$jObj->customerID = $customerID;
			$jObj->titleID = $titleID;
			$jObj->firstName = $firstName;
			$jObj->lastName = $lastName;
			$jObj->birthDate = $birthDate;
			$jObj->countryID = $countryID;
			$jObj->modified = $modified;
			if($finishedStays>0){
				$jObj->oldCustomer = true;
			}
			else {
				$jObj->oldCustomer = false;
			}
		}
	}
	else{
		$jObj->success = 0;
		$jObj->errorMessage = "Login failed";
	}
}
else{
	$jObj->success = 0;
	$jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);

echo $JsonResponse;
