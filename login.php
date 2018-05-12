<?php


include 'dbConfig.php';

$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

$email = $_POST['email'];
$pass = $_POST['pass'];

/*
$query = "SELECT c.CustomerID,(Select Title from Title where Title.ID=c1.TitleID) as Title, c1.FirstName, c1.LastName, c.Hash
          FROM (SELECT a.CustomerID,a.Hash
                FROM Account a
                WHERE Email = ?) c,Customer c1
          WHERE c.CustomerID = c1.ID";
*/
$query = "SELECT c.CustomerID,(Select Title from Title where Title.ID=c1.TitleID) as Title, c1.FirstName, c1.LastName, c.Hash, (SELECT COUNT(Occupancy.ID)
																													                                                                      FROM Occupancy, Reservation
																													                                                                      WHERE Occupancy.ReservationID=Reservation.ID
                                                                                                                                      AND Reservation.CustomerID=c.CustomerID
                                                                                                                                      AND Occupancy.CheckOut IS NOT NULL) AS isOldCustomer
          FROM (SELECT a.CustomerID,a.Hash
                FROM Account a
                WHERE Email = ?) c,Customer c1
          WHERE c.CustomerID = c1.ID";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('s',$email);
$stmt->execute();
$stmt->bind_result($customer,$title,$firstName,$lastName,$hash,$isOldCustomer);
$stmt->store_result();
$stmt->fetch();

$numrows = $stmt->num_rows;
//
//eligible for checkout
//set is_checkedOut: false, is_checkedIn: true
$query = "SELECT Occupancy.CheckOut
FROM Reservation, Occupancy
WHERE Occupancy.ReservationID=Reservation.ID AND Reservation.CustomerID=?
                                            AND Reservation.StartDate<=CURRENT_DATE
                                            AND Reservation.EndDate>=CURRENT_DATE";
$stmt = $dbCon->prepare($query);
$stmt->bind_param('i',$customer);
$stmt->execute();
$stmt->bind_result($checkOut);
$stmt->store_result();
$stmt->fetch();


if($stmt->num_rows==0){
	$isCheckedIn = false;
	$isCheckedOut = false;
}
else{
	$isCheckedIn = true;
	if(is_null($checkOut)){
		$isCheckedOut=false;
	}
	else{
		$isCheckedOut=true;
	}
}
/*
//set is_checkedOut:true, is_checkedIn:true
"SELECT COUNT(Occupancy.ID)
FROM Reservation, Occupancy
WHERE Occupancy.ReservationID=Reservation.ID AND Reservation.CustomerID=23
                                            AND Reservation.StartDate<=CURRENT_DATE
                                            AND Reservation.EndDate>=CURRENT_DATE
                                            AND Occupancy.CheckOut IS NOT NULL;"

//
//(eligible for checkin)
//set is_checkedIn:false, is_checkedOut:false
"SELECT COUNT(Reservation.ID)
FROM Reservation
WHERE Reservation.CustomerID=23 AND Reservation.StartDate<=CURRENT_DATE
                                AND Reservation.EndDate>=CURRENT_DATE
                                AND Reservation.ID NOT IN(SELECT Occupancy.ReservationID
																													FROM Occupancy)"

//vlepw an exw eggrafh g tin hmeromhnia p thelw
//an den exw is_checkedIn:false, is_checkedOut:false
"SELECT COUNT(Reservation.ID)
FROM Reservation
WHERE Reservation.CustomerID=23 AND Reservation.StartDate<=CURRENT_DATE AND Reservation.EndDate>=CURRENT_DATE;"*/

if($numrows == 1 && $verify = password_verify($pass,$hash)){
  $jObj->success = 1;
  $jObj->customerID = $customer;
  $jObj->title = $title;
  $jObj->firstName = $firstName;
  $jObj->lastName = $lastName;
  $jObj->isOldCustomer= $isOldCustomer;
	$jObj->isCheckedIn = $isCheckedIn;
	$jObj->isCheckedOut = $isCheckedOut;
}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Login failed";
}

$JsonResponse = json_encode($jObj);

echo $JsonResponse;

$stmt->close();
$dbCon->close();

?>
