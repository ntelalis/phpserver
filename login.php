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

$_POST['email'];// = 'kate@gmail.com';
$_POST['pass'];// = 'asdF12!@';
$_POST['modified'];// = '2018-06-19 23:25:06';

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

		if (!isset($modifiedClient) || $timeInDB!=$timeInClient) {
			$query = "SELECT t.Title, c.FirstName, c.LastName, c.BirthDate, co.Name, ci.Address1, ci.Address2, ci.City, ci.PostalCode, ci.Phone,
															(SELECT COUNT(o.ID)
															 FROM Occupancy o, Reservation r
															 WHERE r.CustomerID = c.ID AND o.ReservationID = r.ID AND o.CheckOut IS NOT NULL) AS finishedStays,
															 GREATEST(c.Modified,IFNULL(ci.Modified,0)) AS Modified
			 					FROM Country co, Title t, Customer c LEFT JOIN ContactInfo ci ON c.ID=ci.CustomerID
								WHERE c.ID = ? AND co.ID=c.CountryID AND t.ID=c.TitleID";
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param('i', $customerID);
			$stmt->execute();
			$stmt->bind_result($title, $firstName, $lastName, $birthDate, $country, $address1, $address2, $city, $postalCode, $phone, $finishedStays, $modified);
			$stmt->store_result();
			$stmt->fetch();

			$jObj->customerID = $customerID;
			$jObj->title = $title;
			$jObj->firstName = $firstName;
			$jObj->lastName = $lastName;
			$jObj->birthDate = $birthDate;
			$jObj->country = $country;
			$jObj->phone = $phone;
			$jObj->address1 = $address1;
			$jObj->address2 = $address2;
			$jObj->city = $city;
			$jObj->postalCode = $postalCode;
			$jObj->oldCustomer = $finishedStays != 0;
			$jObj->modified = $modified;
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
